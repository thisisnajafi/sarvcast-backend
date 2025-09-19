class AdminCouponManagement {
    constructor() {
        this.currentPage = 1;
        this.filters = {};
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadCoupons();
        this.loadPartners();
        this.bindEvents();
    }

    bindEvents() {
        // Form submission
        document.getElementById('create-coupon-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createCoupon();
        });

        // Filter changes
        document.getElementById('partner-type-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('status-filter').addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('search-filter').addEventListener('input', () => {
            this.debounce(() => this.applyFilters(), 500);
        });
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/v1/coupons/global-statistics', {
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

    async loadCoupons() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });

            const response = await fetch(`/api/v1/coupons/all?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load coupons');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateCouponsTable(data.data);
            }
        } catch (error) {
            console.error('Error loading coupons:', error);
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

    async createCoupon() {
        try {
            const formData = new FormData(document.getElementById('create-coupon-form'));
            const data = Object.fromEntries(formData.entries());

            const response = await fetch('/api/v1/coupons/create', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('کد کوپن با موفقیت ایجاد شد');
                this.closeCreateModal();
                this.loadCoupons();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error creating coupon:', error);
            this.showError('خطا در ایجاد کد کوپن');
        }
    }

    async toggleCouponStatus(couponId, currentStatus) {
        try {
            const newStatus = currentStatus ? false : true;
            
            const response = await fetch(`/api/v1/coupons/${couponId}/toggle-status`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_active: newStatus })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('وضعیت کد کوپن با موفقیت تغییر کرد');
                this.loadCoupons();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error toggling coupon status:', error);
            this.showError('خطا در تغییر وضعیت کد کوپن');
        }
    }

    async deleteCoupon(couponId) {
        if (!confirm('آیا مطمئن هستید که می‌خواهید این کد کوپن را حذف کنید؟')) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/coupons/${couponId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('کد کوپن با موفقیت حذف شد');
                this.loadCoupons();
                this.loadStatistics();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error deleting coupon:', error);
            this.showError('خطا در حذف کد کوپن');
        }
    }

    applyFilters() {
        this.filters = {
            partner_type: document.getElementById('partner-type-filter').value,
            status: document.getElementById('status-filter').value,
            search: document.getElementById('search-filter').value
        };

        this.currentPage = 1;
        this.loadCoupons();
    }

    updateStatistics(stats) {
        document.getElementById('total-coupons').textContent = this.formatNumber(stats.total_coupons);
        document.getElementById('active-coupons').textContent = this.formatNumber(stats.active_coupons);
        document.getElementById('total-usage').textContent = this.formatNumber(stats.total_usage);
        document.getElementById('total-commission').textContent = this.formatNumber(stats.total_commission_paid);
    }

    updateCouponsTable(coupons) {
        const tbody = document.getElementById('coupons-table-body');
        tbody.innerHTML = '';

        if (coupons.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        هیچ کد کوپنی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        coupons.forEach(coupon => {
            const row = this.createCouponRow(coupon);
            tbody.appendChild(row);
        });
    }

    createCouponRow(coupon) {
        const tr = document.createElement('tr');
        
        const statusColor = coupon.is_active ? 'green' : 'red';
        const statusText = coupon.is_active ? 'فعال' : 'غیرفعال';
        const usagePercentage = coupon.usage_percentage || 0;
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">${coupon.code}</code>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${coupon.name}</div>
                <div class="text-sm text-gray-500">${coupon.description || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${this.getPartnerTypeColor(coupon.partner_type)}">
                    ${this.getPartnerTypeText(coupon.partner_type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${coupon.discount_value}${coupon.type === 'percentage' ? '%' : ' تومان'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${coupon.usage_count}/${coupon.usage_limit || '∞'}</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${usagePercentage}%"></div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="toggleCouponStatus(${coupon.id}, ${coupon.is_active})" 
                            class="text-blue-600 hover:text-blue-900">
                        ${coupon.is_active ? 'غیرفعال' : 'فعال'}
                    </button>
                    <button onclick="editCoupon(${coupon.id})" 
                            class="text-yellow-600 hover:text-yellow-900">
                        ویرایش
                    </button>
                    <button onclick="deleteCoupon(${coupon.id})" 
                            class="text-red-600 hover:text-red-900">
                        حذف
                    </button>
                </div>
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

    getPartnerTypeText(type) {
        const types = {
            'influencer': 'اینفلوئنسر',
            'teacher': 'معلم',
            'partner': 'شریک',
            'promotional': 'تبلیغاتی'
        };
        return types[type] || type;
    }

    getPartnerTypeColor(type) {
        const colors = {
            'influencer': 'bg-pink-100 text-pink-800',
            'teacher': 'bg-blue-100 text-blue-800',
            'partner': 'bg-green-100 text-green-800',
            'promotional': 'bg-gray-100 text-gray-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }

    openCreateModal() {
        document.getElementById('create-coupon-modal').classList.remove('hidden');
    }

    closeCreateModal() {
        document.getElementById('create-coupon-modal').classList.add('hidden');
        document.getElementById('create-coupon-form').reset();
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
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
function openCreateModal() {
    window.couponManager.openCreateModal();
}

function closeCreateModal() {
    window.couponManager.closeCreateModal();
}

function toggleCouponStatus(couponId, currentStatus) {
    window.couponManager.toggleCouponStatus(couponId, currentStatus);
}

function deleteCoupon(couponId) {
    window.couponManager.deleteCoupon(couponId);
}

function editCoupon(couponId) {
    // Implement edit functionality
    console.log('Edit coupon:', couponId);
}

function applyFilters() {
    window.couponManager.applyFilters();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.couponManager = new AdminCouponManagement();
});
