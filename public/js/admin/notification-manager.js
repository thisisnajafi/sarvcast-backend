// Enhanced Notification Management JavaScript Functionality
class NotificationManager {
    constructor() {
        this.selectedNotifications = new Set();
        this.isLoading = false;
        this.notificationQueue = [];
        this.realTimeNotifications = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
        this.setupNotificationAnalytics();
        this.initializeNotificationSystem();
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
        document.querySelectorAll('select[name="status"], select[name="type"], select[name="priority"]').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Individual notification actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view"], [data-action="send"], [data-action="schedule"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleNotificationAction(e);
            }
        });

        // Notification management
        this.setupNotificationManagement();
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
        
        // Auto-refresh notifications every 10 seconds
        setInterval(() => this.updateNotifications(), 10000);
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
            // Ctrl+N to create new notification
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.createNewNotification();
            }
        });
    }

    setupNotificationAnalytics() {
        // Initialize notification analytics charts
        this.initializeCharts();
        
        // Track notification metrics
        this.trackNotificationMetrics();
    }

    setupNotificationManagement() {
        // Setup notification management functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="mark-read"], [data-action="mark-unread"], [data-action="archive"], [data-action="unarchive"]')) {
                e.preventDefault();
                this.handleNotificationStatus(e);
            }
        });
    }

    initializeNotificationSystem() {
        // Initialize real-time notification system
        this.setupRealTimeNotifications();
    }

    setupRealTimeNotifications() {
        // Setup real-time notification listening
        this.listenForRealTimeNotifications();
    }

    listenForRealTimeNotifications() {
        // Listen for real-time notifications via WebSocket or Server-Sent Events
        if (typeof EventSource !== 'undefined') {
            const eventSource = new EventSource('/admin/notifications/stream');
            
            eventSource.onmessage = (event) => {
                const notification = JSON.parse(event.data);
                this.handleRealTimeNotification(notification);
            };
            
            eventSource.onerror = (error) => {
                console.error('Real-time notification error:', error);
            };
        }
    }

    handleRealTimeNotification(notification) {
        this.realTimeNotifications.push(notification);
        this.showRealTimeNotification(notification);
        this.updateStatistics();
    }

    showRealTimeNotification(notification) {
        const notificationElement = document.createElement('div');
        notificationElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            notification.type === 'success' ? 'bg-green-500 text-white' :
            notification.type === 'error' ? 'bg-red-500 text-white' :
            notification.type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        notificationElement.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${notification.title}</h4>
                    <p class="text-sm mt-1">${notification.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notificationElement);
        
        setTimeout(() => {
            if (notificationElement.parentElement) {
                notificationElement.remove();
            }
        }, 5000);
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedNotifications.add(checkbox.value);
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        this.updateBulkActionUI();
        this.showToast('همه اعلان‌ها انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        this.selectedNotifications.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        
        document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedNotifications.add(checkbox.value);
            } else {
                this.selectedNotifications.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.notification-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک اعلان را انتخاب کنید', 'warning');
            return;
        }
        
        const actionSelect = document.querySelector('select[name="action"]');
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected notification IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'notification_ids[]';
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
                this.updateTable(data.notifications);
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

    // Individual notification actions
    handleNotificationAction(e) {
        const action = e.target.dataset.action;
        const notificationId = e.target.dataset.notificationId;
        
        if (action === 'delete') {
            this.confirmDelete(notificationId);
        } else if (action === 'view') {
            this.viewNotification(notificationId);
        } else if (action === 'send') {
            this.sendNotification(notificationId);
        } else if (action === 'schedule') {
            this.scheduleNotification(notificationId);
        }
    }

    // Notification status actions
    handleNotificationStatus(e) {
        const action = e.target.dataset.action;
        const notificationId = e.target.dataset.notificationId;
        
        if (action === 'mark-read') {
            this.markAsRead(notificationId);
        } else if (action === 'mark-unread') {
            this.markAsUnread(notificationId);
        } else if (action === 'archive') {
            this.archiveNotification(notificationId);
        } else if (action === 'unarchive') {
            this.unarchiveNotification(notificationId);
        }
    }

    // Notification operations
    viewNotification(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotificationModal(data.notification);
            } else {
                this.showToast(data.message || 'خطا در مشاهده اعلان', 'error');
            }
        })
        .catch(error => {
            console.error('View notification error:', error);
            this.showToast('خطا در مشاهده اعلان', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    sendNotification(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/send`, {
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
                this.updateNotificationStatus(notificationId, 'sent');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در ارسال', 'error');
            }
        })
        .catch(error => {
            console.error('Send notification error:', error);
            this.showToast('خطا در ارسال', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    scheduleNotification(notificationId) {
        this.showScheduleModal(notificationId);
    }

    markAsRead(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/mark-read`, {
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
                this.updateNotificationStatus(notificationId, 'read');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در علامت‌گذاری', 'error');
            }
        })
        .catch(error => {
            console.error('Mark as read error:', error);
            this.showToast('خطا در علامت‌گذاری', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    markAsUnread(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/mark-unread`, {
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
                this.updateNotificationStatus(notificationId, 'unread');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در علامت‌گذاری', 'error');
            }
        })
        .catch(error => {
            console.error('Mark as unread error:', error);
            this.showToast('خطا در علامت‌گذاری', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    archiveNotification(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/archive`, {
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
                this.updateNotificationStatus(notificationId, 'archived');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در آرشیو', 'error');
            }
        })
        .catch(error => {
            console.error('Archive notification error:', error);
            this.showToast('خطا در آرشیو', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    unarchiveNotification(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/unarchive`, {
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
                this.updateNotificationStatus(notificationId, 'active');
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در خارج کردن از آرشیو', 'error');
            }
        })
        .catch(error => {
            console.error('Unarchive notification error:', error);
            this.showToast('خطا در خارج کردن از آرشیو', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update notification status in UI
    updateNotificationStatus(notificationId, status) {
        const row = document.querySelector(`tr[data-notification-id="${notificationId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        
        if (status === 'read') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">خوانده شده</span>';
            }
        } else if (status === 'unread') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">خوانده نشده</span>';
            }
        } else if (status === 'sent') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">ارسال شده</span>';
            }
        } else if (status === 'archived') {
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">آرشیو شده</span>';
            }
        }
    }

    // Notification analytics
    initializeCharts() {
        // Initialize notification analytics charts using Chart.js
        const ctx = document.getElementById('notificationChart');
        if (ctx && typeof Chart !== 'undefined') {
            this.notificationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['اطلاعیه', 'هشدار', 'خطا', 'موفقیت', 'سایر'],
                    datasets: [{
                        label: 'تعداد اعلان‌ها',
                        data: [],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزیع انواع اعلان‌ها'
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

    trackNotificationMetrics() {
        // Track notification metrics in real-time
        setInterval(() => {
            this.updateNotificationMetrics();
        }, 60000); // Update every minute
    }

    updateNotificationMetrics() {
        fetch('/admin/notifications/metrics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCharts(data.metrics);
                this.updateNotificationStatistics(data.statistics);
            }
        })
        .catch(error => {
            console.error('Notification metrics update error:', error);
        });
    }

    updateCharts(metrics) {
        if (this.notificationChart) {
            this.notificationChart.data.datasets[0].data = metrics.types;
            this.notificationChart.update();
        }
    }

    updateNotificationStatistics(statistics) {
        // Update notification statistics
        document.querySelectorAll('.notification-statistic').forEach(element => {
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

    updateNotifications() {
        fetch('/admin/notifications/update', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.notifications);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Notifications update error:', error);
        });
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedNotifications.size;
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
        fetch('/admin/notifications/statistics', {
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
            statElements[1].textContent = stats.unread || 0;
            statElements[2].textContent = stats.sent_today || 0;
            statElements[3].textContent = stats.pending || 0;
        }
    }

    // Modal functions
    showNotificationModal(notification) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">جزئیات اعلان: ${notification.title}</h3>
                <div class="space-y-3">
                    <div><strong>نوع:</strong> ${notification.type}</div>
                    <div><strong>اولویت:</strong> ${notification.priority}</div>
                    <div><strong>وضعیت:</strong> ${notification.status}</div>
                    <div><strong>گیرنده:</strong> ${notification.recipient}</div>
                    <div><strong>تاریخ ایجاد:</strong> ${notification.created_at}</div>
                    <div><strong>تاریخ ارسال:</strong> ${notification.sent_at || 'هنوز ارسال نشده'}</div>
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">متن اعلان:</h4>
                    <p class="text-gray-600">${notification.message}</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">بستن</button>
                    <a href="/admin/notifications/${notification.id}/edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ویرایش</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showScheduleModal(notificationId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">زمان‌بندی اعلان</h3>
                <form id="schedule-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تاریخ ارسال</label>
                            <input type="date" name="scheduled_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ساعت ارسال</label>
                            <input type="time" name="scheduled_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">لغو</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">زمان‌بندی</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#schedule-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.scheduleNotificationSubmit(notificationId, new FormData(e.target));
            modal.remove();
        });
    }

    scheduleNotificationSubmit(notificationId, formData) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}/schedule`, {
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
                this.showToast(data.message || 'خطا در زمان‌بندی', 'error');
            }
        })
        .catch(error => {
            console.error('Schedule notification error:', error);
            this.showToast('خطا در زمان‌بندی', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    createNewNotification() {
        window.location.href = '/admin/notifications/create';
    }

    // Confirmation dialogs
    confirmDelete(notificationId) {
        if (confirm('آیا از حذف این اعلان اطمینان دارید؟')) {
            this.performDelete(notificationId);
        }
    }

    performDelete(notificationId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/notifications/${notificationId}`, {
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
                this.removeNotificationRow(notificationId);
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

    removeNotificationRow(notificationId) {
        const row = document.querySelector(`tr[data-notification-id="${notificationId}"]`);
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
    window.notificationManager = new NotificationManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.notificationManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.notificationManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.notificationManager;
    if (manager) manager.toggleAll();
}
