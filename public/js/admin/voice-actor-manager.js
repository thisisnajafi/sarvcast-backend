// Enhanced Voice Actor Management JavaScript Functionality
class VoiceActorManager {
    constructor() {
        this.selectedVoiceActors = new Set();
        this.isLoading = false;
        this.voiceActorQueue = [];
        this.realTimeVoiceActors = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupVoiceActorAnalytics();
        this.initializeVoiceActorSystem();
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
        document.querySelectorAll('select[name="verified"], select[name="active"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual voice actor actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="edit"], [data-action="verify"], [data-action="duplicate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleVoiceActorAction(e);
            }
        });

        // Voice actor management
        this.setupVoiceActorManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh voice actors every 10 seconds
        setInterval(() => this.updateVoiceActors(), 10000);
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
            // Ctrl+N to create new voice actor
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewVoiceActor();
            }
        });
    }

    setupVoiceActorAnalytics() {
        // Initialize voice actor analytics charts
        this.initializeCharts();
        
        // Track voice actor metrics
        this.trackVoiceActorMetrics();
    }

    setupVoiceActorManagement() {
        // Setup voice actor management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="bulk-edit"], [data-action="export"], [data-action="import"]')) {
                e.preventDefault();
                this.handleVoiceActorStatus(e);
            }
        });
    }

    initializeVoiceActorSystem() {
        // Initialize real-time voice actor system
        this.setupRealTimeVoiceActors();
    }

    setupRealTimeVoiceActors() {
        // Setup real-time voice actor listening
        this.listenForRealTimeVoiceActors();
    }

    listenForRealTimeVoiceActors() {
        // Listen for real-time voice actors via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/voice-actors/stream');
            
            eventSource.onmessage = (event) => {
                const voiceActor = JSON.parse(event.data);
                this.handleRealTimeVoiceActor(voiceActor);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time voice actor error:', error);
            };
        }
    }

    handleRealTimeVoiceActor(voiceActor) {
        this.realTimeVoiceActors.push(voiceActor);
        this.showRealTimeVoiceActor(voiceActor);
        this.updateStatistics();
    }

    showRealTimeVoiceActor(voiceActor) {
        const voiceActorElement = document.createElement('div');
        voiceActorElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            voiceActor.type === 'success' ? 'bg-green-500 text-white' :
            voiceActor.type === 'error' ? 'bg-red-500 text-white' :
            voiceActor.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        voiceActorElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${voiceActor.title}</h4>
                    <p class="text-sm mt-1">${voiceActor.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(voiceActorElement);
        
        setTimeout(() => {
            if (voiceActorElement.parentElement) {
                voiceActorElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.voice-actor-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedVoiceActors.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه صداپیشگان انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.voice-actor-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedVoiceActors.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        
        document.querySelectorAll('.voice-actor-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedVoiceActors.add(checkbox.value);
            } else {
                this.selectedVoiceActors.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.voice-actor-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک صداپیشه را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected voice actor IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'voice_actor_ids[]';
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
                this.updateTable(data.voiceActors);
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

    // Individual voice actor actions
    handleVoiceActorAction(e) {
        const action = e.target.dataset.action;
        const voiceActorId = e.target.dataset.voiceActorId;
        
        if (action === 'delete') {
            this.confirmDelete(voiceActorId);
        } else if (action === 'view') {
            this.viewVoiceActor(voiceActorId);
        } else if (action === 'edit') {
            this.editVoiceActor(voiceActorId);
        } else if (action === 'verify') {
            this.toggleVerification(voiceActorId);
        } else if (action === 'duplicate') {
            this.duplicateVoiceActor(voiceActorId);
        }
    }

    // Voice actor status actions
    handleVoiceActorStatus(e) {
        const action = e.target.dataset.action;
        const voiceActorId = e.target.dataset.voiceActorId;
        
        if (action === 'bulk-edit') {
            this.bulkEditVoiceActors();
        } else if (action === 'export') {
            this.exportVoiceActors();
        } else if (action === 'import') {
            this.importVoiceActors();
        }
    }

    // Voice actor operations
    viewVoiceActor(voiceActorId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/voice-actors/${voiceActorId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showVoiceActorModal(data.voiceActor);
            } else {
                this.showToast(data.message || 'خطا در مشاهده صداپیشه', 'error');
            }
        })
        .catch(error => {
            console.error('View voice actor error:', error);
            this.showToast('خطا در مشاهده صداپیشه', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    editVoiceActor(voiceActorId) {
        window.location.href = `/admin/voice-actors/${voiceActorId}/edit`;
    }

    toggleVerification(voiceActorId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/voice-actors/${voiceActorId}/toggle-verification`, {
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
                this.updateVoiceActorStatus(voiceActorId, data.newStatus);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در تغییر وضعیت تأیید', 'error');
            }
        })
        .catch(error => {
            console.error('Toggle verification error:', error);
            this.showToast('خطا در تغییر وضعیت تأیید', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    duplicateVoiceActor(voiceActorId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/voice-actors/${voiceActorId}/duplicate`, {
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
            console.error('Duplicate voice actor error:', error);
            this.showToast('خطا در کپی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    bulkEditVoiceActors() {
        const selectedCheckboxes = document.querySelectorAll('.voice-actor-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            this.showToast('لطفاً حداقل یک صداپیشه را انتخاب کنید', 'warning');
            return;
        }
        
        this.showBulkEditModal(Array.from(selectedCheckboxes).map(cb => cb.value));
    }

    exportVoiceActors() {
        this.showLoadingSpinner();
        
        fetch('/admin/voice-actors/export', {
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
            a.download = `voice_actors_export_${new Date().toISOString().split('T')[0]}.csv`;
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

    importVoiceActors() {
        this.showImportModal();
    }

    // Update voice actor status in UI
    updateVoiceActorStatus(voiceActorId, status) {
        const row = document.querySelector(`tr[data-voice-actor-id="${voiceActorId}"]`);
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

    // Voice actor analytics
    initializeCharts() {
        // Initialize voice actor analytics charts using Chart.js
        const ctx = document.getElementById('voiceActorChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.voiceActorChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['تأیید شده', 'تأیید نشده'],
                    datasets: [{
                        label: 'تعداد صداپیشگان',
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
                            text: 'توزیع وضعیت تأیید صداپیشگان'
                        }
                    }
                }
            });
        }
    }

    trackVoiceActorMetrics() {
        // Track voice actor metrics in real-time
        setInterval(() => {
            this.updateVoiceActorMetrics();
        }, 60000); // Update every minute
    }

    updateVoiceActorMetrics() {
        fetch('/admin/voice-actors/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateVoiceActorStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Voice actor metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.voiceActorChart) {
            this.voiceActorChart.data.datasets[0].data = metrics.verification_statuses;
            this.voiceActorChart.update();
        }
    }

    updateVoiceActorStatistics(statistics) {
        // Update voice actor statistics
        document.querySelectorAll('.voice-actor-statistic').forEach(element => {
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

    updateVoiceActors() {
        fetch('/admin/voice-actors/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.voiceActors);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Voice actors update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedVoiceActors.size;
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
        fetch('/admin/voice-actors/statistics', {
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
            statElements[3].textContent = stats.total_episodes || 0;
        }
    }

    // Modal functions
    showVoiceActorModal(voiceActor) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات صداپیشه: ${voiceActor.name}</h3>
                <div class="space-y-3">
                    <div><strong>نام:</strong> ${voiceActor.name}</div>
                    <div><strong>نوع صدا:</strong> ${voiceActor.voice_type || 'ندارد'}</div>
                    <div><strong>محدوده صدا:</strong> ${voiceActor.voice_range || 'ندارد'}</div>
                    <div><strong>تخصص‌ها:</strong> ${voiceActor.specialties ? voiceActor.specialties.join(', ') : 'ندارد'}</div>
                    <div><strong>زبان‌ها:</strong> ${voiceActor.languages ? voiceActor.languages.join(', ') : 'ندارد'}</div>
                    <div><strong>سال‌های تجربه:</strong> ${voiceActor.experience_years || 0}</div>
                    <div><strong>نرخ ساعتی:</strong> ${voiceActor.hourly_rate || 'ندارد'}</div>
                    <div><strong>وضعیت تأیید:</strong> ${voiceActor.is_verified ? 'تأیید شده' : 'تأیید نشده'}</div>
                    <div><strong>وضعیت فعال:</strong> ${voiceActor.is_active ? 'فعال' : 'غیرفعال'}</div>
                    <div><strong>تعداد اپیزودها:</strong> ${voiceActor.total_episodes || 0}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${voiceActor.created_at}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">بیوگرافی:</h4>
                    <p class="text-gray-600">${voiceActor.bio || 'بیوگرافی وجود ندارد'}</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/voice-actors/${voiceActor.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showBulkEditModal(voiceActorIds) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">ویرایش گروهی صداپیشگان (${voiceActorIds.length} مورد)</h3>
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
                            <label class="block text-sm font-medium text-gray-700">تغییر وضعیت فعال</label>
                            <select name="is_active" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">تغییر نده</option>
                                <option value="1">فعال</option>
                                <option value="0">غیرفعال</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">نرخ ساعتی جدید</label>
                            <input type="number" name="hourly_rate" step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
            this.bulkEditSubmit(voiceActorIds, new FormData(e.target));
            modal.remove();
        });
    }

    showImportModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">وارد کردن صداپیشگان</h3>
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
                                <li>voice_type (نوع صدا)</li>
                                <li>voice_range (محدوده صدا)</li>
                                <li>specialties (تخصص‌ها - جدا شده با کاما)</li>
                                <li>languages (زبان‌ها - جدا شده با کاما)</li>
                                <li>experience_years (سال‌های تجربه)</li>
                                <li>hourly_rate (نرخ ساعتی)</li>
                                <li>is_verified (تأیید شده - true/false)</li>
                                <li>is_active (فعال - true/false)</li>
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

    bulkEditSubmit(voiceActorIds, formData) {
        this.showLoadingSpinner();
        
        // Add voice actor IDs to form data
        voiceActorIds.forEach(id => {
            formData.append('voice_actor_ids[]', id);
        });

        fetch('/admin/voice-actors/bulk-edit', {
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
        
        fetch('/admin/voice-actors/import', {
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

    createNewVoiceActor() {
        window.location.href = '/admin/voice-actors/create';
    }

    // Confirmation dialogs
    confirmDelete(voiceActorId) {
        if (confirm('آیا از حذف این صداپیشه اطمینان دارید؟')) {
            this.performDelete(voiceActorId);
        }
    }

    performDelete(voiceActorId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/voice-actors/${voiceActorId}`, {
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
                this.removeVoiceActorRow(voiceActorId);
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

    removeVoiceActorRow(voiceActorId) {
        const row = document.querySelector(`tr[data-voice-actor-id="${voiceActorId}"]`);
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
    window.voiceActorManager = new VoiceActorManager();
});

// Legacy functions for backward compatibility
function selectAll(checkbox) {
    const manager = window.voiceActorManager;
    if (manager) manager.toggleAll();
}

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const checkedBoxes = document.querySelectorAll('.voice-actor-checkbox:checked');
    
    if (!action) {
        alert('لطفاً عملیات را انتخاب کنید');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('لطفاً حداقل یک صداپیشه را انتخاب کنید');
        return false;
    }
    
    const actionText = {
        'verify': 'تأیید کردن',
        'unverify': 'لغو تأیید',
        'activate': 'فعال کردن',
        'deactivate': 'غیرفعال کردن',
        'delete': 'حذف'
    };
    
    return confirm(`آیا مطمئن هستید که می‌خواهید عملیات "${actionText[action]}" را روی ${checkedBoxes.length} صداپیشه انجام دهید؟`);
}
