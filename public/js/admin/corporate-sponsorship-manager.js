// Enhanced Corporate Sponsorship Management JavaScript Functionality
class CorporateSponsorshipManager {
    constructor() {
        this.selectedSponsorships = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupSponsorshipAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="sponsorship_type"], select[name="industry"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual sponsorship actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="approve"], [data-action="reject"], [data-action="suspend"], [data-action="activate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleSponsorshipAction(e);
            }
        });

        // Sponsorship performance tracking
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

    setupSponsorshipAnalytics() {
        // Initialize sponsorship performance charts
        this.initializeCharts();
        
        // Track sponsorship metrics
        this.trackSponsorshipMetrics();
    }

    setupPerformanceTracking() {
        // Track sponsorship performance in real-time
        document.querySelectorAll('.sponsorship-row').forEach(row => {
            const sponsorshipId = row.dataset.sponsorshipId;
            if (sponsorshipId) {
                this.trackSponsorshipPerformance(sponsorshipId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.sponsorship-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedSponsorships.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه حمایت‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.sponsorship-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedSponsorships.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.sponsorship-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedSponsorships.add(checkbox.value);
            } else {
                this.selectedSponsorships.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.sponsorship-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک حمایت را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected sponsorship IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'sponsorship_ids[]';
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
                this.updateTable(data.sponsorships);
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

    // Individual sponsorship actions
    handleSponsorshipAction(e) {
        const action = e.target.dataset.action;
        const sponsorshipId = e.target.dataset.sponsorshipId;
        
        if (action === 'delete') {
            this.confirmDelete(sponsorshipId);
        } else if (action === 'approve') {
            this.confirmApprove(sponsorshipId);
        } else if (action === 'reject') {
            this.confirmReject(sponsorshipId);
        } else if (action === 'suspend') {
            this.confirmSuspend(sponsorshipId);
        } else if (action === 'activate') {
            this.confirmActivate(sponsorshipId);
        }
    }

    // Status change handlers
    handleStatusChange(action, sponsorshipId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/corporate/${sponsorshipId}/${action}`, {
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
                this.updateSponsorshipStatus(sponsorshipId, action);
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

    // Update sponsorship status in UI
    updateSponsorshipStatus(sponsorshipId, action) {
        const row = document.querySelector(`tr[data-sponsorship-id="${sponsorshipId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.sponsorship-actions');
        
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

    // Sponsorship analytics
    initializeCharts() {
        // Initialize sponsorship performance charts using Chart.js
        const ctx = document.getElementById('sponsorshipChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.sponsorshipChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['فعال', 'در انتظار', 'معلق', 'رد شده'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(107, 114, 128, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(251, 191, 36)',
                            'rgb(239, 68, 68)',
                            'rgb(107, 114, 128)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'وضعیت حمایت‌های شرکتی'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    trackSponsorshipMetrics() {
        // Track sponsorship metrics in real-time
        setInterval(() => {
            this.updateSponsorshipMetrics();
        }, 60000); // Update every minute
    }

    updateSponsorshipMetrics() {
        fetch('/admin/corporate/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateSponsorshipPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.sponsorshipChart) {
            this.sponsorshipChart.data.datasets[0].data = [
                metrics.active || 0,
                metrics.pending || 0,
                metrics.suspended || 0,
                metrics.rejected || 0
            ];
            this.sponsorshipChart.update();
        }
    }

    updateSponsorshipPerformance(performance) {
        // Update sponsorship performance indicators
        document.querySelectorAll('.sponsorship-performance').forEach(element => {
            const sponsorshipId = element.dataset.sponsorshipId;
            if (performance[sponsorshipId]) {
                const perf = performance[sponsorshipId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.amount} تومان</div>
                    <div class="text-sm text-gray-500">${perf.duration} ماه</div>
                `;
            }
        });
    }

    trackSponsorshipPerformance(sponsorshipId) {
        // Track individual sponsorship performance
        fetch(`/admin/corporate/${sponsorshipId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateSponsorshipRow(sponsorshipId, data.performance);
            }
        })
        .catch(error => {
            console.error('Sponsorship performance error:', error);
        });
    }

    updateSponsorshipRow(sponsorshipId, performance) {
        const row = document.querySelector(`tr[data-sponsorship-id="${sponsorshipId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.sponsorship-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.amount} تومان</div>
                <div class="text-sm text-gray-500">${performance.duration} ماه</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedSponsorships.size;
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
        fetch('/admin/corporate/statistics', {
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
            statElements[3].textContent = stats.total_amount || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(sponsorshipId) {
        if (confirm('آیا از حذف این حمایت اطمینان دارید؟')) {
            this.performDelete(sponsorshipId);
        }
    }

    confirmApprove(sponsorshipId) {
        if (confirm('آیا از تأیید این حمایت اطمینان دارید؟')) {
            this.handleStatusChange('approve', sponsorshipId);
        }
    }

    confirmReject(sponsorshipId) {
        if (confirm('آیا از رد این حمایت اطمینان دارید؟')) {
            this.handleStatusChange('reject', sponsorshipId);
        }
    }

    confirmSuspend(sponsorshipId) {
        if (confirm('آیا از تعلیق این حمایت اطمینان دارید؟')) {
            this.handleStatusChange('suspend', sponsorshipId);
        }
    }

    confirmActivate(sponsorshipId) {
        if (confirm('آیا از فعال‌سازی این حمایت اطمینان دارید؟')) {
            this.handleStatusChange('activate', sponsorshipId);
        }
    }

    performDelete(sponsorshipId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/corporate/${sponsorshipId}`, {
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
                this.removeSponsorshipRow(sponsorshipId);
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

    removeSponsorshipRow(sponsorshipId) {
        const row = document.querySelector(`tr[data-sponsorship-id="${sponsorshipId}"]`);
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
    window.corporateSponsorshipManager = new CorporateSponsorshipManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.corporateSponsorshipManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.corporateSponsorshipManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.corporateSponsorshipManager;
    if (manager) manager.toggleAll();
}
