class AdminCoinManagement {
    constructor() {
        this.currentTab = 'transactions';
        this.currentPage = 1;
        this.filters = {};
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadTransactions();
        this.loadUsers();
        this.loadRedemptionOptions();
        this.loadAllUsers();
        this.bindEvents();
    }

    bindEvents() {
        // Form submissions
        document.getElementById('award-coins-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.awardCoins();
        });

        document.getElementById('create-redemption-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createRedemptionOption();
        });

        // Filter changes
        document.getElementById('transaction-type-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('source-type-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('user-search-filter').addEventListener('input', () => {
            this.debounce(() => this.applyFilters(), 500);
        });
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/v1/coins/global-statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    async loadTransactions() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });

            const response = await fetch(`/api/v1/coins/admin-transactions?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load transactions');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateTransactionsTable(data.data);
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
        }
    }

    async loadUsers() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });

            const response = await fetch(`/api/v1/coins/admin-users?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load users');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateUsersTable(data.data);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    async loadRedemptionOptions() {
        try {
            const response = await fetch('/api/v1/coins/admin-redemption-options', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load redemption options');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateRedemptionTable(data.data);
            }
        } catch (error) {
            console.error('Error loading redemption options:', error);
        }
    }

    async loadAllUsers() {
        try {
            const response = await fetch('/api/v1/users/all', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load users');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateUsersSelect(data.data);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    async awardCoins() {
        try {
            const formData = new FormData(document.getElementById('award-coins-form'));
            const data = Object.fromEntries(formData.entries());

            const response = await fetch('/api/v1/coins/award', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('سکه با موفقیت اعطا شد');
                this.closeAwardCoinsModal();
                this.loadStatistics();
                this.loadTransactions();
                this.loadUsers();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error awarding coins:', error);
            this.showError('خطا در اعطای سکه');
        }
    }

    async createRedemptionOption() {
        try {
            const formData = new FormData(document.getElementById('create-redemption-form'));
            const data = Object.fromEntries(formData.entries());

            const response = await fetch('/api/v1/coins/admin-redemption-options', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('گزینه تبدیل با موفقیت ایجاد شد');
                this.closeCreateRedemptionModal();
                this.loadRedemptionOptions();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error creating redemption option:', error);
            this.showError('خطا در ایجاد گزینه تبدیل');
        }
    }

    async toggleRedemptionOption(optionId, currentStatus) {
        try {
            const newStatus = currentStatus ? false : true;
            
            const response = await fetch(`/api/v1/coins/admin-redemption-options/${optionId}/toggle`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_active: newStatus })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('وضعیت گزینه تبدیل با موفقیت تغییر کرد');
                this.loadRedemptionOptions();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error toggling redemption option:', error);
            this.showError('خطا در تغییر وضعیت گزینه تبدیل');
        }
    }

    async deleteRedemptionOption(optionId) {
        if (!confirm('آیا مطمئن هستید که می‌خواهید این گزینه تبدیل را حذف کنید؟')) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/coins/admin-redemption-options/${optionId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('گزینه تبدیل با موفقیت حذف شد');
                this.loadRedemptionOptions();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error deleting redemption option:', error);
            this.showError('خطا در حذف گزینه تبدیل');
        }
    }

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        
        document.getElementById(`${tabName}-tab`).classList.add('active', 'border-blue-500', 'text-blue-600');
        document.getElementById(`${tabName}-tab`).classList.remove('border-transparent', 'text-gray-500');

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        document.getElementById(`${tabName}-content`).classList.remove('hidden');

        this.currentTab = tabName;
    }

    applyFilters() {
        this.filters = {
            transaction_type: document.getElementById('transaction-type-filter').value,
            source_type: document.getElementById('source-type-filter').value,
            user_search: document.getElementById('user-search-filter').value
        };

        this.currentPage = 1;
        
        if (this.currentTab === 'transactions') {
            this.loadTransactions();
        } else if (this.currentTab === 'users') {
            this.loadUsers();
        }
    }

    updateStatistics(stats) {
        document.getElementById('total-users-with-coins').textContent = this.formatNumber(stats.total_users_with_coins);
        document.getElementById('total-coins-circulation').textContent = this.formatNumber(stats.total_coins_in_circulation);
        document.getElementById('total-coins-earned').textContent = this.formatNumber(stats.total_coins_earned);
        document.getElementById('total-coins-spent').textContent = this.formatNumber(stats.total_coins_spent);
    }

    updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactions-table-body');
        tbody.innerHTML = '';

        if (transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        هیچ تراکنشی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        transactions.forEach(transaction => {
            const row = this.createTransactionRow(transaction);
            tbody.appendChild(row);
        });
    }

    createTransactionRow(transaction) {
        const tr = document.createElement('tr');
        
        const typeColor = transaction.transaction_type === 'earned' ? 'green' : 'red';
        const typeText = transaction.transaction_type === 'earned' ? 'کسب شده' : 'خرج شده';
        const amountColor = transaction.transaction_type === 'earned' ? 'text-green-600' : 'text-red-600';
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${transaction.user_name}</div>
                <div class="text-sm text-gray-500">${transaction.user_email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${typeColor}-100 text-${typeColor}-800">
                    ${typeText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium ${amountColor}">${this.formatNumber(transaction.amount)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    ${this.getSourceTypeText(transaction.source_type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${transaction.description || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${this.formatDate(transaction.created_at)}
            </td>
        `;
        
        return tr;
    }

    updateUsersTable(users) {
        const tbody = document.getElementById('users-table-body');
        tbody.innerHTML = '';

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        هیچ کاربری یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        users.forEach(user => {
            const row = this.createUserRow(user);
            tbody.appendChild(row);
        });
    }

    createUserRow(user) {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${user.name}</div>
                <div class="text-sm text-gray-500">${user.email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-blue-600">${this.formatNumber(user.available_coins)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatNumber(user.earned_coins)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatNumber(user.spent_coins)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="awardCoinsToUser(${user.id})" class="text-green-600 hover:text-green-900">
                    اعطای سکه
                </button>
            </td>
        `;
        
        return tr;
    }

    updateRedemptionTable(options) {
        const tbody = document.getElementById('redemption-table-body');
        tbody.innerHTML = '';

        if (options.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        هیچ گزینه تبدیلی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        options.forEach(option => {
            const row = this.createRedemptionRow(option);
            tbody.appendChild(row);
        });
    }

    createRedemptionRow(option) {
        const tr = document.createElement('tr');
        
        const statusColor = option.is_active ? 'green' : 'red';
        const statusText = option.is_active ? 'فعال' : 'غیرفعال';
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${option.name}</div>
                <div class="text-sm text-gray-500">${option.description || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    ${this.getRedemptionTypeText(option.type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatNumber(option.coin_cost)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatNumber(option.value)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="toggleRedemptionOption(${option.id}, ${option.is_active})" 
                            class="text-blue-600 hover:text-blue-900">
                        ${option.is_active ? 'غیرفعال' : 'فعال'}
                    </button>
                    <button onclick="editRedemptionOption(${option.id})" 
                            class="text-yellow-600 hover:text-yellow-900">
                        ویرایش
                    </button>
                    <button onclick="deleteRedemptionOption(${option.id})" 
                            class="text-red-600 hover:text-red-900">
                        حذف
                    </button>
                </div>
            </td>
        `;
        
        return tr;
    }

    updateUsersSelect(users) {
        const select = document.querySelector('select[name="user_id"]');
        select.innerHTML = '<option value="">انتخاب کاربر</option>';
        
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.name} (${user.email})`;
            select.appendChild(option);
        });
    }

    getSourceTypeText(type) {
        const types = {
            'quiz_reward': 'پاداش آزمون',
            'referral': 'ارجاع',
            'story_completion': 'تکمیل داستان',
            'bonus': 'پاداش',
            'manual': 'دستی'
        };
        return types[type] || type;
    }

    getRedemptionTypeText(type) {
        const types = {
            'subscription_days': 'روز اشتراک',
            'premium_content': 'محتوای ویژه',
            'discount': 'تخفیف',
            'gift': 'هدیه'
        };
        return types[type] || type;
    }

    openAwardCoinsModal() {
        document.getElementById('award-coins-modal').classList.remove('hidden');
    }

    closeAwardCoinsModal() {
        document.getElementById('award-coins-modal').classList.add('hidden');
        document.getElementById('award-coins-form').reset();
    }

    openCreateRedemptionModal() {
        document.getElementById('create-redemption-modal').classList.remove('hidden');
    }

    closeCreateRedemptionModal() {
        document.getElementById('create-redemption-modal').classList.add('hidden');
        document.getElementById('create-redemption-form').reset();
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('fa-IR');
    }

    debounce(func, wait) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, wait);
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    showSuccess(message) {
        // You can implement a toast notification system here
        alert(message);
    }

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Global functions for inline event handlers
function switchTab(tabName) {
    window.coinManager.switchTab(tabName);
}

function openAwardCoinsModal() {
    window.coinManager.openAwardCoinsModal();
}

function closeAwardCoinsModal() {
    window.coinManager.closeAwardCoinsModal();
}

function openCreateRedemptionModal() {
    window.coinManager.openCreateRedemptionModal();
}

function closeCreateRedemptionModal() {
    window.coinManager.closeCreateRedemptionModal();
}

function toggleRedemptionOption(optionId, currentStatus) {
    window.coinManager.toggleRedemptionOption(optionId, currentStatus);
}

function deleteRedemptionOption(optionId) {
    window.coinManager.deleteRedemptionOption(optionId);
}

function editRedemptionOption(optionId) {
    // Implement edit functionality
    console.log('Edit redemption option:', optionId);
}

function awardCoinsToUser(userId) {
    // Set the user in the form and open modal
    const select = document.querySelector('select[name="user_id"]');
    select.value = userId;
    window.coinManager.openAwardCoinsModal();
}

function applyFilters() {
    window.coinManager.applyFilters();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.coinManager = new AdminCoinManagement();
});
