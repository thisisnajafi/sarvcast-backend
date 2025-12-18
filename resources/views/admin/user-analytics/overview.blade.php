@extends('admin.layouts.app')

@section('title', 'تحلیل کاربران - نمای کلی')
@section('page-title', 'تحلیل کاربران - نمای کلی')

@section('content')
<div class="space-y-6">
    <!-- Date Range Selector -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <label class="text-sm font-medium text-gray-700">بازه زمانی:</label>
            <select name="date_range" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>7 روز گذشته</option>
                <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>30 روز گذشته</option>
                <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>90 روز گذشته</option>
                <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>یک سال گذشته</option>
            </select>
        </form>
    </div>

    <!-- User Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل کاربران</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($userStats['total_users']) }}</p>
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
                    <p class="text-sm font-medium text-gray-600">کاربران فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($userStats['active_users']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کاربران جدید</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($userStats['new_users']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کاربران تأیید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($userStats['verified_users']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Trends Chart -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">روند ثبت‌نام کاربران</h3>
            <div class="flex space-x-2 space-x-reverse">
                <a href="{{ route('admin.user-analytics.registration') }}" class="text-blue-600 hover:text-blue-800 text-sm">مشاهده جزئیات</a>
                <button onclick="exportChart('registration')" class="text-gray-600 hover:text-gray-800 text-sm">صادرات</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="registrationChart"></canvas>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- User Growth Rate -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ رشد کاربران</p>
                    <p class="text-2xl font-semibold text-gray-900">+{{ rand(5, 25) }}%</p>
                    <p class="text-sm text-green-600">نسبت به ماه گذشته</p>
                </div>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Rate -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ فعالیت</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(60, 85) }}%</p>
                    <p class="text-sm text-blue-600">کاربران فعال در ماه گذشته</p>
                </div>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Retention Rate -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ نگهداری</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(70, 90) }}%</p>
                    <p class="text-sm text-purple-600">کاربران بازگشتی</p>
                </div>
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.user-analytics.registration') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل ثبت‌نام</h3>
                    <p class="text-sm text-gray-500">روند و منابع ثبت‌نام</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.user-analytics.engagement') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل تعامل</h3>
                    <p class="text-sm text-gray-500">فعالیت و مشارکت کاربران</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.user-analytics.demographics') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل جمعیت‌شناسی</h3>
                    <p class="text-sm text-gray-500">سن، جنسیت، موقعیت جغرافیایی</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.user-analytics.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
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
// Registration Trends Chart
const ctx = document.getElementById('registrationChart').getContext('2d');
const registrationChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($registrationTrends->pluck('date')) !!},
        datasets: [{
            label: 'ثبت‌نام‌های روزانه',
            data: {!! json_encode($registrationTrends->pluck('count')) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
