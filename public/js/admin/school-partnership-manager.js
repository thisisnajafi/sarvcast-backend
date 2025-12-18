// Enhanced School Partnership Management JavaScript Functionality
class SchoolPartnershipManager {
    constructor() {
        this.selectedPartnerships = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupPartnershipAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="partnership_type"], select[name="school_type"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual partnership actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="approve"], [data-action="reject"], [data-action="suspend"], [data-action="activate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handlePartnershipAction(e);
            }
        });

        // Partnership performance tracking
        this.setupPerformanceTracking();
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
        });
    }

    setupPartnershipAnalytics() {
        // Initialize partnership performance charts
        this.initializeCharts();
        
        // Track partnership metrics
        this.trackPartnershipMetrics();
    }

    setupPerformanceTracking() {
        // Track partnership performance in real-time
        document.querySelectorAll('.partnership-row').forEach(row => {
            const partnershipId = row.dataset.partnershipId;
            if (partnershipId) {
                this.trackPartnershipPerformance(partnershipId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.partnership-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedPartnerships.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه مشارکت‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.partnership-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedPartnerships.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.partnership-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedPartnerships.add(checkbox.value);
            } else {
                this.selectedPartnerships.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.partnership-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک مشارکت را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected partnership IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'partnership_ids[]';
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
                this.updateTable(data.partnerships);
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

    // Individual partnership actions
    handlePartnershipAction(e) {
        const action = e.target.dataset.action;
        const partnershipId = e.target.dataset.partnershipId;
        
        if (action === 'delete') {
            this.confirmDelete(partnershipId);
        } else if (action === 'approve') {
            this.confirmApprove(partnershipId);
        } else if (action === 'reject') {
            this.confirmReject(partnershipId);
        } else if (action === 'suspend') {
            this.confirmSuspend(partnershipId);
        } else if (action === 'activate') {
            this.confirmActivate(partnershipId);
        }
    }

    // Status change handlers
    handleStatusChange(action, partnershipId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/schools/${partnershipId}/${action}`, {
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
                this.updatePartnershipStatus(partnershipId, action);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در انجام عملیات', 'error');
            }
        })
        .catch(error => {
            console.error('Status change error:', error);
            this.showToast('خطا در انجام عملیات', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update partnership status in UI
    updatePartnershipStatus(partnershipId, action) {
        const row = document.querySelector(`tr[data-partnership-id="${partnershipId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.partnership-actions');
        
        if (action === 'approve') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">تأیید شده</span>';
            }
            // Update action buttons
            const approveBtn = actionCell?.querySelector('[data-action="approve"]');
            const rejectBtn = actionCell?.querySelector('[data-action="reject"]');
            if (approveBtn) approveBtn.remove();
            if (rejectBtn) rejectBtn.remove();
        } else if (action === 'reject') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">رد شده</span>';
            }
            // Update action buttons
            const approveBtn = actionCell?.querySelector('[data-action="approve"]');
            const rejectBtn = actionCell?.querySelector('[data-action="reject"]');
            if (approveBtn) approveBtn.remove();
            if (rejectBtn) rejectBtn.remove();
        } else if (action === 'suspend') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">معلق</span>';
            }
        } else if (action === 'activate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
            }
        }
    }

    // Partnership analytics
    initializeCharts() {
        // Initialize partnership performance charts using Chart.js
        const ctx = document.getElementById('partnershipChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.partnershipChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'مشارکت‌های فعال',
                        data: [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'روند مشارکت‌های مدرسه‌ای'
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

    trackPartnershipMetrics() {
        // Track partnership metrics in real-time
        setInterval(() => {
            this.updatePartnershipMetrics();
        }, 60000); // Update every minute
    }

    updatePartnershipMetrics() {
        fetch('/admin/schools/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updatePartnershipPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.partnershipChart) {
            this.partnershipChart.data.labels = metrics.labels;
            this.partnershipChart.data.datasets[0].data = metrics.data;
            this.partnershipChart.update();
        }
    }

    updatePartnershipPerformance(performance) {
        // Update partnership performance indicators
        document.querySelectorAll('.partnership-performance').forEach(element => {
            const partnershipId = element.dataset.partnershipId;
            if (performance[partnershipId]) {
                const perf = performance[partnershipId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.students} دانش‌آموز</div>
                    <div class="text-sm text-gray-500">${perf.teachers} معلم</div>
                `;
            }
        });
    }

    trackPartnershipPerformance(partnershipId) {
        // Track individual partnership performance
        fetch(`/admin/schools/${partnershipId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updatePartnershipRow(partnershipId, data.performance);
            }
        })
        .catch(error => {
            console.error('Partnership performance error:', error);
        });
    }

    updatePartnershipRow(partnershipId, performance) {
        const row = document.querySelector(`tr[data-partnership-id="${partnershipId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.partnership-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.students} دانش‌آموز</div>
                <div class="text-sm text-gray-500">${performance.teachers} معلم</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedPartnerships.size;
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
        fetch('/admin/schools/statistics', {
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
            statElements[2].textContent = stats.pending || 0;
            statElements[3].textContent = stats.total_students || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(partnershipId) {
        if (confirm('آیا از حذف این مشارکت اطمینان دارید؟')) {
            this.performDelete(partnershipId);
        }
    }

    confirmApprove(partnershipId) {
        if (confirm('آیا از تأیید این مشارکت اطمینان دارید؟')) {
            this.handleStatusChange('approve', partnershipId);
        }
    }

    confirmReject(partnershipId) {
        if (confirm('آیا از رد این مشارکت اطمینان دارید؟')) {
            this.handleStatusChange('reject', partnershipId);
        }
    }

    confirmSuspend(partnershipId) {
        if (confirm('آیا از تعلیق این مشارکت اطمینان دارید؟')) {
            this.handleStatusChange('suspend', partnershipId);
        }
    }

    confirmActivate(partnershipId) {
        if (confirm('آیا از فعال‌سازی این مشارکت اطمینان دارید؟')) {
            this.handleStatusChange('activate', partnershipId);
        }
    }

    performDelete(partnershipId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/schools/${partnershipId}`, {
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
                this.removePartnershipRow(partnershipId);
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

    removePartnershipRow(partnershipId) {
        const row = document.querySelector(`tr[data-partnership-id="${partnershipId}"]`);
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
    window.schoolPartnershipManager = new SchoolPartnershipManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.schoolPartnershipManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.schoolPartnershipManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.schoolPartnershipManager;
    if (manager) manager.toggleAll();
}
