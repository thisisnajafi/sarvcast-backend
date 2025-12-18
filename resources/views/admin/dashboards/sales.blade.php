@extends('admin.layouts.app')

@section('title', 'داشبورد فروش')
@section('page-title', 'داشبورد فروش')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'داشبورد فروش',
        'subtitle' => 'آمار و تحلیل عملکرد فروش و درآمد',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>',
        'iconBg' => 'bg-yellow-100',
        'iconColor' => 'text-yellow-600',
        'actions' => '<div class="flex flex-wrap gap-2 sm:gap-3 space-x-reverse">
            <select id="dateRange" class="w-full sm:w-auto min-w-[120px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                <option value="7" ' . ($dateRange == 7 ? 'selected' : '') . '>7 روز گذشته</option>
                <option value="30" ' . ($dateRange == 30 ? 'selected' : '') . '>30 روز گذشته</option>
                <option value="90" ' . ($dateRange == 90 ? 'selected' : '') . '>90 روز گذشته</option>
            </select>
            <button onclick="exportData()" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 min-h-[44px] bg-green-600 border border-transparent rounded-lg font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                خروجی
            </button>
        </div>'
    ])

    <!-- Revenue Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">
                        کل درآمد
                    </p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">
                        {{ number_format($stats['total_revenue']) }}
                        <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-green-100 text-green-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">
                        درآمد ماهانه
                    </p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">
                        {{ number_format($stats['monthly_revenue']) }}
                        <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">
                        تراکنش‌های موفق
                    </p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">
                        {{ number_format($stats['successful_transactions']) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">
                        میانگین ارزش تراکنش
                    </p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">
                        {{ number_format($stats['avg_transaction_value']) }}
                        <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Statistics -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">آمار اشتراک‌ها</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($subscriptionStats['total_subscriptions']) }}</div>
                <div class="text-sm text-gray-600">کل اشتراک‌ها</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ number_format($subscriptionStats['active_subscriptions']) }}</div>
                <div class="text-sm text-gray-600">اشتراک‌های فعال</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ number_format($subscriptionStats['monthly_subscriptions']) }}</div>
                <div class="text-sm text-gray-600">اشتراک ماهانه</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($subscriptionStats['yearly_subscriptions']) }}</div>
                <div class="text-sm text-gray-600">اشتراک سالانه</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Trends Chart -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">روند درآمد</h3>
            <div id="revenueTrendsChart" class="h-56 sm:h-64 md:h-72"></div>
        </div>

        <!-- Payment Methods Chart -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">روش‌های پرداخت</h3>
            <div id="paymentMethodsChart" class="h-56 sm:h-64 md:h-72"></div>
        </div>
    </div>

    <!-- Monthly Comparison -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-6">مقایسه ماهانه</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg min-w-[8rem]">
                <div class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ number_format($monthlyComparison['current_month']) }}
                    <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                </div>
                <div class="text-xs sm:text-sm text-gray-600">ماه جاری</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg min-w-[8rem]">
                <div class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ number_format($monthlyComparison['previous_month']) }}
                    <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                </div>
                <div class="text-xs sm:text-sm text-gray-600">ماه گذشته</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg min-w-[8rem]">
                <div class="text-xl sm:text-2xl font-bold {{ $monthlyComparison['growth_direction'] == 'up' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $monthlyComparison['growth_rate'] > 0 ? '+' : '' }}{{ $monthlyComparison['growth_rate'] }}%
                </div>
                <div class="text-xs sm:text-sm text-gray-600">نرخ رشد</div>
            </div>
        </div>
    </div>

    <!-- Conversion Metrics -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-6">معیارهای تبدیل</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
            <div class="text-center min-w-[8rem]">
                <div class="text-2xl sm:text-3xl font-bold text-blue-600 break-words">
                    {{ $conversionMetrics['conversion_rate'] }}%
                </div>
                <div class="text-xs sm:text-sm text-gray-600">نرخ تبدیل</div>
            </div>
            <div class="text-center min-w-[8rem]">
                <div class="text-2xl sm:text-3xl font-bold text-green-600 break-words">
                    {{ number_format($conversionMetrics['average_order_value']) }}
                    <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                </div>
                <div class="text-xs sm:text-sm text-gray-600">میانگین ارزش سفارش</div>
            </div>
            <div class="text-center min-w-[8rem]">
                <div class="text-2xl sm:text-3xl font-bold text-purple-600 break-words">
                    {{ number_format($conversionMetrics['customer_lifetime_value']) }}
                    <span class="text-xs sm:text-sm font-normal whitespace-nowrap">تومان</span>
                </div>
                <div class="text-xs sm:text-sm text-gray-600">ارزش طول عمر مشتری</div>
            </div>
            <div class="text-center min-w-[8rem]">
                <div class="text-2xl sm:text-3xl font-bold text-red-600 break-words">
                    {{ $conversionMetrics['churn_rate'] }}%
                </div>
                <div class="text-xs sm:text-sm text-gray-600">نرخ ریزش</div>
            </div>
        </div>
    </div>

    <!-- Top Customers and Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Customers -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">مشتریان برتر</h3>
            <div class="space-y-4">
                @foreach($topCustomers as $customer)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg space-x-3 space-x-reverse">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate" title="{{ $customer->name }}">
                            {{ $customer->name }}
                        </h4>
                        <p class="text-sm text-gray-600 truncate" title="{{ $customer->email }}">
                            {{ $customer->email }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ number_format($customer->total_spent) }} تومان
                        </div>
                        <div class="text-xs text-gray-500 whitespace-nowrap">کل خرید</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">تراکنش‌های اخیر</h3>
            <div class="space-y-4">
                @foreach($recentTransactions as $transaction)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg space-x-3 space-x-reverse">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate" title="{{ $transaction->user->name ?? 'کاربر ناشناس' }}">
                            {{ $transaction->user->name ?? 'کاربر ناشناس' }}
                        </h4>
                        <p class="text-sm text-gray-600 whitespace-nowrap">
                            {{ $transaction->created_at->format('Y/m/d H:i') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ number_format($transaction->amount) }} تومان
                        </div>
                        <div class="text-xs text-gray-500">
                            <span class="px-2 py-1 rounded-full text-xs {{ $transaction->status == 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $transaction->status == 'completed' ? 'موفق' : ($transaction->status == 'pending' ? 'در انتظار' : 'ناموفق') }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Coupon Statistics -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">آمار کوپن‌ها</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($couponStats['total_coupons']) }}</div>
                <div class="text-sm text-gray-600">کل کوپن‌ها</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ number_format($couponStats['active_coupons']) }}</div>
                <div class="text-sm text-gray-600">کوپن‌های فعال</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ number_format($couponStats['total_usage']) }}</div>
                <div class="text-sm text-gray-600">کل استفاده</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($couponStats['total_discount']) }} تومان</div>
                <div class="text-sm text-gray-600">کل تخفیف</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Trends Chart
const revenueTrendsCtx = document.getElementById('revenueTrendsChart').getContext('2d');
new Chart(revenueTrendsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($revenueTrends->pluck('date')) !!},
        datasets: [{
            label: 'درآمد (تومان)',
            data: {!! json_encode($revenueTrends->pluck('total_amount')) !!},
            borderColor: 'rgb(245, 158, 11)',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            tension: 0.4
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
                beginAtZero: true
            }
        }
    }
});

// Payment Methods Chart
const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentMethodsCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($paymentMethods->pluck('payment_method')) !!},
        datasets: [{
            data: {!! json_encode($paymentMethods->pluck('total_amount')) !!},
            backgroundColor: [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'
            ]
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

// Date range change handler
document.getElementById('dateRange').addEventListener('change', function() {
    const dateRange = this.value;
    window.location.href = `{{ route('admin.dashboards.sales') }}?date_range=${dateRange}`;
});

// Export function
function exportData() {
    alert('قابلیت خروجی در حال توسعه است');
}
</script>
@endsection
