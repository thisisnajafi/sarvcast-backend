/**
 * Coin Transactions Management JavaScript
 * Handles coin transaction history and management for users
 */

class CoinTransactionsManager {
    constructor() {
        this.currentPage = 1;
        this.perPage = 20;
        this.filters = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadTransactions();
    }

    setupEventListeners() {
        // Filter controls
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Reset filters
        const resetBtn = document.getElementById('reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetFilters());
        }

        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('pagination-link')) {
                e.preventDefault();
                this.loadPage(e.target.dataset.page);
            }
        });

        // Export functionality
        const exportBtn = document.getElementById('export-transactions');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportTransactions());
        }

        // Transaction details
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('transaction-details-btn')) {
                e.preventDefault();
                this.showTransactionDetails(e.target.dataset.transactionId);
            }
        });
    }

    loadTransactions(page = 1) {
        this.currentPage = page;
        
        const params = new URLSearchParams({
            page: page,
            per_page: this.perPage,
            ...this.filters
        });

        this.showLoading();

        fetch(`/api/coins/transactions?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayTransactions(data.data);
                this.updatePagination(data.pagination);
                this.updateSummary(data.summary);
            } else {
                this.showError(data.message || 'خطا در بارگذاری تراکنش‌ها');
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            this.showError('خطا در بارگذاری تراکنش‌ها');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    displayTransactions(transactions) {
        const container = document.getElementById('transactions-container');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-coins text-4xl mb-4"></i>
                    <p>هیچ تراکنشی یافت نشد</p>
                </div>
            `;
            return;
        }

        const html = transactions.map(transaction => this.createTransactionRow(transaction)).join('');
        container.innerHTML = html;
    }

    createTransactionRow(transaction) {
        const date = new Date(transaction.created_at).toLocaleDateString('fa-IR');
        const time = new Date(transaction.created_at).toLocaleTimeString('fa-IR');
        
        const typeClass = transaction.type === 'earned' ? 'text-green-600' : 'text-red-600';
        const typeIcon = transaction.type === 'earned' ? 'fa-plus' : 'fa-minus';
        const typeText = transaction.type === 'earned' ? 'دریافت' : 'کسر';

        return `
            <div class="transaction-row bg-white rounded-lg shadow-sm border p-4 mb-3 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="transaction-icon">
                            <i class="fas ${typeIcon} ${typeClass} text-xl"></i>
                        </div>
                        <div class="transaction-info">
                            <h3 class="font-semibold text-gray-800">${transaction.description}</h3>
                            <p class="text-sm text-gray-600">${date} - ${time}</p>
                            ${transaction.reference ? `<p class="text-xs text-gray-500">مرجع: ${transaction.reference}</p>` : ''}
                        </div>
                    </div>
                    <div class="transaction-amount text-right">
                        <div class="amount ${typeClass} font-bold text-lg">
                            ${transaction.type === 'earned' ? '+' : '-'}${transaction.amount}
                        </div>
                        <div class="text-sm text-gray-500">سکه</div>
                        ${transaction.status !== 'completed' ? `<div class="status-badge status-${transaction.status}">${this.getStatusText(transaction.status)}</div>` : ''}
                    </div>
                </div>
                ${transaction.details ? `
                    <div class="transaction-details mt-3 pt-3 border-t border-gray-200">
                        <button class="transaction-details-btn text-blue-600 hover:text-blue-800 text-sm" 
                                data-transaction-id="${transaction.id}">
                            <i class="fas fa-info-circle mr-1"></i>
                            جزئیات بیشتر
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    updatePagination(pagination) {
        const paginationContainer = document.getElementById('pagination-container');
        if (!paginationContainer) return;

        if (pagination.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '<div class="flex justify-center space-x-2 space-x-reverse">';
        
        // Previous button
        if (pagination.current_page > 1) {
            html += `
                <button class="pagination-link px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" 
                        data-page="${pagination.current_page - 1}">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.current_page;
            html += `
                <button class="pagination-link px-3 py-2 rounded ${isActive ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}" 
                        data-page="${i}">
                    ${i}
                </button>
            `;
        }

        // Next button
        if (pagination.current_page < pagination.last_page) {
            html += `
                <button class="pagination-link px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" 
                        data-page="${pagination.current_page + 1}">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
        }

        html += '</div>';
        paginationContainer.innerHTML = html;
    }

    updateSummary(summary) {
        const totalEarnedElement = document.getElementById('total-earned');
        const totalSpentElement = document.getElementById('total-spent');
        const currentBalanceElement = document.getElementById('current-balance');

        if (totalEarnedElement) {
            totalEarnedElement.textContent = summary.total_earned || 0;
        }
        if (totalSpentElement) {
            totalSpentElement.textContent = summary.total_spent || 0;
        }
        if (currentBalanceElement) {
            currentBalanceElement.textContent = summary.current_balance || 0;
        }
    }

    applyFilters() {
        const formData = new FormData(document.getElementById('filter-form'));
        this.filters = {};

        // Get filter values
        const type = formData.get('type');
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');
        const minAmount = formData.get('min_amount');
        const maxAmount = formData.get('max_amount');

        if (type) this.filters.type = type;
        if (dateFrom) this.filters.date_from = dateFrom;
        if (dateTo) this.filters.date_to = dateTo;
        if (minAmount) this.filters.min_amount = minAmount;
        if (maxAmount) this.filters.max_amount = maxAmount;

        this.loadTransactions(1);
    }

    resetFilters() {
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.reset();
        }
        
        this.filters = {};
        this.loadTransactions(1);
    }

    loadPage(page) {
        this.loadTransactions(page);
    }

    showTransactionDetails(transactionId) {
        fetch(`/api/coins/transactions/${transactionId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayTransactionModal(data.data);
            } else {
                this.showError(data.message || 'خطا در بارگذاری جزئیات تراکنش');
            }
        })
        .catch(error => {
            console.error('Error loading transaction details:', error);
            this.showError('خطا در بارگذاری جزئیات تراکنش');
        });
    }

    displayTransactionModal(transaction) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">جزئیات تراکنش</h3>
                    <button class="close-modal text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">توضیحات:</span>
                        <span class="font-medium">${transaction.description}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">مبلغ:</span>
                        <span class="font-medium ${transaction.type === 'earned' ? 'text-green-600' : 'text-red-600'}">
                            ${transaction.type === 'earned' ? '+' : '-'}${transaction.amount} سکه
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاریخ:</span>
                        <span class="font-medium">${new Date(transaction.created_at).toLocaleDateString('fa-IR')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">وضعیت:</span>
                        <span class="font-medium">${this.getStatusText(transaction.status)}</span>
                    </div>
                    ${transaction.reference ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600">مرجع:</span>
                            <span class="font-medium">${transaction.reference}</span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        بستن
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Close modal functionality
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    exportTransactions() {
        const params = new URLSearchParams({
            ...this.filters,
            export: 'true'
        });

        window.open(`/api/coins/transactions/export?${params}`, '_blank');
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'در انتظار',
            'completed': 'تکمیل شده',
            'failed': 'ناموفق',
            'cancelled': 'لغو شده'
        };
        
        return statusTexts[status] || status;
    }

    showLoading() {
        const container = document.getElementById('transactions-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <p class="mt-2 text-gray-600">در حال بارگذاری...</p>
                </div>
            `;
        }
    }

    hideLoading() {
        // Loading will be replaced by actual content
    }

    showError(message) {
        const container = document.getElementById('transactions-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CoinTransactionsManager();
});
