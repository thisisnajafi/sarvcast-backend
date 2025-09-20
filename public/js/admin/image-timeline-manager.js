// Enhanced Image Timeline Management JavaScript Functionality
class ImageTimelineManager {
    constructor() {
        this.selectedImageTimelines = new Set();
        this.isLoading = false;
        this.imageTimelineQueue = [];
        this.realTimeImageTimelines = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupImageTimelineAnalytics();
        this.initializeImageTimelineSystem();
    }

    setupEventListeners() {
        // Bulk form submission
        const bulkForm = document.getElementById('bulkActionForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => this.handleBulkAction(e));
        }
        
        // Search with debounce
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => this.handleSearch(e), 300));
        }

        // Filter changes
        document.querySelectorAll('select[name="story_id"], select[name="episode_id"], select[name="status"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Story change handler
        const storySelect = document.querySelector('select[name="story_id"]');
        if (storySelect) {
            storySelect.addEventListener('change', (e) => this.handleStoryChange(e));
        }

        // Individual image timeline actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="toggle-status"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleImageTimelineAction(e);
            }
        });

        // Image timeline management
        this.setupImageTimelineManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh image timelines every 10 seconds
        setInterval(() => this.updateImageTimelines(), 10000);
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
            // Ctrl+N to create new image timeline
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewImageTimeline();
            }
        });
    }

    setupImageTimelineAnalytics() {
        // Initialize image timeline analytics charts
        this.initializeCharts();
        
        // Track image timeline metrics
        this.trackImageTimelineMetrics();
    }

    setupImageTimelineManagement() {
        // Setup image timeline management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="bulk-edit"], [data-action="export"], [data-action="import"]')) {
                e.preventDefault();
                this.handleImageTimelineStatus(e);
            }
        });
    }

    initializeImageTimelineSystem() {
        // Initialize real-time image timeline system
        this.setupRealTimeImageTimelines();
    }

    setupRealTimeImageTimelines() {
        // Setup real-time image timeline listening
        this.listenForRealTimeImageTimelines();
    }

    listenForRealTimeImageTimelines() {
        // Listen for real-time image timelines via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/image-timelines/stream');
            
            eventSource.onmessage = (event) => {
                const imageTimeline = JSON.parse(event.data);
                this.handleRealTimeImageTimeline(imageTimeline);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time image timeline error:', error);
            };
        }
    }

    handleRealTimeImageTimeline(imageTimeline) {
        this.realTimeImageTimelines.push(imageTimeline);
        this.showRealTimeImageTimeline(imageTimeline);
        this.updateStatistics();
    }

    showRealTimeImageTimeline(imageTimeline) {
        const imageTimelineElement = document.createElement('div');
        imageTimelineElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            imageTimeline.type === 'success' ? 'bg-green-500 text-white' :
            imageTimeline.type === 'error' ? 'bg-red-500 text-white' :
            imageTimeline.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        imageTimelineElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${imageTimeline.title}</h4>
                    <p class="text-sm mt-1">${imageTimeline.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(imageTimelineElement);
        
        setTimeout(() => {
            if (imageTimelineElement.parentElement) {
                imageTimelineElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.image-timeline-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedImageTimelines.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه تایم‌لاین‌های تصویر انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.image-timeline-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedImageTimelines.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.image-timeline-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedImageTimelines.add(checkbox.value);
            } else {
                this.selectedImageTimelines.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.image-timeline-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک تایم‌لاین تصویر را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected image timeline IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'image_timeline_ids[]';
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
                this.updateTable(data.imageTimelines);
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

    // Story change handler
    handleStoryChange(e) {
        const storyId = e.target.value;
        const episodeSelect = document.querySelector('select[name="episode_id"]');
        
        if (storyId) {
            this.loadEpisodesForStory(storyId, episodeSelect);
        } else {
            // Reset episode select
            episodeSelect.innerHTML = '<option value="">همه اپیزودها</option>';
        }
    }

    loadEpisodesForStory(storyId, episodeSelect) {
        fetch(`/admin/image-timelines/episodes-for-story?story_id=${storyId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                episodeSelect.innerHTML = '<option value="">همه اپیزودها</option>';
                data.episodes.forEach(episode => {
                    const option = document.createElement('option');
                    option.value = episode.id;
                    option.textContent = `${episode.title} (${episode.duration} ثانیه)`;
                    episodeSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Load episodes error:', error);
        });
    }

    // Individual image timeline actions
    handleImageTimelineAction(e) {
        const action = e.target.dataset.action;
        const imageTimelineId = e.target.dataset.imageTimelineId;
        
        if (action === 'delete') {
            this.confirmDelete(imageTimelineId);
        } else if (action === 'view') {
            this.viewImageTimeline(imageTimelineId);
        } else if (action === 'edit') {
            this.editImageTimeline(imageTimelineId);
        } else if (action === 'toggle-status') {
            this.toggleImageTimelineStatus(imageTimelineId);
        } else if (action === 'duplicate') {
            this.duplicateImageTimeline(imageTimelineId);
        }
    }

    // Image timeline status actions
    handleImageTimelineStatus(e) {
        const action = e.target.dataset.action;
        const imageTimelineId = e.target.dataset.imageTimelineId;
        
        if (action === 'bulk-edit') {
            this.bulkEditImageTimelines();
        } else if (action === 'export') {
            this.exportImageTimelines();
        } else if (action === 'import') {
            this.importImageTimelines();
        }
    }

    // Image timeline operations
    viewImageTimeline(imageTimelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/image-timelines/${imageTimelineId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showImageTimelineModal(data.imageTimeline);
            } else {
                this.showToast(data.message || 'خطا در مشاهده تایم‌لاین تصویر', 'error');
            }
        })
        .catch(error => {
            console.error('View image timeline error:', error);
            this.showToast('خطا در مشاهده تایم‌لاین تصویر', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editImageTimeline(imageTimelineId) {
        window.location.href = `/admin/image-timelines/${imageTimelineId}/edit`;
    }

    toggleImageTimelineStatus(imageTimelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/image-timelines/${imageTimelineId}/toggle-status`, {
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
                this.updateImageTimelineStatus(imageTimelineId, data.newStatus);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تغییر وضعیت', 'error');
            }
        })
        .catch(error => {
            console.error('Toggle image timeline status error:', error);
            this.showToast('خطا در تغییر وضعیت', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateImageTimeline(imageTimelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/image-timelines/${imageTimelineId}/duplicate`, {
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
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در کپی', 'error');
            }
        })
        .catch(error => {
            console.error('Duplicate image timeline error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditImageTimelines() {
        const selectedCheckboxes = document.querySelectorAll('.image-timeline-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک تایم‌لاین تصویر را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    exportImageTimelines() {
        this.showLoadingSpinner();
        
        fetch('/admin/image-timelines/export', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `image_timelines_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showToast('فایل با موفقیت دانلود شد', 'success');
        })
        .catch(error => {
            console.error('Export error:', error);
            this.showToast('خطا در دانلود فایل', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    importImageTimelines() {
        this.showImportModal();
    }

    // Update image timeline status in UI
    updateImageTimelineStatus(imageTimelineId, status) {
        const row = document.querySelector(`tr[data-image-timeline-id="${imageTimelineId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'active') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
            }
        } else if (status === 'inactive') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">غیرفعال</span>';
            }
        }
    }

    // Image timeline analytics
    initializeCharts() {
        // Initialize image timeline analytics charts using Chart.js
        const ctx = document.getElementById('imageTimelineChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.imageTimelineChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['فعال', 'غیرفعال'],
                    datasets: [{
                        label: 'تعداد تایم‌لاین‌های تصویر',
                        data: [],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع وضعیت تایم‌لاین‌های تصویر'
                        }
                    }
                }
            });
        }
    }

    trackImageTimelineMetrics() {
        // Track image timeline metrics in real-time
        setInterval(() => {
            this.updateImageTimelineMetrics();
        }, 60000); // Update every minute
    }

    updateImageTimelineMetrics() {
        fetch('/admin/image-timelines/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateImageTimelineStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Image timeline metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.imageTimelineChart) {
            this.imageTimelineChart.data.datasets[0].data = metrics.statuses;
            this.imageTimelineChart.update();
        }
    }

    updateImageTimelineStatistics(statistics) {
        // Update image timeline statistics
        document.querySelectorAll('.image-timeline-statistic').forEach(element => {
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

    updateImageTimelines() {
        fetch('/admin/image-timelines/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.imageTimelines);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Image timelines update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedImageTimelines.size;
        const bulkActionBtn = document.querySelector('#bulkActionForm button[type="submit"]');
        
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
        fetch('/admin/image-timelines/statistics', {
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
            statElements[2].textContent = stats.inactive || 0;
            statElements[3].textContent = stats.total_episodes || 0;
        }
    }

    // Modal functions
    showImageTimelineModal(imageTimeline) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات تایم‌لاین تصویر</h3>
                <div class="space-y-3">
                    <div><strong>اپیزود:</strong> ${imageTimeline.episode.title}</div>
                    <div><strong>داستان:</strong> ${imageTimeline.episode.story.title}</div>
                    <div><strong>زمان شروع:</strong> ${imageTimeline.start_time_formatted}</div>
                    <div><strong>زمان پایان:</strong> ${imageTimeline.end_time_formatted}</div>
                    <div><strong>مدت:</strong> ${imageTimeline.duration} ثانیه</div>
                    <div><strong>ترتیب:</strong> ${imageTimeline.image_order}</div>
                    <div><strong>وضعیت:</strong> ${imageTimeline.status === 'active' ? 'فعال' : 'غیرفعال'}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${imageTimeline.created_at}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">تصویر:</h4>
                    <img src="${imageTimeline.image_url}" alt="تصویر تایم‌لاین" class="w-full h-48 object-cover rounded-lg">
                </div>
                ${imageTimeline.description ? `
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">توضیحات:</h4>
                    <p class="text-gray-600">${imageTimeline.description}</p>
                </div>
                ` : ''}
                ${imageTimeline.tags ? `
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">تگ‌ها:</h4>
                    <p class="text-gray-600">${imageTimeline.tags}</p>
                </div>
                ` : ''}
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/image-timelines/${imageTimeline.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showBulkEditModal(imageTimelineIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی تایم‌لاین‌های تصویر (${imageTimelineIds.length} مورد)</h3>
                <form id="bulk-edit-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="active">فعال</option>
                                <option value="inactive">غیرفعال</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">افزودن به ترتیب</label>
                            <input type="number" name="add_to_order" step="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">اعمال تغییرات</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#bulk-edit-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.bulkEditSubmit(imageTimelineIds, new FormData(e.target));
            modal.remove();
        });
    }

    showImportModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">وارد کردن تایم‌لاین‌های تصویر</h3>
                <form id="import-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">فایل CSV</label>
                            <input type="file" name="file" accept=".csv" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="text-sm text-gray-500">
                            <p>فرمت فایل باید CSV باشد و شامل ستون‌های زیر:</p>
                            <ul class="list-disc list-inside mt-2">
                                <li>episode_id (شناسه اپیزود)</li>
                                <li>start_time (زمان شروع - ثانیه)</li>
                                <li>end_time (زمان پایان - ثانیه)</li>
                                <li>image_url (آدرس تصویر)</li>
                                <li>image_order (ترتیب تصویر)</li>
                                <li>status (وضعیت - active/inactive)</li>
                                <li>description (توضیحات)</li>
                                <li>tags (تگ‌ها)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">وارد کردن</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#import-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.importSubmit(new FormData(e.target));
            modal.remove();
        });
    }

    bulkEditSubmit(imageTimelineIds, formData) {
        this.showLoadingSpinner();
        
        // Add image timeline IDs to form data
        imageTimelineIds.forEach(id => {
            formData.append('image_timeline_ids[]', id);
        });

        fetch('/admin/image-timelines/bulk-edit', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در ویرایش گروهی', 'error');
            }
        })
        .catch(error => {
            console.error('Bulk edit error:', error);
            this.showToast('خطا در ویرایش گروهی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    importSubmit(formData) {
        this.showLoadingSpinner();
        
        fetch('/admin/image-timelines/import', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در وارد کردن فایل', 'error');
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            this.showToast('خطا در وارد کردن فایل', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    createNewImageTimeline() {
        window.location.href = '/admin/image-timelines/create';
    }

    // Confirmation dialogs
    confirmDelete(imageTimelineId) {
        if (confirm('آیا از حذف این تایم‌لاین تصویر اطمینان دارید؟')) {
            this.performDelete(imageTimelineId);
        }
    }

    performDelete(imageTimelineId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/image-timelines/${imageTimelineId}`, {
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
                this.removeImageTimelineRow(imageTimelineId);
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

    removeImageTimelineRow(imageTimelineId) {
        const row = document.querySelector(`tr[data-image-timeline-id="${imageTimelineId}"]`);
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
    window.imageTimelineManager = new ImageTimelineManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.imageTimelineManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.image-timeline-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک تایم‌لاین تصویر را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'activate': 'فعال کردن',
        'deactivate': 'غیرفعال کردن',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} تایم‌لاین تصویر انجام دهید؟`);
}
