// Enhanced Person Management JavaScript Functionality
class PersonManager {
    constructor() {
        this.selectedPeople = new Set();
        this.isLoading = false;
        this.personQueue = [];
        this.realTimePeople = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupPersonAnalytics();
        this.initializePersonSystem();
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
        document.querySelectorAll('select[name="role"], select[name="verified"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual person actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="verify"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handlePersonAction(e);
            }
        });

        // Person management
        this.setupPersonManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh people every 10 seconds
        setInterval(() => this.updatePeople(), 10000);
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
            // Ctrl+N to create new person
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewPerson();
            }
        });
    }

    setupPersonAnalytics() {
        // Initialize person analytics charts
        this.initializeCharts();
        
        // Track person metrics
        this.trackPersonMetrics();
    }

    setupPersonManagement() {
        // Setup person management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="bulk-edit"], [data-action="export"], [data-action="import"]')) {
                e.preventDefault();
                this.handlePersonStatus(e);
            }
        });
    }

    initializePersonSystem() {
        // Initialize real-time person system
        this.setupRealTimePeople();
    }

    setupRealTimePeople() {
        // Setup real-time person listening
        this.listenForRealTimePeople();
    }

    listenForRealTimePeople() {
        // Listen for real-time people via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/people/stream');
            
            eventSource.onmessage = (event) => {
                const person = JSON.parse(event.data);
                this.handleRealTimePerson(person);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time person error:', error);
            };
        }
    }

    handleRealTimePerson(person) {
        this.realTimePeople.push(person);
        this.showRealTimePerson(person);
        this.updateStatistics();
    }

    showRealTimePerson(person) {
        const personElement = document.createElement('div');
        personElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            person.type === 'success' ? 'bg-green-500 text-white' :
            person.type === 'error' ? 'bg-red-500 text-white' :
            person.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        personElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${person.title}</h4>
                    <p class="text-sm mt-1">${person.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(personElement);
        
        setTimeout(() => {
            if (personElement.parentElement) {
                personElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.person-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedPeople.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه افراد انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.person-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedPeople.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.person-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedPeople.add(checkbox.value);
            } else {
                this.selectedPeople.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.person-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک فرد را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected person IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'person_ids[]';
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
                this.updateTable(data.people);
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

    // Individual person actions
    handlePersonAction(e) {
        const action = e.target.dataset.action;
        const personId = e.target.dataset.personId;
        
        if (action === 'delete') {
            this.confirmDelete(personId);
        } else if (action === 'view') {
            this.viewPerson(personId);
        } else if (action === 'edit') {
            this.editPerson(personId);
        } else if (action === 'verify') {
            this.verifyPerson(personId);
        } else if (action === 'duplicate') {
            this.duplicatePerson(personId);
        }
    }

    // Person status actions
    handlePersonStatus(e) {
        const action = e.target.dataset.action;
        const personId = e.target.dataset.personId;
        
        if (action === 'bulk-edit') {
            this.bulkEditPeople();
        } else if (action === 'export') {
            this.exportPeople();
        } else if (action === 'import') {
            this.importPeople();
        }
    }

    // Person operations
    viewPerson(personId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/people/${personId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showPersonModal(data.person);
            } else {
                this.showToast(data.message || 'خطا در مشاهده فرد', 'error');
            }
        })
        .catch(error => {
            console.error('View person error:', error);
            this.showToast('خطا در مشاهده فرد', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editPerson(personId) {
        window.location.href = `/admin/people/${personId}/edit`;
    }

    verifyPerson(personId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/people/${personId}/verify`, {
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
                this.updatePersonStatus(personId, data.newStatus);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تأیید', 'error');
            }
        })
        .catch(error => {
            console.error('Verify person error:', error);
            this.showToast('خطا در تأیید', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicatePerson(personId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/people/${personId}/duplicate`, {
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
            console.error('Duplicate person error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditPeople() {
        const selectedCheckboxes = document.querySelectorAll('.person-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک فرد را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    exportPeople() {
        this.showLoadingSpinner();
        
        fetch('/admin/people/export', {
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
            a.download = `people_export_${new Date().toISOString().split('T')[0]}.csv`;
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

    importPeople() {
        this.showImportModal();
    }

    // Update person status in UI
    updatePersonStatus(personId, status) {
        const row = document.querySelector(`tr[data-person-id="${personId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'verified') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">تأیید شده</span>';
            }
        } else if (status === 'unverified') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">تأیید نشده</span>';
            }
        }
    }

    // Person analytics
    initializeCharts() {
        // Initialize person analytics charts using Chart.js
        const ctx = document.getElementById('personChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.personChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['صداپیشه', 'کارگردان', 'نویسنده', 'تهیه‌کننده', 'نویسنده اصلی', 'گوینده'],
                    datasets: [{
                        label: 'تعداد افراد',
                        data: [],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(236, 72, 153, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(236, 72, 153, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع نقش‌های افراد'
                        }
                    }
                }
            });
        }
    }

    trackPersonMetrics() {
        // Track person metrics in real-time
        setInterval(() => {
            this.updatePersonMetrics();
        }, 60000); // Update every minute
    }

    updatePersonMetrics() {
        fetch('/admin/people/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updatePersonStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Person metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.personChart) {
            this.personChart.data.datasets[0].data = metrics.roles;
            this.personChart.update();
        }
    }

    updatePersonStatistics(statistics) {
        // Update person statistics
        document.querySelectorAll('.person-statistic').forEach(element => {
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

    updatePeople() {
        fetch('/admin/people/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.people);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('People update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedPeople.size;
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
        fetch('/admin/people/statistics', {
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
            statElements[1].textContent = stats.verified || 0;
            statElements[2].textContent = stats.unverified || 0;
            statElements[3].textContent = stats.total_stories || 0;
        }
    }

    // Modal functions
    showPersonModal(person) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات فرد: ${person.name}</h3>
                <div class="space-y-3">
                    <div><strong>نام:</strong> ${person.name}</div>
                    <div><strong>نقش‌ها:</strong> ${person.roles ? person.roles.join(', ') : 'ندارد'}</div>
                    <div><strong>وضعیت تأیید:</strong> ${person.is_verified ? 'تأیید شده' : 'تأیید نشده'}</div>
                    <div><strong>تعداد داستان‌ها:</strong> ${person.total_stories || 0}</div>
                    <div><strong>تعداد اپیزودها:</strong> ${person.total_episodes || 0}</div>
                    <div><strong>میانگین امتیاز:</strong> ${person.average_rating || 0}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${person.created_at}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">بیوگرافی:</h4>
                    <p class="text-gray-600">${person.bio || 'بیوگرافی وجود ندارد'}</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/people/${person.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showBulkEditModal(personIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی افراد (${personIds.length} مورد)</h3>
                <form id="bulk-edit-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت تأیید</label>
                            <select name="is_verified" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="1">تأیید شده</option>
                                <option value="0">تأیید نشده</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">افزودن نقش</label>
                            <select name="add_role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">نقش اضافه نکن</option>
                                <option value="voice_actor">صداپیشه</option>
                                <option value="director">کارگردان</option>
                                <option value="writer">نویسنده</option>
                                <option value="producer">تهیه‌کننده</option>
                                <option value="author">نویسنده اصلی</option>
                                <option value="narrator">گوینده</option>
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

        // Handle form submission
        modal.querySelector('#bulk-edit-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.bulkEditSubmit(personIds, new FormData(e.target));
            modal.remove();
        });
    }

    showImportModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">وارد کردن افراد</h3>
                <form id="import-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">فایل CSV</label>
                            <input type="file" name="file" accept=".csv" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="text-sm text-gray-500">
                            <p>فرمت فایل باید CSV باشد و شامل ستون‌های زیر:</p>
                            <ul class="list-disc list-inside mt-2">
                                <li>name (نام)</li>
                                <li>bio (بیوگرافی)</li>
                                <li>roles (نقش‌ها - جدا شده با کاما)</li>
                                <li>is_verified (تأیید شده - true/false)</li>
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

    bulkEditSubmit(personIds, formData) {
        this.showLoadingSpinner();
        
        // Add person IDs to form data
        personIds.forEach(id => {
            formData.append('person_ids[]', id);
        });

        fetch('/admin/people/bulk-edit', {
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
        
        fetch('/admin/people/import', {
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

    createNewPerson() {
        window.location.href = '/admin/people/create';
    }

    // Confirmation dialogs
    confirmDelete(personId) {
        if (confirm('آیا از حذف این فرد اطمینان دارید؟')) {
            this.performDelete(personId);
        }
    }

    performDelete(personId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/people/${personId}`, {
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
                this.removePersonRow(personId);
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

    removePersonRow(personId) {
        const row = document.querySelector(`tr[data-person-id="${personId}"]`);
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
    window.personManager = new PersonManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.personManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.person-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک فرد را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'verify': 'تأیید کردن',
        'unverify': 'لغو تأیید',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} فرد انجام دهید؟`);
}
