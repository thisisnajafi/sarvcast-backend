// Enhanced Referral Management JavaScript Functionality
class ReferralManager {
    constructor() {
        this.selectedReferrals = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupReferralAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="referral_type"], select[name="reward_status"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual referral actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="approve"], [data-action="reject"], [data-action="pay"], [data-action="activate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleReferralAction(e);
            }
        });

        // Referral performance tracking
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

    setupReferralAnalytics() {
        // Initialize referral performance charts
        this.initializeCharts();
        
        // Track referral metrics
        this.trackReferralMetrics();
    }

    setupPerformanceTracking() {
        // Track referral performance in real-time
        document.querySelectorAll('.referral-row').forEach(row => {
            const referralId = row.dataset.referralId;
            if (referralId) {
                this.trackReferralPerformance(referralId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedReferrals.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه ارجاعات انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedReferrals.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedReferrals.add(checkbox.value);
            } else {
                this.selectedReferrals.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.referral-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک ارجاع را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected referral IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'referral_ids[]';
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
                this.updateTable(data.referrals);
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

    // Individual referral actions
    handleReferralAction(e) {
        const action = e.target.dataset.action;
        const referralId = e.target.dataset.referralId;
        
        if (action === 'delete') {
            this.confirmDelete(referralId);
        } else if (action === 'approve') {
            this.confirmApprove(referralId);
        } else if (action === 'reject') {
            this.confirmReject(referralId);
        } else if (action === 'pay') {
            this.confirmPay(referralId);
        } else if (action === 'activate') {
            this.confirmActivate(referralId);
        }
    }

    // Status change handlers
    handleStatusChange(action, referralId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/referrals/${referralId}/${action}`, {
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
                this.updateReferralStatus(referralId, action);
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

    // Update referral status in UI
    updateReferralStatus(referralId, action) {
        const row = document.querySelector(`tr[data-referral-id="${referralId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.referral-actions');
        
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
        } else if (action === 'pay') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">پرداخت شده</span>';
            }
            // Update action buttons
            const payBtn = actionCell?.querySelector('[data-action="pay"]');
            if (payBtn) payBtn.remove();
        } else if (action === 'activate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
            }
        }
    }

    // Referral analytics
    initializeCharts() {
        // Initialize referral performance charts using Chart.js
        const ctx = document.getElementById('referralChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.referralChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'ارجاعات موفق',
                        data: [],
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    }, {
                        label: 'ارجاعات ناموفق',
                        data: [],
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'آمار سیستم ارجاع'
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

    trackReferralMetrics() {
        // Track referral metrics in real-time
        setInterval(() => {
            this.updateReferralMetrics();
        }, 60000); // Update every minute
    }

    updateReferralMetrics() {
        fetch('/admin/referrals/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateReferralPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.referralChart) {
            this.referralChart.data.labels = metrics.labels;
            this.referralChart.data.datasets[0].data = metrics.successful;
            this.referralChart.data.datasets[1].data = metrics.unsuccessful;
            this.referralChart.update();
        }
    }

    updateReferralPerformance(performance) {
        // Update referral performance indicators
        document.querySelectorAll('.referral-performance').forEach(element => {
            const referralId = element.dataset.referralId;
            if (performance[referralId]) {
                const perf = performance[referralId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.referrals_count} ارجاع</div>
                    <div class="text-sm text-gray-500">${perf.total_reward} تومان پاداش</div>
                `;
            }
        });
    }

    trackReferralPerformance(referralId) {
        // Track individual referral performance
        fetch(`/admin/referrals/${referralId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateReferralRow(referralId, data.performance);
            }
        })
        .catch(error => {
            console.error('Referral performance error:', error);
        });
    }

    updateReferralRow(referralId, performance) {
        const row = document.querySelector(`tr[data-referral-id="${referralId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.referral-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.referrals_count} ارجاع</div>
                <div class="text-sm text-gray-500">${performance.total_reward} تومان پاداش</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedReferrals.size;
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
        fetch('/admin/referrals/statistics', {
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
            statElements[1].textContent = stats.successful || 0;
            statElements[2].textContent = stats.pending || 0;
            statElements[3].textContent = stats.total_rewards || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(referralId) {
        if (confirm('آیا از حذف این ارجاع اطمینان دارید؟')) {
            this.performDelete(referralId);
        }
    }

    confirmApprove(referralId) {
        if (confirm('آیا از تأیید این ارجاع اطمینان دارید؟')) {
            this.handleStatusChange('approve', referralId);
        }
    }

    confirmReject(referralId) {
        if (confirm('آیا از رد این ارجاع اطمینان دارید؟')) {
            this.handleStatusChange('reject', referralId);
        }
    }

    confirmPay(referralId) {
        if (confirm('آیا از پرداخت پاداش این ارجاع اطمینان دارید؟')) {
            this.handleStatusChange('pay', referralId);
        }
    }

    confirmActivate(referralId) {
        if (confirm('آیا از فعال‌سازی این ارجاع اطمینان دارید؟')) {
            this.handleStatusChange('activate', referralId);
        }
    }

    performDelete(referralId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/referrals/${referralId}`, {
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
                this.removeReferralRow(referralId);
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

    removeReferralRow(referralId) {
        const row = document.querySelector(`tr[data-referral-id="${referralId}"]`);
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
    window.referralManager = new ReferralManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.referralManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.referralManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.referralManager;
    if (manager) manager.toggleAll();
}
