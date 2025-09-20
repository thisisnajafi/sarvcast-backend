// Enhanced Gamification Management JavaScript Functionality
class GamificationManager {
    constructor() {
        this.selectedAchievements = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupGamificationAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="achievement_type"], select[name="difficulty"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual achievement actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="activate"], [data-action="deactivate"], [data-action="feature"], [data-action="unfeature"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleAchievementAction(e);
            }
        });

        // Achievement performance tracking
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

    setupGamificationAnalytics() {
        // Initialize gamification performance charts
        this.initializeCharts();
        
        // Track gamification metrics
        this.trackGamificationMetrics();
    }

    setupPerformanceTracking() {
        // Track achievement performance in real-time
        document.querySelectorAll('.achievement-row').forEach(row => {
            const achievementId = row.dataset.achievementId;
            if (achievementId) {
                this.trackAchievementPerformance(achievementId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.achievement-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedAchievements.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه دستاوردها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.achievement-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedAchievements.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.achievement-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedAchievements.add(checkbox.value);
            } else {
                this.selectedAchievements.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.achievement-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک دستاورد را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected achievement IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'achievement_ids[]';
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
                this.updateTable(data.achievements);
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

    // Individual achievement actions
    handleAchievementAction(e) {
        const action = e.target.dataset.action;
        const achievementId = e.target.dataset.achievementId;
        
        if (action === 'delete') {
            this.confirmDelete(achievementId);
        } else if (action === 'activate') {
            this.confirmActivate(achievementId);
        } else if (action === 'deactivate') {
            this.confirmDeactivate(achievementId);
        } else if (action === 'feature') {
            this.confirmFeature(achievementId);
        } else if (action === 'unfeature') {
            this.confirmUnfeature(achievementId);
        }
    }

    // Status change handlers
    handleStatusChange(action, achievementId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/gamification/${achievementId}/${action}`, {
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
                this.updateAchievementStatus(achievementId, action);
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

    // Update achievement status in UI
    updateAchievementStatus(achievementId, action) {
        const row = document.querySelector(`tr[data-achievement-id="${achievementId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.achievement-actions');
        
        if (action === 'activate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
            }
            // Update action buttons
            const activateBtn = actionCell?.querySelector('[data-action="activate"]');
            if (activateBtn) {
                activateBtn.innerHTML = 'غیرفعال';
                activateBtn.setAttribute('data-action', 'deactivate');
            }
        } else if (action === 'deactivate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">غیرفعال</span>';
            }
            // Update action buttons
            const deactivateBtn = actionCell?.querySelector('[data-action="deactivate"]');
            if (deactivateBtn) {
                deactivateBtn.innerHTML = 'فعال';
                deactivateBtn.setAttribute('data-action', 'activate');
            }
        } else if (action === 'feature') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">ویژه</span>';
            }
            // Update action buttons
            const featureBtn = actionCell?.querySelector('[data-action="feature"]');
            if (featureBtn) {
                featureBtn.innerHTML = 'لغو ویژه';
                featureBtn.setAttribute('data-action', 'unfeature');
            }
        } else if (action === 'unfeature') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">عادی</span>';
            }
            // Update action buttons
            const unfeatureBtn = actionCell?.querySelector('[data-action="unfeature"]');
            if (unfeatureBtn) {
                unfeatureBtn.innerHTML = 'ویژه';
                unfeatureBtn.setAttribute('data-action', 'feature');
            }
        }
    }

    // Gamification analytics
    initializeCharts() {
        // Initialize gamification performance charts using Chart.js
        const ctx = document.getElementById('gamificationChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.gamificationChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['دستاوردهای فعال', 'دستاوردهای غیرفعال', 'دستاوردهای ویژه'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(59, 130, 246, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(59, 130, 246)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'وضعیت دستاوردها'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    trackGamificationMetrics() {
        // Track gamification metrics in real-time
        setInterval(() => {
            this.updateGamificationMetrics();
        }, 60000); // Update every minute
    }

    updateGamificationMetrics() {
        fetch('/admin/gamification/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateAchievementPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.gamificationChart) {
            this.gamificationChart.data.datasets[0].data = [
                metrics.active || 0,
                metrics.inactive || 0,
                metrics.featured || 0
            ];
            this.gamificationChart.update();
        }
    }

    updateAchievementPerformance(performance) {
        // Update achievement performance indicators
        document.querySelectorAll('.achievement-performance').forEach(element => {
            const achievementId = element.dataset.achievementId;
            if (performance[achievementId]) {
                const perf = performance[achievementId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.users_earned} کاربر</div>
                    <div class="text-sm text-gray-500">${perf.completion_rate}% تکمیل</div>
                `;
            }
        });
    }

    trackAchievementPerformance(achievementId) {
        // Track individual achievement performance
        fetch(`/admin/gamification/${achievementId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateAchievementRow(achievementId, data.performance);
            }
        })
        .catch(error => {
            console.error('Achievement performance error:', error);
        });
    }

    updateAchievementRow(achievementId, performance) {
        const row = document.querySelector(`tr[data-achievement-id="${achievementId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.achievement-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.users_earned} کاربر</div>
                <div class="text-sm text-gray-500">${performance.completion_rate}% تکمیل</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedAchievements.size;
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
        fetch('/admin/gamification/statistics', {
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
            statElements[2].textContent = stats.featured || 0;
            statElements[3].textContent = stats.total_users || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(achievementId) {
        if (confirm('آیا از حذف این دستاورد اطمینان دارید؟')) {
            this.performDelete(achievementId);
        }
    }

    confirmActivate(achievementId) {
        if (confirm('آیا از فعال‌سازی این دستاورد اطمینان دارید؟')) {
            this.handleStatusChange('activate', achievementId);
        }
    }

    confirmDeactivate(achievementId) {
        if (confirm('آیا از غیرفعال‌سازی این دستاورد اطمینان دارید؟')) {
            this.handleStatusChange('deactivate', achievementId);
        }
    }

    confirmFeature(achievementId) {
        if (confirm('آیا از ویژه کردن این دستاورد اطمینان دارید؟')) {
            this.handleStatusChange('feature', achievementId);
        }
    }

    confirmUnfeature(achievementId) {
        if (confirm('آیا از لغو ویژه بودن این دستاورد اطمینان دارید؟')) {
            this.handleStatusChange('unfeature', achievementId);
        }
    }

    performDelete(achievementId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/gamification/${achievementId}`, {
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
                this.removeAchievementRow(achievementId);
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

    removeAchievementRow(achievementId) {
        const row = document.querySelector(`tr[data-achievement-id="${achievementId}"]`);
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
    window.gamificationManager = new GamificationManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.gamificationManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.gamificationManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.gamificationManager;
    if (manager) manager.toggleAll();
}
