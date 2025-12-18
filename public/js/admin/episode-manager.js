// Enhanced Episode Management JavaScript Functionality
class EpisodeManager {
    constructor() {
        this.selectedEpisodes = new Set();
        this.isLoading = false;
        this.episodeQueue = [];
        this.realTimeEpisodes = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupEpisodeAnalytics();
        this.initializeEpisodeSystem();
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
        document.querySelectorAll('select[name="status"], select[name="story_id"], select[name="is_premium"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual episode actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="publish"], [data-action="duplicate"], [data-action="delete"], [data-action="play"]')) {
                e.preventDefault();
                this.handleEpisodeAction(e);
            }
        });

        // Episode management
        this.setupEpisodeManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh episodes every 10 seconds
        setInterval(() => this.updateEpisodes(), 10000);
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
            // Ctrl+N to create new episode
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewEpisode();
            }
        });
    }

    setupEpisodeAnalytics() {
        // Initialize episode analytics charts
        this.initializeCharts();
        
        // Track episode metrics
        this.trackEpisodeMetrics();
    }

    setupEpisodeManagement() {
        // Setup episode management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="toggle-status"], [data-action="change-story"], [data-action="bulk-edit"], [data-action="audio-process"]')) {
                e.preventDefault();
                this.handleEpisodeStatus(e);
            }
        });
    }

    initializeEpisodeSystem() {
        // Initialize real-time episode system
        this.setupRealTimeEpisodes();
    }

    setupRealTimeEpisodes() {
        // Setup real-time episode listening
        this.listenForRealTimeEpisodes();
    }

    listenForRealTimeEpisodes() {
        // Listen for real-time episodes via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/episodes/stream');
            
            eventSource.onmessage = (event) => {
                const episode = JSON.parse(event.data);
                this.handleRealTimeEpisode(episode);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time episode error:', error);
            };
        }
    }

    handleRealTimeEpisode(episode) {
        this.realTimeEpisodes.push(episode);
        this.showRealTimeEpisode(episode);
        this.updateStatistics();
    }

    showRealTimeEpisode(episode) {
        const episodeElement = document.createElement('div');
        episodeElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            episode.type === 'success' ? 'bg-green-500 text-white' :
            episode.type === 'error' ? 'bg-red-500 text-white' :
            episode.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        episodeElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${episode.title}</h4>
                    <p class="text-sm mt-1">${episode.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(episodeElement);
        
        setTimeout(() => {
            if (episodeElement.parentElement) {
                episodeElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.episode-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedEpisodes.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه اپیزودها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.episode-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedEpisodes.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.episode-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedEpisodes.add(checkbox.value);
            } else {
                this.selectedEpisodes.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.episode-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک اپیزود را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected episode IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'episode_ids[]';
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
                this.updateTable(data.episodes);
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

    // Individual episode actions
    handleEpisodeAction(e) {
        const action = e.target.dataset.action;
        const episodeId = e.target.dataset.episodeId;
        
        if (action === 'delete') {
            this.confirmDelete(episodeId);
        } else if (action === 'view') {
            this.viewEpisode(episodeId);
        } else if (action === 'edit') {
            this.editEpisode(episodeId);
        } else if (action === 'publish') {
            this.publishEpisode(episodeId);
        } else if (action === 'duplicate') {
            this.duplicateEpisode(episodeId);
        } else if (action === 'play') {
            this.playEpisode(episodeId);
        }
    }

    // Episode status actions
    handleEpisodeStatus(e) {
        const action = e.target.dataset.action;
        const episodeId = e.target.dataset.episodeId;
        
        if (action === 'toggle-status') {
            this.toggleEpisodeStatus(episodeId);
        } else if (action === 'change-story') {
            this.changeEpisodeStory(episodeId);
        } else if (action === 'bulk-edit') {
            this.bulkEditEpisodes();
        } else if (action === 'audio-process') {
            this.processAudio(episodeId);
        }
    }

    // Episode operations
    viewEpisode(episodeId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showEpisodeModal(data.episode);
            } else {
                this.showToast(data.message || 'خطا در مشاهده اپیزود', 'error');
            }
        })
        .catch(error => {
            console.error('View episode error:', error);
            this.showToast('خطا در مشاهده اپیزود', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editEpisode(episodeId) {
        window.location.href = `/admin/episodes/${episodeId}/edit`;
    }

    publishEpisode(episodeId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}/publish`, {
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
                this.updateEpisodeStatus(episodeId, 'published');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در انتشار', 'error');
            }
        })
        .catch(error => {
            console.error('Publish episode error:', error);
            this.showToast('خطا در انتشار', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateEpisode(episodeId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}/duplicate`, {
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
            console.error('Duplicate episode error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    playEpisode(episodeId) {
        this.showAudioPlayer(episodeId);
    }

    processAudio(episodeId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}/process-audio`, {
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
                this.showToast(data.message || 'خطا در پردازش صدا', 'error');
            }
        })
        .catch(error => {
            console.error('Process audio error:', error);
            this.showToast('خطا در پردازش صدا', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    toggleEpisodeStatus(episodeId) {
        this.showStatusModal(episodeId);
    }

    changeEpisodeStory(episodeId) {
        this.showStoryModal(episodeId);
    }

    bulkEditEpisodes() {
        const selectedCheckboxes = document.querySelectorAll('.episode-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک اپیزود را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    // Update episode status in UI
    updateEpisodeStatus(episodeId, status) {
        const row = document.querySelector(`tr[data-episode-id="${episodeId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'published') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">منتشر شده</span>';
            }
        } else if (status === 'draft') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">پیش‌نویس</span>';
            }
        } else if (status === 'archived') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">آرشیو شده</span>';
            }
        }
    }

    // Episode analytics
    initializeCharts() {
        // Initialize episode analytics charts using Chart.js
        const ctx = document.getElementById('episodeChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.episodeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['منتشر شده', 'پیش‌نویس', 'آرشیو شده'],
                    datasets: [{
                        label: 'تعداد اپیزودها',
                        data: [],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(107, 114, 128, 0.8)',
                            'rgba(234, 179, 8, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(107, 114, 128, 1)',
                            'rgba(234, 179, 8, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع وضعیت اپیزودها'
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

    trackEpisodeMetrics() {
        // Track episode metrics in real-time
        setInterval(() => {
            this.updateEpisodeMetrics();
        }, 60000); // Update every minute
    }

    updateEpisodeMetrics() {
        fetch('/admin/episodes/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateEpisodeStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Episode metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.episodeChart) {
            this.episodeChart.data.datasets[0].data = metrics.statuses;
            this.episodeChart.update();
        }
    }

    updateEpisodeStatistics(statistics) {
        // Update episode statistics
        document.querySelectorAll('.episode-statistic').forEach(element => {
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

    updateEpisodes() {
        fetch('/admin/episodes/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.episodes);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Episodes update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedEpisodes.size;
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
        fetch('/admin/episodes/statistics', {
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
            statElements[2].textContent = stats.draft || 0;
            statElements[3].textContent = stats.total_duration || 0;
        }
    }

    // Modal functions
    showEpisodeModal(episode) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات اپیزود: ${episode.title}</h3>
                <div class="space-y-3">
                    <div><strong>داستان:</strong> ${episode.story?.title || 'ندارد'}</div>
                    <div><strong>شماره اپیزود:</strong> ${episode.episode_number}</div>
                    <div><strong>مدت زمان:</strong> ${episode.duration} دقیقه</div>
                    <div><strong>وضعیت:</strong> ${episode.status}</div>
                    <div><strong>نوع:</strong> ${episode.is_premium ? 'پریمیوم' : 'رایگان'}</div>
                    <div><strong>امتیاز:</strong> ${episode.rating}</div>
                    <div><strong>تعداد پخش:</strong> ${episode.play_count}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${episode.created_at}</div>
                    <div><strong>تاریخ انتشار:</strong> ${episode.release_date || 'هنوز منتشر نشده'}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">توضیحات:</h4>
                    <p class="text-gray-600">${episode.description || 'توضیحاتی وجود ندارد'}</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/episodes/${episode.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showAudioPlayer(episodeId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">پخش اپیزود</h3>
                <div class="space-y-4">
                    <audio controls class="w-full">
                        <source src="/admin/episodes/${episodeId}/audio" type="audio/mpeg">
                        مرورگر شما از پخش صدا پشتیبانی نمی‌کند.
                    </audio>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showStatusModal(episodeId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">تغییر وضعیت اپیزود</h3>
                <form id="status-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">وضعیت جدید</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="draft">پیش‌نویس</option>
                                <option value="published">منتشر شده</option>
                                <option value="archived">آرشیو شده</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">تغییر وضعیت</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#status-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateEpisodeStatusSubmit(episodeId, new FormData(e.target));
            modal.remove();
        });
    }

    showStoryModal(episodeId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">تغییر داستان اپیزود</h3>
                <form id="story-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">داستان جدید</label>
                            <select name="story_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">انتخاب داستان</option>
                                <!-- Stories will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">تغییر داستان</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Load stories
        this.loadStories(modal.querySelector('select[name="story_id"]'));

        // Handle form submission
        modal.querySelector('#story-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateEpisodeStorySubmit(episodeId, new FormData(e.target));
            modal.remove();
        });
    }

    showBulkEditModal(episodeIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی اپیزودها (${episodeIds.length} مورد)</h3>
                <form id="bulk-edit-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="draft">پیش‌نویس</option>
                                <option value="published">منتشر شده</option>
                                <option value="archived">آرشیو شده</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر داستان</label>
                            <select name="story_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <!-- Stories will be loaded dynamically -->
                            </select>
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

        // Load stories
        this.loadStories(modal.querySelector('select[name="story_id"]'));

        // Handle form submission
        modal.querySelector('#bulk-edit-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.bulkEditSubmit(episodeIds, new FormData(e.target));
            modal.remove();
        });
    }

    loadStories(selectElement) {
        fetch('/admin/stories', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.stories.forEach(story => {
                    const option = document.createElement('option');
                    option.value = story.id;
                    option.textContent = story.title;
                    selectElement.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Load stories error:', error);
        });
    }

    updateEpisodeStatusSubmit(episodeId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}/update-status`, {
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
                this.showToast(data.message || 'خطا در تغییر وضعیت', 'error');
            }
        })
        .catch(error => {
            console.error('Update episode status error:', error);
            this.showToast('خطا در تغییر وضعیت', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    updateEpisodeStorySubmit(episodeId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}/update-story`, {
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
                this.showToast(data.message || 'خطا در تغییر داستان', 'error');
            }
        })
        .catch(error => {
            console.error('Update episode story error:', error);
            this.showToast('خطا در تغییر داستان', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditSubmit(episodeIds, formData) {
        this.showLoadingSpinner();
        
        // Add episode IDs to form data
        episodeIds.forEach(id => {
            formData.append('episode_ids[]', id);
        });

        fetch('/admin/episodes/bulk-edit', {
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

    createNewEpisode() {
        window.location.href = '/admin/episodes/create';
    }

    // Confirmation dialogs
    confirmDelete(episodeId) {
        if (confirm('آیا از حذف این اپیزود اطمینان دارید؟')) {
            this.performDelete(episodeId);
        }
    }

    performDelete(episodeId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/episodes/${episodeId}`, {
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
                this.removeEpisodeRow(episodeId);
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

    removeEpisodeRow(episodeId) {
        const row = document.querySelector(`tr[data-episode-id="${episodeId}"]`);
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
    window.episodeManager = new EpisodeManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.episodeManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.episode-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک اپیزود را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'publish': 'انتشار',
        'unpublish': 'لغو انتشار',
        'change_status': 'تغییر وضعیت',
        'change_story': 'تغییر داستان',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} اپیزود انجام دهید؟`);
}
