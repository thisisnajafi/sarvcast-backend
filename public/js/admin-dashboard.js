class AdminDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.loadDashboardData();
        this.bindEvents();
    }

    bindEvents() {
        // Auto refresh every 5 minutes
        setInterval(() => {
            this.loadDashboardData();
        }, 300000);
    }

    async loadDashboardData() {
        try {
            await Promise.all([
                this.loadUserStatistics(),
                this.loadCoinStatistics(),
                this.loadCouponStatistics(),
                this.loadPaymentStatistics()
            ]);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    async loadUserStatistics() {
        try {
            const response = await fetch('/api/v1/users/statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load user statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateUserStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading user statistics:', error);
        }
    }

    async loadCoinStatistics() {
        try {
            const response = await fetch('/api/v1/coins/global-statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load coin statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateCoinStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading coin statistics:', error);
        }
    }

    async loadCouponStatistics() {
        try {
            const response = await fetch('/api/v1/coupons/global-statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load coupon statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateCouponStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading coupon statistics:', error);
        }
    }

    async loadPaymentStatistics() {
        try {
            const response = await fetch('/api/v1/commission-payments/statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load payment statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updatePaymentStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading payment statistics:', error);
        }
    }

    updateUserStatistics(stats) {
        document.getElementById('total-users').textContent = this.formatNumber(stats.total_users);
    }

    updateCoinStatistics(stats) {
        document.getElementById('total-coins').textContent = this.formatNumber(stats.total_coins_in_circulation);
        document.getElementById('users-with-coins').textContent = this.formatNumber(stats.total_users_with_coins);
        document.getElementById('coins-earned').textContent = this.formatNumber(stats.total_coins_earned);
        document.getElementById('coins-spent').textContent = this.formatNumber(stats.total_coins_spent);
    }

    updateCouponStatistics(stats) {
        document.getElementById('active-coupons').textContent = this.formatNumber(stats.active_coupons);
        document.getElementById('total-coupons').textContent = this.formatNumber(stats.total_coupons);
        document.getElementById('total-usage').textContent = this.formatNumber(stats.total_usage);
        document.getElementById('total-commission').textContent = this.formatNumber(stats.total_commission_paid);
    }

    updatePaymentStatistics(stats) {
        document.getElementById('pending-payments').textContent = this.formatNumber(stats.pending_payments);
        document.getElementById('pending-count').textContent = this.formatNumber(stats.pending_payments);
        document.getElementById('processing-count').textContent = this.formatNumber(stats.processing_payments);
        document.getElementById('paid-count').textContent = this.formatNumber(stats.paid_payments);
        document.getElementById('total-amount').textContent = this.formatNumber(stats.total_amount_paid);
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
});
