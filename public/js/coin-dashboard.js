class CoinDashboard {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.init();
    }

    init() {
        this.loadCoinBalance();
        this.loadRecentTransactions();
        this.loadRedemptionOptions();
        this.bindEvents();
    }

    bindEvents() {
        // Load more transactions
        document.getElementById('load-more-transactions')?.addEventListener('click', () => {
            this.loadRecentTransactions();
        });

        // Close redemption modal
        document.getElementById('close-redemption-modal')?.addEventListener('click', () => {
            this.closeRedemptionModal();
        });

        // Close modal on outside click
        document.getElementById('redemption-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'redemption-modal') {
                this.closeRedemptionModal();
            }
        });
    }

    async loadCoinBalance() {
        try {
            const response = await fetch('/api/coins/balance', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load coin balance');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateCoinBalance(data.data);
            } else {
                this.showError('خطا در بارگذاری موجودی سکه');
            }
        } catch (error) {
            console.error('Error loading coin balance:', error);
            this.showError('خطا در بارگذاری موجودی سکه');
        }
    }

    async loadRecentTransactions() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('recent-transactions');

        try {
            const response = await fetch(`/api/coins/transactions?page=${this.currentPage}&limit=5`, {
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
                this.updateRecentTransactions(data.data.transactions);
                this.currentPage++;
            } else {
                this.showError('خطا در بارگذاری تراکنش‌ها');
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            this.showError('خطا در بارگذاری تراکنش‌ها');
        } finally {
            this.loading = false;
            this.hideLoading('recent-transactions');
        }
    }

    async loadRedemptionOptions() {
        try {
            const response = await fetch('/api/coins/redemption-options', {
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
                this.updateRedemptionOptions(data.data);
            } else {
                this.showError('خطا در بارگذاری گزینه‌های تبدیل');
            }
        } catch (error) {
            console.error('Error loading redemption options:', error);
            this.showError('خطا در بارگذاری گزینه‌های تبدیل');
        }
    }

    updateCoinBalance(balance) {
        document.getElementById('coin-balance').textContent = balance.available_coins || 0;
        document.getElementById('total-earned').textContent = balance.earned_coins || 0;
        document.getElementById('total-spent').textContent = balance.spent_coins || 0;
    }

    updateRecentTransactions(transactions) {
        const container = document.getElementById('recent-transactions');
        
        if (transactions.length === 0 && this.currentPage === 1) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ تراکنشی یافت نشد</div>';
            return;
        }

        if (this.currentPage === 1) {
            container.innerHTML = '';
        }

        transactions.forEach(transaction => {
            const transactionElement = this.createTransactionElement(transaction);
            container.appendChild(transactionElement);
        });

        // Hide load more button if no more transactions
        if (transactions.length < 5) {
            document.getElementById('load-more-transactions').style.display = 'none';
        }
    }

    updateRedemptionOptions(options) {
        const container = document.getElementById('redemption-options');
        
        if (options.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ گزینه تبدیل‌ای در دسترس نیست</div>';
            return;
        }

        container.innerHTML = '';
        
        options.slice(0, 6).forEach(option => {
            const optionElement = this.createRedemptionOptionElement(option);
            container.appendChild(optionElement);
        });
    }

    createTransactionElement(transaction) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';
        
        const isEarned = transaction.transaction_type === 'earned' || 
                        transaction.transaction_type === 'referral' || 
                        transaction.transaction_type === 'quiz_reward' || 
                        transaction.transaction_type === 'story_completion';
        
        const amountClass = isEarned ? 'text-green-600' : 'text-red-600';
        const amountPrefix = isEarned ? '+' : '-';
        
        div.innerHTML = `
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center ${isEarned ? 'bg-green-100' : 'bg-red-100'}">
                    <svg class="w-5 h-5 ${isEarned ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">${this.getTransactionTypeText(transaction.transaction_type)}</h3>
                    <p class="text-sm text-gray-500">${transaction.description || ''}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium ${amountClass}">${amountPrefix}${transaction.amount}</div>
                <div class="text-xs text-gray-500">${this.formatDate(transaction.created_at)}</div>
            </div>
        `;
        
        return div;
    }

    createRedemptionOptionElement(option) {
        const div = document.createElement('div');
        div.className = 'redemption-option-card';
        div.onclick = () => this.openRedemptionModal(option);
        
        div.innerHTML = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">${option.title}</h3>
                <p class="text-gray-600 mb-4">${option.description}</p>
                <div class="text-2xl font-bold text-blue-600 mb-2">${option.coins_required} سکه</div>
                <div class="text-sm text-gray-500">${option.category}</div>
            </div>
        `;
        
        return div;
    }

    openRedemptionModal(option) {
        const modal = document.getElementById('redemption-modal');
        const form = document.getElementById('redemption-form');
        
        form.innerHTML = `
            <div class="text-center mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-2">${option.title}</h4>
                <p class="text-gray-600 mb-4">${option.description}</p>
                <div class="text-2xl font-bold text-blue-600">${option.coins_required} سکه</div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">تعداد</label>
                    <input type="number" id="redemption-quantity" class="w-full border border-gray-300 rounded-lg px-3 py-2" min="1" max="${option.max_quantity || 10}" value="1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات اضافی (اختیاری)</label>
                    <textarea id="redemption-notes" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3" placeholder="توضیحات اضافی..."></textarea>
                </div>
            </div>
            <div class="flex space-x-4 mt-6">
                <button id="proceed-redemption" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    ادامه
                </button>
                <button id="cancel-redemption" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    لغو
                </button>
            </div>
        `;
        
        // Bind events
        document.getElementById('proceed-redemption').onclick = () => this.proceedRedemption(option);
        document.getElementById('cancel-redemption').onclick = () => this.closeRedemptionModal();
        
        modal.classList.remove('hidden');
    }

    async proceedRedemption(option) {
        const quantity = document.getElementById('redemption-quantity').value;
        const notes = document.getElementById('redemption-notes').value;
        
        if (!quantity || quantity < 1) {
            this.showError('لطفاً تعداد معتبر وارد کنید');
            return;
        }
        
        const totalCoins = option.coins_required * quantity;
        
        try {
            const response = await fetch('/api/coins/spend', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    amount: totalCoins,
                    redemption_option_id: option.id,
                    quantity: quantity,
                    notes: notes
                })
            });

            if (!response.ok) {
                throw new Error('Failed to process redemption');
            }

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('تبدیل سکه با موفقیت انجام شد');
                this.closeRedemptionModal();
                this.loadCoinBalance();
                this.loadRecentTransactions();
            } else {
                this.showError(data.message || 'خطا در تبدیل سکه');
            }
        } catch (error) {
            console.error('Error processing redemption:', error);
            this.showError('خطا در تبدیل سکه');
        }
    }

    closeRedemptionModal() {
        document.getElementById('redemption-modal').classList.add('hidden');
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
            element.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';
        }
    }

    hideLoading(elementId) {
        // Loading will be replaced by actual content
    }

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }

    showSuccess(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CoinDashboard();
});
