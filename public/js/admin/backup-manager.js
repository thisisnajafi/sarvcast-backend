// Enhanced Backup Management JavaScript Functionality
class BackupManager {
    constructor() {
        this.selectedBackups = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupBackupAnalytics();
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
        document.querySelectorAll('select[name="status"], select[name="backup_type"], select[name="storage_type"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual backup actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="restore"], [data-action="download"], [data-action="verify"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleBackupAction(e);
            }
        });

        // Backup progress tracking
        this.setupProgressTracking();
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

    setupBackupAnalytics() {
        // Initialize backup performance charts
        this.initializeCharts();
        
        // Track backup metrics
        this.trackBackupMetrics();
    }

    setupProgressTracking() {
        // Track backup progress in real-time
        document.querySelectorAll('.backup-row').forEach(row => {
            const backupId = row.dataset.backupId;
            if (backupId) {
                this.trackBackupProgress(backupId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.backup-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedBackups.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه پشتیبان‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.backup-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedBackups.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.backup-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedBackups.add(checkbox.value);
            } else {
                this.selectedBackups.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.backup-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک پشتیبان را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected backup IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'backup_ids[]';
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
                this.updateTable(data.backups);
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

    // Individual backup actions
    handleBackupAction(e) {
        const action = e.target.dataset.action;
        const backupId = e.target.dataset.backupId;
        
        if (action === 'delete') {
            this.confirmDelete(backupId);
        } else if (action === 'restore') {
            this.confirmRestore(backupId);
        } else if (action === 'download') {
            this.performDownload(backupId);
        } else if (action === 'verify') {
            this.performVerify(backupId);
        }
    }

    // Backup operations
    performRestore(backupId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/backup/${backupId}/restore`, {
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
                this.updateBackupStatus(backupId, 'restoring');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در بازیابی', 'error');
            }
        })
        .catch(error => {
            console.error('Restore error:', error);
            this.showToast('خطا در بازیابی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    performDownload(backupId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/backup/${backupId}/download`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Download failed');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `backup-${backupId}.zip`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showToast('دانلود شروع شد', 'success');
        })
        .catch(error => {
            console.error('Download error:', error);
            this.showToast('خطا در دانلود', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    performVerify(backupId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/backup/${backupId}/verify`, {
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
                this.updateBackupStatus(backupId, 'verified');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تأیید', 'error');
            }
        })
        .catch(error => {
            console.error('Verify error:', error);
            this.showToast('خطا در تأیید', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update backup status in UI
    updateBackupStatus(backupId, status) {
        const row = document.querySelector(`tr[data-backup-id="${backupId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'restoring') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">در حال بازیابی</span>';
            }
        } else if (status === 'verified') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">تأیید شده</span>';
            }
        }
    }

    // Backup analytics
    initializeCharts() {
        // Initialize backup performance charts using Chart.js
        const ctx = document.getElementById('backupChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.backupChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'اندازه پشتیبان (MB)',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'زمان ایجاد (دقیقه)',
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
                            text: 'آمار عملکرد پشتیبان‌گیری'
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

    trackBackupMetrics() {
        // Track backup metrics in real-time
        setInterval(() => {
            this.updateBackupMetrics();
        }, 60000); // Update every minute
    }

    updateBackupMetrics() {
        fetch('/admin/backup/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateBackupPerformance(data.performance);
            }
        })
        .catch(error => {
            console.error('Metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.backupChart) {
            this.backupChart.data.labels = metrics.labels;
            this.backupChart.data.datasets[0].data = metrics.sizes;
            this.backupChart.data.datasets[1].data = metrics.durations;
            this.backupChart.update();
        }
    }

    updateBackupPerformance(performance) {
        // Update backup performance indicators
        document.querySelectorAll('.backup-performance').forEach(element => {
            const backupId = element.dataset.backupId;
            if (performance[backupId]) {
                const perf = performance[backupId];
                element.innerHTML = `
                    <div class="text-sm text-gray-900">${perf.size} MB</div>
                    <div class="text-sm text-gray-500">${perf.duration} دقیقه</div>
                `;
            }
        });
    }

    trackBackupProgress(backupId) {
        // Track individual backup progress
        fetch(`/admin/backup/${backupId}/progress`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateBackupRow(backupId, data.progress);
            }
        })
        .catch(error => {
            console.error('Backup progress error:', error);
        });
    }

    updateBackupRow(backupId, progress) {
        const row = document.querySelector(`tr[data-backup-id="${backupId}"]`);
        if (!row) return;

        const progressCell = row.querySelector('.backup-performance');
        if (progressCell) {
            progressCell.innerHTML = `
                <div class="text-sm text-gray-900">${progress.size} MB</div>
                <div class="text-sm text-gray-500">${progress.duration} دقیقه</div>
            `;
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedBackups.size;
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
        fetch('/admin/backup/statistics', {
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
            statElements[2].textContent = stats.failed || 0;
            statElements[3].textContent = stats.total_size || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(backupId) {
        if (confirm('آیا از حذف این پشتیبان اطمینان دارید؟')) {
            this.performDelete(backupId);
        }
    }

    confirmRestore(backupId) {
        if (confirm('آیا از بازیابی این پشتیبان اطمینان دارید؟ این عمل ممکن است زمان‌بر باشد.')) {
            this.performRestore(backupId);
        }
    }

    performDelete(backupId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/backup/${backupId}`, {
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
                this.removeBackupRow(backupId);
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

    removeBackupRow(backupId) {
        const row = document.querySelector(`tr[data-backup-id="${backupId}"]`);
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
    window.backupManager = new BackupManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.backupManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.backupManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.backupManager;
    if (manager) manager.toggleAll();
}
