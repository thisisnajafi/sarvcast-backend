/**
 * Coin Dashboard Management JavaScript
 * Handles coin dashboard functionality for users
 */

class CoinDashboardManager {
    constructor() {
        this.currentBalance = 0;
        this.recentTransactions = [];
        this.coinPackages = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.loadCoinPackages();
    }

    setupEventListeners() {
        // Purchase coin packages
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('purchase-package-btn')) {
                e.preventDefault();
                this.purchasePackage(e.target.dataset.packageId);
            }
        });

        // Redeem coins
        const redeemBtn = document.getElementById('redeem-coins-btn');
        if (redeemBtn) {
            redeemBtn.addEventListener('click', () => this.showRedeemModal());
        }

        // View transaction history
        const historyBtn = document.getElementById('view-history-btn');
        if (historyBtn) {
            historyBtn.addEventListener('click', () => this.viewTransactionHistory());
        }

        // Quick actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quick-action-btn')) {
                e.preventDefault();
                this.handleQuickAction(e.target.dataset.action);
            }
        });

        // Refresh data
        const refreshBtn = document.getElementById('refresh-data-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }
    }

    loadDashboardData() {
        this.showLoading();

        fetch('/api/coins/dashboard', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentBalance = data.data.balance;
                this.recentTransactions = data.data.recent_transactions || [];
                this.updateDashboard(data.data);
            } else {
                this.showError(data.message || 'خطا در بارگذاری اطلاعات داشبورد');
            }
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            this.showError('خطا در بارگذاری اطلاعات داشبورد');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    loadCoinPackages() {
        fetch('/api/coins/packages', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.coinPackages = data.data;
                this.displayCoinPackages();
            }
        })
        .catch(error => {
            console.error('Error loading coin packages:', error);
        });
    }

    updateDashboard(data) {
        // Update balance
        this.updateBalance(data.balance);

        // Update recent transactions
        this.updateRecentTransactions(data.recent_transactions || []);

        // Update statistics
        this.updateStatistics(data.statistics || {});

        // Update achievements
        this.updateAchievements(data.achievements || []);
    }

    updateBalance(balance) {
        const balanceElement = document.getElementById('current-balance');
        if (balanceElement) {
            balanceElement.textContent = balance.toLocaleString('fa-IR');
        }

        // Update balance in header if exists
        const headerBalanceElement = document.getElementById('header-balance');
        if (headerBalanceElement) {
            headerBalanceElement.textContent = balance.toLocaleString('fa-IR');
        }
    }

    updateRecentTransactions(transactions) {
        const container = document.getElementById('recent-transactions');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-coins text-2xl mb-2"></i>
                    <p>هیچ تراکنش اخیری وجود ندارد</p>
                </div>
            `;
            return;
        }

        const html = transactions.map(transaction => this.createTransactionItem(transaction)).join('');
        container.innerHTML = html;
    }

    createTransactionItem(transaction) {
        const date = new Date(transaction.created_at).toLocaleDateString('fa-IR');
        const typeClass = transaction.type === 'earned' ? 'text-green-600' : 'text-red-600';
        const typeIcon = transaction.type === 'earned' ? 'fa-plus' : 'fa-minus';

        return `
            <div class="transaction-item flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                <div class="flex items-center space-x-3 space-x-reverse min-w-0">
                    <div class="transaction-icon flex-shrink-0">
                        <i class="fas ${typeIcon} ${typeClass}"></i>
                    </div>
                    <div class="transaction-info min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate" title="${transaction.description}">${transaction.description}</p>
                        <p class="text-xs text-gray-500 whitespace-nowrap">${date}</p>
                    </div>
                </div>
                <div class="transaction-amount ${typeClass} font-semibold whitespace-nowrap ml-3">
                    ${transaction.type === 'earned' ? '+' : '-'}${transaction.amount}
                </div>
            </div>
        `;
    }

    updateStatistics(stats) {
        const elements = {
            'total-earned': stats.total_earned || 0,
            'total-spent': stats.total_spent || 0,
            'this-month-earned': stats.this_month_earned || 0,
            'this-month-spent': stats.this_month_spent || 0
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id].toLocaleString('fa-IR');
            }
        });
    }

    updateAchievements(achievements) {
        const container = document.getElementById('achievements-container');
        if (!container) return;

        if (achievements.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-trophy text-2xl mb-2"></i>
                    <p>هنوز دستاوردی کسب نکرده‌اید</p>
                </div>
            `;
            return;
        }

        const html = achievements.map(achievement => this.createAchievementItem(achievement)).join('');
        container.innerHTML = html;
    }

    createAchievementItem(achievement) {
        const isUnlocked = achievement.unlocked_at !== null;
        const iconClass = isUnlocked ? 'text-yellow-500' : 'text-gray-300';

        return `
            <div class="achievement-item flex items-center space-x-3 space-x-reverse p-3 rounded-lg ${isUnlocked ? 'bg-yellow-50' : 'bg-gray-50'}">
                <div class="achievement-icon">
                    <i class="fas fa-trophy ${iconClass} text-xl"></i>
                </div>
                <div class="achievement-info flex-1">
                    <h4 class="font-medium ${isUnlocked ? 'text-gray-800' : 'text-gray-500'}">${achievement.name}</h4>
                    <p class="text-sm ${isUnlocked ? 'text-gray-600' : 'text-gray-400'}">${achievement.description}</p>
                    ${achievement.reward ? `<p class="text-xs text-green-600 mt-1">پاداش: ${achievement.reward} سکه</p>` : ''}
                </div>
                ${isUnlocked ? `
                    <div class="achievement-date text-xs text-gray-500">
                        ${new Date(achievement.unlocked_at).toLocaleDateString('fa-IR')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    displayCoinPackages() {
        const container = document.getElementById('coin-packages');
        if (!container) return;

        const html = this.coinPackages.map(package => this.createPackageItem(package)).join('');
        container.innerHTML = html;
    }

    createPackageItem(package) {
        const discount = package.original_price > package.price ?
            Math.round(((package.original_price - package.price) / package.original_price) * 100) : 0;

        return `
            <div class="package-item bg-white rounded-lg shadow-sm border p-4 hover:shadow-md transition-shadow">
                <div class="package-header text-center mb-4">
                    <h3 class="font-bold text-lg text-gray-800">${package.name}</h3>
                    ${discount > 0 ? `<span class="discount-badge bg-red-500 text-white text-xs px-2 py-1 rounded-full">${discount}% تخفیف</span>` : ''}
                </div>

                <div class="package-content text-center">
                    <div class="coins-amount text-3xl font-bold text-blue-600 mb-2">
                        ${package.coins.toLocaleString('fa-IR')}
                    </div>
                    <div class="price-info mb-4">
                        <div class="current-price text-xl font-semibold text-gray-800">
                            ${package.price.toLocaleString('fa-IR')} تومان
                        </div>
                        ${package.original_price > package.price ? `
                            <div class="original-price text-sm text-gray-500 line-through">
                                ${package.original_price.toLocaleString('fa-IR')} تومان
                            </div>
                        ` : ''}
                    </div>

                    <button class="purchase-package-btn w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors"
                            data-package-id="${package.id}">
                        خرید
                    </button>
                </div>
            </div>
        `;
    }

    purchasePackage(packageId) {
        const package = this.coinPackages.find(p => p.id == packageId);
        if (!package) {
            this.showError('بسته سکه یافت نشد');
            return;
        }

        if (confirm(`آیا می‌خواهید بسته ${package.name} را به مبلغ ${package.price.toLocaleString('fa-IR')} تومان خریداری کنید؟`)) {
            this.processPurchase(packageId);
        }
    }

    processPurchase(packageId) {
        this.showLoading();

        fetch('/api/coins/purchase', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                package_id: packageId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess('خرید با موفقیت انجام شد!');
                this.refreshData();

                // Redirect to payment if needed
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                }
            } else {
                this.showError(data.message || 'خطا در خرید بسته سکه');
            }
        })
        .catch(error => {
            console.error('Error purchasing package:', error);
            this.showError('خطا در خرید بسته سکه');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    showRedeemModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">تبدیل سکه</h3>
                    <button class="close-modal text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تعداد سکه برای تبدیل:</label>
                        <input type="number" id="redeem-amount" class="w-full border border-gray-300 rounded-lg px-3 py-2"
                               min="1" max="${this.currentBalance}" placeholder="تعداد سکه">
                    </div>

                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            هر 100 سکه معادل 1000 تومان است
                        </p>
                    </div>

                    <div id="redeem-preview" class="hidden">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-600">
                                مبلغ قابل دریافت: <span id="redeem-value" class="font-semibold"></span> تومان
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        انصراف
                    </button>
                    <button id="confirm-redeem" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        تبدیل
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle redeem amount input
        const redeemAmountInput = modal.querySelector('#redeem-amount');
        const redeemPreview = modal.querySelector('#redeem-preview');
        const redeemValue = modal.querySelector('#redeem-value');

        redeemAmountInput.addEventListener('input', () => {
            const amount = parseInt(redeemAmountInput.value);
            if (amount > 0) {
                const value = Math.floor(amount / 100) * 1000;
                redeemValue.textContent = value.toLocaleString('fa-IR');
                redeemPreview.classList.remove('hidden');
            } else {
                redeemPreview.classList.add('hidden');
            }
        });

        // Handle confirm redeem
        modal.querySelector('#confirm-redeem').addEventListener('click', () => {
            const amount = parseInt(redeemAmountInput.value);
            if (amount > 0 && amount <= this.currentBalance) {
                this.processRedeem(amount);
                document.body.removeChild(modal);
            } else {
                this.showError('مبلغ وارد شده نامعتبر است');
            }
        });

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

    processRedeem(amount) {
        this.showLoading();

        fetch('/api/coins/redeem', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                amount: amount
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess('درخواست تبدیل سکه با موفقیت ثبت شد!');
                this.refreshData();
            } else {
                this.showError(data.message || 'خطا در تبدیل سکه');
            }
        })
        .catch(error => {
            console.error('Error redeeming coins:', error);
            this.showError('خطا در تبدیل سکه');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    viewTransactionHistory() {
        window.location.href = '/coins/transactions';
    }

    handleQuickAction(action) {
        switch (action) {
            case 'earn_coins':
                this.showEarnCoinsModal();
                break;
            case 'spend_coins':
                this.showSpendCoinsModal();
                break;
            case 'view_achievements':
                this.showAchievementsModal();
                break;
        }
    }

    showEarnCoinsModal() {
        // Implementation for earning coins modal
        this.showNotification('امکان کسب سکه از طریق کویزها و فعالیت‌های مختلف', 'info');
    }

    showSpendCoinsModal() {
        // Implementation for spending coins modal
        this.showNotification('امکان خرج سکه برای خرید محتوا و ویژگی‌های ویژه', 'info');
    }

    showAchievementsModal() {
        // Implementation for achievements modal
        this.showNotification('دستاوردهای شما در حال بارگذاری...', 'info');
    }

    refreshData() {
        this.loadDashboardData();
        this.loadCoinPackages();
    }

    showLoading() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.classList.remove('hidden');
        }
    }

    hideLoading() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.classList.add('hidden');
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-sm max-w-sm transform transition-all duration-300 translate-x-full`;

        switch(type) {
            case 'success': notification.classList.add('bg-green-500'); break;
            case 'error': notification.classList.add('bg-red-500'); break;
            case 'warning': notification.classList.add('bg-yellow-500'); break;
            default: notification.classList.add('bg-blue-500');
        }

        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => { notification.classList.remove('translate-x-full'); }, 100);
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CoinDashboardManager();
});
