// Enhanced Story Management JavaScript Functionality
class StoryManager {
    constructor() {
        this.selectedStories = new Set();
        this.isLoading = false;
        this.storyQueue = [];
        this.realTimeStories = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupStoryAnalytics();
        this.initializeStorySystem();
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
        document.querySelectorAll('select[name="status"], select[name="category_id"], select[name="is_premium"], select[name="age_group"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual story actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="publish"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleStoryAction(e);
            }
        });

        // Story management
        this.setupStoryManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh stories every 10 seconds
        setInterval(() => this.updateStories(), 10000);
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
            // Ctrl+N to create new story
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewStory();
            }
        });
    }

    setupStoryAnalytics() {
        // Initialize story analytics charts
        this.initializeCharts();
        
        // Track story metrics
        this.trackStoryMetrics();
    }

    setupStoryManagement() {
        // Setup story management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="toggle-status"], [data-action="change-category"], [data-action="bulk-edit"]')) {
                e.preventDefault();
                this.handleStoryStatus(e);
            }
        });
    }

    initializeStorySystem() {
        // Initialize real-time story system
        this.setupRealTimeStories();
    }

    setupRealTimeStories() {
        // Setup real-time story listening
        this.listenForRealTimeStories();
    }

    listenForRealTimeStories() {
        // Listen for real-time stories via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/stories/stream');
            
            eventSource.onmessage = (event) => {
                const story = JSON.parse(event.data);
                this.handleRealTimeStory(story);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time story error:', error);
            };
        }
    }

    handleRealTimeStory(story) {
        this.realTimeStories.push(story);
        this.showRealTimeStory(story);
        this.updateStatistics();
    }

    showRealTimeStory(story) {
        const storyElement = document.createElement('div');
        storyElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            story.type === 'success' ? 'bg-green-500 text-white' :
            story.type === 'error' ? 'bg-red-500 text-white' :
            story.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        storyElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${story.title}</h4>
                    <p class="text-sm mt-1">${story.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(storyElement);
        
        setTimeout(() => {
            if (storyElement.parentElement) {
                storyElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.story-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedStories.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه داستان‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.story-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedStories.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.story-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedStories.add(checkbox.value);
            } else {
                this.selectedStories.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.story-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک داستان را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected story IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'story_ids[]';
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
                this.updateTable(data.stories);
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
            const newTable = doc.querySelector('.bg-white.shadow.rounded-lg');
            const currentTable = document.querySelector('.bg-white.shadow.rounded-lg');
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

    // Individual story actions
    handleStoryAction(e) {
        const action = e.target.dataset.action;
        const storyId = e.target.dataset.storyId;
        
        if (action === 'delete') {
            this.confirmDelete(storyId);
        } else if (action === 'view') {
            this.viewStory(storyId);
        } else if (action === 'edit') {
            this.editStory(storyId);
        } else if (action === 'publish') {
            this.publishStory(storyId);
        } else if (action === 'duplicate') {
            this.duplicateStory(storyId);
        }
    }

    // Story status actions
    handleStoryStatus(e) {
        const action = e.target.dataset.action;
        const storyId = e.target.dataset.storyId;
        
        if (action === 'toggle-status') {
            this.toggleStoryStatus(storyId);
        } else if (action === 'change-category') {
            this.changeStoryCategory(storyId);
        } else if (action === 'bulk-edit') {
            this.bulkEditStories();
        }
    }

    // Story operations
    viewStory(storyId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showStoryModal(data.story);
            } else {
                this.showToast(data.message || 'خطا در مشاهده داستان', 'error');
            }
        })
        .catch(error => {
            console.error('View story error:', error);
            this.showToast('خطا در مشاهده داستان', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editStory(storyId) {
        window.location.href = `/admin/stories/${storyId}/edit`;
    }

    publishStory(storyId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}/publish`, {
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
                this.updateStoryStatus(storyId, 'published');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در انتشار', 'error');
            }
        })
        .catch(error => {
            console.error('Publish story error:', error);
            this.showToast('خطا در انتشار', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateStory(storyId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}/duplicate`, {
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
            console.error('Duplicate story error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    toggleStoryStatus(storyId) {
        this.showStatusModal(storyId);
    }

    changeStoryCategory(storyId) {
        this.showCategoryModal(storyId);
    }

    bulkEditStories() {
        const selectedCheckboxes = document.querySelectorAll('.story-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک داستان را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    // Update story status in UI
    updateStoryStatus(storyId, status) {
        const row = document.querySelector(`tr[data-story-id="${storyId}"]`);
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
        } else if (status === 'pending') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">در انتظار</span>';
            }
        }
    }

    // Story analytics
    initializeCharts() {
        // Initialize story analytics charts using Chart.js
        const ctx = document.getElementById('storyChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.storyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['منتشر شده', 'پیش‌نویس', 'در انتظار', 'تأیید شده', 'رد شده'],
                    datasets: [{
                        label: 'تعداد داستان‌ها',
                        data: [],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(107, 114, 128, 0.8)',
                            'rgba(234, 179, 8, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(107, 114, 128, 1)',
                            'rgba(234, 179, 8, 1)',
                            'rgba(59, 130, 246, 1)',
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
                            text: 'توزیع وضعیت داستان‌ها'
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

    trackStoryMetrics() {
        // Track story metrics in real-time
        setInterval(() => {
            this.updateStoryMetrics();
        }, 60000); // Update every minute
    }

    updateStoryMetrics() {
        fetch('/admin/stories/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateStoryStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Story metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.storyChart) {
            this.storyChart.data.datasets[0].data = metrics.statuses;
            this.storyChart.update();
        }
    }

    updateStoryStatistics(statistics) {
        // Update story statistics
        document.querySelectorAll('.story-statistic').forEach(element => {
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

    updateStories() {
        fetch('/admin/stories/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.stories);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Stories update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedStories.size;
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
        fetch('/admin/stories/statistics', {
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
        if (statElements.length >= 5) {
            statElements[0].textContent = stats.total || 0;
            statElements[1].textContent = stats.published || 0;
            statElements[2].textContent = stats.pending || 0;
            statElements[3].textContent = stats.premium || 0;
            statElements[4].textContent = stats.total_plays || 0;
        }
    }

    // Modal functions
    showStoryModal(story) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات داستان: ${story.title}</h3>
                <div class="space-y-3">
                    <div><strong>زیرعنوان:</strong> ${story.subtitle || 'ندارد'}</div>
                    <div><strong>دسته‌بندی:</strong> ${story.category?.name || 'ندارد'}</div>
                    <div><strong>گروه سنی:</strong> ${story.age_group}</div>
                    <div><strong>مدت زمان:</strong> ${story.duration} دقیقه</div>
                    <div><strong>تعداد قسمت‌ها:</strong> ${story.total_episodes}</div>
                    <div><strong>وضعیت:</strong> ${story.status}</div>
                    <div><strong>امتیاز:</strong> ${story.rating}</div>
                    <div><strong>تعداد پخش:</strong> ${story.play_count}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${story.created_at}</div>
                    <div><strong>تاریخ انتشار:</strong> ${story.published_at || 'هنوز منتشر نشده'}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">توضیحات:</h4>
                    <p class="text-gray-600">${story.description}</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/stories/${story.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showStatusModal(storyId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">تغییر وضعیت داستان</h3>
                <form id="status-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">وضعیت جدید</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="draft">پیش‌نویس</option>
                                <option value="pending">در انتظار</option>
                                <option value="approved">تأیید شده</option>
                                <option value="rejected">رد شده</option>
                                <option value="published">منتشر شده</option>
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
            this.updateStoryStatusSubmit(storyId, new FormData(e.target));
            modal.remove();
        });
    }

    showCategoryModal(storyId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">تغییر دسته‌بندی داستان</h3>
                <form id="category-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">دسته‌بندی جدید</label>
                            <select name="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">انتخاب دسته‌بندی</option>
                                <!-- Categories will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">تغییر دسته‌بندی</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Load categories
        this.loadCategories(modal.querySelector('select[name="category_id"]'));

        // Handle form submission
        modal.querySelector('#category-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateStoryCategorySubmit(storyId, new FormData(e.target));
            modal.remove();
        });
    }

    showBulkEditModal(storyIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی داستان‌ها (${storyIds.length} مورد)</h3>
                <form id="bulk-edit-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="draft">پیش‌نویس</option>
                                <option value="pending">در انتظار</option>
                                <option value="approved">تأیید شده</option>
                                <option value="rejected">رد شده</option>
                                <option value="published">منتشر شده</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر دسته‌بندی</label>
                            <select name="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <!-- Categories will be loaded dynamically -->
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

        // Load categories
        this.loadCategories(modal.querySelector('select[name="category_id"]'));

        // Handle form submission
        modal.querySelector('#bulk-edit-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.bulkEditSubmit(storyIds, new FormData(e.target));
            modal.remove();
        });
    }

    loadCategories(selectElement) {
        fetch('/admin/categories', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    selectElement.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Load categories error:', error);
        });
    }

    updateStoryStatusSubmit(storyId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}/update-status`, {
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
            console.error('Update story status error:', error);
            this.showToast('خطا در تغییر وضعیت', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    updateStoryCategorySubmit(storyId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}/update-category`, {
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
                this.showToast(data.message || 'خطا در تغییر دسته‌بندی', 'error');
            }
        })
        .catch(error => {
            console.error('Update story category error:', error);
            this.showToast('خطا در تغییر دسته‌بندی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditSubmit(storyIds, formData) {
        this.showLoadingSpinner();
        
        // Add story IDs to form data
        storyIds.forEach(id => {
            formData.append('story_ids[]', id);
        });

        fetch('/admin/stories/bulk-edit', {
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

    createNewStory() {
        window.location.href = '/admin/stories/create';
    }

    // Confirmation dialogs
    confirmDelete(storyId) {
        if (confirm('آیا از حذف این داستان اطمینان دارید؟')) {
            this.performDelete(storyId);
        }
    }

    performDelete(storyId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/stories/${storyId}`, {
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
                this.removeStoryRow(storyId);
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

    removeStoryRow(storyId) {
        const row = document.querySelector(`tr[data-story-id="${storyId}"]`);
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
    window.storyManager = new StoryManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.storyManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.story-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک داستان را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'publish': 'انتشار',
        'unpublish': 'لغو انتشار',
        'change_status': 'تغییر وضعیت',
        'change_category': 'تغییر دسته‌بندی',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} داستان انجام دهید؟`);
}
