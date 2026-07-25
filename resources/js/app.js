import Alpine from 'alpinejs';
import $ from 'jquery';
import select2 from 'select2';
import { DataTable } from 'simple-datatables';

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

// Register Alpine data components before start(). With Vite ESM, relying only on
// `alpine:init` can race and leave x-data="cookieNotice" unresolved as a JS expr.
Alpine.data('siteNav', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
}));

Alpine.data('cookieNotice', () => ({
    visible: false,
    storageKey: 'eping_cookie_notice_v1',
    init() {
        try {
            this.visible = window.localStorage.getItem(this.storageKey) !== '1';
        } catch {
            this.visible = true;
        }
    },
    accept() {
        try {
            window.localStorage.setItem(this.storageKey, '1');
        } catch {
            // ignore quota / private mode
        }
        this.visible = false;
    },
}));

Alpine.data('targetSortOrder', () => ({
    url: '',
    excludeId: null,
    autoOnLoad: false,
    manual: false,
    loading: false,
    init() {
        this.readConfig();
    },
    readConfig() {
        const el = this.$el;
        this.url = el.dataset.sortUrl || '';
        this.excludeId = el.dataset.sortExcludeId ? Number(el.dataset.sortExcludeId) : null;
        this.autoOnLoad = el.dataset.sortAutoOnLoad === '1';
    },
    categoryValue() {
        return this.$refs.category?.value
            ?? this.$el.querySelector('select[name="category"]')?.value
            ?? '';
    },
    onCategoryChange() {
        this.manual = false;
        this.fetchNext(true);
    },
    async fetchNext(force) {
        if (!this.url) {
            this.readConfig();
        }
        if (!force && this.manual) {
            return;
        }
        const category = this.categoryValue();
        if (!category || !this.url) {
            return;
        }
        this.loading = true;
        try {
            const params = new URLSearchParams({ category });
            if (this.excludeId) {
                params.set('exclude_id', String(this.excludeId));
            }
            const res = await fetch(`${this.url}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            const sortInput = this.$refs.sortOrder ?? this.$el.querySelector('[name="sort_order"]');
            if (typeof data.next === 'number' && sortInput && (!this.manual || force)) {
                sortInput.value = String(data.next);
                this.manual = false;
            }
        } catch {
            // keep current value
        } finally {
            this.loading = false;
        }
    },
}));

function getAlpineData(el) {
    if (el?._x_dataStack?.length) {
        return el._x_dataStack[0];
    }
    if (typeof Alpine.$data === 'function') {
        return Alpine.$data(el);
    }
    return null;
}

window.Alpine = Alpine;

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
            const allowClearAttr = $el.data('allowClear');
            const hasEmptyOption = $el.find('option[value=""]').length > 0;

            $el.select2({
                width: $el.data('width') || '100%',
                minimumResultsForSearch:
                    minResults === 'Infinity' ? Infinity : (minResults ?? 0),
                placeholder: placeholder || undefined,
                allowClear:
                    allowClearAttr === true || allowClearAttr === 'true'
                        ? !!placeholder && hasEmptyOption
                        : !!placeholder && hasEmptyOption && !$el.prop('required'),
                dropdownParent: $(document.body),
            });

            const sortForm = this.closest('form[data-sort-url]');
            if (sortForm && this.name === 'category') {
                $el.off('.targetSortOrder').on('change.targetSortOrder select2:select.targetSortOrder', () => {
                    getAlpineData(sortForm)?.onCategoryChange?.();
                });
            }
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

function bootstrapTargetSortOrderForms(root = document) {
    root.querySelectorAll('form[data-sort-url]').forEach((form) => {
        const component = getAlpineData(form);
        if (!component?.fetchNext) {
            return;
        }

        component.readConfig();

        if (component.autoOnLoad) {
            queueMicrotask(() => component.fetchNext(false));
        }
    });
}

function bootUi() {
    initSelect2();
    initDataTables();
}

function bootApp() {
    bootUi();
    Alpine.start();
    bootstrapTargetSortOrderForms();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootApp);
} else {
    // Module scripts can execute after DOMContentLoaded already fired.
    bootApp();
}
