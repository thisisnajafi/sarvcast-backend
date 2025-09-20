class CoinTransactions {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.filters = {
            type: '',
            startDate: '',
            endDate: ''
        };
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadTransactions();
        this.bindEvents();
    }

    bindEvents() {
        // Apply filters
        document.getElementById('apply-filters')?.addEventListener('click', () => {
            this.applyFilters();
        });

        // Pagination
        document.getElementById('prev-page')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadTransactions();
            }
        });

        document.getElementById('next-page')?.addEventListener('click', () => {
            this.currentPage++;
            this.loadTransactions();
        });

        // Filter inputs
        document.getElementById('transaction-type-filter')?.addEventListener('change', (e) => {
            this.filters.type = e.target.value;
        });

        document.getElementById('start-date-filter')?.addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
        });

        document.getElementById('end-date-filter')?.addEventListener('change', (e) => {
            this.filters.endDate = e.target.value;
        });
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/coins/statistics', {
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
            } else {
                this.showError('خطا در بارگذاری آمار');
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showError('خطا در بارگذاری آمار');
        }
    }

    async loadTransactions() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('transactions-table-body');

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...this.filters
            });

            const response = await fetch(`/api/coins/transactions?${params}`, {
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
                this.updateTransactionsTable(data.data.transactions);
                this.updatePaginationInfo(data.data);
            } else {
                this.showError('خطا در بارگذاری تراکنش‌ها');
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            this.showError('خطا در بارگذاری تراکنش‌ها');
        } finally {
            this.loading = false;
            this.hideLoading('transactions-table-body');
        }
    }

    applyFilters() {
        this.currentPage = 1;
        this.loadTransactions();
    }

    updateStatistics(stats) {
        document.getElementById('total-earned').textContent = stats.total_earned || 0;
        document.getElementById('total-spent').textContent = stats.total_spent || 0;
        document.getElementById('total-transactions').textContent = stats.total_transactions || 0;
        document.getElementById('current-balance').textContent = stats.current_balance || 0;
    }

    updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactions-table-body');
        
        if (transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        هیچ تراکنشی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        
        transactions.forEach(transaction => {
            const row = this.createTransactionRow(transaction);
            tbody.appendChild(row);
        });
    }

    updatePaginationInfo(data) {
        document.getElementById('showing-count').textContent = data.transactions.length;
        document.getElementById('total-count').textContent = data.total || 0;
        
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        prevBtn.disabled = this.currentPage <= 1;
        nextBtn.disabled = data.transactions.length < 20;
    }

    createTransactionRow(transaction) {
        const row = document.createElement('tr');
        
        const isEarned = transaction.transaction_type === 'earned' || 
                        transaction.transaction_type === 'referral' || 
                        transaction.transaction_type === 'quiz_reward' || 
                        transaction.transaction_type === 'story_completion';
        
        const amountClass = isEarned ? 'text-green-600' : 'text-red-600';
        const amountPrefix = isEarned ? '+' : '-';
        const statusClass = this.getStatusClass(transaction.status);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center ${isEarned ? 'bg-green-100' : 'bg-red-100'}">
                        <svg class="w-4 h-4 ${isEarned ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">${this.getTransactionTypeText(transaction.transaction_type)}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium ${amountClass}">${amountPrefix}${transaction.amount}</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900">${transaction.description || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatDate(transaction.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                    ${this.getStatusText(transaction.status)}
                </span>
            </td>
        `;
        
        return row;
    }

    getTransactionTypeText(type) {
        const types = {
            'earned': 'کسب شده',
            'spent': 'خرج شده',
            'bonus': 'پاداش',
            'referral': 'ارجاع',
            'quiz_reward': 'پاداش آزمون',
            'story_completion': 'تکمیل داستان'
        };
        return types[type] || type;
    }

    getStatusText(status) {
        const statuses = {
            'completed': 'تکمیل شده',
            'pending': 'در انتظار',
            'failed': 'ناموفق',
            'cancelled': 'لغو شده'
        };
        return statuses[status] || status;
    }

    getStatusClass(status) {
        const classes = {
            'completed': 'bg-green-100 text-green-800',
            'pending': 'bg-yellow-100 text-yellow-800',
            'failed': 'bg-red-100 text-red-800',
            'cancelled': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fa-IR');
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </td>
                </tr>
            `;
        }
    }

    hideLoading(elementId) {
        // Loading will be replaced by actual content
    }

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CoinTransactions();
});
