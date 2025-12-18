class AdminCommissionPayments {
    constructor() {
        this.currentPage = 1;
        this.filters = {};
        this.selectedPayments = new Set();
        this.currentPayment = null;
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadPayments();
        this.loadPartners();
        this.bindEvents();
    }

    bindEvents() {
        // Form submission
        document.getElementById('manual-payment-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createManualPayment();
        });

        // Select all checkbox
        document.getElementById('select-all').addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        // Filter changes
        document.getElementById('status-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('payment-type-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('date-from-filter').addEventListener('change', () => {
            this.applyFilters();
        });
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/v1/commission-payments/statistics', {
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

    async loadPayments() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });

            const response = await fetch(`/api/v1/commission-payments/all?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load payments');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updatePaymentsTable(data.data);
            }
        } catch (error) {
            console.error('Error loading payments:', error);
        }
    }

    async loadPartners() {
        try {
            const response = await fetch('/api/v1/affiliate/partners/all', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load partners');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updatePartnersSelect(data.data);
            }
        } catch (error) {
            console.error('Error loading partners:', error);
        }
    }

    async createManualPayment() {
        try {
            const formData = new FormData(document.getElementById('manual-payment-form'));
            const data = Object.fromEntries(formData.entries());

            const response = await fetch('/api/v1/commission-payments/create-manual', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('پرداخت دستی با موفقیت ایجاد شد');
                this.closeManualPaymentModal();
                this.loadPayments();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error creating manual payment:', error);
            this.showError('خطا در ایجاد پرداخت دستی');
        }
    }

    async processPayment(paymentId) {
        try {
            const response = await fetch('/api/v1/commission-payments/process', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ payment_id: paymentId })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('پرداخت با موفقیت پردازش شد');
                this.closePaymentActionsModal();
                this.loadPayments();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            this.showError('خطا در پردازش پرداخت');
        }
    }

    async markAsPaid(paymentId) {
        try {
            const response = await fetch('/api/v1/commission-payments/mark-paid', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ payment_id: paymentId })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('پرداخت به عنوان پرداخت شده علامت‌گذاری شد');
                this.closePaymentActionsModal();
                this.loadPayments();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error marking payment as paid:', error);
            this.showError('خطا در علامت‌گذاری پرداخت');
        }
    }

    async markAsFailed(paymentId) {
        const reason = prompt('دلیل ناموفق بودن پرداخت:');
        if (!reason) return;

        try {
            const response = await fetch('/api/v1/commission-payments/mark-failed', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ payment_id: paymentId, reason: reason })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('پرداخت به عنوان ناموفق علامت‌گذاری شد');
                this.closePaymentActionsModal();
                this.loadPayments();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error marking payment as failed:', error);
            this.showError('خطا در علامت‌گذاری پرداخت');
        }
    }

    async bulkProcessPayments() {
        if (this.selectedPayments.size === 0) {
            this.showError('لطفاً حداقل یک پرداخت را انتخاب کنید');
            return;
        }

        if (!confirm(`آیا مطمئن هستید که می‌خواهید ${this.selectedPayments.size} پرداخت را پردازش کنید؟`)) {
            return;
        }

        try {
            const response = await fetch('/api/v1/commission-payments/bulk-process', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ payment_ids: Array.from(this.selectedPayments) })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(`${result.data.processed_count} پرداخت با موفقیت پردازش شد`);
                this.selectedPayments.clear();
                this.loadPayments();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error bulk processing payments:', error);
            this.showError('خطا در پردازش دسته‌ای پرداخت‌ها');
        }
    }

    applyFilters() {
        this.filters = {
            status: document.getElementById('status-filter').value,
            payment_type: document.getElementById('payment-type-filter').value,
            date_from: document.getElementById('date-from-filter').value
        };

        this.currentPage = 1;
        this.loadPayments();
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="payment_ids"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            if (checked) {
                this.selectedPayments.add(parseInt(checkbox.value));
            } else {
                this.selectedPayments.delete(parseInt(checkbox.value));
            }
        });
    }

    togglePaymentSelection(paymentId, checked) {
        if (checked) {
            this.selectedPayments.add(paymentId);
        } else {
            this.selectedPayments.delete(paymentId);
        }
    }

    updateStatistics(stats) {
        document.getElementById('pending-payments').textContent = this.formatNumber(stats.pending_payments);
        document.getElementById('processing-payments').textContent = this.formatNumber(stats.processing_payments);
        document.getElementById('paid-payments').textContent = this.formatNumber(stats.paid_payments);
        document.getElementById('total-amount').textContent = this.formatNumber(stats.total_amount_paid);
    }

    updatePaymentsTable(payments) {
        const tbody = document.getElementById('payments-table-body');
        tbody.innerHTML = '';

        if (payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        هیچ پرداختی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        payments.forEach(payment => {
            const row = this.createPaymentRow(payment);
            tbody.appendChild(row);
        });
    }

    createPaymentRow(payment) {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" name="payment_ids" value="${payment.id}" 
                       onchange="togglePaymentSelection(${payment.id}, this.checked)"
                       class="rounded border-gray-300">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${payment.partner_name}</div>
                <div class="text-sm text-gray-500">${payment.partner_email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${payment.formatted_amount}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${this.getPaymentTypeColor(payment.payment_type)}">
                    ${this.getPaymentTypeText(payment.payment_type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${payment.status_color}-100 text-${payment.status_color}-800">
                    ${payment.status_text}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${this.formatDate(payment.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="openPaymentActions(${payment.id})" 
                        class="text-blue-600 hover:text-blue-900">
                    عملیات
                </button>
            </td>
        `;
        
        return tr;
    }

    updatePartnersSelect(partners) {
        const select = document.querySelector('select[name="partner_id"]');
        select.innerHTML = '<option value="">انتخاب شریک</option>';
        
        partners.forEach(partner => {
            const option = document.createElement('option');
            option.value = partner.id;
            option.textContent = `${partner.name} (${this.getPartnerTypeText(partner.type)})`;
            select.appendChild(option);
        });
    }

    getPaymentTypeText(type) {
        const types = {
            'coupon_commission': 'کمیسیون کوپن',
            'referral_commission': 'کمیسیون ارجاع',
            'manual': 'دستی'
        };
        return types[type] || type;
    }

    getPaymentTypeColor(type) {
        const colors = {
            'coupon_commission': 'bg-blue-100 text-blue-800',
            'referral_commission': 'bg-green-100 text-green-800',
            'manual': 'bg-gray-100 text-gray-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }

    getPartnerTypeText(type) {
        const types = {
            'influencer': 'اینفلوئنسر',
            'teacher': 'معلم',
            'partner': 'شریک',
            'promotional': 'تبلیغاتی'
        };
        return types[type] || type;
    }

    openManualPaymentModal() {
        document.getElementById('manual-payment-modal').classList.remove('hidden');
    }

    closeManualPaymentModal() {
        document.getElementById('manual-payment-modal').classList.add('hidden');
        document.getElementById('manual-payment-form').reset();
    }

    openPaymentActions(paymentId) {
        this.currentPayment = paymentId;
        // Load payment details and show modal
        document.getElementById('payment-actions-modal').classList.remove('hidden');
    }

    closePaymentActionsModal() {
        document.getElementById('payment-actions-modal').classList.add('hidden');
        this.currentPayment = null;
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('fa-IR');
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
function openManualPaymentModal() {
    window.paymentManager.openManualPaymentModal();
}

function closeManualPaymentModal() {
    window.paymentManager.closeManualPaymentModal();
}

function bulkProcessPayments() {
    window.paymentManager.bulkProcessPayments();
}

function openPaymentActions(paymentId) {
    window.paymentManager.openPaymentActions(paymentId);
}

function closePaymentActionsModal() {
    window.paymentManager.closePaymentActionsModal();
}

function processPayment() {
    window.paymentManager.processPayment(window.paymentManager.currentPayment);
}

function markAsPaid() {
    window.paymentManager.markAsPaid(window.paymentManager.currentPayment);
}

function markAsFailed() {
    window.paymentManager.markAsFailed(window.paymentManager.currentPayment);
}

function togglePaymentSelection(paymentId, checked) {
    window.paymentManager.togglePaymentSelection(paymentId, checked);
}

function applyFilters() {
    window.paymentManager.applyFilters();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.paymentManager = new AdminCommissionPayments();
});
