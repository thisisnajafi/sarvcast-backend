// Enhanced Influencer Campaign Management JavaScript Functionality
class InfluencerManager {
    constructor() {
        this.selectedInfluencers = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupCampaignAnalytics();
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

        // Individual influencer actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="approve"], [data-action="reject"], [data-action="suspend"], [data-action="activate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleInfluencerAction(e);
            }
        });

        // Campaign performance tracking
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

    setupCampaignAnalytics() {
        // Initialize campaign performance charts
        this.initializeCharts();
        
        // Track campaign metrics
        this.trackCampaignMetrics();
    }

    setupPerformanceTracking() {
        // Track campaign performance in real-time
        document.querySelectorAll('.campaign-row').forEach(row => {
            const campaignId = row.dataset.campaignId;
            if (campaignId) {
                this.trackCampaignPerformance(campaignId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedInfluencers.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه کمپین‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedInfluencers.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedInfluencers.add(checkbox.value);
            } else {
                this.selectedInfluencers.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.influencer-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک کمپین را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected influencer IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'influencer_ids[]';
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
                this.updateTable(data.influencers);
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

    // Individual influencer actions
    handleInfluencerAction(e) {
        const action = e.target.dataset.action;
        const influencerId = e.target.dataset.influencerId;
        
        if (action === 'delete') {
            this.confirmDelete(influencerId);
        } else if (action === 'approve') {
            this.confirmApprove(influencerId);
        } else if (action === 'reject') {
            this.confirmReject(influencerId);
        } else if (action === 'suspend') {
            this.confirmSuspend(influencerId);
        } else if (action === 'activate') {
            this.confirmActivate(influencerId);
        }
    }

    // Status change handlers
    handleStatusChange(action, influencerId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/influencers/${influencerId}/${action}`, {
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
                this.updateInfluencerStatus(influencerId, action);
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

    // Update influencer status in UI
    updateInfluencerStatus(influencerId, action) {
        const row = document.querySelector(`tr[data-influencer-id="${influencerId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.influencer-actions');
        
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

    // Campaign analytics
    initializeCharts() {
        // Initialize campaign performance charts using Chart.js
        const ctx = document.getElementById('campaignChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.campaignChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'کمپین‌های فعال',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'روند کمپین‌های اینفلوئنسری'
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

    trackCampaignMetrics() {
        // Track campaign metrics in real-time
        setInterval(() => {
            this.updateCampaignMetrics();
        }, 60000); // Update every minute
    }

    updateCampaignMetrics() {
        fetch('/admin/influencers/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateCampaignPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.campaignChart) {
            this.campaignChart.data.labels = metrics.labels;
            this.campaignChart.data.datasets[0].data = metrics.data;
            this.campaignChart.update();
        }
    }

    updateCampaignPerformance(performance) {
        // Update campaign performance indicators
        document.querySelectorAll('.campaign-performance').forEach(element => {
            const campaignId = element.dataset.campaignId;
            if (performance[campaignId]) {
                const perf = performance[campaignId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.views} بازدید</div>
                    <div class="text-sm text-gray-500">${perf.engagement}% تعامل</div>
                `;
            }
        });
    }

    trackCampaignPerformance(campaignId) {
        // Track individual campaign performance
        fetch(`/admin/influencers/${campaignId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCampaignRow(campaignId, data.performance);
            }
        })
        .catch(error => {
            console.error('Campaign performance error:', error);
        });
    }

    updateCampaignRow(campaignId, performance) {
        const row = document.querySelector(`tr[data-campaign-id="${campaignId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.campaign-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.views} بازدید</div>
                <div class="text-sm text-gray-500">${performance.engagement}% تعامل</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedInfluencers.size;
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
        fetch('/admin/influencers/statistics', {
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
            statElements[3].textContent = stats.total_reach || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(influencerId) {
        if (confirm('آیا از حذف این کمپین اطمینان دارید؟')) {
            this.performDelete(influencerId);
        }
    }

    confirmApprove(influencerId) {
        if (confirm('آیا از تأیید این کمپین اطمینان دارید؟')) {
            this.handleStatusChange('approve', influencerId);
        }
    }

    confirmReject(influencerId) {
        if (confirm('آیا از رد این کمپین اطمینان دارید؟')) {
            this.handleStatusChange('reject', influencerId);
        }
    }

    confirmSuspend(influencerId) {
        if (confirm('آیا از تعلیق این کمپین اطمینان دارید؟')) {
            this.handleStatusChange('suspend', influencerId);
        }
    }

    confirmActivate(influencerId) {
        if (confirm('آیا از فعال‌سازی این کمپین اطمینان دارید؟')) {
            this.handleStatusChange('activate', influencerId);
        }
    }

    performDelete(influencerId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/influencers/${influencerId}`, {
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
                this.removeInfluencerRow(influencerId);
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

    removeInfluencerRow(influencerId) {
        const row = document.querySelector(`tr[data-influencer-id="${influencerId}"]`);
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
    window.influencerManager = new InfluencerManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.influencerManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.influencerManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.influencerManager;
    if (manager) manager.toggleAll();
}
