@extends('admin.layouts.app')

@section('title', 'داشبورد شرکا')
@section('page-title', 'داشبورد شرکا')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'داشبورد شرکا',
        'subtitle' => 'آمار و تحلیل عملکرد شرکای تجاری',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
        'iconBg' => 'bg-green-100',
        'iconColor' => 'text-green-600',
        'actions' => '<div class="flex space-x-2 space-x-reverse">
            <select id="dateRange" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="7" ' . ($dateRange == 7 ? 'selected' : '') . '>7 روز گذشته</option>
                <option value="30" ' . ($dateRange == 30 ? 'selected' : '') . '>30 روز گذشته</option>
                <option value="90" ' . ($dateRange == 90 ? 'selected' : '') . '>90 روز گذشته</option>
            </select>
            <button onclick="exportData()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                خروجی
            </button>
        </div>'
    ])

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل شرکا</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_partners']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">شرکای فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['active_partners']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کمیسیون پرداخت شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_commission_paid']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">شرکای تأیید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['verified_partners']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Partner Types Chart -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع انواع شرکا</h3>
            <div id="partnerTypesChart" class="h-64"></div>
        </div>

        <!-- Commission Trends -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">روند کمیسیون‌ها</h3>
            <div id="commissionTrendsChart" class="h-64"></div>
        </div>
    </div>

    <!-- Engagement Metrics -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">معیارهای تعامل</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($engagementMetrics['avg_campaigns_per_partner'], 1) }}</div>
                <div class="text-sm text-gray-600">میانگین کمپین per شریک</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $engagementMetrics['conversion_rate'] }}%</div>
                <div class="text-sm text-gray-600">نرخ تبدیل</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ $engagementMetrics['retention_rate'] }}%</div>
                <div class="text-sm text-gray-600">نرخ حفظ</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ number_format($engagementMetrics['avg_commission_per_partner']) }}</div>
                <div class="text-sm text-gray-600">میانگین کمیسیون per شریک</div>
            </div>
        </div>
    </div>

    <!-- Top Partners and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performing Partners -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">شرکای برتر</h3>
            <div class="space-y-4">
                @foreach($topPartners as $partner)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ $partner->name }}</h4>
                        <p class="text-sm text-gray-600">{{ ucfirst($partner->type) }}</p>
                    </div>
                    <div class="text-left">
                        <div class="text-sm font-medium text-gray-900">{{ number_format($partner->total_earnings ?? 0) }} تومان</div>
                        <div class="text-xs text-gray-500">{{ $partner->campaigns_count ?? 0 }} کمپین</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Partners -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">شرکای اخیر</h3>
            <div class="space-y-4">
                @foreach($recentPartners as $partner)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ $partner->name }}</h4>
                        <p class="text-sm text-gray-600">{{ $partner->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="text-left">
                        <div class="text-sm font-medium text-gray-900">{{ ucfirst($partner->type) }}</div>
                        <div class="text-xs text-gray-500">
                            <span class="px-2 py-1 rounded-full text-xs {{ $partner->status == 'active' ? 'bg-green-100 text-green-800' : ($partner->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $partner->status == 'active' ? 'فعال' : ($partner->status == 'pending' ? 'در انتظار' : 'غیرفعال') }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Monthly Performance -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">عملکرد ماهانه</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ماه</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شرکای جدید</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کل کمیسیون</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کمپین‌های فعال</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($monthlyPerformance as $month)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $month['month_name'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($month['new_partners']) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($month['total_commissions']) }} تومان</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($month['active_campaigns']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Partner Types Chart
const partnerTypesCtx = document.getElementById('partnerTypesChart').getContext('2d');
new Chart(partnerTypesCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($partnerTypes->toArray())) !!},
        datasets: [{
            data: {!! json_encode(array_values($partnerTypes->toArray())) !!},
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

// Commission Trends Chart
const commissionTrendsCtx = document.getElementById('commissionTrendsChart').getContext('2d');
new Chart(commissionTrendsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($commissionTrends->pluck('date')) !!},
        datasets: [{
            label: 'کمیسیون (تومان)',
            data: {!! json_encode($commissionTrends->pluck('total_amount')) !!},
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
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

// Date range change handler
document.getElementById('dateRange').addEventListener('change', function() {
    const dateRange = this.value;
    window.location.href = `{{ route('admin.dashboards.partners') }}?date_range=${dateRange}`;
});

// Export function
function exportData() {
    alert('قابلیت خروجی در حال توسعه است');
}
</script>
@endsection
