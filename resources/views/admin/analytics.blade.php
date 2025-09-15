@extends('admin.layouts.app')

@section('title', 'آمار و تحلیل‌ها')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">آمار و تحلیل‌ها</h1>
        <div class="flex space-x-4 space-x-reverse">
            <select id="date_range" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="7">آخرین 7 روز</option>
                <option value="30" selected>آخرین 30 روز</option>
                <option value="90">آخرین 90 روز</option>
                <option value="365">آخرین سال</option>
            </select>
            <button onclick="refreshAnalytics()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                به‌روزرسانی
            </button>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">کل کاربران</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_users'] ?? 0 }}</p>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-green-600 font-medium">+{{ $stats['new_users_this_month'] ?? 0 }} این ماه</span>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">اشتراک‌های فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['active_subscriptions'] ?? 0 }}</p>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-green-600 font-medium">{{ $stats['subscription_rate'] ?? 0 }}% نرخ اشتراک</span>
            </div>
        </div>

        <!-- Total Stories -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">کل داستان‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_stories'] ?? 0 }}</p>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-blue-600 font-medium">{{ $stats['published_stories'] ?? 0 }} منتشر شده</span>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">کل درآمد</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_revenue'] ?? 0) }} تومان</p>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-green-600 font-medium">+{{ number_format($stats['revenue_this_month'] ?? 0) }} این ماه</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- User Growth Chart -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">رشد کاربران</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">درآمد ماهانه</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Top Stories -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">پربازدیدترین داستان‌ها</h3>
            <div class="space-y-3">
                @forelse($top_stories ?? [] as $story)
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $story->title }}</p>
                            <p class="text-sm text-gray-500">{{ $story->play_count }} بازدید</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $story->category->name ?? 'بدون دسته‌بندی' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">هیچ داستانی یافت نشد</p>
                @endforelse
            </div>
        </div>

        <!-- User Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">فعالیت کاربران</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">کاربران فعال امروز</span>
                    <span class="text-sm font-medium">{{ $stats['active_users_today'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">کاربران فعال این هفته</span>
                    <span class="text-sm font-medium">{{ $stats['active_users_this_week'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">میانگین زمان گوش دادن</span>
                    <span class="text-sm font-medium">{{ $stats['avg_listening_time'] ?? 0 }} دقیقه</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">تعداد پخش‌های امروز</span>
                    <span class="text-sm font-medium">{{ $stats['plays_today'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Subscription Analytics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">آمار اشتراک‌ها</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">اشتراک‌های ماهانه</span>
                    <span class="text-sm font-medium">{{ $stats['monthly_subscriptions'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">اشتراک‌های سالانه</span>
                    <span class="text-sm font-medium">{{ $stats['yearly_subscriptions'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">نرخ لغو اشتراک</span>
                    <span class="text-sm font-medium">{{ $stats['cancellation_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">میانگین درآمد هر کاربر</span>
                    <span class="text-sm font-medium">{{ number_format($stats['avg_revenue_per_user'] ?? 0) }} تومان</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">فعالیت‌های اخیر</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">فعالیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">داستان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recent_activities ?? [] as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $activity->user->first_name }} {{ $activity->user->last_name }}
                                </div>
                                <div class="text-sm text-gray-500">{{ $activity->user->phone_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $activity->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $activity->story->title ?? 'نامشخص' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                هیچ فعالیتی یافت نشد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function refreshAnalytics() {
    const dateRange = document.getElementById('date_range').value;
    // Reload page with new date range
    window.location.href = `{{ route('admin.analytics') }}?range=${dateRange}`;
}

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chart_data['user_growth']['labels'] ?? []) !!},
            datasets: [{
                label: 'کاربران جدید',
                data: {!! json_encode($chart_data['user_growth']['data'] ?? []) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
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

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chart_data['revenue']['labels'] ?? []) !!},
            datasets: [{
                label: 'درآمد (تومان)',
                data: {!! json_encode($chart_data['revenue']['data'] ?? []) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgb(34, 197, 94)',
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
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endsection
