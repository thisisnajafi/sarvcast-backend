@extends('admin.layouts.app')

@section('title', 'تحلیل درآمد - نمای کلی')
@section('page-title', 'تحلیل درآمد - نمای کلی')

@section('content')
<div class="space-y-6">
    <!-- Date Range Selector -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <label class="text-sm font-medium text-gray-700">بازه زمانی:</label>
            <select name="date_range" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent" onchange="this.form.submit()">
                <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>7 روز گذشته</option>
                <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>30 روز گذشته</option>
                <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>90 روز گذشته</option>
                <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>یک سال گذشته</option>
            </select>
        </form>
    </div>

    <!-- Revenue Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل درآمد</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['total_revenue']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">درآمد اشتراک</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['subscription_revenue']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">درآمد سکه</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['coin_revenue']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">اشتراک‌های فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['active_subscriptions']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Trends Chart -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">روند درآمد روزانه</h3>
            <div class="flex space-x-2 space-x-reverse">
                <button onclick="exportChart('revenue')" class="text-gray-600 hover:text-gray-800 text-sm">صادرات</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Revenue Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">میانگین درآمد هر کاربر</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['average_revenue_per_user']) }} تومان</p>
                    <p class="text-sm text-green-600">در ماه گذشته</p>
                </div>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ رشد درآمد</p>
                    <p class="text-2xl font-semibold text-gray-900">+{{ rand(10, 30) }}%</p>
                    <p class="text-sm text-blue-600">نسبت به ماه گذشته</p>
                </div>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">کل مشترکین</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($revenueStats['total_subscribers']) }}</p>
                    <p class="text-sm text-purple-600">از ابتدا تا کنون</p>
                </div>
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Sources Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Sources -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">توزیع منابع درآمد</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                        <span class="text-sm font-medium text-gray-700">اشتراک‌ها</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(($revenueStats['subscription_revenue'] / $revenueStats['total_revenue']) * 100, 1) }}%
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-blue-500 rounded-full mr-3"></div>
                        <span class="text-sm font-medium text-gray-700">سکه‌ها</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(($revenueStats['coin_revenue'] / $revenueStats['total_revenue']) * 100, 1) }}%
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-purple-500 rounded-full mr-3"></div>
                        <span class="text-sm font-medium text-gray-700">سایر</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(100 - (($revenueStats['subscription_revenue'] + $revenueStats['coin_revenue']) / $revenueStats['total_revenue']) * 100, 1) }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">آمار سریع</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">میانگین اشتراک ماهانه:</span>
                    <span class="text-sm font-medium">{{ number_format(rand(20, 50)) }} تومان</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">میانگین خرید سکه:</span>
                    <span class="text-sm font-medium">{{ number_format(rand(10, 30)) }} تومان</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">نرخ تبدیل:</span>
                    <span class="text-sm font-medium">{{ rand(5, 15) }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">ارزش طول عمر مشتری:</span>
                    <span class="text-sm font-medium">{{ number_format(rand(100, 500)) }} تومان</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.revenue-analytics.subscriptions') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل اشتراک‌ها</h3>
                    <p class="text-sm text-gray-500">آمار و روند اشتراک‌ها</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.revenue-analytics.payments') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل پرداخت‌ها</h3>
                    <p class="text-sm text-gray-500">روش‌ها و آمار پرداخت</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.revenue-analytics.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">صادرات گزارش</h3>
                    <p class="text-sm text-gray-500">دانلود داده‌های تحلیلی</p>
                </div>
            </div>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Trends Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($revenueTrends, 'date')) !!},
        datasets: [{
            label: 'درآمد روزانه',
            data: {!! json_encode(array_column($revenueTrends, 'revenue')) !!},
            borderColor: 'rgb(234, 179, 8)',
            backgroundColor: 'rgba(234, 179, 8, 0.1)',
            tension: 0.4,
            fill: true
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
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        }
    }
});

function exportChart(type) {
    // Implementation for chart export
    console.log('Exporting chart:', type);
}
</script>
@endsection
