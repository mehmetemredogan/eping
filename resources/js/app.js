import Alpine from 'alpinejs';
import $ from 'jquery';
import select2 from 'select2';
import { DataTable } from 'simple-datatables';

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
});

Alpine.start();
