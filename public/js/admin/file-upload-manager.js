// Enhanced File Upload Management JavaScript Functionality
class FileUploadManager {
    constructor() {
        this.selectedFiles = new Set();
        this.isLoading = false;
        this.uploadProgress = {};
        this.filePreview = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupFileAnalytics();
        this.initializeFilePreview();
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
        document.querySelectorAll('select[name="status"], select[name="file_type"], select[name="category"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual file actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="download"], [data-action="preview"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleFileAction(e);
            }
        });

        // File upload handling
        this.setupFileUpload();
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
            // Ctrl+U to upload files
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                this.triggerFileUpload();
            }
        });
    }

    setupFileAnalytics() {
        // Initialize file analytics charts
        this.initializeCharts();
        
        // Track file metrics
        this.trackFileMetrics();
    }

    setupFileUpload() {
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileUpload(e));
        }

        // Drag and drop functionality
        const dropZone = document.getElementById('drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
            dropZone.addEventListener('drop', (e) => this.handleDrop(e));
            dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        }
    }

    initializeFilePreview() {
        // Initialize file preview functionality
        this.setupFilePreview();
    }

    setupFilePreview() {
        // Setup file preview modal
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="preview"]')) {
                e.preventDefault();
                this.showFilePreview(e.target.dataset.fileId);
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.file-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedFiles.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه فایل‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.file-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedFiles.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.file-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedFiles.add(checkbox.value);
            } else {
                this.selectedFiles.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.file-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک فایل را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected file IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'file_ids[]';
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
                this.updateTable(data.files);
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

    // Individual file actions
    handleFileAction(e) {
        const action = e.target.dataset.action;
        const fileId = e.target.dataset.fileId;
        
        if (action === 'delete') {
            this.confirmDelete(fileId);
        } else if (action === 'view') {
            this.viewFile(fileId);
        } else if (action === 'download') {
            this.downloadFile(fileId);
        } else if (action === 'preview') {
            this.showFilePreview(fileId);
        }
    }

    // File operations
    viewFile(fileId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/file-upload/${fileId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showFileModal(data.file);
            } else {
                this.showToast(data.message || 'خطا در مشاهده فایل', 'error');
            }
        })
        .catch(error => {
            console.error('View file error:', error);
            this.showToast('خطا در مشاهده فایل', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    downloadFile(fileId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/file-upload/${fileId}/download`, {
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
            a.download = `file-${fileId}`;
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

    showFilePreview(fileId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/file-upload/${fileId}/preview`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showPreviewModal(data.file);
            } else {
                this.showToast(data.message || 'خطا در پیش‌نمایش فایل', 'error');
            }
        })
        .catch(error => {
            console.error('Preview error:', error);
            this.showToast('خطا در پیش‌نمایش فایل', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // File upload handling
    handleFileUpload(e) {
        const files = e.target.files;
        if (files.length === 0) return;

        Array.from(files).forEach(file => {
            this.uploadFile(file);
        });
    }

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
    }

    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');
    }

    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');

        const files = e.dataTransfer.files;
        if (files.length === 0) return;

        Array.from(files).forEach(file => {
            this.uploadFile(file);
        });
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        const uploadId = Date.now() + Math.random();
        this.uploadProgress[uploadId] = 0;

        this.showUploadProgress(uploadId, file.name);

        fetch('/admin/file-upload/upload', {
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
                this.updateTable(data.files);
                this.updateStatistics(data.stats);
            } else {
                this.showToast(data.message || 'خطا در آپلود', 'error');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            this.showToast('خطا در آپلود', 'error');
        })
        .finally(() => {
            this.hideUploadProgress(uploadId);
        });
    }

    showUploadProgress(uploadId, fileName) {
        const progressContainer = document.getElementById('upload-progress-container');
        if (!progressContainer) return;

        const progressElement = document.createElement('div');
        progressElement.id = `upload-progress-${uploadId}`;
        progressElement.className = 'mb-2 p-3 bg-blue-50 border border-blue-200 rounded-lg';
        progressElement.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-blue-900">${fileName}</span>
                <span class="text-sm text-blue-700">0%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        `;
        progressContainer.appendChild(progressElement);
    }

    hideUploadProgress(uploadId) {
        const progressElement = document.getElementById(`upload-progress-${uploadId}`);
        if (progressElement) {
            progressElement.remove();
        }
        delete this.uploadProgress[uploadId];
    }

    triggerFileUpload() {
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.click();
        }
    }

    // File analytics
    initializeCharts() {
        // Initialize file analytics charts using Chart.js
        const ctx = document.getElementById('fileChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.fileChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['تصاویر', 'اسناد', 'ویدیو', 'صوت', 'سایر'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع انواع فایل‌ها'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    trackFileMetrics() {
        // Track file metrics in real-time
        setInterval(() => {
            this.updateFileMetrics();
        }, 60000); // Update every minute
    }

    updateFileMetrics() {
        fetch('/admin/file-upload/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateFileStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('File metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.fileChart) {
            this.fileChart.data.datasets[0].data = metrics.types;
            this.fileChart.update();
        }
    }

    updateFileStatistics(statistics) {
        // Update file statistics
        document.querySelectorAll('.file-statistic').forEach(element => {
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
        const selectedCount = this.selectedFiles.size;
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
        fetch('/admin/file-upload/statistics', {
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
            statElements[1].textContent = stats.total_size || 0;
            statElements[2].textContent = stats.uploaded_today || 0;
            statElements[3].textContent = stats.pending || 0;
        }
    }

    // Modal functions
    showFileModal(file) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات فایل: ${file.name}</h3>
                <div class="space-y-3">
                    <div><strong>نام فایل:</strong> ${file.name}</div>
                    <div><strong>نوع:</strong> ${file.type}</div>
                    <div><strong>اندازه:</strong> ${file.size}</div>
                    <div><strong>دسته‌بندی:</strong> ${file.category}</div>
                    <div><strong>وضعیت:</strong> ${file.status}</div>
                    <div><strong>تاریخ آپلود:</strong> ${file.uploaded_at}</div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/file-upload/${file.id}/download" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">دانلود</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showPreviewModal(file) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        
        let previewContent = '';
        if (file.type.startsWith('image/')) {
            previewContent = `<img src="${file.url}" alt="${file.name}" class="max-w-full max-h-96 object-contain">`;
        } else if (file.type.startsWith('video/')) {
            previewContent = `<video controls class="max-w-full max-h-96"><source src="${file.url}" type="${file.type}"></video>`;
        } else if (file.type.startsWith('audio/')) {
            previewContent = `<audio controls class="w-full"><source src="${file.url}" type="${file.type}"></audio>`;
        } else {
            previewContent = `<div class="text-center text-gray-500">پیش‌نمایش برای این نوع فایل در دسترس نیست</div>`;
        }
        
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-4xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">پیش‌نمایش: ${file.name}</h3>
                <div class="flex justify-center mb-4">
                    ${previewContent}
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/file-upload/${file.id}/download" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">دانلود</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Confirmation dialogs
    confirmDelete(fileId) {
        if (confirm('آیا از حذف این فایل اطمینان دارید؟')) {
            this.performDelete(fileId);
        }
    }

    performDelete(fileId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/file-upload/${fileId}`, {
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
                this.removeFileRow(fileId);
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

    removeFileRow(fileId) {
        const row = document.querySelector(`tr[data-file-id="${fileId}"]`);
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
    window.fileUploadManager = new FileUploadManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.fileUploadManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.fileUploadManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.fileUploadManager;
    if (manager) manager.toggleAll();
}
