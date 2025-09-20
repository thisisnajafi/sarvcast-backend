class AdminCoinAnalytics {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.loadOverview();
        this.loadTransactionTrends();
        this.loadEarningSources();
        this.loadUserDistribution();
        this.loadSpendingPatterns();
        this.loadQuizPerformance();
        this.loadReferralPerformance();
        this.loadTopEarners();
        this.loadSystemHealth();
        this.bindEvents();
    }

    bindEvents() {
        // You can add event listeners here for interactive elements
    }

    async loadOverview() {
        try {
            const response = await fetch('/api/v1/coin-analytics/overview', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load overview');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateOverview(data.data);
            } else {
                this.showError('خطا در بارگذاری آمار کلی');
            }
        } catch (error) {
            console.error('Error loading overview:', error);
            this.showError('خطا در بارگذاری آمار کلی');
        }
    }

    async loadTransactionTrends() {
        try {
            const response = await fetch('/api/v1/coin-analytics/transaction-trends?days=30', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load transaction trends');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createTransactionTrendsChart(data.data);
            }
        } catch (error) {
            console.error('Error loading transaction trends:', error);
        }
    }

    async loadEarningSources() {
        try {
            const response = await fetch('/api/v1/coin-analytics/earning-sources', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load earning sources');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createEarningSourcesChart(data.data);
            }
        } catch (error) {
            console.error('Error loading earning sources:', error);
        }
    }

    async loadUserDistribution() {
        try {
            const response = await fetch('/api/v1/coin-analytics/user-distribution', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load user distribution');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createUserDistributionChart(data.data);
            }
        } catch (error) {
            console.error('Error loading user distribution:', error);
        }
    }

    async loadSpendingPatterns() {
        try {
            const response = await fetch('/api/v1/coin-analytics/spending-patterns', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load spending patterns');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createSpendingPatternsChart(data.data);
            }
        } catch (error) {
            console.error('Error loading spending patterns:', error);
        }
    }

    async loadQuizPerformance() {
        try {
            const response = await fetch('/api/v1/coin-analytics/quiz-performance', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load quiz performance');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateQuizPerformance(data.data);
            }
        } catch (error) {
            console.error('Error loading quiz performance:', error);
        }
    }

    async loadReferralPerformance() {
        try {
            const response = await fetch('/api/v1/coin-analytics/referral-performance', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load referral performance');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateReferralPerformance(data.data);
            }
        } catch (error) {
            console.error('Error loading referral performance:', error);
        }
    }

    async loadTopEarners() {
        try {
            const response = await fetch('/api/v1/coin-analytics/top-earners?limit=10', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load top earners');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateTopEarners(data.data);
            }
        } catch (error) {
            console.error('Error loading top earners:', error);
        }
    }

    async loadSystemHealth() {
        try {
            const response = await fetch('/api/v1/coin-analytics/system-health', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load system health');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateSystemHealth(data.data);
            }
        } catch (error) {
            console.error('Error loading system health:', error);
        }
    }

    updateOverview(overview) {
        document.getElementById('total-users-with-coins').textContent = this.formatNumber(overview.total_users_with_coins);
        document.getElementById('total-coins-circulation').textContent = this.formatNumber(overview.total_coins_in_circulation);
        document.getElementById('coin-velocity').textContent = `${overview.coin_velocity}%`;
        document.getElementById('system-health-score').textContent = overview.system_health_score || 0;
    }

    createTransactionTrendsChart(data) {
        const ctx = document.getElementById('transaction-trends-chart');
        if (!ctx) return;

        const earnedLabels = data.earned_trends.map(item => item.date);
        const earnedData = data.earned_trends.map(item => item.total_amount);
        const spentData = data.spent_trends.map(item => item.total_amount);

        this.charts.transactionTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: earnedLabels,
                datasets: [
                    {
                        label: 'سکه‌های کسب شده',
                        data: earnedData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'سکه‌های خرج شده',
                        data: spentData,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fa-IR');
                            }
                        }
                    }
                }
            }
        });
    }

    createEarningSourcesChart(data) {
        const ctx = document.getElementById('earning-sources-chart');
        if (!ctx) return;

        const labels = data.map(item => this.getSourceTypeText(item.source_type));
        const amounts = data.map(item => item.total_amount);
        const colors = this.generateColors(data.length);

        this.charts.earningSources = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    createUserDistributionChart(data) {
        const ctx = document.getElementById('user-distribution-chart');
        if (!ctx) return;

        const labels = data.map(item => item.coin_range);
        const counts = data.map(item => item.user_count);

        this.charts.userDistribution = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'تعداد کاربران',
                    data: counts,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fa-IR');
                            }
                        }
                    }
                }
            }
        });
    }

    createSpendingPatternsChart(data) {
        const ctx = document.getElementById('spending-patterns-chart');
        if (!ctx) return;

        const labels = data.map(item => `گزینه ${item.redemption_option_id}`);
        const amounts = data.map(item => item.total_amount);

        this.charts.spendingPatterns = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: [
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    updateQuizPerformance(data) {
        const container = document.getElementById('quiz-performance-metrics');
        
        container.innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">${this.formatNumber(data.total_quiz_attempts)}</div>
                    <div class="text-sm text-gray-600">کل آزمون‌ها</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">${data.accuracy_rate}%</div>
                    <div class="text-sm text-gray-600">نرخ دقت</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">${this.formatNumber(data.total_coins_awarded)}</div>
                    <div class="text-sm text-gray-600">سکه اعطا شده</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">${data.average_coins_per_correct_answer}</div>
                    <div class="text-sm text-gray-600">میانگین سکه</div>
                </div>
            </div>
        `;
    }

    updateReferralPerformance(data) {
        const container = document.getElementById('referral-performance-metrics');
        
        container.innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">${this.formatNumber(data.total_referrals)}</div>
                    <div class="text-sm text-gray-600">کل ارجاعات</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">${data.completion_rate}%</div>
                    <div class="text-sm text-gray-600">نرخ تکمیل</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">${this.formatNumber(data.total_coins_awarded)}</div>
                    <div class="text-sm text-gray-600">سکه اعطا شده</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">${data.average_coins_per_referral}</div>
                    <div class="text-sm text-gray-600">میانگین سکه</div>
                </div>
            </div>
        `;
    }

    updateTopEarners(earners) {
        const container = document.getElementById('top-earners-list');
        
        if (earners.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ داده‌ای یافت نشد</div>';
            return;
        }

        container.innerHTML = '';
        
        earners.forEach((earner, index) => {
            const rankClass = this.getRankClass(index + 1);
            const earnerElement = this.createEarnerElement(earner, index + 1, rankClass);
            container.appendChild(earnerElement);
        });
    }

    createEarnerElement(earner, rank, rankClass) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';
        
        div.innerHTML = `
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ${rankClass} mr-3">
                    <span class="text-sm font-bold text-white">${rank}</span>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900">${earner.user_name}</h4>
                    <p class="text-xs text-gray-500">${earner.user_email}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium text-green-600">${this.formatNumber(earner.earned_coins)}</div>
                <div class="text-xs text-gray-500">${this.formatNumber(earner.available_coins)} موجود</div>
            </div>
        `;
        
        return div;
    }

    updateSystemHealth(data) {
        const container = document.getElementById('system-health-details');
        
        container.innerHTML = `
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">${data.coin_velocity}%</div>
                <div class="text-sm text-gray-600">سرعت گردش سکه</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600">${data.user_engagement_rate}%</div>
                <div class="text-sm text-gray-600">نرخ مشارکت کاربران</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600">${data.coin_circulation_ratio}%</div>
                <div class="text-sm text-gray-600">نسبت گردش سکه</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">${data.spending_ratio}%</div>
                <div class="text-sm text-gray-600">نسبت خرج کردن</div>
            </div>
        `;
    }

    getSourceTypeText(sourceType) {
        const types = {
            'quiz_reward': 'پاداش آزمون',
            'referral': 'ارجاع',
            'story_completion': 'تکمیل داستان',
            'bonus': 'پاداش',
            'manual': 'دستی'
        };
        return types[sourceType] || sourceType;
    }

    getRankClass(rank) {
        if (rank === 1) return 'bg-yellow-500';
        if (rank === 2) return 'bg-gray-400';
        if (rank === 3) return 'bg-orange-500';
        return 'bg-blue-500';
    }

    generateColors(count) {
        const colors = [
            'rgb(239, 68, 68)',
            'rgb(245, 158, 11)',
            'rgb(59, 130, 246)',
            'rgb(34, 197, 94)',
            'rgb(168, 85, 247)',
            'rgb(236, 72, 153)',
            'rgb(14, 165, 233)',
            'rgb(20, 184, 166)',
            'rgb(245, 101, 101)',
            'rgb(156, 163, 175)'
        ];
        
        return colors.slice(0, count);
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fa-IR').format(number);
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
    new AdminCoinAnalytics();
});
