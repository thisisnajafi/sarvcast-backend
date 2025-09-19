// Enhanced Performance Monitoring JavaScript Functionality
class PerformanceMonitoringManager {
    constructor() {
        this.selectedMetrics = new Set();
        this.isLoading = false;
        this.charts = {};
        this.alerts = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupPerformanceAnalytics();
        this.initializeCharts();
    }

    setupEventListeners() {
        // Bulk form submission
        const bulkForm = document.getElementById('bulk-form');
        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => this.handleBulkAction(e));
        }
        
        // Search with debounce
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => this.handleSearch(e), 300));
        }

        // Filter changes
        document.querySelectorAll('select[name="status"], select[name="metric_type"], select[name="severity"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual metric actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="configure"], [data-action="test"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleMetricAction(e);
            }
        });

        // Alert management
        this.setupAlertManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh metrics every 10 seconds
        setInterval(() => this.updateMetrics(), 10000);
        
        // Auto-refresh alerts every 5 seconds
        setInterval(() => this.updateAlerts(), 5000);
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                this.selectAll();
            }
            // Escape to deselect all
            if (e.key === 'Escape') {
                this.deselectAll();
            }
        });
    }

    setupPerformanceAnalytics() {
        // Initialize performance monitoring charts
        this.initializePerformanceCharts();
        
        // Track performance metrics
        this.trackPerformanceMetrics();
    }

    setupAlertManagement() {
        // Setup alert management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="acknowledge"], [data-action="resolve"], [data-action="dismiss"]')) {
                e.preventDefault();
                this.handleAlertAction(e);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.metric-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedMetrics.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه متریک‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.metric-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedMetrics.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.metric-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedMetrics.add(checkbox.value);
            } else {
                this.selectedMetrics.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.metric-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک متریک را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected metric IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'metric_ids[]';
            hiddenInput.value = checkbox.value;
            e.target.appendChild(hiddenInput);
        });

        this.showLoadingSpinner();
    }

    // Search functionality
    handleSearch(e) {
        const query = e.target.value;
        if (query.length >= 2 || query.length === 0) {
            this.performSearch(query);
        }
    }

    performSearch(query) {
        this.showLoadingSpinner();
        
        const formData = new FormData();
        formData.append('search', query);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            formData.append('_token', csrfToken.getAttribute('content'));
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.metrics);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            this.showToast('خطا در جستجو', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Filter changes
    handleFilterChange() {
        this.showLoadingSpinner();
        
        const form = document.querySelector('form[method="GET"]');
        if (!form) return;
        
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Update the table section
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.querySelector('.bg-white.rounded-lg.shadow-sm.overflow-hidden');
            const currentTable = document.querySelector('.bg-white.rounded-lg.shadow-sm.overflow-hidden');
            if (newTable && currentTable) {
                currentTable.innerHTML = newTable.innerHTML;
            }
        })
        .catch(error => {
            console.error('Filter error:', error);
            this.showToast('خطا در فیلتر', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Individual metric actions
    handleMetricAction(e) {
        const action = e.target.dataset.action;
        const metricId = e.target.dataset.metricId;
        
        if (action === 'delete') {
            this.confirmDelete(metricId);
        } else if (action === 'view') {
            this.viewMetric(metricId);
        } else if (action === 'configure') {
            this.configureMetric(metricId);
        } else if (action === 'test') {
            this.testMetric(metricId);
        }
    }

    // Alert actions
    handleAlertAction(e) {
        const action = e.target.dataset.action;
        const alertId = e.target.dataset.alertId;
        
        if (action === 'acknowledge') {
            this.acknowledgeAlert(alertId);
        } else if (action === 'resolve') {
            this.resolveAlert(alertId);
        } else if (action === 'dismiss') {
            this.dismissAlert(alertId);
        }
    }

    // Metric operations
    viewMetric(metricId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/${metricId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showMetricModal(data.metric);
            } else {
                this.showToast(data.message || 'خطا در مشاهده متریک', 'error');
            }
        })
        .catch(error => {
            console.error('View metric error:', error);
            this.showToast('خطا در مشاهده متریک', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    configureMetric(metricId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/${metricId}/configure`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showConfigureModal(data.metric);
            } else {
                this.showToast(data.message || 'خطا در پیکربندی متریک', 'error');
            }
        })
        .catch(error => {
            console.error('Configure metric error:', error);
            this.showToast('خطا در پیکربندی متریک', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    testMetric(metricId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/${metricId}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateMetricStatus(metricId, 'testing');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تست متریک', 'error');
            }
        })
        .catch(error => {
            console.error('Test metric error:', error);
            this.showToast('خطا در تست متریک', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Alert operations
    acknowledgeAlert(alertId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/alerts/${alertId}/acknowledge`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateAlertStatus(alertId, 'acknowledged');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تأیید هشدار', 'error');
            }
        })
        .catch(error => {
            console.error('Acknowledge alert error:', error);
            this.showToast('خطا در تأیید هشدار', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    resolveAlert(alertId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/alerts/${alertId}/resolve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateAlertStatus(alertId, 'resolved');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در حل هشدار', 'error');
            }
        })
        .catch(error => {
            console.error('Resolve alert error:', error);
            this.showToast('خطا در حل هشدار', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    dismissAlert(alertId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/alerts/${alertId}/dismiss`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.removeAlertRow(alertId);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در رد هشدار', 'error');
            }
        })
        .catch(error => {
            console.error('Dismiss alert error:', error);
            this.showToast('خطا در رد هشدار', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update metric status in UI
    updateMetricStatus(metricId, status) {
        const row = document.querySelector(`tr[data-metric-id="${metricId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'testing') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">در حال تست</span>';
            }
        }
    }

    // Update alert status in UI
    updateAlertStatus(alertId, status) {
        const row = document.querySelector(`tr[data-alert-id="${alertId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'acknowledged') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">تأیید شده</span>';
            }
        } else if (status === 'resolved') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">حل شده</span>';
            }
        }
    }

    // Performance analytics
    initializeCharts() {
        // Initialize performance monitoring charts using Chart.js
        this.initializePerformanceCharts();
    }

    initializePerformanceCharts() {
        // CPU Usage Chart
        const cpuCtx = document.getElementById('cpuChart');
        if (cpuCtx && typeof Chart !== 'undefined') {
            this.charts.cpu = new Chart(cpuCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'استفاده از CPU (%)',
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'استفاده از CPU'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Memory Usage Chart
        const memoryCtx = document.getElementById('memoryChart');
        if (memoryCtx && typeof Chart !== 'undefined') {
            this.charts.memory = new Chart(memoryCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'استفاده از حافظه (%)',
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'استفاده از حافظه'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Response Time Chart
        const responseCtx = document.getElementById('responseChart');
        if (responseCtx && typeof Chart !== 'undefined') {
            this.charts.response = new Chart(responseCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'زمان پاسخ (ms)',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'زمان پاسخ'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    trackPerformanceMetrics() {
        // Track performance metrics in real-time
        setInterval(() => {
            this.updatePerformanceMetrics();
        }, 30000); // Update every 30 seconds
    }

    updatePerformanceMetrics() {
        fetch('/admin/performance-monitoring/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updatePerformanceIndicators(data.indicators);
            }
        })
        .catch(error => {
            console.error('Performance metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.charts.cpu) {
            this.charts.cpu.data.labels = metrics.labels;
            this.charts.cpu.data.datasets[0].data = metrics.cpu;
            this.charts.cpu.update();
        }

        if (this.charts.memory) {
            this.charts.memory.data.labels = metrics.labels;
            this.charts.memory.data.datasets[0].data = metrics.memory;
            this.charts.memory.update();
        }

        if (this.charts.response) {
            this.charts.response.data.labels = metrics.labels;
            this.charts.response.data.datasets[0].data = metrics.response;
            this.charts.response.update();
        }
    }

    updatePerformanceIndicators(indicators) {
        // Update performance indicators
        document.querySelectorAll('.performance-indicator').forEach(element => {
            const type = element.dataset.type;
            if (indicators[type]) {
                const indicator = indicators[type];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${indicator.value}</div>
                    <div class="text-sm text-gray-500">${indicator.unit}</div>
                `;
            }
        });
    }

    updateMetrics() {
        fetch('/admin/performance-monitoring/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.metrics);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateAlerts() {
        fetch('/admin/performance-monitoring/alerts', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateAlertsTable(data.alerts);
                this.updateAlertStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Alerts update error:', error);
        });
    }

    updateAlertsTable(alerts) {
        const alertsContainer = document.getElementById('alerts-container');
        if (!alertsContainer) return;

        alertsContainer.innerHTML = alerts.map(alert => `
            <div class="alert-item p-4 border rounded-lg mb-2 ${alert.severity === 'critical' ? 'border-red-200 bg-red-50' : alert.severity === 'warning' ? 'border-yellow-200 bg-yellow-50' : 'border-blue-200 bg-blue-50'}">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold text-gray-900">${alert.title}</h4>
                        <p class="text-sm text-gray-600">${alert.message}</p>
                        <span class="text-xs text-gray-500">${alert.timestamp}</span>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <button data-action="acknowledge" data-alert-id="${alert.id}" class="text-blue-600 hover:text-blue-900 text-sm">تأیید</button>
                        <button data-action="resolve" data-alert-id="${alert.id}" class="text-green-600 hover:text-green-900 text-sm">حل</button>
                        <button data-action="dismiss" data-alert-id="${alert.id}" class="text-red-600 hover:text-red-900 text-sm">رد</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    updateAlertStatistics(stats) {
        const alertStatsElements = document.querySelectorAll('.alert-stats .text-2xl');
        if (alertStatsElements.length >= 3) {
            alertStatsElements[0].textContent = stats.total || 0;
            alertStatsElements[1].textContent = stats.active || 0;
            alertStatsElements[2].textContent = stats.resolved || 0;
        }
    }

    // Modal functions
    showMetricModal(metric) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات متریک: ${metric.name}</h3>
                <div class="space-y-3">
                    <div><strong>نوع:</strong> ${metric.type}</div>
                    <div><strong>مقدار فعلی:</strong> ${metric.current_value}</div>
                    <div><strong>آستانه:</strong> ${metric.threshold}</div>
                    <div><strong>وضعیت:</strong> ${metric.status}</div>
                    <div><strong>آخرین بروزرسانی:</strong> ${metric.last_updated}</div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showConfigureModal(metric) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">پیکربندی متریک: ${metric.name}</h3>
                <form id="configure-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">آستانه هشدار</label>
                            <input type="number" name="threshold" value="${metric.threshold}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">فاصله بررسی (دقیقه)</label>
                            <input type="number" name="check_interval" value="${metric.check_interval}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ذخیره</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#configure-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveMetricConfiguration(metric.id, new FormData(e.target));
            modal.remove();
        });
    }

    saveMetricConfiguration(metricId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/${metricId}/configure`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در ذخیره پیکربندی', 'error');
            }
        })
        .catch(error => {
            console.error('Save configuration error:', error);
            this.showToast('خطا در ذخیره پیکربندی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedMetrics.size;
        const bulkActionBtn = document.querySelector('#bulk-form button[type="submit"]');
        
        if (bulkActionBtn) {
            if (selectedCount > 0) {
                bulkActionBtn.textContent = `اجرا (${selectedCount} مورد)`;
                bulkActionBtn.disabled = false;
            } else {
                bulkActionBtn.textContent = 'اجرا';
                bulkActionBtn.disabled = true;
            }
        }
    }

    // Update statistics
    updateStatistics() {
        fetch('/admin/performance-monitoring/statistics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateStatsCards(data.stats);
            }
        })
        .catch(error => {
            console.error('Statistics update error:', error);
        });
    }

    updateStatsCards(stats) {
        const statElements = document.querySelectorAll('.grid .bg-white .text-2xl');
        if (statElements.length >= 4) {
            statElements[0].textContent = stats.total_metrics || 0;
            statElements[1].textContent = stats.active_alerts || 0;
            statElements[2].textContent = stats.system_health || 0;
            statElements[3].textContent = stats.avg_response_time || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(metricId) {
        if (confirm('آیا از حذف این متریک اطمینان دارید؟')) {
            this.performDelete(metricId);
        }
    }

    performDelete(metricId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/performance-monitoring/${metricId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.removeMetricRow(metricId);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در حذف', 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            this.showToast('خطا در حذف', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    removeMetricRow(metricId) {
        const row = document.querySelector(`tr[data-metric-id="${metricId}"]`);
        if (row) {
            row.remove();
        }
    }

    removeAlertRow(alertId) {
        const row = document.querySelector(`tr[data-alert-id="${alertId}"]`);
        if (row) {
            row.remove();
        }
    }

    // UI helpers
    showLoadingSpinner() {
        if (this.isLoading) return;
        this.isLoading = true;
        
        const spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        spinner.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                </div>
            </div>
        `;
        document.body.appendChild(spinner);
    }

    hideLoadingSpinner() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
        this.isLoading = false;
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.performanceMonitoringManager = new PerformanceMonitoringManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.performanceMonitoringManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.performanceMonitoringManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.performanceMonitoringManager;
    if (manager) manager.toggleAll();
}
