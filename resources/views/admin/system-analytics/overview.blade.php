@extends('admin.layouts.app')

@section('title', 'تحلیل سیستم - نمای کلی')
@section('page-title', 'تحلیل سیستم - نمای کلی')

@section('content')
<div class="space-y-6">
    <!-- Date Range Selector -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <label class="text-sm font-medium text-gray-700">بازه زمانی:</label>
            <select name="date_range" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" onchange="this.form.submit()">
                <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>7 روز گذشته</option>
                <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>30 روز گذشته</option>
                <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>90 روز گذشته</option>
                <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>یک سال گذشته</option>
            </select>
        </form>
    </div>

    <!-- System Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">آپتایم سرور</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $systemStats['server_uptime'] }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">زمان پاسخ</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $systemStats['response_time'] }}ms</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">استفاده CPU</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $systemStats['cpu_usage'] }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">استفاده حافظه</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $systemStats['memory_usage'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends Chart -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">روند عملکرد سیستم</h3>
            <div class="flex space-x-2 space-x-reverse">
                <button onclick="exportChart('performance')" class="text-gray-600 hover:text-gray-800 text-sm">صادرات</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>

    <!-- System Resources -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">منابع سیستم</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600">CPU</span>
                        <span class="text-sm font-medium">{{ $systemStats['cpu_usage'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $systemStats['cpu_usage'] }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600">حافظه</span>
                        <span class="text-sm font-medium">{{ $systemStats['memory_usage'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $systemStats['memory_usage'] }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600">دیسک</span>
                        <span class="text-sm font-medium">{{ $systemStats['disk_usage'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $systemStats['disk_usage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">اتصالات</h3>
            </div>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">اتصالات دیتابیس:</span>
                    <span class="text-sm font-medium">{{ $systemStats['database_connections'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">کاربران فعال:</span>
                    <span class="text-sm font-medium">{{ number_format($systemStats['active_users']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">درخواست‌های API:</span>
                    <span class="text-sm font-medium">{{ number_format($systemStats['api_requests']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">میانگین درخواست/ثانیه:</span>
                    <span class="text-sm font-medium">{{ number_format($systemStats['api_requests'] / 86400) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">وضعیت سیستم</h3>
            </div>
            <div class="space-y-3">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full ml-3"></div>
                    <span class="text-sm text-gray-700">سرور اصلی</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full ml-3"></div>
                    <span class="text-sm text-gray-700">دیتابیس</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full ml-3"></div>
                    <span class="text-sm text-gray-700">کش</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full ml-3"></div>
                    <span class="text-sm text-gray-700">ایمیل</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full ml-3"></div>
                    <span class="text-sm text-gray-700">ذخیره‌سازی</span>
                </div>
            </div>
        </div>
    </div>

    <!-- System Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">میانگین زمان پاسخ</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(150, 400) }}ms</p>
                    <p class="text-sm text-green-600">در 24 ساعت گذشته</p>
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
                    <p class="text-sm font-medium text-gray-600">نرخ خطا</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(1, 5) }}%</p>
                    <p class="text-sm text-red-600">درخواست‌های ناموفق</p>
                </div>
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ کش</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(80, 95) }}%</p>
                    <p class="text-sm text-blue-600">هیت کش</p>
                </div>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">درخواست/ثانیه</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(50, 200) }}</p>
                    <p class="text-sm text-purple-600">میانگین بار</p>
                </div>
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.system-analytics.performance') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل عملکرد</h3>
                    <p class="text-sm text-gray-500">آمار دقیق عملکرد سیستم</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.system-analytics.health') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">سلامت سیستم</h3>
                    <p class="text-sm text-gray-500">بررسی وضعیت سرویس‌ها</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.system-analytics.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">صادرات گزارش</h3>
                    <p class="text-sm text-gray-500">دانلود داده‌های تحلیلی</p>
                </div>
            </div>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Trends Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($performanceTrends, 'date')) !!},
        datasets: [{
            label: 'زمان پاسخ (ms)',
            data: {!! json_encode(array_column($performanceTrends, 'response_time')) !!},
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'استفاده CPU (%)',
            data: {!! json_encode(array_column($performanceTrends, 'cpu_usage')) !!},
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'استفاده حافظه (%)',
            data: {!! json_encode(array_column($performanceTrends, 'memory_usage')) !!},
            borderColor: 'rgb(147, 51, 234)',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
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
