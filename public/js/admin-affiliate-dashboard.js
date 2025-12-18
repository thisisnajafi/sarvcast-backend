class AdminAffiliateDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadRecentActivity();
        this.loadTopPerformers();
        this.loadCommissionOverview();
        this.bindEvents();
    }

    bindEvents() {
        // You can add event listeners here for interactive elements
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/admin/affiliate/statistics', {
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

    async loadRecentActivity() {
        try {
            const response = await fetch('/api/admin/affiliate/recent-activity', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load recent activity');
            }

            const data = await response.json();

            if (data.success) {
                this.updateRecentActivity(data.data);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }

    async loadTopPerformers() {
        try {
            const response = await fetch('/api/admin/affiliate/top-performers', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load top performers');
            }

            const data = await response.json();

            if (data.success) {
                this.updateTopPerformers(data.data);
            }
        } catch (error) {
            console.error('Error loading top performers:', error);
        }
    }

    async loadCommissionOverview() {
        try {
            const response = await fetch('/api/admin/affiliate/commission-overview', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load commission overview');
            }

            const data = await response.json();

            if (data.success) {
                this.updateCommissionOverview(data.data);
            }
        } catch (error) {
            console.error('Error loading commission overview:', error);
        }
    }

    updateStatistics(stats) {
        document.getElementById('total-partners').textContent = stats.total_partners || 0;
        document.getElementById('total-commissions').textContent = this.formatCurrency(stats.total_commissions || 0);
        document.getElementById('total-sales').textContent = this.formatCurrency(stats.total_sales || 0);
        document.getElementById('average-commission').textContent = `${stats.average_commission || 0}%`;
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');

        if (activities.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ فعالیتی یافت نشد</div>';
            return;
        }

        container.innerHTML = '';

        activities.forEach(activity => {
            const activityElement = this.createActivityElement(activity);
            container.appendChild(activityElement);
        });
    }

    updateTopPerformers(performers) {
        const container = document.getElementById('top-performers');

        if (performers.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ داده‌ای یافت نشد</div>';
            return;
        }

        container.innerHTML = '';

        performers.forEach((performer, index) => {
            const performerElement = this.createPerformerElement(performer, index + 1);
            container.appendChild(performerElement);
        });
    }

    updateCommissionOverview(overview) {
        const pendingContainer = document.getElementById('pending-commissions');
        const paidContainer = document.getElementById('paid-commissions');

        // Update pending commissions
        if (overview.pending && overview.pending.length > 0) {
            pendingContainer.innerHTML = '';
            overview.pending.forEach(commission => {
                const commissionElement = this.createCommissionElement(commission);
                pendingContainer.appendChild(commissionElement);
            });
        } else {
            pendingContainer.innerHTML = '<div class="text-center text-gray-500 py-4">هیچ کمیسیون پرداخت نشده‌ای وجود ندارد</div>';
        }

        // Update paid commissions
        if (overview.paid && overview.paid.length > 0) {
            paidContainer.innerHTML = '';
            overview.paid.forEach(commission => {
                const commissionElement = this.createCommissionElement(commission);
                paidContainer.appendChild(commissionElement);
            });
        } else {
            paidContainer.innerHTML = '<div class="text-center text-gray-500 py-4">هیچ کمیسیون پرداخت شده‌ای وجود ندارد</div>';
        }
    }

    createActivityElement(activity) {
        const div = document.createElement('div');
        div.className = 'flex items-center p-3 bg-gray-50 rounded-lg';

        const iconClass = this.getActivityIconClass(activity.type);

        div.innerHTML = `
            <div class="w-10 h-10 rounded-full flex items-center justify-center ${iconClass} flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate" title="${activity.title}">${activity.title}</h4>
                <p class="text-xs text-gray-500 whitespace-nowrap">${this.formatDate(activity.created_at)}</p>
            </div>
            <div class="text-right flex-shrink-0 ml-3">
                <div class="text-sm font-medium text-blue-600 whitespace-nowrap">${this.formatCurrency(activity.amount || 0)}</div>
                <div class="text-xs text-gray-500 truncate max-w-[8rem]" title="${activity.partner_name || ''}">${activity.partner_name || ''}</div>
            </div>
        `;

        return div;
    }

    createPerformerElement(performer, rank) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';

        const rankClass = this.getRankClass(rank);

        div.innerHTML = `
            <div class="flex items-center min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ${rankClass} mr-3 flex-shrink-0">
                    <span class="text-sm font-bold text-white">${rank}</span>
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate" title="${performer.name}">${performer.name}</h4>
                    <p class="text-xs text-gray-500 truncate" title="${performer.type}">${performer.type}</p>
                </div>
            </div>
            <div class="text-right flex-shrink-0 ml-3">
                <div class="text-sm font-medium text-green-600 whitespace-nowrap">${this.formatCurrency(performer.total_commission || 0)}</div>
                <div class="text-xs text-gray-500 whitespace-nowrap">${performer.total_sales || 0} فروش</div>
            </div>
        `;

        return div;
    }

    createCommissionElement(commission) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg';

        const statusClass = this.getCommissionStatusClass(commission.status);

        div.innerHTML = `
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate" title="${commission.partner_name}">${commission.partner_name}</h4>
                <p class="text-xs text-gray-500 whitespace-nowrap">${this.formatDate(commission.created_at)}</p>
            </div>
            <div class="text-right flex-shrink-0 ml-3">
                <div class="text-sm font-medium text-green-600 whitespace-nowrap">${this.formatCurrency(commission.amount)}</div>
                <div class="text-xs ${statusClass} whitespace-nowrap">${this.getCommissionStatusText(commission.status)}</div>
            </div>
        `;

        return div;
    }

    getActivityIconClass(type) {
        const classes = {
            'commission_earned': 'bg-green-100 text-green-600',
            'partner_registered': 'bg-blue-100 text-blue-600',
            'payment_made': 'bg-yellow-100 text-yellow-600',
            'sale_completed': 'bg-purple-100 text-purple-600'
        };
        return classes[type] || 'bg-gray-100 text-gray-600';
    }

    getRankClass(rank) {
        if (rank === 1) return 'bg-yellow-500';
        if (rank === 2) return 'bg-gray-400';
        if (rank === 3) return 'bg-orange-500';
        return 'bg-blue-500';
    }

    getCommissionStatusClass(status) {
        const classes = {
            'pending': 'text-yellow-600',
            'paid': 'text-green-600',
            'cancelled': 'text-red-600'
        };
        return classes[status] || 'text-gray-600';
    }

    getCommissionStatusText(status) {
        const statuses = {
            'pending': 'در انتظار',
            'paid': 'پرداخت شده',
            'cancelled': 'لغو شده'
        };
        return statuses[status] || status;
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

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminAffiliateDashboard();
});
