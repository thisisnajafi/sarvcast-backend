class AdminReferralAnalytics {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.loadOverview();
        this.loadTrends();
        this.loadSources();
        this.loadFunnelAnalysis();
        this.loadGeographicDistribution();
        this.loadPerformanceByTimeframe();
        this.loadTopReferrers();
        this.loadRevenueAnalysis();
        this.loadSystemHealth();
        this.bindEvents();
    }

    bindEvents() {
        // You can add event listeners here for interactive elements
    }

    async loadOverview() {
        try {
            const response = await fetch('/api/v1/referral-analytics/overview', {
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

    async loadTrends() {
        try {
            const response = await fetch('/api/v1/referral-analytics/trends?days=30', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load trends');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createReferralTrendsChart(data.data);
            }
        } catch (error) {
            console.error('Error loading trends:', error);
        }
    }

    async loadSources() {
        try {
            const response = await fetch('/api/v1/referral-analytics/sources', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load sources');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createReferralSourcesChart(data.data);
            }
        } catch (error) {
            console.error('Error loading sources:', error);
        }
    }

    async loadFunnelAnalysis() {
        try {
            const response = await fetch('/api/v1/referral-analytics/funnel-analysis', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load funnel analysis');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateFunnelAnalysis(data.data);
            }
        } catch (error) {
            console.error('Error loading funnel analysis:', error);
        }
    }

    async loadGeographicDistribution() {
        try {
            const response = await fetch('/api/v1/referral-analytics/geographic-distribution', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load geographic distribution');
            }

            const data = await response.json();
            
            if (data.success) {
                this.createGeographicDistributionChart(data.data);
            }
        } catch (error) {
            console.error('Error loading geographic distribution:', error);
        }
    }

    async loadPerformanceByTimeframe() {
        try {
            const response = await fetch('/api/v1/referral-analytics/performance-by-timeframe', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load performance by timeframe');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updatePerformanceByTimeframe(data.data);
            }
        } catch (error) {
            console.error('Error loading performance by timeframe:', error);
        }
    }

    async loadTopReferrers() {
        try {
            const response = await fetch('/api/v1/referral-analytics/top-referrers?limit=10', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load top referrers');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateTopReferrers(data.data);
            }
        } catch (error) {
            console.error('Error loading top referrers:', error);
        }
    }

    async loadRevenueAnalysis() {
        try {
            const response = await fetch('/api/v1/referral-analytics/revenue-analysis', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load revenue analysis');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateRevenueAnalysis(data.data);
            }
        } catch (error) {
            console.error('Error loading revenue analysis:', error);
        }
    }

    async loadSystemHealth() {
        try {
            const response = await fetch('/api/v1/referral-analytics/system-health', {
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
        document.getElementById('total-referral-codes').textContent = this.formatNumber(overview.total_referral_codes);
        document.getElementById('completed-referrals').textContent = this.formatNumber(overview.completed_referrals);
        document.getElementById('conversion-rate').textContent = `${overview.conversion_rate}%`;
        document.getElementById('total-referral-revenue').textContent = this.formatNumber(overview.total_referral_revenue);
    }

    createReferralTrendsChart(data) {
        const ctx = document.getElementById('referral-trends-chart');
        if (!ctx) return;

        const referralLabels = data.referral_trends.map(item => item.date);
        const referralData = data.referral_trends.map(item => item.referral_count);
        const completionData = data.completion_trends.map(item => item.completion_count);

        this.charts.referralTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: referralLabels,
                datasets: [
                    {
                        label: 'ارجاعات جدید',
                        data: referralData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'ارجاعات تکمیل شده',
                        data: completionData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
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

    createReferralSourcesChart(data) {
        const ctx = document.getElementById('referral-sources-chart');
        if (!ctx) return;

        const labels = data.map(item => this.getSourceText(item.source));
        const counts = data.map(item => item.referral_count);
        const colors = this.generateColors(data.length);

        this.charts.referralSources = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
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

    createGeographicDistributionChart(data) {
        const ctx = document.getElementById('geographic-distribution-chart');
        if (!ctx) return;

        const labels = data.map(item => item.country);
        const counts = data.map(item => item.referral_count);

        this.charts.geographicDistribution = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'تعداد ارجاعات',
                    data: counts,
                    backgroundColor: 'rgba(168, 85, 247, 0.8)',
                    borderColor: 'rgb(168, 85, 247)',
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

    updateFunnelAnalysis(data) {
        const container = document.getElementById('funnel-analysis');
        
        const funnelSteps = [
            { name: 'کدهای ارجاع ایجاد شده', value: data.referral_codes_created, rate: 100 },
            { name: 'کدهای ارجاع استفاده شده', value: data.referral_codes_used, rate: data.code_usage_rate },
            { name: 'ارجاعات آغاز شده', value: data.referrals_initiated, rate: 100 },
            { name: 'ارجاعات تکمیل شده', value: data.referrals_completed, rate: data.completion_rate },
            { name: 'تبدیل به مشتری پولی', value: data.referrals_converted_to_paid, rate: data.paid_conversion_rate },
        ];

        container.innerHTML = '';
        
        funnelSteps.forEach((step, index) => {
            const stepElement = this.createFunnelStep(step, index);
            container.appendChild(stepElement);
        });
    }

    createFunnelStep(step, index) {
        const div = document.createElement('div');
        div.className = 'relative';
        
        const width = Math.max(20, (step.rate / 100) * 100);
        const colorClass = this.getFunnelColorClass(index);
        
        div.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">${step.name}</span>
                <span class="text-sm text-gray-500">${step.rate}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full ${colorClass}" style="width: ${width}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">${this.formatNumber(step.value)}</div>
        `;
        
        return div;
    }

    updatePerformanceByTimeframe(data) {
        const container = document.getElementById('performance-by-timeframe');
        
        const timeframes = [
            { key: 'today', label: 'امروز', icon: '📅' },
            { key: 'this_week', label: 'این هفته', icon: '📊' },
            { key: 'this_month', label: 'این ماه', icon: '📈' },
            { key: 'last_30_days', label: '30 روز گذشته', icon: '⏰' },
        ];

        container.innerHTML = '';
        
        timeframes.forEach(timeframe => {
            const performance = data[timeframe.key];
            const element = this.createTimeframeElement(timeframe, performance);
            container.appendChild(element);
        });
    }

    createTimeframeElement(timeframe, performance) {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-4 text-center';
        
        div.innerHTML = `
            <div class="text-2xl mb-2">${timeframe.icon}</div>
            <h3 class="text-sm font-medium text-gray-900 mb-2">${timeframe.label}</h3>
            <div class="space-y-1">
                <div class="text-lg font-bold text-blue-600">${this.formatNumber(performance.referrals)}</div>
                <div class="text-xs text-gray-500">ارجاعات</div>
                <div class="text-lg font-bold text-green-600">${performance.conversion_rate}%</div>
                <div class="text-xs text-gray-500">نرخ تبدیل</div>
            </div>
        `;
        
        return div;
    }

    updateTopReferrers(referrers) {
        const container = document.getElementById('top-referrers-list');
        
        if (referrers.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ داده‌ای یافت نشد</div>';
            return;
        }

        container.innerHTML = '';
        
        referrers.forEach((referrer, index) => {
            const rankClass = this.getRankClass(index + 1);
            const referrerElement = this.createReferrerElement(referrer, index + 1, rankClass);
            container.appendChild(referrerElement);
        });
    }

    createReferrerElement(referrer, rank, rankClass) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';
        
        div.innerHTML = `
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ${rankClass} mr-3">
                    <span class="text-sm font-bold text-white">${rank}</span>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900">${referrer.user_name}</h4>
                    <p class="text-xs text-gray-500">${referrer.referral_code}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium text-green-600">${this.formatNumber(referrer.total_referrals)}</div>
                <div class="text-xs text-gray-500">${referrer.conversion_rate}% تبدیل</div>
                <div class="text-xs text-yellow-600">${this.formatNumber(referrer.total_coins_earned)} سکه</div>
            </div>
        `;
        
        return div;
    }

    updateRevenueAnalysis(data) {
        const container = document.getElementById('revenue-analysis');
        
        container.innerHTML = `
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">${this.formatNumber(data.total_referral_revenue)}</div>
                <div class="text-sm text-gray-600">کل درآمد ارجاعات</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600">${data.average_revenue_per_referral}</div>
                <div class="text-sm text-gray-600">میانگین درآمد هر ارجاع</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">${this.formatNumber(data.revenue_by_status.completed)}</div>
                <div class="text-sm text-gray-600">درآمد از ارجاعات تکمیل شده</div>
            </div>
        `;
    }

    updateSystemHealth(data) {
        const container = document.getElementById('system-health-details');
        
        container.innerHTML = `
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">${data.active_referral_codes}</div>
                <div class="text-sm text-gray-600">کدهای فعال</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600">${data.referral_velocity}%</div>
                <div class="text-sm text-gray-600">سرعت ارجاع</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600">${data.conversion_rate}%</div>
                <div class="text-sm text-gray-600">نرخ تبدیل</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">${data.system_health_score}</div>
                <div class="text-sm text-gray-600">نمره سلامت سیستم</div>
            </div>
        `;
    }

    getSourceText(source) {
        const sources = {
            'social_media': 'شبکه‌های اجتماعی',
            'email': 'ایمیل',
            'website': 'وب‌سایت',
            'word_of_mouth': 'دهان به دهان',
            'advertisement': 'تبلیغات',
            'other': 'سایر'
        };
        return sources[source] || source;
    }

    getRankClass(rank) {
        if (rank === 1) return 'bg-yellow-500';
        if (rank === 2) return 'bg-gray-400';
        if (rank === 3) return 'bg-orange-500';
        return 'bg-blue-500';
    }

    getFunnelColorClass(index) {
        const colors = [
            'bg-blue-500',
            'bg-green-500',
            'bg-yellow-500',
            'bg-orange-500',
            'bg-red-500'
        ];
        return colors[index] || 'bg-gray-500';
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
    new AdminReferralAnalytics();
});
