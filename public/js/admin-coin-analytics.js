/**
 * Admin Coin Analytics JavaScript
 * Handles coin analytics in admin panel
 */

class AdminCoinAnalyticsManager extends AdminManager {
    constructor() {
        super();
        this.charts = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadAnalytics();
    }

    setupEventListeners() {
        // Date range filter
        const dateRangeForm = document.getElementById('date-range-form');
        if (dateRangeForm) {
            dateRangeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyDateFilter();
            });
        }

        // Reset filter
        const resetBtn = document.getElementById('reset-filter');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetFilter());
        }

        // Export analytics
        const exportBtn = document.getElementById('export-analytics');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportAnalytics());
        }

        // Refresh data
        const refreshBtn = document.getElementById('refresh-data');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // Chart type toggle
        document.addEventListener('change', (e) => {
            if (e.target.name === 'chart_type') {
                this.updateChartType(e.target.value);
            }
        });
    }

    loadAnalytics() {
        this.showLoading();

        const params = new URLSearchParams(window.location.search);
        
        fetch(`/admin/analytics/coins?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayAnalytics(data.data);
                this.createCharts(data.data);
            } else {
                this.showError(data.message || 'خطا در بارگذاری آمار سکه‌ها');
            }
        })
        .catch(error => {
            console.error('Error loading analytics:', error);
            this.showError('خطا در بارگذاری آمار سکه‌ها');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    displayAnalytics(data) {
        this.updateOverviewCards(data.overview);
        this.updateTopUsers(data.top_users);
        this.updateTransactionSummary(data.transaction_summary);
        this.updateCoinDistribution(data.coin_distribution);
    }

    updateOverviewCards(overview) {
        const cards = {
            'total-coins-in-circulation': overview.total_coins_in_circulation || 0,
            'total-coins-earned': overview.total_coins_earned || 0,
            'total-coins-spent': overview.total_coins_spent || 0,
            'average-coins-per-user': overview.average_coins_per_user || 0,
            'active-users': overview.active_users || 0,
            'coin-transactions-today': overview.coin_transactions_today || 0
        };

        Object.keys(cards).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = cards[id].toLocaleString('fa-IR');
            }
        });
    }

    updateTopUsers(topUsers) {
        const container = document.getElementById('top-users-container');
        if (!container) return;

        if (topUsers.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p>هیچ کاربری یافت نشد</p>
                </div>
            `;
            return;
        }

        const html = topUsers.map((user, index) => this.createTopUserItem(user, index + 1)).join('');
        container.innerHTML = html;
    }

    createTopUserItem(user, position) {
        const positionClass = position <= 3 ? 'text-yellow-500' : 'text-gray-600';
        const positionIcon = position === 1 ? 'fa-crown' : position === 2 ? 'fa-medal' : position === 3 ? 'fa-award' : 'fa-hashtag';

        return `
            <div class="top-user-item bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="position">
                            <i class="fas ${positionIcon} ${positionClass} text-xl"></i>
                        </div>
                        <div class="user-info">
                            <h3 class="font-semibold text-gray-800">${user.name}</h3>
                            <p class="text-sm text-gray-600">${user.email}</p>
                        </div>
                    </div>
                    <div class="user-stats text-right">
                        <div class="total-coins font-bold text-blue-600 text-lg">
                            ${user.total_coins.toLocaleString('fa-IR')}
                        </div>
                        <div class="text-sm text-gray-500">سکه</div>
                        <div class="text-xs text-gray-400 mt-1">
                            ${user.coins_earned} کسب شده | ${user.coins_spent} خرج شده
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    updateTransactionSummary(summary) {
        const elements = {
            'earned-transactions': summary.earned_transactions || 0,
            'spent-transactions': summary.spent_transactions || 0,
            'total-transactions': summary.total_transactions || 0,
            'average-transaction-value': summary.average_transaction_value || 0
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id].toLocaleString('fa-IR');
            }
        });
    }

    updateCoinDistribution(distribution) {
        const container = document.getElementById('coin-distribution-container');
        if (!container) return;

        const ranges = [
            { min: 0, max: 100, label: '0-100 سکه' },
            { min: 101, max: 500, label: '101-500 سکه' },
            { min: 501, max: 1000, label: '501-1000 سکه' },
            { min: 1001, max: 5000, label: '1001-5000 سکه' },
            { min: 5001, max: 10000, label: '5001-10000 سکه' },
            { min: 10001, max: null, label: 'بیش از 10000 سکه' }
        ];

        const html = ranges.map(range => {
            const count = distribution[`${range.min}_${range.max || 'inf'}`] || 0;
            const percentage = distribution.total_users > 0 ? 
                Math.round((count / distribution.total_users) * 100) : 0;

            return `
                <div class="distribution-item flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                    <span class="text-gray-700">${range.label}</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <span class="font-semibold text-gray-800">${count}</span>
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: ${percentage}%"></div>
                        </div>
                        <span class="text-sm text-gray-500 w-8">${percentage}%</span>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    createCharts(data) {
        this.createCoinTrendChart(data.coin_trends);
        this.createTransactionChart(data.transaction_chart);
        this.createEarningSourcesChart(data.earning_sources);
        this.createSpendingCategoriesChart(data.spending_categories);
    }

    createCoinTrendChart(trendData) {
        const ctx = document.getElementById('coin-trend-chart');
        if (!ctx) return;

        const labels = trendData.map(item => new Date(item.date).toLocaleDateString('fa-IR'));
        const earnedData = trendData.map(item => item.coins_earned);
        const spentData = trendData.map(item => item.coins_spent);
        const netData = trendData.map(item => item.net_coins);

        this.charts.coinTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'سکه‌های کسب شده',
                        data: earnedData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'سکه‌های خرج شده',
                        data: spentData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'سکه‌های خالص',
                        data: netData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'روند سکه‌ها در زمان'
                    }
                }
            }
        });
    }

    createTransactionChart(transactionData) {
        const ctx = document.getElementById('transaction-chart');
        if (!ctx) return;

        const labels = transactionData.map(item => new Date(item.date).toLocaleDateString('fa-IR'));
        const transactionCounts = transactionData.map(item => item.transaction_count);

        this.charts.transaction = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'تعداد تراکنش‌ها',
                    data: transactionCounts,
                    backgroundColor: '#8b5cf6',
                    borderColor: '#7c3aed',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'تعداد تراکنش‌های روزانه'
                    }
                }
            }
        });
    }

    createEarningSourcesChart(sourcesData) {
        const ctx = document.getElementById('earning-sources-chart');
        if (!ctx) return;

        const labels = Object.keys(sourcesData);
        const data = Object.values(sourcesData);

        this.charts.earningSources = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#22c55e',
                        '#3b82f6',
                        '#8b5cf6',
                        '#f59e0b',
                        '#ef4444',
                        '#06b6d4'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'منابع کسب سکه'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    createSpendingCategoriesChart(categoriesData) {
        const ctx = document.getElementById('spending-categories-chart');
        if (!ctx) return;

        const labels = Object.keys(categoriesData);
        const data = Object.values(categoriesData);

        this.charts.spendingCategories = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#ef4444',
                        '#f97316',
                        '#eab308',
                        '#22c55e',
                        '#06b6d4',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'دسته‌بندی خرج سکه‌ها'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    applyDateFilter() {
        const formData = new FormData(document.getElementById('date-range-form'));
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');

        const url = new URL(window.location);
        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        if (dateTo) url.searchParams.set('date_to', dateTo);
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    resetFilter() {
        const url = new URL(window.location);
        url.searchParams.delete('date_from');
        url.searchParams.delete('date_to');
        url.searchParams.delete('chart_type');
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    updateChartType(chartType) {
        // Update chart display based on selected type
        const charts = document.querySelectorAll('.chart-container');
        charts.forEach(chart => {
            chart.style.display = 'none';
        });

        const selectedChart = document.getElementById(`${chartType}-chart-container`);
        if (selectedChart) {
            selectedChart.style.display = 'block';
        }
    }

    exportAnalytics() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'true');
        
        window.open(`/admin/analytics/coins/export?${params}`, '_blank');
    }

    refreshData() {
        this.loadAnalytics();
        this.showNotification('داده‌ها به‌روزرسانی شد', 'success');
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

    showError(message) {
        const container = document.getElementById('analytics-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminCoinAnalyticsManager();
});
