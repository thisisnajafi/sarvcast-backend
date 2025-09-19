// Enhanced Quiz Management JavaScript Functionality
class QuizManager {
    constructor() {
        this.selectedQuizzes = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupQuizAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="difficulty"], select[name="category"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual quiz actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="publish"], [data-action="unpublish"], [data-action="activate"], [data-action="deactivate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleQuizAction(e);
            }
        });

        // Quiz performance tracking
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

    setupQuizAnalytics() {
        // Initialize quiz performance charts
        this.initializeCharts();
        
        // Track quiz metrics
        this.trackQuizMetrics();
    }

    setupPerformanceTracking() {
        // Track quiz performance in real-time
        document.querySelectorAll('.quiz-row').forEach(row => {
            const quizId = row.dataset.quizId;
            if (quizId) {
                this.trackQuizPerformance(quizId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.quiz-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedQuizzes.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه آزمون‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.quiz-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedQuizzes.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.quiz-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedQuizzes.add(checkbox.value);
            } else {
                this.selectedQuizzes.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.quiz-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک آزمون را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected quiz IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'quiz_ids[]';
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
                this.updateTable(data.quizzes);
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

    // Individual quiz actions
    handleQuizAction(e) {
        const action = e.target.dataset.action;
        const quizId = e.target.dataset.quizId;
        
        if (action === 'delete') {
            this.confirmDelete(quizId);
        } else if (action === 'publish') {
            this.confirmPublish(quizId);
        } else if (action === 'unpublish') {
            this.confirmUnpublish(quizId);
        } else if (action === 'activate') {
            this.confirmActivate(quizId);
        } else if (action === 'deactivate') {
            this.confirmDeactivate(quizId);
        }
    }

    // Status change handlers
    handleStatusChange(action, quizId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/quizzes/${quizId}/${action}`, {
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
                this.updateQuizStatus(quizId, action);
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

    // Update quiz status in UI
    updateQuizStatus(quizId, action) {
        const row = document.querySelector(`tr[data-quiz-id="${quizId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.quiz-actions');
        
        if (action === 'publish') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">منتشر شده</span>';
            }
            // Update action buttons
            const publishBtn = actionCell?.querySelector('[data-action="publish"]');
            if (publishBtn) {
                publishBtn.innerHTML = 'لغو انتشار';
                publishBtn.setAttribute('data-action', 'unpublish');
            }
        } else if (action === 'unpublish') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">منتشر نشده</span>';
            }
            // Update action buttons
            const unpublishBtn = actionCell?.querySelector('[data-action="unpublish"]');
            if (unpublishBtn) {
                unpublishBtn.innerHTML = 'انتشار';
                unpublishBtn.setAttribute('data-action', 'publish');
            }
        } else if (action === 'activate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
            }
        } else if (action === 'deactivate') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">غیرفعال</span>';
            }
        }
    }

    // Quiz analytics
    initializeCharts() {
        // Initialize quiz performance charts using Chart.js
        const ctx = document.getElementById('quizChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.quizChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'تعداد شرکت‌کنندگان',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'میانگین نمره',
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
                            text: 'آمار عملکرد آزمون‌ها'
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

    trackQuizMetrics() {
        // Track quiz metrics in real-time
        setInterval(() => {
            this.updateQuizMetrics();
        }, 60000); // Update every minute
    }

    updateQuizMetrics() {
        fetch('/admin/quizzes/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateQuizPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.quizChart) {
            this.quizChart.data.labels = metrics.labels;
            this.quizChart.data.datasets[0].data = metrics.participants;
            this.quizChart.data.datasets[1].data = metrics.average_scores;
            this.quizChart.update();
        }
    }

    updateQuizPerformance(performance) {
        // Update quiz performance indicators
        document.querySelectorAll('.quiz-performance').forEach(element => {
            const quizId = element.dataset.quizId;
            if (performance[quizId]) {
                const perf = performance[quizId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.participants} شرکت‌کننده</div>
                    <div class="text-sm text-gray-500">میانگین: ${perf.average_score}</div>
                `;
            }
        });
    }

    trackQuizPerformance(quizId) {
        // Track individual quiz performance
        fetch(`/admin/quizzes/${quizId}/performance`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateQuizRow(quizId, data.performance);
            }
        })
        .catch(error => {
            console.error('Quiz performance error:', error);
        });
    }

    updateQuizRow(quizId, performance) {
        const row = document.querySelector(`tr[data-quiz-id="${quizId}"]`);
        if (!row) return;

        const performanceCell = row.querySelector('.quiz-performance');
        if (performanceCell) {
            performanceCell.innerHTML = `
                <div class="text-sm text-gray-900">${performance.participants} شرکت‌کننده</div>
                <div class="text-sm text-gray-500">میانگین: ${performance.average_score}</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedQuizzes.size;
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
        fetch('/admin/quizzes/statistics', {
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
            statElements[1].textContent = stats.published || 0;
            statElements[2].textContent = stats.active || 0;
            statElements[3].textContent = stats.total_participants || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(quizId) {
        if (confirm('آیا از حذف این آزمون اطمینان دارید؟')) {
            this.performDelete(quizId);
        }
    }

    confirmPublish(quizId) {
        if (confirm('آیا از انتشار این آزمون اطمینان دارید؟')) {
            this.handleStatusChange('publish', quizId);
        }
    }

    confirmUnpublish(quizId) {
        if (confirm('آیا از لغو انتشار این آزمون اطمینان دارید؟')) {
            this.handleStatusChange('unpublish', quizId);
        }
    }

    confirmActivate(quizId) {
        if (confirm('آیا از فعال‌سازی این آزمون اطمینان دارید؟')) {
            this.handleStatusChange('activate', quizId);
        }
    }

    confirmDeactivate(quizId) {
        if (confirm('آیا از غیرفعال‌سازی این آزمون اطمینان دارید؟')) {
            this.handleStatusChange('deactivate', quizId);
        }
    }

    performDelete(quizId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/quizzes/${quizId}`, {
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
                this.removeQuizRow(quizId);
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

    removeQuizRow(quizId) {
        const row = document.querySelector(`tr[data-quiz-id="${quizId}"]`);
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
    window.quizManager = new QuizManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.quizManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.quizManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.quizManager;
    if (manager) manager.toggleAll();
}
