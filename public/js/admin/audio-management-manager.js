// Enhanced Audio Management JavaScript Functionality
class AudioManagementManager {
    constructor() {
        this.selectedAudios = new Set();
        this.isLoading = false;
        this.uploadProgress = {};
        this.audioPlayer = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupAudioAnalytics();
        this.initializeAudioPlayer();
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
        document.querySelectorAll('select[name="status"], select[name="audio_type"], select[name="quality"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual audio actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="play"], [data-action="download"], [data-action="process"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleAudioAction(e);
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
            // Space to play/pause audio
            if (e.key === ' ' && this.audioPlayer) {
                e.preventDefault();
                this.toggleAudioPlayback();
            }
        });
    }

    setupAudioAnalytics() {
        // Initialize audio analytics charts
        this.initializeCharts();
        
        // Track audio metrics
        this.trackAudioMetrics();
    }

    setupFileUpload() {
        const fileInput = document.getElementById('audio-file');
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

    initializeAudioPlayer() {
        // Initialize HTML5 audio player
        this.audioPlayer = document.createElement('audio');
        this.audioPlayer.preload = 'metadata';
        this.audioPlayer.addEventListener('loadedmetadata', () => this.updateAudioInfo());
        this.audioPlayer.addEventListener('timeupdate', () => this.updateProgress());
        this.audioPlayer.addEventListener('ended', () => this.onAudioEnded());
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.audio-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedAudios.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه فایل‌های صوتی انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.audio-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedAudios.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.audio-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedAudios.add(checkbox.value);
            } else {
                this.selectedAudios.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.audio-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک فایل صوتی را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected audio IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'audio_ids[]';
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
                this.updateTable(data.audios);
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

    // Individual audio actions
    handleAudioAction(e) {
        const action = e.target.dataset.action;
        const audioId = e.target.dataset.audioId;
        
        if (action === 'delete') {
            this.confirmDelete(audioId);
        } else if (action === 'play') {
            this.playAudio(audioId);
        } else if (action === 'download') {
            this.downloadAudio(audioId);
        } else if (action === 'process') {
            this.processAudio(audioId);
        }
    }

    // Audio operations
    playAudio(audioId) {
        const audioRow = document.querySelector(`tr[data-audio-id="${audioId}"]`);
        if (!audioRow) return;

        const audioUrl = audioRow.dataset.audioUrl;
        if (!audioUrl) return;

        this.audioPlayer.src = audioUrl;
        this.audioPlayer.play().then(() => {
            this.showToast('پخش شروع شد', 'success');
            this.updateAudioStatus(audioId, 'playing');
        }).catch(error => {
            console.error('Play error:', error);
            this.showToast('خطا در پخش', 'error');
        });
    }

    downloadAudio(audioId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/audio-management/${audioId}/download`, {
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
            a.download = `audio-${audioId}.mp3`;
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

    processAudio(audioId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/audio-management/${audioId}/process`, {
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
                this.updateAudioStatus(audioId, 'processing');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در پردازش', 'error');
            }
        })
        .catch(error => {
            console.error('Process error:', error);
            this.showToast('خطا در پردازش', 'error');
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
            if (file.type.startsWith('audio/')) {
                this.uploadFile(file);
            } else {
                this.showToast('فقط فایل‌های صوتی مجاز هستند', 'warning');
            }
        });
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('audio_file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        const uploadId = Date.now() + Math.random();
        this.uploadProgress[uploadId] = 0;

        this.showUploadProgress(uploadId, file.name);

        fetch('/admin/audio-management/upload', {
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
                this.updateTable(data.audios);
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

    // Audio player controls
    toggleAudioPlayback() {
        if (this.audioPlayer.paused) {
            this.audioPlayer.play();
        } else {
            this.audioPlayer.pause();
        }
    }

    updateAudioInfo() {
        const duration = this.audioPlayer.duration;
        const currentTime = this.audioPlayer.currentTime;
        
        // Update audio info display
        const audioInfo = document.getElementById('audio-info');
        if (audioInfo) {
            audioInfo.innerHTML = `
                <div class="text-sm text-gray-600">
                    مدت زمان: ${this.formatTime(duration)} | 
                    زمان فعلی: ${this.formatTime(currentTime)}
                </div>
            `;
        }
    }

    updateProgress() {
        const progress = (this.audioPlayer.currentTime / this.audioPlayer.duration) * 100;
        const progressBar = document.getElementById('audio-progress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }
    }

    onAudioEnded() {
        this.showToast('پخش تمام شد', 'info');
        this.updateAudioStatus(null, 'stopped');
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Update audio status in UI
    updateAudioStatus(audioId, status) {
        if (audioId) {
            const row = document.querySelector(`tr[data-audio-id="${audioId}"]`);
            if (!row) return;

            const statusCell = row.querySelector('.status-badge');
            
            if (status === 'playing') {
                if (statusCell) {
                    statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">در حال پخش</span>';
                }
            } else if (status === 'processing') {
                if (statusCell) {
                    statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">در حال پردازش</span>';
                }
            }
        }
    }

    // Audio analytics
    initializeCharts() {
        // Initialize audio analytics charts using Chart.js
        const ctx = document.getElementById('audioChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.audioChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['MP3', 'WAV', 'FLAC', 'AAC', 'OGG'],
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
                            text: 'توزیع فرمت‌های صوتی'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    trackAudioMetrics() {
        // Track audio metrics in real-time
        setInterval(() => {
            this.updateAudioMetrics();
        }, 60000); // Update every minute
    }

    updateAudioMetrics() {
        fetch('/admin/audio-management/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateAudioStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Audio metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.audioChart) {
            this.audioChart.data.datasets[0].data = metrics.formats;
            this.audioChart.update();
        }
    }

    updateAudioStatistics(statistics) {
        // Update audio statistics
        document.querySelectorAll('.audio-statistic').forEach(element => {
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
        const selectedCount = this.selectedAudios.size;
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
        fetch('/admin/audio-management/statistics', {
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
            statElements[2].textContent = stats.processed || 0;
            statElements[3].textContent = stats.pending || 0;
        }
    }

    // Confirmation dialogs
    confirmDelete(audioId) {
        if (confirm('آیا از حذف این فایل صوتی اطمینان دارید؟')) {
            this.performDelete(audioId);
        }
    }

    performDelete(audioId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/audio-management/${audioId}`, {
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
                this.removeAudioRow(audioId);
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

    removeAudioRow(audioId) {
        const row = document.querySelector(`tr[data-audio-id="${audioId}"]`);
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
    window.audioManagementManager = new AudioManagementManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.audioManagementManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.audioManagementManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.audioManagementManager;
    if (manager) manager.toggleAll();
}
