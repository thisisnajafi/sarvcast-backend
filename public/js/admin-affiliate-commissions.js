class AdminAffiliateCommissions {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.filters = {
            partnerId: '',
            status: '',
            startDate: '',
            endDate: ''
        };
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadCommissions();
        this.loadPartners();
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
                this.loadCommissions();
            }
        });

        document.getElementById('next-page')?.addEventListener('click', () => {
            this.currentPage++;
            this.loadCommissions();
        });

        // Filter inputs
        document.getElementById('partner-filter')?.addEventListener('change', (e) => {
            this.filters.partnerId = e.target.value;
        });

        document.getElementById('status-filter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
        });

        document.getElementById('start-date-filter')?.addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
        });

        document.getElementById('end-date-filter')?.addEventListener('change', (e) => {
            this.filters.endDate = e.target.value;
        });

        // Select all checkbox
        document.getElementById('select-all')?.addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        // Bulk actions
        document.getElementById('bulk-pay-btn')?.addEventListener('click', () => {
            this.bulkPayCommissions();
        });

        document.getElementById('export-commissions-btn')?.addEventListener('click', () => {
            this.exportCommissions();
        });

        // Modal events
        document.getElementById('close-pay-commission-modal')?.addEventListener('click', () => {
            this.closePayCommissionModal();
        });

        document.getElementById('cancel-pay-commission')?.addEventListener('click', () => {
            this.closePayCommissionModal();
        });

        document.getElementById('close-commission-detail-modal')?.addEventListener('click', () => {
            this.closeCommissionDetailModal();
        });

        // Close modals on outside click
        document.getElementById('pay-commission-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'pay-commission-modal') {
                this.closePayCommissionModal();
            }
        });

        document.getElementById('commission-detail-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'commission-detail-modal') {
                this.closeCommissionDetailModal();
            }
        });

        // Pay commission form
        document.getElementById('pay-commission-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitPayment();
        });
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/admin/affiliate/commission-statistics', {
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

    async loadCommissions() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('commissions-table-body');

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...this.filters
            });

            const response = await fetch(`/api/admin/affiliate/commissions?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load commissions');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateCommissionsTable(data.data.commissions);
                this.updatePaginationInfo(data.data);
            } else {
                this.showError('خطا در بارگذاری کمیسیون‌ها');
            }
        } catch (error) {
            console.error('Error loading commissions:', error);
            this.showError('خطا در بارگذاری کمیسیون‌ها');
        } finally {
            this.loading = false;
            this.hideLoading('commissions-table-body');
        }
    }

    async loadPartners() {
        try {
            const response = await fetch('/api/admin/affiliate/partners', {
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
                this.updatePartnerFilter(data.data.partners);
            }
        } catch (error) {
            console.error('Error loading partners:', error);
        }
    }

    applyFilters() {
        this.currentPage = 1;
        this.loadCommissions();
    }

    updateStatistics(stats) {
        document.getElementById('total-commissions').textContent = this.formatCurrency(stats.total_commissions || 0);
        document.getElementById('pending-commissions').textContent = this.formatCurrency(stats.pending_commissions || 0);
        document.getElementById('paid-commissions').textContent = this.formatCurrency(stats.paid_commissions || 0);
        document.getElementById('average-commission').textContent = this.formatCurrency(stats.average_commission || 0);
    }

    updatePartnerFilter(partners) {
        const select = document.getElementById('partner-filter');
        
        partners.forEach(partner => {
            const option = document.createElement('option');
            option.value = partner.id;
            option.textContent = partner.name;
            select.appendChild(option);
        });
    }

    updateCommissionsTable(commissions) {
        const tbody = document.getElementById('commissions-table-body');
        
        if (commissions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        هیچ کمیسیونی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        
        commissions.forEach(commission => {
            const row = this.createCommissionRow(commission);
            tbody.appendChild(row);
        });
    }

    updatePaginationInfo(data) {
        document.getElementById('showing-count').textContent = data.commissions.length;
        document.getElementById('total-count').textContent = data.total || 0;
        
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        prevBtn.disabled = this.currentPage <= 1;
        nextBtn.disabled = data.commissions.length < 20;
    }

    createCommissionRow(commission) {
        const row = document.createElement('tr');
        
        const statusClass = this.getStatusClass(commission.status);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="commission-checkbox rounded border-gray-300" data-commission-id="${commission.id}">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${commission.partner_name}</div>
                <div class="text-sm text-gray-500">${commission.partner_email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-green-600">${this.formatCurrency(commission.amount)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-blue-600">${commission.percentage}%</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                    ${this.getStatusText(commission.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatDate(commission.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="adminAffiliateCommissions.showCommissionDetail(${commission.id})" class="text-blue-600 hover:text-blue-900">
                        مشاهده
                    </button>
                    ${commission.status === 'pending' ? `
                        <button onclick="adminAffiliateCommissions.payCommission(${commission.id})" class="text-green-600 hover:text-green-900">
                            پرداخت
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        
        return row;
    }

    async showCommissionDetail(commissionId) {
        try {
            const response = await fetch(`/api/admin/affiliate/commissions/${commissionId}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load commission detail');
            }

            const data = await response.json();
            
            if (data.success) {
                this.displayCommissionDetail(data.data);
            } else {
                this.showError('خطا در بارگذاری جزئیات کمیسیون');
            }
        } catch (error) {
            console.error('Error loading commission detail:', error);
            this.showError('خطا در بارگذاری جزئیات کمیسیون');
        }
    }

    displayCommissionDetail(commission) {
        const modal = document.getElementById('commission-detail-modal');
        const content = document.getElementById('commission-detail-content');
        
        content.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات کمیسیون</h4>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">شریک:</span>
                            <span class="text-sm text-gray-900 ml-2">${commission.partner_name}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">مبلغ:</span>
                            <span class="text-sm text-green-600 ml-2">${this.formatCurrency(commission.amount)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">درصد:</span>
                            <span class="text-sm text-blue-600 ml-2">${commission.percentage}%</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">وضعیت:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.getStatusText(commission.status)}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات فروش</h4>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">مبلغ فروش:</span>
                            <span class="text-sm text-blue-600 ml-2">${this.formatCurrency(commission.sale_amount)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">تاریخ فروش:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.formatDate(commission.sale_date)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">تاریخ ایجاد:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.formatDate(commission.created_at)}</span>
                        </div>
                        ${commission.paid_at ? `
                            <div>
                                <span class="text-sm font-medium text-gray-500">تاریخ پرداخت:</span>
                                <span class="text-sm text-gray-900 ml-2">${this.formatDate(commission.paid_at)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            ${commission.notes ? `
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">توضیحات</h4>
                    <p class="text-sm text-gray-700">${commission.notes}</p>
                </div>
            ` : ''}
        `;
        
        modal.classList.remove('hidden');
    }

    closeCommissionDetailModal() {
        document.getElementById('commission-detail-modal').classList.add('hidden');
    }

    payCommission(commissionId) {
        this.currentCommissionId = commissionId;
        document.getElementById('pay-commission-modal').classList.remove('hidden');
    }

    closePayCommissionModal() {
        document.getElementById('pay-commission-modal').classList.add('hidden');
        document.getElementById('pay-commission-form').reset();
        this.currentCommissionId = null;
    }

    async submitPayment() {
        const formData = {
            amount: document.getElementById('payment-amount').value,
            payment_method: document.getElementById('payment-method').value,
            notes: document.getElementById('payment-notes').value
        };

        try {
            const response = await fetch(`/api/admin/affiliate/commissions/${this.currentCommissionId}/pay`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error('Failed to pay commission');
            }

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('کمیسیون با موفقیت پرداخت شد');
                this.closePayCommissionModal();
                this.loadCommissions();
                this.loadStatistics();
            } else {
                this.showError(data.message || 'خطا در پرداخت کمیسیون');
            }
        } catch (error) {
            console.error('Error paying commission:', error);
            this.showError('خطا در پرداخت کمیسیون');
        }
    }

    async bulkPayCommissions() {
        const selectedCommissions = this.getSelectedCommissions();
        
        if (selectedCommissions.length === 0) {
            this.showError('لطفاً کمیسیون‌هایی را برای پرداخت انتخاب کنید');
            return;
        }

        if (confirm(`آیا مطمئن هستید که می‌خواهید ${selectedCommissions.length} کمیسیون را پرداخت کنید؟`)) {
            try {
                const response = await fetch('/api/admin/affiliate/commissions/bulk-pay', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        commission_ids: selectedCommissions
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to bulk pay commissions');
                }

                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess(`${data.data.paid_count} کمیسیون با موفقیت پرداخت شد`);
                    this.loadCommissions();
                    this.loadStatistics();
                } else {
                    this.showError(data.message || 'خطا در پرداخت دسته‌ای کمیسیون‌ها');
                }
            } catch (error) {
                console.error('Error bulk paying commissions:', error);
                this.showError('خطا در پرداخت دسته‌ای کمیسیون‌ها');
            }
        }
    }

    async exportCommissions() {
        try {
            const params = new URLSearchParams(this.filters);
            const response = await fetch(`/api/admin/affiliate/commissions/export?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to export commissions');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `commissions-${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Error exporting commissions:', error);
            this.showError('خطا در صادرات گزارش');
        }
    }

    getSelectedCommissions() {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.dataset.commissionId);
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.commission-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    getStatusText(status) {
        const statuses = {
            'pending': 'در انتظار',
            'paid': 'پرداخت شده',
            'cancelled': 'لغو شده'
        };
        return statuses[status] || status;
    }

    getStatusClass(status) {
        const classes = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'paid': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('fa-IR', {
            style: 'currency',
            currency: 'IRR',
            minimumFractionDigits: 0
        }).format(amount);
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
                    <td colspan="7" class="px-6 py-4 text-center">
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

    showSuccess(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminAffiliateCommissions = new AdminAffiliateCommissions();
});
