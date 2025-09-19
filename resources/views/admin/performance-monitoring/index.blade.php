@extends('admin.layouts.app')

@section('title', 'نظارت بر عملکرد سیستم')
@section('page-title', 'نظارت بر عملکرد')

@section('content')
<div class="space-y-6">
    <!-- System Health Status -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900">وضعیت سلامت سیستم</h2>
            <div class="flex items-center space-x-2 space-x-reverse">
                @php
                    $healthColors = [
                        'healthy' => 'bg-green-100 text-green-800',
                        'warning' => 'bg-yellow-100 text-yellow-800',
                        'critical' => 'bg-red-100 text-red-800',
                    ];
                    $healthLabels = [
                        'healthy' => 'سالم',
                        'warning' => 'هشدار',
                        'critical' => 'بحرانی',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $healthColors[$healthStatus['status']] }}">
                    {{ $healthLabels[$healthStatus['status']] }}
                </span>
                <div class="w-3 h-3 rounded-full {{ $healthStatus['status'] === 'healthy' ? 'bg-green-500' : ($healthStatus['status'] === 'warning' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
            </div>
        </div>
        
        @if(count($healthStatus['issues']) > 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-red-800 mb-2">مشکلات شناسایی شده:</h3>
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($healthStatus['issues'] as $issue)
                <li>• {{ $issue }}</li>
                @endforeach
            </ul>
        </div>
        @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-800">سیستم در وضعیت سالم قرار دارد.</p>
        </div>
        @endif
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">زمان پاسخ</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['average_response_time']) }}ms</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">درصد آپتایم</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['uptime_percentage'], 1) }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کاربران فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['active_users']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">نرخ خطا</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['error_rate'], 2) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Usage -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">استفاده از حافظه</h3>
                <span class="text-sm text-gray-500">{{ $stats['memory_usage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['memory_usage'] }}%"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">استفاده از CPU</h3>
                <span class="text-sm text-gray-500">{{ $stats['cpu_usage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['cpu_usage'] }}%"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">استفاده از دیسک</h3>
                <span class="text-sm text-gray-500">{{ $stats['disk_usage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['disk_usage'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Performance Trends Chart -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">روند عملکرد (24 ساعت گذشته)</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">زمان پاسخ (میلی‌ثانیه)</h3>
                    <div class="h-32 bg-gray-50 rounded-lg flex items-center justify-center">
                        <p class="text-gray-500">نمودار زمان پاسخ</p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">استفاده از منابع (%)</h3>
                    <div class="h-32 bg-gray-50 rounded-lg flex items-center justify-center">
                        <p class="text-gray-500">نمودار استفاده از منابع</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">هشدارهای اخیر</h2>
                <a href="{{ route('admin.performance-monitoring.alerts') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentAlerts->count() > 0)
                <div class="space-y-3">
                    @foreach($recentAlerts as $alert)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                @php
                                    $severityColors = [
                                        'critical' => 'bg-red-100 text-red-800',
                                        'warning' => 'bg-yellow-100 text-yellow-800',
                                        'info' => 'bg-blue-100 text-blue-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $severityColors[$alert->severity] }}">
                                    {{ ucfirst($alert->severity) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $alert->title }}</p>
                                <p class="text-xs text-gray-500">{{ $alert->source }} • {{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            @if($alert->status === 'unresolved')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    حل نشده
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    حل شده
                                </span>
                            @endif
                            <a href="{{ route('admin.performance-monitoring.show-alert', $alert) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">مشاهده</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">هیچ هشداری وجود ندارد</h3>
                    <p class="mt-1 text-sm text-gray-500">سیستم در وضعیت سالم قرار دارد.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('admin.performance-monitoring.statistics') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-sm font-medium text-gray-900">آمار تفصیلی</h3>
                        <p class="text-xs text-gray-500">مشاهده آمار کامل عملکرد</p>
                    </div>
                </a>

                <a href="{{ route('admin.performance-monitoring.alerts') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-sm font-medium text-gray-900">مدیریت هشدارها</h3>
                        <p class="text-xs text-gray-500">مشاهده و مدیریت هشدارها</p>
                    </div>
                </a>

                <a href="{{ route('admin.performance-monitoring.create-alert') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-sm font-medium text-gray-900">ایجاد هشدار</h3>
                        <p class="text-xs text-gray-500">ایجاد هشدار جدید</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh page every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endsection
