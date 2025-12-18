/**
 * SarvCast Admin JavaScript Functionality
 * Comprehensive admin panel JavaScript functions
 */

// Global Admin Object
window.AdminPanel = {
    // Configuration
    config: {
        apiBaseUrl: '/admin/api',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        locale: 'fa',
        dateFormat: 'YYYY/MM/DD',
        timeFormat: 'HH:mm'
    },

    // Initialize admin panel
    init: function() {
        this.initTooltips();
        this.initModals();
        this.initForms();
        this.initTables();
        this.initCharts();
        this.initFileUploads();
        this.initNotifications();
        this.initFilters();
        this.initBulkActions();
        this.initRealTimeUpdates();
    },

    // Tooltip initialization
    initTooltips: function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    // Modal functionality
    initModals: function() {
        // Auto-close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
            }
        });

        // Confirm dialogs
        window.confirmAction = function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        };
    },

    // Form functionality
    initForms: function() {
        // Auto-save forms
        const autoSaveForms = document.querySelectorAll('[data-auto-save]');
        autoSaveForms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    AdminPanel.autoSaveForm(form);
                });
            });
        });

        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Character counters
        const textareas = document.querySelectorAll('[data-max-length]');
        textareas.forEach(textarea => {
            const maxLength = parseInt(textarea.dataset.maxLength);
            const counter = document.createElement('div');
            counter.className = 'text-sm text-gray-500 mt-1';
            textarea.parentNode.appendChild(counter);

            function updateCounter() {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${remaining} کاراکتر باقی مانده`;
                counter.className = remaining < 0 ? 'text-sm text-red-500 mt-1' : 'text-sm text-gray-500 mt-1';
            }

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    },

    // Table functionality
    initTables: function() {
        // Sortable tables
        const sortableHeaders = document.querySelectorAll('[data-sortable]');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const table = this.closest('table');
                const column = this.dataset.sortable;
                const currentSort = table.dataset.sort || '';
                const currentDirection = table.dataset.direction || 'asc';
                
                let direction = 'asc';
                if (currentSort === column && currentDirection === 'asc') {
                    direction = 'desc';
                }
                
                table.dataset.sort = column;
                table.dataset.direction = direction;
                
                // Update URL and reload
                const url = new URL(window.location);
                url.searchParams.set('sort', column);
                url.searchParams.set('direction', direction);
                window.location.href = url.toString();
            });
        });

        // Select all functionality
        const selectAllCheckboxes = document.querySelectorAll('#select-all');
        selectAllCheckboxes.forEach(selectAll => {
            selectAll.addEventListener('change', function() {
                const table = this.closest('table');
                const checkboxes = table.querySelectorAll('input[name="selected_items[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                AdminPanel.updateBulkActionButtons();
            });
        });

        // Individual checkbox change
        const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                AdminPanel.updateBulkActionButtons();
            });
        });
    },

    // Chart functionality
    initCharts: function() {
        // Initialize Chart.js charts
        const chartElements = document.querySelectorAll('canvas[data-chart]');
        chartElements.forEach(canvas => {
            const chartType = canvas.dataset.chart;
            const chartData = JSON.parse(canvas.dataset.data || '{}');
            const chartOptions = JSON.parse(canvas.dataset.options || '{}');
            
            new Chart(canvas, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                    },
                    ...chartOptions
                }
            });
        });
    },

    // File upload functionality
    initFileUploads: function() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                AdminPanel.handleFileUpload(this);
            });
        });

        // Drag and drop
        const dropZones = document.querySelectorAll('[data-drop-zone]');
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-blue-500', 'bg-blue-50');
            });

            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('border-blue-500', 'bg-blue-50');
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-blue-500', 'bg-blue-50');
                
                const files = e.dataTransfer.files;
                const fileInput = this.querySelector('input[type="file"]');
                if (fileInput && files.length > 0) {
                    fileInput.files = files;
                    AdminPanel.handleFileUpload(fileInput);
                }
            });
        });
    },

    // Notification system
    initNotifications: function() {
        // Auto-hide notifications
        const notifications = document.querySelectorAll('.alert[data-auto-hide]');
        notifications.forEach(notification => {
            const delay = parseInt(notification.dataset.autoHide) || 5000;
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, delay);
        });

        // Real-time notifications
        if (window.Echo) {
            window.Echo.channel('admin-notifications')
                .listen('AdminNotification', (e) => {
                    AdminPanel.showNotification(e.message, e.type);
                });
        }
    },

    // Filter functionality
    initFilters: function() {
        const filterForms = document.querySelectorAll('[data-filter-form]');
        filterForms.forEach(form => {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    AdminPanel.applyFilters(form);
                });
            });
        });

        // Clear filters
        const clearButtons = document.querySelectorAll('[data-clear-filters]');
        clearButtons.forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('form');
                form.reset();
                AdminPanel.applyFilters(form);
            });
        });
    },

    // Bulk actions
    initBulkActions: function() {
        const bulkActionSelects = document.querySelectorAll('#bulk-action');
        bulkActionSelects.forEach(select => {
            select.addEventListener('change', function() {
                AdminPanel.updateBulkActionButtons();
            });
        });

        const executeButtons = document.querySelectorAll('[data-execute-bulk-action]');
        executeButtons.forEach(button => {
            button.addEventListener('click', function() {
                AdminPanel.executeBulkAction();
            });
        });
    },

    // Real-time updates
    initRealTimeUpdates: function() {
        // Update statistics every 30 seconds
        setInterval(() => {
            AdminPanel.updateStatistics();
        }, 30000);

        // Update online users every 10 seconds
        setInterval(() => {
            AdminPanel.updateOnlineUsers();
        }, 10000);
    },

    // Utility functions
    autoSaveForm: function(form) {
        const formData = new FormData(form);
        const url = form.dataset.autoSaveUrl || form.action;
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': this.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                AdminPanel.showNotification('فرم به صورت خودکار ذخیره شد', 'success');
            }
        })
        .catch(error => {
            console.error('Auto-save error:', error);
        });
    },

    handleFileUpload: function(input) {
        const files = input.files;
        const preview = input.closest('.file-upload').querySelector('.file-preview');
        
        if (preview) {
            preview.innerHTML = '';
            
            Array.from(files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center p-2 bg-gray-50 rounded mb-2';
                
                const fileIcon = document.createElement('div');
                fileIcon.className = 'w-8 h-8 bg-blue-100 rounded flex items-center justify-center ml-3';
                fileIcon.innerHTML = '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                
                const fileInfo = document.createElement('div');
                fileInfo.innerHTML = `
                    <p class="text-sm font-medium text-gray-900">${file.name}</p>
                    <p class="text-xs text-gray-500">${this.formatFileSize(file.size)}</p>
                `;
                
                fileItem.appendChild(fileIcon);
                fileItem.appendChild(fileInfo);
                preview.appendChild(fileItem);
            });
        }
    },

    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-${type}-50 border border-${type}-200 rounded-lg p-4 shadow-lg`;
        
        const iconMap = {
            success: '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        
        notification.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    ${iconMap[type] || iconMap.info}
                </div>
                <div class="mr-3">
                    <p class="text-sm font-medium text-${type}-800">${message}</p>
                </div>
                <div class="mr-auto pl-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex bg-${type}-50 rounded-md p-1.5 text-${type}-500 hover:bg-${type}-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    },

    applyFilters: function(form) {
        const formData = new FormData(form);
        const url = new URL(window.location);
        
        // Clear existing filters
        url.search = '';
        
        // Add new filters
        for (let [key, value] of formData.entries()) {
            if (value) {
                url.searchParams.set(key, value);
            }
        }
        
        window.location.href = url.toString();
    },

    updateBulkActionButtons: function() {
        const selectedItems = document.querySelectorAll('input[name="selected_items[]"]:checked');
        const bulkActionSelect = document.getElementById('bulk-action');
        const executeButton = document.querySelector('[data-execute-bulk-action]');
        
        if (executeButton) {
            executeButton.disabled = selectedItems.length === 0 || !bulkActionSelect.value;
        }
    },

    executeBulkAction: function() {
        const selectedItems = document.querySelectorAll('input[name="selected_items[]"]:checked');
        const action = document.getElementById('bulk-action').value;
        
        if (selectedItems.length === 0) {
            this.showNotification('لطفاً حداقل یک مورد را انتخاب کنید.', 'warning');
            return;
        }
        
        if (!action) {
            this.showNotification('لطفاً یک عملیات را انتخاب کنید.', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', action);
        selectedItems.forEach(item => {
            formData.append('selected_items[]', item.value);
        });
        formData.append('_token', this.config.csrfToken);
        
        fetch(window.location.href + '/bulk-action', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                this.showNotification(data.message || 'خطا در اجرای عملیات', 'error');
            }
        })
        .catch(error => {
            console.error('Bulk action error:', error);
            this.showNotification('خطا در اجرای عملیات', 'error');
        });
    },

    updateStatistics: function() {
        const statsElements = document.querySelectorAll('[data-statistic]');
        statsElements.forEach(element => {
            const url = element.dataset.statistic;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    element.textContent = data.value;
                })
                .catch(error => {
                    console.error('Statistics update error:', error);
                });
        });
    },

    updateOnlineUsers: function() {
        const onlineUsersElement = document.getElementById('online-users');
        if (onlineUsersElement) {
            fetch('/admin/api/online-users')
                .then(response => response.json())
                .then(data => {
                    onlineUsersElement.textContent = data.count;
                })
                .catch(error => {
                    console.error('Online users update error:', error);
                });
        }
    },

    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    formatDate: function(date) {
        return new Date(date).toLocaleDateString('fa-IR');
    },

    formatTime: function(date) {
        return new Date(date).toLocaleTimeString('fa-IR');
    },

    formatCurrency: function(amount) {
        return new Intl.NumberFormat('fa-IR').format(amount) + ' تومان';
    },

    // Analytics functionality
    initAnalytics: function() {
        this.initUserAnalytics();
        this.initContentAnalytics();
        this.initRevenueAnalytics();
        this.initSystemAnalytics();
    },

    // User Analytics
    initUserAnalytics: function() {
        const userAnalyticsContainer = document.getElementById('user-analytics-container');
        if (!userAnalyticsContainer) return;

        // Load user analytics data
        this.loadUserAnalyticsData();

        // Date range picker
        const dateRangeSelect = document.getElementById('user-analytics-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => {
                this.loadUserAnalyticsData();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('user-analytics-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadUserAnalyticsData();
            });
        }
    },

    loadUserAnalyticsData: function() {
        const dateRange = document.getElementById('user-analytics-date-range')?.value || '30';
        
        // Show loading
        this.showLoading('user-analytics-container');

        fetch(`/admin/api/user-analytics/overview?date_range=${dateRange}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateUserAnalyticsCharts(data.data);
                    this.updateUserAnalyticsStats(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading user analytics:', error);
                this.showNotification('خطا در بارگذاری داده‌های تحلیل کاربران', 'error');
            })
            .finally(() => {
                this.hideLoading('user-analytics-container');
            });
    },

    updateUserAnalyticsCharts: function(data) {
        // Update registration trends chart
        if (data.registration_trends && window.Chart) {
            const ctx = document.getElementById('registration-trends-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.registration_trends.map(item => item.date),
                        datasets: [{
                            label: 'ثبت‌نام‌های جدید',
                            data: data.registration_trends.map(item => item.count),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
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

        // Update status distribution chart
        if (data.status_distribution && window.Chart) {
            const ctx = document.getElementById('status-distribution-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.status_distribution.map(item => item.status),
                        datasets: [{
                            data: data.status_distribution.map(item => item.count),
                            backgroundColor: [
                                '#10B981',
                                '#F59E0B',
                                '#EF4444',
                                '#6B7280'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }
        }
    },

    updateUserAnalyticsStats: function(data) {
        const stats = data.user_stats;
        
        // Update stat cards
        this.updateStatCard('total-users', stats.total_users);
        this.updateStatCard('active-users', stats.active_users);
        this.updateStatCard('new-users', stats.new_users);
        this.updateStatCard('verified-users', stats.verified_users);
    },

    // Content Analytics
    initContentAnalytics: function() {
        const contentAnalyticsContainer = document.getElementById('content-analytics-container');
        if (!contentAnalyticsContainer) return;

        this.loadContentAnalyticsData();

        const dateRangeSelect = document.getElementById('content-analytics-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => {
                this.loadContentAnalyticsData();
            });
        }
    },

    loadContentAnalyticsData: function() {
        const dateRange = document.getElementById('content-analytics-date-range')?.value || '30';
        
        this.showLoading('content-analytics-container');

        fetch(`/admin/api/content-analytics/overview?date_range=${dateRange}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateContentAnalyticsCharts(data.data);
                    this.updateContentAnalyticsStats(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading content analytics:', error);
                this.showNotification('خطا در بارگذاری داده‌های تحلیل محتوا', 'error');
            })
            .finally(() => {
                this.hideLoading('content-analytics-container');
            });
    },

    updateContentAnalyticsCharts: function(data) {
        // Update content performance chart
        if (data.content_performance && window.Chart) {
            const ctx = document.getElementById('content-performance-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.content_performance.map(item => item.title),
                        datasets: [{
                            label: 'تعداد بازدید',
                            data: data.content_performance.map(item => item.views),
                            backgroundColor: '#8B5CF6'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
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
    },

    updateContentAnalyticsStats: function(data) {
        const stats = data.content_stats;
        
        this.updateStatCard('total-stories', stats.total_stories);
        this.updateStatCard('total-episodes', stats.total_episodes);
        this.updateStatCard('total-views', stats.total_views);
        this.updateStatCard('total-downloads', stats.total_downloads);
    },

    // Revenue Analytics
    initRevenueAnalytics: function() {
        const revenueAnalyticsContainer = document.getElementById('revenue-analytics-container');
        if (!revenueAnalyticsContainer) return;

        this.loadRevenueAnalyticsData();

        const dateRangeSelect = document.getElementById('revenue-analytics-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => {
                this.loadRevenueAnalyticsData();
            });
        }
    },

    loadRevenueAnalyticsData: function() {
        const dateRange = document.getElementById('revenue-analytics-date-range')?.value || '30';
        
        this.showLoading('revenue-analytics-container');

        fetch(`/admin/api/revenue-analytics/overview?date_range=${dateRange}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateRevenueAnalyticsCharts(data.data);
                    this.updateRevenueAnalyticsStats(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading revenue analytics:', error);
                this.showNotification('خطا در بارگذاری داده‌های تحلیل درآمد', 'error');
            })
            .finally(() => {
                this.hideLoading('revenue-analytics-container');
            });
    },

    updateRevenueAnalyticsCharts: function(data) {
        // Update revenue trends chart
        if (data.revenue_trends && window.Chart) {
            const ctx = document.getElementById('revenue-trends-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.revenue_trends.map(item => item.date),
                        datasets: [{
                            label: 'درآمد روزانه',
                            data: data.revenue_trends.map(item => item.revenue),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
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

        // Update revenue sources chart
        if (data.revenue_sources && window.Chart) {
            const ctx = document.getElementById('revenue-sources-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.revenue_sources.map(item => item.source),
                        datasets: [{
                            data: data.revenue_sources.map(item => item.amount),
                            backgroundColor: [
                                '#3B82F6',
                                '#8B5CF6',
                                '#10B981',
                                '#F59E0B'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }
        }
    },

    updateRevenueAnalyticsStats: function(data) {
        const stats = data.revenue_stats;
        
        this.updateStatCard('total-revenue', this.formatCurrency(stats.total_revenue));
        this.updateStatCard('subscription-revenue', this.formatCurrency(stats.subscription_revenue));
        this.updateStatCard('coin-revenue', this.formatCurrency(stats.coin_revenue));
        this.updateStatCard('active-subscriptions', stats.active_subscriptions);
    },

    // System Analytics
    initSystemAnalytics: function() {
        const systemAnalyticsContainer = document.getElementById('system-analytics-container');
        if (!systemAnalyticsContainer) return;

        this.loadSystemAnalyticsData();

        const dateRangeSelect = document.getElementById('system-analytics-date-range');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => {
                this.loadSystemAnalyticsData();
            });
        }

        // Auto-refresh system analytics every 10 seconds
        setInterval(() => {
            this.loadSystemAnalyticsData();
        }, 10000);
    },

    loadSystemAnalyticsData: function() {
        const dateRange = document.getElementById('system-analytics-date-range')?.value || '30';
        
        fetch(`/admin/api/system-analytics/overview?date_range=${dateRange}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateSystemAnalyticsCharts(data.data);
                    this.updateSystemAnalyticsStats(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading system analytics:', error);
            });
    },

    updateSystemAnalyticsCharts: function(data) {
        // Update performance trends chart
        if (data.performance_trends && window.Chart) {
            const ctx = document.getElementById('performance-trends-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.performance_trends.map(item => item.date),
                        datasets: [{
                            label: 'زمان پاسخ (ms)',
                            data: data.performance_trends.map(item => item.response_time),
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'استفاده از CPU (%)',
                            data: data.performance_trends.map(item => item.cpu_usage),
                            borderColor: '#F59E0B',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
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

        // Update resource usage chart
        if (data.resource_usage && window.Chart) {
            const ctx = document.getElementById('resource-usage-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.resource_usage.map(item => item.resource),
                        datasets: [{
                            label: 'درصد استفاده',
                            data: data.resource_usage.map(item => item.usage),
                            backgroundColor: data.resource_usage.map(item => 
                                item.status === 'warning' ? '#F59E0B' : '#10B981'
                            )
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        }
    },

    updateSystemAnalyticsStats: function(data) {
        const stats = data.system_stats;
        
        this.updateStatCard('server-uptime', stats.server_uptime + '%');
        this.updateStatCard('response-time', stats.response_time + 'ms');
        this.updateStatCard('cpu-usage', stats.cpu_usage + '%');
        this.updateStatCard('memory-usage', stats.memory_usage + '%');
        this.updateStatCard('active-users', stats.active_users);
        this.updateStatCard('api-requests', this.formatNumber(stats.api_requests));
    },

    // Utility functions for analytics
    updateStatCard: function(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    },

    formatCurrency: function(amount) {
        return new Intl.NumberFormat('fa-IR', {
            style: 'currency',
            currency: 'IRR'
        }).format(amount);
    },

    formatNumber: function(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    },

    showLoading: function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.classList.add('loading');
        }
    },

    hideLoading: function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.classList.remove('loading');
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    AdminPanel.init();
});

// Export for global access
window.AdminPanel = AdminPanel;
