@extends('admin.layouts.app')

@section('title', 'آمار و تحلیل اشتراک‌ها')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">آمار و تحلیل اشتراک‌ها</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <!-- Revenue Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Daily Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">درآمد روزانه (30 روز گذشته)</h2>
            <div class="h-64">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>

        <!-- Monthly Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">درآمد ماهانه (12 ماه گذشته)</h2>
            <div class="h-64">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Status Distribution Pie Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">توزیع وضعیت اشتراک‌ها</h2>
            <div class="h-64">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>

        <!-- Plan Popularity Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">محبوبیت پلن‌ها</h2>
            <div class="h-64">
                <canvas id="planPopularityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Subscriptions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین اشتراک‌ها</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کاربر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نوع پلن</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">قیمت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تاریخ ایجاد</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentSubscriptions as $subscription)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center ml-4">
                                    <span class="text-white font-semibold text-sm">{{ substr($subscription->user->first_name ?? 'U', 0, 1) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $subscription->user->first_name }} {{ $subscription->user->last_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ \App\Services\SubscriptionService::PLANS[$subscription->type]['name'] ?? $subscription->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($subscription->status == 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    فعال
                                </span>
                            @elseif($subscription->status == 'expired')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    منقضی
                                </span>
                            @elseif($subscription->status == 'cancelled')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    لغو شده
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                    {{ $subscription->status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($subscription->price) }} تومان
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->created_at, 'Y/m/d H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            هیچ اشتراکی یافت نشد
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">کل درآمد</h3>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($revenueData['daily']->sum('revenue')) }} تومان
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">اشتراک‌های فعال</h3>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $statusDistribution->where('status', 'active')->first()->count ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">میانگین درآمد روزانه</h3>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ number_format($revenueData['daily']->avg('revenue')) }} تومان
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">محبوب‌ترین پلن</h3>
                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                        {{ $planPopularity->first()->type ?? 'نامشخص' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Daily Revenue Chart
const dailyRevenueCtx = document.getElementById('dailyRevenueChart').getContext('2d');
const dailyRevenueChart = new Chart(dailyRevenueCtx, {
    type: 'line',
    data: {
        labels: [
            @foreach($revenueData['daily'] as $data)
                '{{ \App\Helpers\JalaliHelper::formatForDisplay($data->date, "m/d") }}',
            @endforeach
        ],
        datasets: [{
            label: 'درآمد روزانه',
            data: [
                @foreach($revenueData['daily'] as $data)
                    {{ $data->revenue }},
                @endforeach
            ],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Monthly Revenue Chart
const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
const monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
    type: 'bar',
    data: {
        labels: [
            @foreach($revenueData['monthly'] as $data)
                '{{ \App\Helpers\JalaliHelper::formatForDisplay($data->year . "-" . $data->month . "-01", "Y/m") }}',
            @endforeach
        ],
        datasets: [{
            label: 'درآمد ماهانه',
            data: [
                @foreach($revenueData['monthly'] as $data)
                    {{ $data->revenue }},
                @endforeach
            ],
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Distribution Chart
const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
const statusDistributionChart = new Chart(statusDistributionCtx, {
    type: 'doughnut',
    data: {
        labels: [
            @foreach($statusDistribution as $status)
                '{{ $status->status }}',
            @endforeach
        ],
        datasets: [{
            data: [
                @foreach($statusDistribution as $status)
                    {{ $status->count }},
                @endforeach
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',
                'rgba(251, 191, 36, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ],
            borderColor: [
                'rgb(34, 197, 94)',
                'rgb(251, 191, 36)',
                'rgb(239, 68, 68)',
                'rgb(107, 114, 128)'
            ],
            borderWidth: 2
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

// Plan Popularity Chart
const planPopularityCtx = document.getElementById('planPopularityChart').getContext('2d');
const planPopularityChart = new Chart(planPopularityCtx, {
    type: 'pie',
    data: {
        labels: [
            @foreach($planPopularity as $plan)
                '{{ $plan->type }}',
            @endforeach
        ],
        datasets: [{
            data: [
                @foreach($planPopularity as $plan)
                    {{ $plan->count }},
                @endforeach
            ],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(147, 51, 234, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(16, 185, 129, 0.8)'
            ],
            borderColor: [
                'rgb(59, 130, 246)',
                'rgb(147, 51, 234)',
                'rgb(236, 72, 153)',
                'rgb(16, 185, 129)'
            ],
            borderWidth: 2
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
</script>
@endsection
