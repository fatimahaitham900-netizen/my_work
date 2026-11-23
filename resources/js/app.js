import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Chart.defaults.font.family = "'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#475569';

Alpine.store('toast', {
    items: [],
    show(message, type = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => {
            this.remove(id);
        }, 4200);
    },
    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});

window.fleetModuleManager = function fleetModuleManager(config) {
    return {
        baseUrl: config.baseUrl,
        storeUrl: config.storeUrl,
        exportBaseUrl: config.exportBaseUrl,
        filters: { ...config.initialFilters },
        defaultFilters: { ...config.defaultFilters },
        kpis: { ...config.initialKpis },
        defaultForm: { ...config.defaultForm },
        form: { ...config.defaultForm },
        exportUrl: config.initialExportUrl || config.exportBaseUrl,

        loadingTable: false,
        submitting: false,
        deleting: false,
        loadingDetails: false,
        loadingEdit: false,

        drawerOpen: false,
        drawerMode: 'create',
        editingId: null,
        errors: {},

        detailsOpen: false,
        detailsData: null,

        deleteOpen: false,
        deleteTarget: { id: null, label: '' },

        importModalOpen: false,
        purgeModalOpen: false,

        searchTimer: null,

        init() {
            this.syncExportUrl();

            if (this.$refs.tableContainer) {
                this.$refs.tableContainer.addEventListener('click', (event) => {
                    const paginationLink = event.target.closest('a[data-pagination-link]');
                    if (paginationLink) {
                        event.preventDefault();
                        this.fetchTable(paginationLink.getAttribute('href'), true);
                        return;
                    }

                    const actionBtn = event.target.closest('[data-action]');
                    if (!actionBtn) {
                        return;
                    }

                    const action = actionBtn.dataset.action;
                    const id = actionBtn.dataset.id;

                    if (action === 'view' && id) {
                        this.openDetails(id);
                    } else if (action === 'edit' && id) {
                        this.openEdit(id);
                    } else if (action === 'mark-repaired' && id) {
                        this.openEdit(id, {
                            repair_status: 'repaired',
                            resolve_linked_stoppages: true,
                        });
                    } else if (action === 'delete' && id) {
                        this.confirmDelete(id, actionBtn.dataset.label || '');
                    } else if (action === 'quick-status' && id && actionBtn.dataset.status) {
                        this.quickToggleStatus(id, actionBtn.dataset.status);
                    }
                });
            }
        },

        csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') || '' : '';
        },

        hasActiveFilters() {
            return Object.values(this.filters).some((value) => value !== null && value !== undefined && String(value).trim() !== '');
        },

        buildQueryParams() {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== '') {
                    params.set(key, String(value).trim());
                }
            });
            return params;
        },

        syncExportUrl() {
            const query = this.buildQueryParams().toString();
            this.exportUrl = query ? `${this.exportBaseUrl}?${query}` : this.exportBaseUrl;
        },

        onSearchInput() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.applyFilters();
            }, 300);
        },

        applyFilters() {
            const query = this.buildQueryParams().toString();
            const targetUrl = query ? `${this.baseUrl}?${query}` : this.baseUrl;
            this.syncExportUrl();
            this.fetchTable(targetUrl, true);
        },

        resetFilters() {
            this.filters = { ...this.defaultFilters };
            this.applyFilters();
        },

        currentPageUrl() {
            return window.location.pathname + window.location.search;
        },

        async fetchTable(url, updateHistory = false) {
            this.loadingTable = true;
            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Partial': 'true',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to load table');
                }

                const payload = await response.json();

                if (this.$refs.tableContainer && typeof payload.table_html === 'string') {
                    this.$refs.tableContainer.innerHTML = payload.table_html;
                }

                if (payload.kpis) {
                    this.kpis = payload.kpis;
                }

                if (payload.export_url) {
                    this.exportUrl = payload.export_url;
                }

                if (updateHistory) {
                    window.history.replaceState({}, '', url);
                }
            } catch {
                Alpine.store('toast').show('تعذر تحديث البيانات، يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                this.loadingTable = false;
            }
        },

        openCreate() {
            this.drawerMode = 'create';
            this.editingId = null;
            this.form = { ...this.defaultForm };
            this.errors = {};
            this.drawerOpen = true;
        },

        async openEdit(id, overrides = null) {
            this.drawerMode = 'edit';
            this.editingId = id;
            this.errors = {};
            this.loadingEdit = true;
            this.drawerOpen = true;

            try {
                const response = await fetch(`${this.baseUrl}/${id}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to load record');
                }

                const payload = await response.json();
                this.form = {
                    ...this.defaultForm,
                    ...(payload.record || {}),
                    ...(overrides || {}),
                };
            } catch {
                this.drawerOpen = false;
                Alpine.store('toast').show('تعذر تحميل بيانات السجل للتعديل.', 'error');
            } finally {
                this.loadingEdit = false;
            }
        },

        closeDrawer() {
            this.drawerOpen = false;
            this.errors = {};
        },

        async submitForm() {
            this.submitting = true;
            this.errors = {};

            const isEdit = this.drawerMode === 'edit' && this.editingId !== null;
            const url = isEdit ? `${this.baseUrl}/${this.editingId}` : this.storeUrl;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = payload.errors || {};
                    Alpine.store('toast').show('يرجى مراجعة الحقول المطلوبة وتصحيح الأخطاء.', 'error');
                    return;
                }

                if (response.status === 403) {
                    Alpine.store('toast').show('ليست لديك صلاحية لتنفيذ هذا الإجراء.', 'error');
                    return;
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'Save error');
                }

                this.closeDrawer();
                await this.fetchTable(this.currentPageUrl(), false);
                Alpine.store('toast').show(
                    payload.message || (isEdit ? 'تم التحديث بنجاح' : 'تمت الإضافة بنجاح'),
                    'success',
                );
            } catch {
                Alpine.store('toast').show('حدث خطأ أثناء حفظ البيانات، يرجى المحاولة مجدداً.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async openDetails(id) {
            this.detailsOpen = true;
            this.loadingDetails = true;
            this.detailsData = null;

            try {
                const response = await fetch(`${this.baseUrl}/${id}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch details');
                }

                this.detailsData = await response.json();
            } catch {
                this.detailsOpen = false;
                Alpine.store('toast').show('تعذر تحميل تفاصيل السجل.', 'error');
            } finally {
                this.loadingDetails = false;
            }
        },

        confirmDelete(id, label) {
            this.deleteTarget = { id, label };
            this.deleteOpen = true;
        },

        async executeDelete() {
            if (!this.deleteTarget.id) {
                return;
            }

            this.deleting = true;

            try {
                const response = await fetch(`${this.baseUrl}/${this.deleteTarget.id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 403) {
                    Alpine.store('toast').show('ليست لديك صلاحية حذف هذا السجل.', 'error');
                    this.deleteOpen = false;
                    return;
                }

                if (!response.ok) {
                    throw new Error('Delete failed');
                }

                this.deleteOpen = false;
                this.deleteTarget = { id: null, label: '' };
                await this.fetchTable(this.currentPageUrl(), false);
                Alpine.store('toast').show(payload.message || 'تم الحذف بنجاح', 'success');
            } catch {
                Alpine.store('toast').show('تعذر حذف السجل، يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                this.deleting = false;
            }
        },

        async quickToggleStatus(id, status) {
            try {
                const response = await fetch(`${this.baseUrl}/${id}/status`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ status }),
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 403) {
                    Alpine.store('toast').show('ليست لديك صلاحية تعديل الحالة.', 'error');
                    return;
                }

                if (!response.ok) {
                    throw new Error('Status update failed');
                }

                await this.fetchTable(this.currentPageUrl(), false);
                Alpine.store('toast').show(payload.message || 'تم التحديث بنجاح', 'success');
            } catch {
                Alpine.store('toast').show('تعذر تحديث حالة السائق.', 'error');
            }
        },

        firstError(field) {
            return Array.isArray(this.errors[field]) && this.errors[field].length > 0
                ? this.errors[field][0]
                : '';
        },
    };
};

function initExecutiveDashboardCharts() {
    const dataNode = document.getElementById('dashboard-chart-data');
    if (!dataNode) {
        return;
    }

    let chartsData;
    try {
        chartsData = JSON.parse(dataNode.textContent || '{}');
    } catch {
        return;
    }

    const commonTooltip = {
        rtl: true,
        textDirection: 'rtl',
        backgroundColor: '#0f172a',
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        borderColor: 'rgba(148, 163, 184, 0.2)',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 10,
        titleFont: { weight: '600', size: 13 },
        bodyFont: { size: 12 },
    };

    const moduleCanvas = document.getElementById('moduleDeviationsChart');
    if (moduleCanvas && chartsData.by_module) {
        new Chart(moduleCanvas, {
            type: 'bar',
            data: {
                labels: chartsData.by_module.labels,
                datasets: [
                    {
                        label: 'انحرافات نشطة',
                        data: chartsData.by_module.active,
                        backgroundColor: 'rgba(15, 23, 42, 0.85)',
                        hoverBackgroundColor: '#0f172a',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.65,
                        categoryPercentage: 0.65,
                    },
                    {
                        label: 'تنبيهات حرجة',
                        data: chartsData.by_module.critical,
                        backgroundColor: 'rgba(225, 29, 72, 0.88)',
                        hoverBackgroundColor: '#be123c',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.65,
                        categoryPercentage: 0.65,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        rtl: true,
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16,
                            font: { size: 12, weight: '600' },
                        },
                    },
                    tooltip: commonTooltip,
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 12, weight: '500' } },
                    },
                    y: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                    },
                },
            },
        });
    }

    const categoryCanvas = document.getElementById('breakdownCategoryChart');
    if (categoryCanvas && chartsData.breakdowns_by_category) {
        const counts = chartsData.breakdowns_by_category.counts || [];
        const hasValues = counts.some((value) => value > 0);

        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: hasValues ? chartsData.breakdowns_by_category.labels : ['لا توجد أعطال نشطة'],
                datasets: [
                    {
                        data: hasValues ? counts : [1],
                        backgroundColor: hasValues ? chartsData.breakdowns_by_category.colors : ['#e2e8f0'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            font: { size: 12, weight: '500' },
                        },
                    },
                    tooltip: commonTooltip,
                },
            },
        });
    }

    const severityCanvas = document.getElementById('severityBreakdownChart');
    if (severityCanvas && chartsData.by_severity) {
        const counts = chartsData.by_severity.counts || [];
        const hasValues = counts.some((value) => value > 0);

        new Chart(severityCanvas, {
            type: 'doughnut',
            data: {
                labels: hasValues ? chartsData.by_severity.labels : ['لا توجد انحرافات'],
                datasets: [
                    {
                        data: hasValues ? counts : [1],
                        backgroundColor: hasValues ? chartsData.by_severity.colors : ['#e2e8f0'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            font: { size: 12, weight: '500' },
                        },
                    },
                    tooltip: commonTooltip,
                },
            },
        });
    }
}

document.addEventListener('DOMContentLoaded', initExecutiveDashboardCharts);
Alpine.start();
