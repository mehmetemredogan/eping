import Alpine from 'alpinejs';
import $ from 'jquery';
import select2 from 'select2';
import { DataTable } from 'simple-datatables';
import { measureLatency, latencyClass } from './client-ping';
import { detectClientDns } from './client-dns';

import 'select2/dist/css/select2.min.css';
import 'simple-datatables/dist/style.css';

window.$ = window.jQuery = $;

// jQuery 4 removed deprecated utilities that Select2 4.1.0-rc.0 still calls.
$.camelCase ??= (str) => str.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
$.isArray ??= Array.isArray;
$.trim ??= (str) => (str == null ? '' : String(str).trim());
$.isFunction ??= (fn) => typeof fn === 'function';
$.now ??= Date.now;
$.proxy ??= (fn, ctx, ...args) => fn.bind(ctx, ...args);

select2($);
window.Alpine = Alpine;
window.measureLatency = measureLatency;
window.latencyClass = latencyClass;

const dataTableLabels = {
    tr: {
        placeholder: 'Tabloda ara…',
        searchTitle: 'Tabloda ara',
        perPage: 'kayıt / sayfa',
        noRows: 'Kayıt bulunamadı',
        noResults: 'Aramayla eşleşen kayıt yok',
        info: '{start}–{end} / {rows} kayıt',
    },
    en: {
        placeholder: 'Search table…',
        searchTitle: 'Search table',
        perPage: 'rows / page',
        noRows: 'No records found',
        noResults: 'No matching records',
        info: '{start}–{end} of {rows} rows',
    },
};

function initDataTables(root = document) {
    const locale = (document.documentElement.lang || 'tr').startsWith('en') ? 'en' : 'tr';

    root.querySelectorAll('table.js-datatable').forEach((table) => {
        if (table.dataset.dtReady) {
            return;
        }

        // Skip empty-state tables (single row spanning all columns).
        if (table.querySelector('tbody td[colspan]')) {
            return;
        }
        table.dataset.dtReady = '1';

        const paging = table.dataset.dtPaging !== 'false';
        const perPage = parseInt(table.dataset.dtPerPage || '25', 10);
        const noSort = (table.dataset.dtNosort || '')
            .split(',')
            .map((s) => parseInt(s.trim(), 10))
            .filter((n) => Number.isInteger(n));

        new DataTable(table, {
            searchable: table.dataset.dtSearch !== 'false',
            sortable: true,
            paging,
            perPage,
            perPageSelect: paging ? [10, 25, 50, 100] : false,
            labels: dataTableLabels[locale],
            columns: noSort.map((index) => ({ select: index, sortable: false, searchable: false })),
        });
    });
}

function initSelect2(root = document) {
    // Enhance every select on the page unless explicitly opted out.
    $(root)
        .find('select')
        .not('.no-select2')
        .each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            const minResults = $el.data('minimum-results-for-search');
            const placeholder = $el.data('placeholder');

            $el.select2({
                width: $el.data('width') || '100%',
                minimumResultsForSearch:
                    minResults === 'Infinity' ? Infinity : (minResults ?? 0),
                placeholder: placeholder || undefined,
                // Select2 requires a placeholder for allowClear.
                allowClear: !!placeholder && !!$el.find('option[value=""]').length && !$el.prop('required'),
                dropdownParent: $(document.body),
            });
        });

    $(root)
        .find('select.js-locale-select')
        .off('change.locale')
        .on('change.locale', function () {
            const form = this.closest('form');
            if (!form || this.dataset.submitting) {
                return;
            }
            this.dataset.submitting = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
}

function bootUi() {
    initSelect2();
    initDataTables();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootUi);
} else {
    // Module scripts can execute after DOMContentLoaded already fired.
    bootUi();
}

document.addEventListener('alpine:init', () => {
    Alpine.data('pingApp', (targets = [], i18n = {}, clientGeo = null) => ({
        targets,
        i18n,
        clientGeo,
        clientDns: null,
        clientDnsLoading: true,
        clientDnsError: false,
        results: {},
        loadingIds: [],
        loading: false,
        expanded: {},
        expandedProviders: {},
        sessionId: crypto.randomUUID(),
        concurrency: 2,

        async init() {
            await this.loadClientDns();
        },

        async loadClientDns() {
            this.clientDnsLoading = true;
            this.clientDnsError = false;
            try {
                this.clientDns = await detectClientDns();
                if (!this.clientDns) {
                    this.clientDnsError = true;
                }
            } catch {
                this.clientDns = null;
                this.clientDnsError = true;
            } finally {
                this.clientDnsLoading = false;
            }
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        isLoading(id) {
            return this.loadingIds.includes(id);
        },

        isProviderExpanded(provider) {
            return !!this.expandedProviders[provider];
        },

        toggleProvider(provider) {
            this.expandedProviders = {
                ...this.expandedProviders,
                [provider]: !this.expandedProviders[provider],
            };
        },

        async runSingle(target) {
            const id = typeof target === 'object' ? target.id : target;
            const item = typeof target === 'object' ? target : this.targets.find((t) => t.id === id);

            if (!item || this.loadingIds.includes(id)) {
                return;
            }

            this.loadingIds.push(id);
            this.results = { ...this.results, [id]: { status: 'pending' } };

            try {
                const ping = await measureLatency(item.host, {
                    warmup: 2,
                    samples: 5,
                    timeoutMs: 4000,
                });

                // Show latency immediately; DNS enrichment is non-blocking.
                this.results = {
                    ...this.results,
                    [id]: { ...ping },
                };

                const controller = new AbortController();
                const reportTimer = setTimeout(() => controller.abort(), 12000);

                try {
                    const res = await fetch(`/api/ping/${id}/report`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            client_geo: this.clientGeo,
                            client_dns: this.clientDns,
                            ...ping,
                        }),
                        signal: controller.signal,
                    });

                    if (res.ok) {
                        const meta = await res.json();
                        this.results = {
                            ...this.results,
                            [id]: {
                                ...this.results[id],
                                resolved_ip: meta.resolved_ip ?? null,
                                rdns: meta.rdns ?? null,
                                dns_records: meta.dns_records ?? [],
                                edns_data: meta.edns_data ?? null,
                                client_dns: meta.client_dns ?? this.clientDns,
                                id: meta.id ?? null,
                                tested_at: meta.tested_at ?? new Date().toISOString(),
                            },
                        };
                    }
                } catch {
                    // Report/DNS failed — ping metrics already shown.
                } finally {
                    clearTimeout(reportTimer);
                }
            } catch {
                this.results = { ...this.results, [id]: { status: 'failed' } };
            } finally {
                this.loadingIds = this.loadingIds.filter((x) => x !== id);
            }
        },

        async runAll() {
            if (this.loading) return;
            this.loading = true;
            this.sessionId = crypto.randomUUID();

            if (!this.clientDns && !this.clientDnsLoading) {
                await this.loadClientDns();
            }

            const queue = [...this.targets];
            const workers = Array.from({ length: this.concurrency }, async () => {
                while (queue.length) {
                    const next = queue.shift();
                    if (next) {
                        await this.runSingle(next);
                    }
                }
            });

            await Promise.all(workers);
            this.loading = false;
        },

        toggleDetails(id) {
            this.expanded = { ...this.expanded, [id]: !this.expanded[id] };
        },

        formatMs(val) {
            if (val === null || val === undefined) return '—';
            return `${parseFloat(val).toFixed(0)} ms`;
        },

        latencyClass(ms) {
            return latencyClass(ms);
        },

        statusLabel(status) {
            return (
                {
                    success: this.i18n.status_success,
                    failed: this.i18n.status_failed,
                    timeout: this.i18n.status_timeout,
                    pending: this.i18n.status_pending,
                }[status] || status
            );
        },
    }));

    Alpine.data('siteNav', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));
});

Alpine.start();
