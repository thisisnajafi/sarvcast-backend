// Enhanced Content Moderation Management JavaScript Functionality
class ContentModerationManager {
    constructor() {
        this.selectedContent = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupModerationAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="content_type"], select[name="priority"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual content actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="approve"], [data-action="reject"], [data-action="flag"], [data-action="unflag"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleContentAction(e);
            }
        });

        // Content moderation tracking
        this.setupModerationTracking();
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

    setupModerationAnalytics() {
        // Initialize moderation performance charts
        this.initializeCharts();
        
        // Track moderation metrics
        this.trackModerationMetrics();
    }

    setupModerationTracking() {
        // Track content moderation in real-time
        document.querySelectorAll('.content-row').forEach(row => {
            const contentId = row.dataset.contentId;
            if (contentId) {
                this.trackContentModeration(contentId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.content-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedContent.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه محتواها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.content-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedContent.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.content-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedContent.add(checkbox.value);
            } else {
                this.selectedContent.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.content-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک محتوا را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected content IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'content_ids[]';
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
                this.updateTable(data.content);
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

    // Individual content actions
    handleContentAction(e) {
        const action = e.target.dataset.action;
        const contentId = e.target.dataset.contentId;
        
        if (action === 'delete') {
            this.confirmDelete(contentId);
        } else if (action === 'approve') {
            this.confirmApprove(contentId);
        } else if (action === 'reject') {
            this.confirmReject(contentId);
        } else if (action === 'flag') {
            this.confirmFlag(contentId);
        } else if (action === 'unflag') {
            this.confirmUnflag(contentId);
        }
    }

    // Status change handlers
    handleStatusChange(action, contentId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/content-moderation/${contentId}/${action}`, {
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
                this.updateContentStatus(contentId, action);
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

    // Update content status in UI
    updateContentStatus(contentId, action) {
        const row = document.querySelector(`tr[data-content-id="${contentId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.content-actions');
        
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
        } else if (action === 'flag') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">پرچم‌دار</span>';
            }
            // Update action buttons
            const flagBtn = actionCell?.querySelector('[data-action="flag"]');
            if (flagBtn) {
                flagBtn.innerHTML = 'لغو پرچم';
                flagBtn.setAttribute('data-action', 'unflag');
            }
        } else if (action === 'unflag') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">عادی</span>';
            }
            // Update action buttons
            const unflagBtn = actionCell?.querySelector('[data-action="unflag"]');
            if (unflagBtn) {
                unflagBtn.innerHTML = 'پرچم';
                unflagBtn.setAttribute('data-action', 'flag');
            }
        }
    }

    // Moderation analytics
    initializeCharts() {
        // Initialize moderation performance charts using Chart.js
        const ctx = document.getElementById('moderationChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.moderationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['تأیید شده', 'رد شده', 'در انتظار', 'پرچم‌دار'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(168, 85, 247, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(251, 191, 36)',
                            'rgb(168, 85, 247)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'وضعیت نظارت بر محتوا'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    trackModerationMetrics() {
        // Track moderation metrics in real-time
        setInterval(() => {
            this.updateModerationMetrics();
        }, 60000); // Update every minute
    }

    updateModerationMetrics() {
        fetch('/admin/content-moderation/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateContentModeration(data.moderation);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.moderationChart) {
            this.moderationChart.data.datasets[0].data = [
                metrics.approved || 0,
                metrics.rejected || 0,
                metrics.pending || 0,
                metrics.flagged || 0
            ];
            this.moderationChart.update();
        }
    }

    updateContentModeration(moderation) {
        // Update content moderation indicators
        document.querySelectorAll('.content-moderation').forEach(element => {
            const contentId = element.dataset.contentId;
            if (moderation[contentId]) {
                const mod = moderation[contentId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${mod.reports_count} گزارش</div>
                    <div class="text-sm text-gray-500">${mod.moderation_score} امتیاز</div>
                `;
            }
        });
    }

    trackContentModeration(contentId) {
        // Track individual content moderation
        fetch(`/admin/content-moderation/${contentId}/moderation`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateContentRow(contentId, data.moderation);
            }
        })
        .catch(error => {
            console.error('Content moderation error:', error);
        });
    }

    updateContentRow(contentId, moderation) {
        const row = document.querySelector(`tr[data-content-id="${contentId}"]`);
        if (!row) return;

        const moderationCell = row.querySelector('.content-moderation');
        if (moderationCell) {
            moderationCell.innerHTML = `
                <div class="text-sm text-gray-900">${moderation.reports_count} گزارش</div>
                <div class="text-sm text-gray-500">${moderation.moderation_score} امتیاز</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedContent.size;
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
        fetch('/admin/content-moderation/statistics', {
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
            statElements[1].textContent = stats.pending || 0;
            statElements[2].textContent = stats.approved || 0;
            statElements[3].textContent = stats.rejected || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(contentId) {
        if (confirm('آیا از حذف این محتوا اطمینان دارید؟')) {
            this.performDelete(contentId);
        }
    }

    confirmApprove(contentId) {
        if (confirm('آیا از تأیید این محتوا اطمینان دارید؟')) {
            this.handleStatusChange('approve', contentId);
        }
    }

    confirmReject(contentId) {
        if (confirm('آیا از رد این محتوا اطمینان دارید؟')) {
            this.handleStatusChange('reject', contentId);
        }
    }

    confirmFlag(contentId) {
        if (confirm('آیا از پرچم‌دار کردن این محتوا اطمینان دارید؟')) {
            this.handleStatusChange('flag', contentId);
        }
    }

    confirmUnflag(contentId) {
        if (confirm('آیا از لغو پرچم این محتوا اطمینان دارید؟')) {
            this.handleStatusChange('unflag', contentId);
        }
    }

    performDelete(contentId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/content-moderation/${contentId}`, {
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
                this.removeContentRow(contentId);
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

    removeContentRow(contentId) {
        const row = document.querySelector(`tr[data-content-id="${contentId}"]`);
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
    window.contentModerationManager = new ContentModerationManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.contentModerationManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.contentModerationManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.contentModerationManager;
    if (manager) manager.toggleAll();
}
