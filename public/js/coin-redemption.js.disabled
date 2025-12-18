class CoinRedemption {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.currentCategory = 'all';
        this.init();
    }

    init() {
        this.loadCoinBalance();
        this.loadRedemptionOptions();
        this.loadRedemptionHistory();
        this.bindEvents();
    }

    bindEvents() {
        // Category buttons
        document.querySelectorAll('.redemption-category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setActiveCategory(e.target.dataset.category);
            });
        });

        // Load more history
        document.getElementById('load-more-history')?.addEventListener('click', () => {
            this.loadRedemptionHistory();
        });

        // Close modals
        document.getElementById('close-redemption-modal')?.addEventListener('click', () => {
            this.closeRedemptionModal();
        });

        document.getElementById('close-confirmation-modal')?.addEventListener('click', () => {
            this.closeConfirmationModal();
        });

        // Close modals on outside click
        document.getElementById('redemption-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'redemption-modal') {
                this.closeRedemptionModal();
            }
        });

        document.getElementById('confirmation-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'confirmation-modal') {
                this.closeConfirmationModal();
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

    async loadRedemptionHistory() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('redemption-history');

        try {
            const response = await fetch(`/api/coins/redemption-history?page=${this.currentPage}&limit=10`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load redemption history');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateRedemptionHistory(data.data.redemptions);
                this.currentPage++;
            } else {
                this.showError('خطا در بارگذاری تاریخچه تبدیل‌ها');
            }
        } catch (error) {
            console.error('Error loading redemption history:', error);
            this.showError('خطا در بارگذاری تاریخچه تبدیل‌ها');
        } finally {
            this.loading = false;
            this.hideLoading('redemption-history');
        }
    }

    setActiveCategory(category) {
        this.currentCategory = category;
        
        // Update active button
        document.querySelectorAll('.redemption-category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-category="${category}"]`).classList.add('active');
        
        // Filter options
        this.filterRedemptionOptions();
    }

    filterRedemptionOptions() {
        const options = document.querySelectorAll('.redemption-option-card');
        
        options.forEach(option => {
            const category = option.dataset.category;
            const shouldShow = this.currentCategory === 'all' || category === this.currentCategory;
            
            option.style.display = shouldShow ? 'block' : 'none';
        });
    }

    updateCoinBalance(balance) {
        document.getElementById('current-balance').textContent = balance.available_coins || 0;
        document.getElementById('total-earned').textContent = balance.earned_coins || 0;
        document.getElementById('total-spent').textContent = balance.spent_coins || 0;
    }

    updateRedemptionOptions(options) {
        const container = document.getElementById('redemption-options');
        
        if (options.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ گزینه تبدیل‌ای در دسترس نیست</div>';
            return;
        }

        container.innerHTML = '';
        
        options.forEach(option => {
            const optionElement = this.createRedemptionOptionElement(option);
            container.appendChild(optionElement);
        });
    }

    updateRedemptionHistory(redemptions) {
        const container = document.getElementById('redemption-history');
        
        if (redemptions.length === 0 && this.currentPage === 1) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ تبدیل‌ای انجام نشده</div>';
            return;
        }

        if (this.currentPage === 1) {
            container.innerHTML = '';
        }

        redemptions.forEach(redemption => {
            const redemptionElement = this.createRedemptionHistoryElement(redemption);
            container.appendChild(redemptionElement);
        });

        // Hide load more button if no more redemptions
        if (redemptions.length < 10) {
            document.getElementById('load-more-history').style.display = 'none';
        }
    }

    createRedemptionOptionElement(option) {
        const div = document.createElement('div');
        div.className = 'redemption-option-card';
        div.dataset.category = option.category;
        div.onclick = () => this.openRedemptionModal(option);
        
        const isDisabled = option.coins_required > this.getCurrentBalance();
        if (isDisabled) {
            div.classList.add('disabled');
        }
        
        div.innerHTML = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 ${this.getCategoryColor(option.category)} rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 ${this.getCategoryIconColor(option.category)}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">${option.title}</h3>
                <p class="text-gray-600 mb-4">${option.description}</p>
                <div class="text-2xl font-bold ${isDisabled ? 'text-gray-400' : 'text-blue-600'} mb-2">${option.coins_required} سکه</div>
                <div class="text-sm text-gray-500">${this.getCategoryText(option.category)}</div>
                ${isDisabled ? '<div class="text-sm text-red-500 mt-2">موجودی کافی نیست</div>' : ''}
            </div>
        `;
        
        return div;
    }

    createRedemptionHistoryElement(redemption) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';
        
        const statusClass = this.getRedemptionStatusClass(redemption.status);
        
        div.innerHTML = `
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">${redemption.title}</h3>
                    <p class="text-sm text-gray-500">${redemption.description}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium text-red-600">-${redemption.coins_spent}</div>
                <div class="text-xs text-gray-500">${this.formatDate(redemption.created_at)}</div>
                <div class="text-xs ${statusClass}">${this.getRedemptionStatusText(redemption.status)}</div>
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
        
        if (totalCoins > this.getCurrentBalance()) {
            this.showError('موجودی سکه کافی نیست');
            return;
        }
        
        // Show confirmation modal
        this.showConfirmationModal(option, quantity, totalCoins, notes);
    }

    showConfirmationModal(option, quantity, totalCoins, notes) {
        const modal = document.getElementById('confirmation-modal');
        const content = document.getElementById('confirmation-content');
        
        content.innerHTML = `
            <div class="text-center mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-2">تأیید تبدیل سکه</h4>
                <p class="text-gray-600 mb-4">آیا از تبدیل سکه‌های خود اطمینان دارید؟</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">آیتم:</span>
                    <span class="text-sm font-medium text-gray-900">${option.title}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">تعداد:</span>
                    <span class="text-sm font-medium text-gray-900">${quantity}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">سکه مورد نیاز:</span>
                    <span class="text-sm font-medium text-red-600">${totalCoins}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">موجودی فعلی:</span>
                    <span class="text-sm font-medium text-green-600">${this.getCurrentBalance()}</span>
                </div>
            </div>
        `;
        
        // Bind events
        document.getElementById('confirm-redemption').onclick = () => this.confirmRedemption(option, quantity, totalCoins, notes);
        document.getElementById('cancel-redemption').onclick = () => this.closeConfirmationModal();
        
        modal.classList.remove('hidden');
    }

    async confirmRedemption(option, quantity, totalCoins, notes) {
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
                this.closeConfirmationModal();
                this.closeRedemptionModal();
                this.loadCoinBalance();
                this.loadRedemptionHistory();
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

    closeConfirmationModal() {
        document.getElementById('confirmation-modal').classList.add('hidden');
    }

    getCurrentBalance() {
        const balanceElement = document.getElementById('current-balance');
        return parseInt(balanceElement.textContent) || 0;
    }

    getCategoryText(category) {
        const categories = {
            'premium': 'پریمیوم',
            'discount': 'تخفیف',
            'gift': 'هدیه',
            'special': 'ویژه'
        };
        return categories[category] || category;
    }

    getCategoryColor(category) {
        const colors = {
            'premium': 'bg-purple-100',
            'discount': 'bg-green-100',
            'gift': 'bg-pink-100',
            'special': 'bg-yellow-100'
        };
        return colors[category] || 'bg-blue-100';
    }

    getCategoryIconColor(category) {
        const colors = {
            'premium': 'text-purple-600',
            'discount': 'text-green-600',
            'gift': 'text-pink-600',
            'special': 'text-yellow-600'
        };
        return colors[category] || 'text-blue-600';
    }

    getRedemptionStatusText(status) {
        const statuses = {
            'pending': 'در انتظار',
            'approved': 'تأیید شده',
            'completed': 'تکمیل شده',
            'cancelled': 'لغو شده'
        };
        return statuses[status] || status;
    }

    getRedemptionStatusClass(status) {
        const classes = {
            'pending': 'text-yellow-600',
            'approved': 'text-blue-600',
            'completed': 'text-green-600',
            'cancelled': 'text-red-600'
        };
        return classes[status] || 'text-gray-600';
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
    new CoinRedemption();
});
