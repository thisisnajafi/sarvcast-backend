/**
 * Coin Redemption Management JavaScript
 * Handles coin redemption functionality for users
 */

class CoinRedemptionManager {
    constructor() {
        this.currentBalance = 0;
        this.redemptionRate = 100; // 100 coins = 1000 toman
        this.pendingRedemptions = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadUserData();
        this.loadRedemptionHistory();
    }

    setupEventListeners() {
        // Redemption form
        const redemptionForm = document.getElementById('redemption-form');
        if (redemptionForm) {
            redemptionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitRedemption();
            });
        }

        // Amount input
        const amountInput = document.getElementById('redemption-amount');
        if (amountInput) {
            amountInput.addEventListener('input', () => this.updateRedemptionPreview());
        }

        // Payment method selection
        document.addEventListener('change', (e) => {
            if (e.target.name === 'payment_method') {
                this.updatePaymentMethodInfo(e.target.value);
            }
        });

        // Cancel redemption
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cancel-redemption-btn')) {
                e.preventDefault();
                this.cancelRedemption(e.target.dataset.redemptionId);
            }
        });

        // View redemption details
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('view-redemption-details')) {
                e.preventDefault();
                this.viewRedemptionDetails(e.target.dataset.redemptionId);
            }
        });
    }

    loadUserData() {
        fetch('/api/coins/balance', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentBalance = data.data.balance;
                this.updateBalanceDisplay();
            } else {
                this.showError(data.message || 'خطا در بارگذاری موجودی');
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            this.showError('خطا در بارگذاری اطلاعات کاربر');
        });
    }

    loadRedemptionHistory() {
        fetch('/api/coins/redemptions', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.pendingRedemptions = data.data.pending || [];
                this.displayRedemptionHistory(data.data);
            } else {
                this.showError(data.message || 'خطا در بارگذاری تاریخچه تبدیل');
            }
        })
        .catch(error => {
            console.error('Error loading redemption history:', error);
            this.showError('خطا در بارگذاری تاریخچه تبدیل');
        });
    }

    updateBalanceDisplay() {
        const balanceElement = document.getElementById('current-balance');
        if (balanceElement) {
            balanceElement.textContent = this.currentBalance.toLocaleString('fa-IR');
        }

        // Update max amount for input
        const amountInput = document.getElementById('redemption-amount');
        if (amountInput) {
            amountInput.max = this.currentBalance;
        }
    }

    updateRedemptionPreview() {
        const amountInput = document.getElementById('redemption-amount');
        const previewElement = document.getElementById('redemption-preview');
        const valueElement = document.getElementById('redemption-value');

        if (!amountInput || !previewElement || !valueElement) return;

        const amount = parseInt(amountInput.value);
        
        if (amount > 0 && amount <= this.currentBalance) {
            const value = Math.floor(amount / this.redemptionRate) * 1000;
            const remainingCoins = this.currentBalance - amount;
            
            valueElement.textContent = value.toLocaleString('fa-IR');
            
            const remainingElement = document.getElementById('remaining-coins');
            if (remainingElement) {
                remainingElement.textContent = remainingCoins.toLocaleString('fa-IR');
            }
            
            previewElement.classList.remove('hidden');
        } else {
            previewElement.classList.add('hidden');
        }
    }

    updatePaymentMethodInfo(method) {
        const infoElement = document.getElementById('payment-method-info');
        if (!infoElement) return;

        const methodInfo = {
            'bank_transfer': {
                title: 'واریز به حساب بانکی',
                description: 'مبلغ به حساب بانکی شما واریز خواهد شد',
                processingTime: '2-3 روز کاری'
            },
            'wallet': {
                title: 'کیف پول دیجیتال',
                description: 'مبلغ به کیف پول دیجیتال شما اضافه خواهد شد',
                processingTime: 'فوری'
            },
            'gift_card': {
                title: 'کارت هدیه',
                description: 'کارت هدیه برای شما ارسال خواهد شد',
                processingTime: '1-2 روز کاری'
            }
        };

        const info = methodInfo[method];
        if (info) {
            infoElement.innerHTML = `
                <div class="bg-blue-50 p-3 rounded-lg">
                    <h4 class="font-semibold text-blue-800">${info.title}</h4>
                    <p class="text-sm text-blue-700">${info.description}</p>
                    <p class="text-xs text-blue-600 mt-1">زمان پردازش: ${info.processingTime}</p>
                </div>
            `;
            infoElement.classList.remove('hidden');
        } else {
            infoElement.classList.add('hidden');
        }
    }

    submitRedemption() {
        const formData = new FormData(document.getElementById('redemption-form'));
        const amount = parseInt(formData.get('amount'));
        const paymentMethod = formData.get('payment_method');
        const bankAccount = formData.get('bank_account');
        const notes = formData.get('notes');

        // Validation
        if (!amount || amount <= 0) {
            this.showError('لطفاً مبلغ معتبر وارد کنید');
            return;
        }

        if (amount > this.currentBalance) {
            this.showError('مبلغ درخواستی بیشتر از موجودی شما است');
            return;
        }

        if (amount < this.redemptionRate) {
            this.showError(`حداقل مبلغ قابل تبدیل ${this.redemptionRate} سکه است`);
            return;
        }

        if (!paymentMethod) {
            this.showError('لطفاً روش پرداخت را انتخاب کنید');
            return;
        }

        if (paymentMethod === 'bank_transfer' && !bankAccount) {
            this.showError('لطفاً شماره حساب بانکی را وارد کنید');
            return;
        }

        // Confirm redemption
        const value = Math.floor(amount / this.redemptionRate) * 1000;
        const confirmMessage = `آیا می‌خواهید ${amount.toLocaleString('fa-IR')} سکه را به مبلغ ${value.toLocaleString('fa-IR')} تومان تبدیل کنید؟`;
        
        if (confirm(confirmMessage)) {
            this.processRedemption({
                amount: amount,
                payment_method: paymentMethod,
                bank_account: bankAccount,
                notes: notes
            });
        }
    }

    processRedemption(data) {
        this.showLoading();

        fetch('/api/coins/redeem', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showSuccess('درخواست تبدیل سکه با موفقیت ثبت شد!');
                this.resetForm();
                this.loadUserData();
                this.loadRedemptionHistory();
            } else {
                this.showError(result.message || 'خطا در ثبت درخواست تبدیل');
            }
        })
        .catch(error => {
            console.error('Error processing redemption:', error);
            this.showError('خطا در ثبت درخواست تبدیل');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    displayRedemptionHistory(data) {
        const historyContainer = document.getElementById('redemption-history');
        const pendingContainer = document.getElementById('pending-redemptions');

        if (!historyContainer && !pendingContainer) return;

        // Display pending redemptions
        if (pendingContainer) {
            if (data.pending && data.pending.length > 0) {
                const html = data.pending.map(redemption => this.createRedemptionItem(redemption, true)).join('');
                pendingContainer.innerHTML = html;
            } else {
                pendingContainer.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-clock text-2xl mb-2"></i>
                        <p>هیچ درخواست در انتظاری وجود ندارد</p>
                    </div>
                `;
            }
        }

        // Display completed redemptions
        if (historyContainer) {
            if (data.completed && data.completed.length > 0) {
                const html = data.completed.map(redemption => this.createRedemptionItem(redemption, false)).join('');
                historyContainer.innerHTML = html;
            } else {
                historyContainer.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-history text-2xl mb-2"></i>
                        <p>هیچ درخواست تکمیل شده‌ای وجود ندارد</p>
                    </div>
                `;
            }
        }
    }

    createRedemptionItem(redemption, isPending = false) {
        const date = new Date(redemption.created_at).toLocaleDateString('fa-IR');
        const statusClass = this.getStatusClass(redemption.status);
        const statusText = this.getStatusText(redemption.status);

        return `
            <div class="redemption-item bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <div class="redemption-info">
                        <h4 class="font-semibold text-gray-800">درخواست تبدیل سکه</h4>
                        <p class="text-sm text-gray-600">${date}</p>
                    </div>
                    <div class="redemption-status">
                        <span class="status-badge ${statusClass} px-2 py-1 rounded-full text-xs">
                            ${statusText}
                        </span>
                    </div>
                </div>
                
                <div class="redemption-details grid grid-cols-2 gap-4 mb-3">
                    <div>
                        <span class="text-sm text-gray-600">مبلغ سکه:</span>
                        <span class="font-semibold">${redemption.amount.toLocaleString('fa-IR')} سکه</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">مبلغ تومان:</span>
                        <span class="font-semibold">${redemption.value.toLocaleString('fa-IR')} تومان</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">روش پرداخت:</span>
                        <span class="font-semibold">${this.getPaymentMethodText(redemption.payment_method)}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">شماره پیگیری:</span>
                        <span class="font-semibold text-blue-600">${redemption.tracking_number}</span>
                    </div>
                </div>

                ${redemption.notes ? `
                    <div class="redemption-notes mb-3">
                        <span class="text-sm text-gray-600">یادداشت:</span>
                        <p class="text-sm text-gray-800 mt-1">${redemption.notes}</p>
                    </div>
                ` : ''}

                <div class="redemption-actions flex justify-end space-x-2 space-x-reverse">
                    <button class="view-redemption-details text-blue-600 hover:text-blue-800 text-sm" 
                            data-redemption-id="${redemption.id}">
                        <i class="fas fa-eye mr-1"></i>
                        جزئیات
                    </button>
                    ${isPending && redemption.status === 'pending' ? `
                        <button class="cancel-redemption-btn text-red-600 hover:text-red-800 text-sm" 
                                data-redemption-id="${redemption.id}">
                            <i class="fas fa-times mr-1"></i>
                            لغو
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    cancelRedemption(redemptionId) {
        if (confirm('آیا از لغو این درخواست اطمینان دارید؟')) {
            fetch(`/api/coins/redemptions/${redemptionId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showSuccess('درخواست با موفقیت لغو شد');
                    this.loadUserData();
                    this.loadRedemptionHistory();
                } else {
                    this.showError(data.message || 'خطا در لغو درخواست');
                }
            })
            .catch(error => {
                console.error('Error cancelling redemption:', error);
                this.showError('خطا در لغو درخواست');
            });
        }
    }

    viewRedemptionDetails(redemptionId) {
        fetch(`/api/coins/redemptions/${redemptionId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayRedemptionModal(data.data);
            } else {
                this.showError(data.message || 'خطا در بارگذاری جزئیات');
            }
        })
        .catch(error => {
            console.error('Error loading redemption details:', error);
            this.showError('خطا در بارگذاری جزئیات');
        });
    }

    displayRedemptionModal(redemption) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">جزئیات درخواست تبدیل</h3>
                    <button class="close-modal text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">شماره پیگیری:</span>
                            <p class="font-semibold text-blue-600">${redemption.tracking_number}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">وضعیت:</span>
                            <span class="status-badge ${this.getStatusClass(redemption.status)} px-2 py-1 rounded-full text-xs">
                                ${this.getStatusText(redemption.status)}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">مبلغ سکه:</span>
                            <p class="font-semibold">${redemption.amount.toLocaleString('fa-IR')} سکه</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">مبلغ تومان:</span>
                            <p class="font-semibold">${redemption.value.toLocaleString('fa-IR')} تومان</p>
                        </div>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">روش پرداخت:</span>
                        <p class="font-semibold">${this.getPaymentMethodText(redemption.payment_method)}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">تاریخ درخواست:</span>
                        <p class="font-semibold">${new Date(redemption.created_at).toLocaleDateString('fa-IR')}</p>
                    </div>
                    
                    ${redemption.processed_at ? `
                        <div>
                            <span class="text-sm text-gray-600">تاریخ پردازش:</span>
                            <p class="font-semibold">${new Date(redemption.processed_at).toLocaleDateString('fa-IR')}</p>
                        </div>
                    ` : ''}
                    
                    ${redemption.bank_account ? `
                        <div>
                            <span class="text-sm text-gray-600">شماره حساب:</span>
                            <p class="font-semibold">${redemption.bank_account}</p>
                        </div>
                    ` : ''}
                    
                    ${redemption.notes ? `
                        <div>
                            <span class="text-sm text-gray-600">یادداشت:</span>
                            <p class="font-semibold">${redemption.notes}</p>
                        </div>
                    ` : ''}
                    
                    ${redemption.admin_notes ? `
                        <div>
                            <span class="text-sm text-gray-600">یادداشت ادمین:</span>
                            <p class="font-semibold">${redemption.admin_notes}</p>
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

    resetForm() {
        const form = document.getElementById('redemption-form');
        if (form) {
            form.reset();
        }
        
        const previewElement = document.getElementById('redemption-preview');
        if (previewElement) {
            previewElement.classList.add('hidden');
        }
        
        const infoElement = document.getElementById('payment-method-info');
        if (infoElement) {
            infoElement.classList.add('hidden');
        }
    }

    getStatusClass(status) {
        const statusClasses = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'processing': 'bg-blue-100 text-blue-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800',
            'failed': 'bg-red-100 text-red-800'
        };
        
        return statusClasses[status] || 'bg-gray-100 text-gray-800';
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'در انتظار',
            'processing': 'در حال پردازش',
            'completed': 'تکمیل شده',
            'cancelled': 'لغو شده',
            'failed': 'ناموفق'
        };
        
        return statusTexts[status] || status;
    }

    getPaymentMethodText(method) {
        const methodTexts = {
            'bank_transfer': 'واریز به حساب بانکی',
            'wallet': 'کیف پول دیجیتال',
            'gift_card': 'کارت هدیه'
        };
        
        return methodTexts[method] || method;
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
    new CoinRedemptionManager();
});
