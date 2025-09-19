class AdminAffiliatePartners {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.filters = {
            partnerType: '',
            status: '',
            startDate: ''
        };
        this.init();
    }

    init() {
        this.loadPartners();
        this.bindEvents();
    }

    bindEvents() {
        // Add partner button
        document.getElementById('add-partner-btn')?.addEventListener('click', () => {
            this.openAddPartnerModal();
        });

        // Apply filters
        document.getElementById('apply-filters')?.addEventListener('click', () => {
            this.applyFilters();
        });

        // Pagination
        document.getElementById('prev-page')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadPartners();
            }
        });

        document.getElementById('next-page')?.addEventListener('click', () => {
            this.currentPage++;
            this.loadPartners();
        });

        // Filter inputs
        document.getElementById('partner-type-filter')?.addEventListener('change', (e) => {
            this.filters.partnerType = e.target.value;
        });

        document.getElementById('status-filter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
        });

        document.getElementById('start-date-filter')?.addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
        });

        // Select all checkbox
        document.getElementById('select-all')?.addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        // Modal events
        document.getElementById('close-add-partner-modal')?.addEventListener('click', () => {
            this.closeAddPartnerModal();
        });

        document.getElementById('cancel-add-partner')?.addEventListener('click', () => {
            this.closeAddPartnerModal();
        });

        document.getElementById('close-partner-detail-modal')?.addEventListener('click', () => {
            this.closePartnerDetailModal();
        });

        // Close modals on outside click
        document.getElementById('add-partner-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'add-partner-modal') {
                this.closeAddPartnerModal();
            }
        });

        document.getElementById('partner-detail-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'partner-detail-modal') {
                this.closePartnerDetailModal();
            }
        });

        // Add partner form
        document.getElementById('add-partner-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitAddPartner();
        });
    }

    async loadPartners() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('partners-table-body');

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...this.filters
            });

            const response = await fetch(`/api/admin/affiliate/partners?${params}`, {
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
                this.updatePartnersTable(data.data.partners);
                this.updatePaginationInfo(data.data);
            } else {
                this.showError('خطا در بارگذاری شرکا');
            }
        } catch (error) {
            console.error('Error loading partners:', error);
            this.showError('خطا در بارگذاری شرکا');
        } finally {
            this.loading = false;
            this.hideLoading('partners-table-body');
        }
    }

    applyFilters() {
        this.currentPage = 1;
        this.loadPartners();
    }

    updatePartnersTable(partners) {
        const tbody = document.getElementById('partners-table-body');
        
        if (partners.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        هیچ شریکی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        
        partners.forEach(partner => {
            const row = this.createPartnerRow(partner);
            tbody.appendChild(row);
        });
    }

    updatePaginationInfo(data) {
        document.getElementById('showing-count').textContent = data.partners.length;
        document.getElementById('total-count').textContent = data.total || 0;
        
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        prevBtn.disabled = this.currentPage <= 1;
        nextBtn.disabled = data.partners.length < 20;
    }

    createPartnerRow(partner) {
        const row = document.createElement('tr');
        
        const statusClass = this.getStatusClass(partner.status);
        const typeClass = this.getTypeClass(partner.partner_type);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="partner-checkbox rounded border-gray-300" data-partner-id="${partner.id}">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">${partner.name.charAt(0)}</span>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${partner.name}</div>
                        <div class="text-sm text-gray-500">${partner.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${typeClass}">
                    ${this.getTypeText(partner.partner_type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                    ${this.getStatusText(partner.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-green-600">${this.formatCurrency(partner.total_commission || 0)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-blue-600">${this.formatCurrency(partner.total_sales || 0)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatDate(partner.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="adminAffiliatePartners.showPartnerDetail(${partner.id})" class="text-blue-600 hover:text-blue-900">
                        مشاهده
                    </button>
                    <button onclick="adminAffiliatePartners.editPartner(${partner.id})" class="text-green-600 hover:text-green-900">
                        ویرایش
                    </button>
                    <button onclick="adminAffiliatePartners.togglePartnerStatus(${partner.id})" class="text-yellow-600 hover:text-yellow-900">
                        ${partner.status === 'active' ? 'معلق' : 'فعال'}
                    </button>
                </div>
            </td>
        `;
        
        return row;
    }

    openAddPartnerModal() {
        document.getElementById('add-partner-modal').classList.remove('hidden');
    }

    closeAddPartnerModal() {
        document.getElementById('add-partner-modal').classList.add('hidden');
        document.getElementById('add-partner-form').reset();
    }

    async submitAddPartner() {
        const formData = {
            name: document.getElementById('partner-name').value,
            email: document.getElementById('partner-email').value,
            phone: document.getElementById('partner-phone').value,
            partner_type: document.getElementById('partner-type').value,
            description: document.getElementById('partner-description').value
        };

        try {
            const response = await fetch('/api/admin/affiliate/partners', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error('Failed to add partner');
            }

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('شریک با موفقیت افزوده شد');
                this.closeAddPartnerModal();
                this.loadPartners();
            } else {
                this.showError(data.message || 'خطا در افزودن شریک');
            }
        } catch (error) {
            console.error('Error adding partner:', error);
            this.showError('خطا در افزودن شریک');
        }
    }

    async showPartnerDetail(partnerId) {
        try {
            const response = await fetch(`/api/admin/affiliate/partners/${partnerId}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load partner detail');
            }

            const data = await response.json();
            
            if (data.success) {
                this.displayPartnerDetail(data.data);
            } else {
                this.showError('خطا در بارگذاری جزئیات شریک');
            }
        } catch (error) {
            console.error('Error loading partner detail:', error);
            this.showError('خطا در بارگذاری جزئیات شریک');
        }
    }

    displayPartnerDetail(partner) {
        const modal = document.getElementById('partner-detail-modal');
        const content = document.getElementById('partner-detail-content');
        
        content.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات شخصی</h4>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">نام:</span>
                            <span class="text-sm text-gray-900 ml-2">${partner.name}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">ایمیل:</span>
                            <span class="text-sm text-gray-900 ml-2">${partner.email}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">تلفن:</span>
                            <span class="text-sm text-gray-900 ml-2">${partner.phone || '-'}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">نوع:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.getTypeText(partner.partner_type)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">وضعیت:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.getStatusText(partner.status)}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">آمار عملکرد</h4>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">کل فروش:</span>
                            <span class="text-sm text-blue-600 ml-2">${this.formatCurrency(partner.total_sales || 0)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">کل کمیسیون:</span>
                            <span class="text-sm text-green-600 ml-2">${this.formatCurrency(partner.total_commission || 0)}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">تعداد ارجاعات:</span>
                            <span class="text-sm text-gray-900 ml-2">${partner.total_referrals || 0}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">تاریخ عضویت:</span>
                            <span class="text-sm text-gray-900 ml-2">${this.formatDate(partner.created_at)}</span>
                        </div>
                    </div>
                </div>
            </div>
            ${partner.description ? `
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">توضیحات</h4>
                    <p class="text-sm text-gray-700">${partner.description}</p>
                </div>
            ` : ''}
        `;
        
        modal.classList.remove('hidden');
    }

    closePartnerDetailModal() {
        document.getElementById('partner-detail-modal').classList.add('hidden');
    }

    async editPartner(partnerId) {
        // Implement edit functionality
        this.showError('قابلیت ویرایش در حال توسعه است');
    }

    async togglePartnerStatus(partnerId) {
        if (confirm('آیا مطمئن هستید که می‌خواهید وضعیت این شریک را تغییر دهید؟')) {
            try {
                const response = await fetch(`/api/admin/affiliate/partners/${partnerId}/toggle-status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to toggle partner status');
                }

                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess('وضعیت شریک با موفقیت تغییر کرد');
                    this.loadPartners();
                } else {
                    this.showError(data.message || 'خطا در تغییر وضعیت شریک');
                }
            } catch (error) {
                console.error('Error toggling partner status:', error);
                this.showError('خطا در تغییر وضعیت شریک');
            }
        }
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.partner-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    getStatusText(status) {
        const statuses = {
            'pending': 'در انتظار',
            'active': 'فعال',
            'suspended': 'معلق',
            'rejected': 'رد شده'
        };
        return statuses[status] || status;
    }

    getStatusClass(status) {
        const classes = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'active': 'bg-green-100 text-green-800',
            'suspended': 'bg-red-100 text-red-800',
            'rejected': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    getTypeText(type) {
        const types = {
            'teacher': 'معلم/مربی',
            'influencer': 'اینفلوئنسر',
            'school': 'مدرسه',
            'corporate': 'شرکتی',
            'individual': 'فردی'
        };
        return types[type] || type;
    }

    getTypeClass(type) {
        const classes = {
            'teacher': 'bg-blue-100 text-blue-800',
            'influencer': 'bg-purple-100 text-purple-800',
            'school': 'bg-green-100 text-green-800',
            'corporate': 'bg-yellow-100 text-yellow-800',
            'individual': 'bg-gray-100 text-gray-800'
        };
        return classes[type] || 'bg-gray-100 text-gray-800';
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
                    <td colspan="8" class="px-6 py-4 text-center">
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
    window.adminAffiliatePartners = new AdminAffiliatePartners();
});
