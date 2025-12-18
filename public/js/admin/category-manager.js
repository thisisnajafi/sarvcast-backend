// Enhanced Category Management JavaScript Functionality
class CategoryManager {
    constructor() {
        this.selectedCategories = new Set();
        this.isLoading = false;
        this.categoryQueue = [];
        this.realTimeCategories = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupCategoryAnalytics();
        this.initializeCategorySystem();
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
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual category actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="toggle-status"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleCategoryAction(e);
            }
        });

        // Category management
        this.setupCategoryManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh categories every 10 seconds
        setInterval(() => this.updateCategories(), 10000);
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
            // Ctrl+N to create new category
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewCategory();
            }
        });
    }

    setupCategoryAnalytics() {
        // Initialize category analytics charts
        this.initializeCharts();
        
        // Track category metrics
        this.trackCategoryMetrics();
    }

    setupCategoryManagement() {
        // Setup category management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="bulk-edit"], [data-action="reorder"], [data-action="export"]')) {
                e.preventDefault();
                this.handleCategoryStatus(e);
            }
        });
    }

    initializeCategorySystem() {
        // Initialize real-time category system
        this.setupRealTimeCategories();
    }

    setupRealTimeCategories() {
        // Setup real-time category listening
        this.listenForRealTimeCategories();
    }

    listenForRealTimeCategories() {
        // Listen for real-time categories via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/categories/stream');
            
            eventSource.onmessage = (event) => {
                const category = JSON.parse(event.data);
                this.handleRealTimeCategory(category);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time category error:', error);
            };
        }
    }

    handleRealTimeCategory(category) {
        this.realTimeCategories.push(category);
        this.showRealTimeCategory(category);
        this.updateStatistics();
    }

    showRealTimeCategory(category) {
        const categoryElement = document.createElement('div');
        categoryElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            category.type === 'success' ? 'bg-green-500 text-white' :
            category.type === 'error' ? 'bg-red-500 text-white' :
            category.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        categoryElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${category.title}</h4>
                    <p class="text-sm mt-1">${category.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(categoryElement);
        
        setTimeout(() => {
            if (categoryElement.parentElement) {
                categoryElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedCategories.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه دسته‌بندی‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedCategories.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedCategories.add(checkbox.value);
            } else {
                this.selectedCategories.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.category-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک دسته‌بندی را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected category IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'category_ids[]';
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
                this.updateTable(data.categories);
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

    // Individual category actions
    handleCategoryAction(e) {
        const action = e.target.dataset.action;
        const categoryId = e.target.dataset.categoryId;
        
        if (action === 'delete') {
            this.confirmDelete(categoryId);
        } else if (action === 'view') {
            this.viewCategory(categoryId);
        } else if (action === 'edit') {
            this.editCategory(categoryId);
        } else if (action === 'toggle-status') {
            this.toggleCategoryStatus(categoryId);
        } else if (action === 'duplicate') {
            this.duplicateCategory(categoryId);
        }
    }

    // Category status actions
    handleCategoryStatus(e) {
        const action = e.target.dataset.action;
        const categoryId = e.target.dataset.categoryId;
        
        if (action === 'bulk-edit') {
            this.bulkEditCategories();
        } else if (action === 'reorder') {
            this.reorderCategories();
        } else if (action === 'export') {
            this.exportCategories();
        }
    }

    // Category operations
    viewCategory(categoryId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/categories/${categoryId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showCategoryModal(data.category);
            } else {
                this.showToast(data.message || 'خطا در مشاهده دسته‌بندی', 'error');
            }
        })
        .catch(error => {
            console.error('View category error:', error);
            this.showToast('خطا در مشاهده دسته‌بندی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editCategory(categoryId) {
        window.location.href = `/admin/categories/${categoryId}/edit`;
    }

    toggleCategoryStatus(categoryId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/categories/${categoryId}/toggle-status`, {
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
                this.updateCategoryStatus(categoryId, data.newStatus);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تغییر وضعیت', 'error');
            }
        })
        .catch(error => {
            console.error('Toggle category status error:', error);
            this.showToast('خطا در تغییر وضعیت', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateCategory(categoryId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/categories/${categoryId}/duplicate`, {
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
            console.error('Duplicate category error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditCategories() {
        const selectedCheckboxes = document.querySelectorAll('.category-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک دسته‌بندی را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    reorderCategories() {
        this.showReorderModal();
    }

    exportCategories() {
        this.showLoadingSpinner();
        
        fetch('/admin/categories/export', {
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
            a.download = `categories_export_${new Date().toISOString().split('T')[0]}.csv`;
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

    // Update category status in UI
    updateCategoryStatus(categoryId, status) {
        const row = document.querySelector(`tr[data-category-id="${categoryId}"]`);
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

    // Category analytics
    initializeCharts() {
        // Initialize category analytics charts using Chart.js
        const ctx = document.getElementById('categoryChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['فعال', 'غیرفعال'],
                    datasets: [{
                        label: 'تعداد دسته‌بندی‌ها',
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
                            text: 'توزیع وضعیت دسته‌بندی‌ها'
                        }
                    }
                }
            });
        }
    }

    trackCategoryMetrics() {
        // Track category metrics in real-time
        setInterval(() => {
            this.updateCategoryMetrics();
        }, 60000); // Update every minute
    }

    updateCategoryMetrics() {
        fetch('/admin/categories/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateCategoryStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Category metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.categoryChart) {
            this.categoryChart.data.datasets[0].data = metrics.statuses;
            this.categoryChart.update();
        }
    }

    updateCategoryStatistics(statistics) {
        // Update category statistics
        document.querySelectorAll('.category-statistic').forEach(element => {
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

    updateCategories() {
        fetch('/admin/categories/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.categories);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Categories update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedCategories.size;
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
        fetch('/admin/categories/statistics', {
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
            statElements[3].textContent = stats.total_stories || 0;
        }
    }

    // Modal functions
    showCategoryModal(category) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات دسته‌بندی: ${category.name}</h3>
                <div class="space-y-3">
                    <div><strong>نام:</strong> ${category.name}</div>
                    <div><strong>اسلاگ:</strong> ${category.slug || 'ندارد'}</div>
                    <div><strong>توضیحات:</strong> ${category.description || 'ندارد'}</div>
                    <div><strong>رنگ:</strong> ${category.color || 'ندارد'}</div>
                    <div><strong>وضعیت:</strong> ${category.is_active ? 'فعال' : 'غیرفعال'}</div>
                    <div><strong>ترتیب:</strong> ${category.sort_order || 0}</div>
                    <div><strong>تعداد داستان‌ها:</strong> ${category.story_count || 0}</div>
                    <div><strong>تعداد اپیزودها:</strong> ${category.total_episodes || 0}</div>
                    <div><strong>میانگین امتیاز:</strong> ${category.average_rating || 0}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${category.created_at}</div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/categories/${category.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showBulkEditModal(categoryIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی دسته‌بندی‌ها (${categoryIds.length} مورد)</h3>
                <form id="bulk-edit-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت</label>
                            <select name="is_active" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="1">فعال</option>
                                <option value="0">غیرفعال</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">رنگ جدید</label>
                            <input type="color" name="color" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
            this.bulkEditSubmit(categoryIds, new FormData(e.target));
            modal.remove();
        });
    }

    showReorderModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ترتیب‌بندی دسته‌بندی‌ها</h3>
                <div id="reorder-list" class="space-y-2">
                    <!-- Categories will be loaded dynamically -->
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                    <button type="button" onclick="this.saveReorder()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ذخیره ترتیب</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Load categories for reordering
        this.loadCategoriesForReorder(modal.querySelector('#reorder-list'));
    }

    loadCategoriesForReorder(container) {
        fetch('/admin/categories', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.categories.forEach(category => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center p-2 bg-gray-50 rounded cursor-move';
                    item.innerHTML = `
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                        <span class="flex-1">${category.name}</span>
                        <input type="hidden" name="category_ids[]" value="${category.id}">
                        <input type="number" name="sort_orders[]" value="${category.sort_order || 0}" class="w-16 px-2 py-1 border border-gray-300 rounded text-sm">
                    `;
                    container.appendChild(item);
                });
            }
        })
        .catch(error => {
            console.error('Load categories for reorder error:', error);
        });
    }

    saveReorder() {
        const formData = new FormData();
        const categoryIds = document.querySelectorAll('input[name="category_ids[]"]');
        const sortOrders = document.querySelectorAll('input[name="sort_orders[]"]');
        
        categoryIds.forEach((idInput, index) => {
            formData.append('category_ids[]', idInput.value);
            formData.append('sort_orders[]', sortOrders[index].value);
        });

        this.showLoadingSpinner();
        
        fetch('/admin/categories/reorder', {
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
                this.showToast(data.message || 'خطا در ذخیره ترتیب', 'error');
            }
        })
        .catch(error => {
            console.error('Save reorder error:', error);
            this.showToast('خطا در ذخیره ترتیب', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditSubmit(categoryIds, formData) {
        this.showLoadingSpinner();
        
        // Add category IDs to form data
        categoryIds.forEach(id => {
            formData.append('category_ids[]', id);
        });

        fetch('/admin/categories/bulk-edit', {
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

    createNewCategory() {
        window.location.href = '/admin/categories/create';
    }

    // Confirmation dialogs
    confirmDelete(categoryId) {
        if (confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')) {
            this.performDelete(categoryId);
        }
    }

    performDelete(categoryId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/categories/${categoryId}`, {
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
                this.removeCategoryRow(categoryId);
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

    removeCategoryRow(categoryId) {
        const row = document.querySelector(`tr[data-category-id="${categoryId}"]`);
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
    window.categoryManager = new CategoryManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.categoryManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک دسته‌بندی را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'activate': 'فعال کردن',
        'deactivate': 'غیرفعال کردن',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} دسته‌بندی انجام دهید؟`);
}
