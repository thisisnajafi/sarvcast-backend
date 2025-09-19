// Enhanced Timeline Management JavaScript Functionality
class TimelineManagementManager {
    constructor() {
        this.selectedTimelines = new Set();
        this.isLoading = false;
        this.timelineChart = null;
        this.timelineData = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupTimelineAnalytics();
        this.initializeTimelineVisualization();
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
        document.querySelectorAll('select[name="status"], select[name="timeline_type"], select[name="priority"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual timeline actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleTimelineAction(e);
            }
        });

        // Timeline visualization
        this.setupTimelineVisualization();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
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
            // Ctrl+D to duplicate selected timeline
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                this.duplicateSelected();
            }
        });
    }

    setupTimelineAnalytics() {
        // Initialize timeline analytics charts
        this.initializeCharts();
        
        // Track timeline metrics
        this.trackTimelineMetrics();
    }

    setupTimelineVisualization() {
        // Setup timeline visualization controls
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="zoom-in"], [data-action="zoom-out"], [data-action="reset-view"]')) {
                e.preventDefault();
                this.handleTimelineVisualization(e);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.timeline-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedTimelines.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه تایم‌لاین‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.timeline-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedTimelines.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.timeline-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedTimelines.add(checkbox.value);
            } else {
                this.selectedTimelines.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.timeline-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک تایم‌لاین را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected timeline IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'timeline_ids[]';
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
                this.updateTable(data.timelines);
                this.updateStatistics(data.stats);
                this.updateTimelineVisualization(data.timelines);
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

    // Individual timeline actions
    handleTimelineAction(e) {
        const action = e.target.dataset.action;
        const timelineId = e.target.dataset.timelineId;
        
        if (action === 'delete') {
            this.confirmDelete(timelineId);
        } else if (action === 'view') {
            this.viewTimeline(timelineId);
        } else if (action === 'edit') {
            this.editTimeline(timelineId);
        } else if (action === 'duplicate') {
            this.duplicateTimeline(timelineId);
        }
    }

    // Timeline operations
    viewTimeline(timelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/timeline-management/${timelineId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showTimelineModal(data.timeline);
            } else {
                this.showToast(data.message || 'خطا در مشاهده تایم‌لاین', 'error');
            }
        })
        .catch(error => {
            console.error('View timeline error:', error);
            this.showToast('خطا در مشاهده تایم‌لاین', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editTimeline(timelineId) {
        window.location.href = `/admin/timeline-management/${timelineId}/edit`;
    }

    duplicateTimeline(timelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/timeline-management/${timelineId}/duplicate`, {
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
                this.updateTable(data.timelines);
                this.updateStatistics(data.stats);
                this.updateTimelineVisualization(data.timelines);
            } else {
                this.showToast(data.message || 'خطا در کپی', 'error');
            }
        })
        .catch(error => {
            console.error('Duplicate timeline error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateSelected() {
        if (this.selectedTimelines.size === 0) {
            this.showToast('لطفاً حداقل یک تایم‌لاین را انتخاب کنید', 'warning');
            return;
        }

        if (confirm(`آیا از کپی ${this.selectedTimelines.size} تایم‌لاین اطمینان دارید؟`)) {
            this.showLoadingSpinner();
            
            const formData = new FormData();
            Array.from(this.selectedTimelines).forEach(id => {
                formData.append('timeline_ids[]', id);
            });
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch('/admin/timeline-management/bulk-duplicate', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.updateTable(data.timelines);
                    this.updateStatistics(data.stats);
                    this.updateTimelineVisualization(data.timelines);
                } else {
                    this.showToast(data.message || 'خطا در کپی', 'error');
                }
            })
            .catch(error => {
                console.error('Bulk duplicate error:', error);
                this.showToast('خطا در کپی', 'error');
            })
            .finally(() => {
                this.hideLoadingSpinner();
            });
        }
    }

    // Timeline visualization
    initializeTimelineVisualization() {
        // Initialize timeline visualization using Chart.js or custom timeline
        this.initializeTimelineChart();
    }

    initializeTimelineChart() {
        const ctx = document.getElementById('timelineChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.timelineChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'تایم‌لاین‌ها',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع تایم‌لاین‌ها بر اساس نوع'
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

    updateTimelineVisualization(timelines) {
        if (this.timelineChart && timelines) {
            const timelineTypes = {};
            timelines.forEach(timeline => {
                timelineTypes[timeline.type] = (timelineTypes[timeline.type] || 0) + 1;
            });

            this.timelineChart.data.labels = Object.keys(timelineTypes);
            this.timelineChart.data.datasets[0].data = Object.values(timelineTypes);
            this.timelineChart.update();
        }
    }

    handleTimelineVisualization(e) {
        const action = e.target.dataset.action;
        
        if (action === 'zoom-in') {
            this.zoomTimeline(1.2);
        } else if (action === 'zoom-out') {
            this.zoomTimeline(0.8);
        } else if (action === 'reset-view') {
            this.resetTimelineView();
        }
    }

    zoomTimeline(factor) {
        const timelineContainer = document.getElementById('timeline-container');
        if (timelineContainer) {
            const currentScale = parseFloat(timelineContainer.style.transform?.match(/scale\(([^)]+)\)/)?.[1] || 1);
            const newScale = currentScale * factor;
            timelineContainer.style.transform = `scale(${newScale})`;
        }
    }

    resetTimelineView() {
        const timelineContainer = document.getElementById('timeline-container');
        if (timelineContainer) {
            timelineContainer.style.transform = 'scale(1)';
        }
    }

    // Timeline analytics
    initializeCharts() {
        // Initialize timeline analytics charts using Chart.js
        const ctx = document.getElementById('timelineAnalyticsChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.timelineAnalyticsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'تایم‌لاین‌های ایجاد شده',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'تایم‌لاین‌های تکمیل شده',
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
                            text: 'آمار تایم‌لاین‌ها'
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

    trackTimelineMetrics() {
        // Track timeline metrics in real-time
        setInterval(() => {
            this.updateTimelineMetrics();
        }, 60000); // Update every minute
    }

    updateTimelineMetrics() {
        fetch('/admin/timeline-management/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateTimelineStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Timeline metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.timelineAnalyticsChart) {
            this.timelineAnalyticsChart.data.labels = metrics.labels;
            this.timelineAnalyticsChart.data.datasets[0].data = metrics.created;
            this.timelineAnalyticsChart.data.datasets[1].data = metrics.completed;
            this.timelineAnalyticsChart.update();
        }
    }

    updateTimelineStatistics(statistics) {
        // Update timeline statistics
        document.querySelectorAll('.timeline-statistic').forEach(element => {
            const type = element.dataset.type;
            if (statistics[type]) {
                const stat = statistics[type];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${stat.value}</div>
                    <div class="text-sm text-gray-500">${stat.unit}</div>
                `;
            }
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedTimelines.size;
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
        fetch('/admin/timeline-management/statistics', {
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
            statElements[0].textContent = stats.total || 0;
            statElements[1].textContent = stats.active || 0;
            statElements[2].textContent = stats.completed || 0;
            statElements[3].textContent = stats.pending || 0;
        }
    }

    // Modal functions
    showTimelineModal(timeline) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-4xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات تایم‌لاین: ${timeline.title}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">اطلاعات کلی</h4>
                        <div class="space-y-2">
                            <div><strong>نوع:</strong> ${timeline.type}</div>
                            <div><strong>اولویت:</strong> ${timeline.priority}</div>
                            <div><strong>وضعیت:</strong> ${timeline.status}</div>
                            <div><strong>تاریخ شروع:</strong> ${timeline.start_date}</div>
                            <div><strong>تاریخ پایان:</strong> ${timeline.end_date}</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">توضیحات</h4>
                        <p class="text-gray-600">${timeline.description || 'بدون توضیحات'}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/timeline-management/${timeline.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Confirmation dialogs
    confirmDelete(timelineId) {
        if (confirm('آیا از حذف این تایم‌لاین اطمینان دارید؟')) {
            this.performDelete(timelineId);
        }
    }

    performDelete(timelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/timeline-management/${timelineId}`, {
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
                this.removeTimelineRow(timelineId);
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

    removeTimelineRow(timelineId) {
        const row = document.querySelector(`tr[data-timeline-id="${timelineId}"]`);
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
    window.timelineManagementManager = new TimelineManagementManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.timelineManagementManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.timelineManagementManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.timelineManagementManager;
    if (manager) manager.toggleAll();
}
