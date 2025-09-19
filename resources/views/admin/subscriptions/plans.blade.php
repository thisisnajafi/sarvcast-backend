@extends('admin.layouts.app')

@section('title', 'مدیریت پلن‌های اشتراک')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">مدیریت پلن‌های اشتراک</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <!-- Plans Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @foreach($plans as $type => $plan)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $plan['name'] }}</h3>
                <div class="text-3xl font-bold text-primary mb-2">{{ number_format($plan['price']) }} تومان</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $plan['duration_days'] }} روز</div>
                
                @if(isset($plan['discount']) && $plan['discount'] > 0)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mb-4">
                    {{ $plan['discount'] }}% تخفیف
                </div>
                @endif
                
                <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $plan['description'] }}</div>
                
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">کل اشتراک‌ها</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($planStats[$type]['total_subscriptions']) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">اشتراک‌های فعال</div>
                            <div class="font-semibold text-green-600 dark:text-green-400">{{ number_format($planStats[$type]['active_subscriptions']) }}</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">کل درآمد</div>
                        <div class="font-semibold text-green-600 dark:text-green-400">{{ number_format($planStats[$type]['total_revenue']) }} تومان</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Detailed Plans Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">جزئیات پلن‌ها</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نام پلن</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مدت زمان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">قیمت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تخفیف</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کل اشتراک‌ها</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">اشتراک‌های فعال</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کل درآمد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">درآمد متوسط</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($plans as $type => $plan)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center ml-4">
                                    <span class="text-white font-semibold text-sm">{{ substr($plan['name'], 0, 1) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $plan['name'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $type }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $plan['duration_days'] }} روز
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($plan['price']) }} تومان
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(isset($plan['discount']) && $plan['discount'] > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $plan['discount'] }}%
                                </span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">بدون تخفیف</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($planStats[$type]['total_subscriptions']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($planStats[$type]['active_subscriptions']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($planStats[$type]['total_revenue']) }} تومان
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if($planStats[$type]['total_subscriptions'] > 0)
                                {{ number_format($planStats[$type]['total_revenue'] / $planStats[$type]['total_subscriptions']) }} تومان
                            @else
                                0 تومان
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Plan Performance Chart -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">عملکرد پلن‌ها</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Subscription Count Chart -->
            <div>
                <h3 class="text-md font-medium text-gray-900 dark:text-white mb-4">تعداد اشتراک‌ها</h3>
                <div class="space-y-3">
                    @foreach($plans as $type => $plan)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $plan['name'] }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 ml-3">
                                @php
                                    $maxSubscriptions = max(array_column($planStats, 'total_subscriptions'));
                                    $percentage = $maxSubscriptions > 0 ? ($planStats[$type]['total_subscriptions'] / $maxSubscriptions) * 100 : 0;
                                @endphp
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($planStats[$type]['total_subscriptions']) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Revenue Chart -->
            <div>
                <h3 class="text-md font-medium text-gray-900 dark:text-white mb-4">درآمد</h3>
                <div class="space-y-3">
                    @foreach($plans as $type => $plan)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $plan['name'] }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 ml-3">
                                @php
                                    $maxRevenue = max(array_column($planStats, 'total_revenue'));
                                    $percentage = $maxRevenue > 0 ? ($planStats[$type]['total_revenue'] / $maxRevenue) * 100 : 0;
                                @endphp
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($planStats[$type]['total_revenue']) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Recommendations -->
    <div class="mt-8 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">توصیه‌های بهبود</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">محبوب‌ترین پلن</h3>
                @php
                    $mostPopular = collect($planStats)->sortByDesc('total_subscriptions')->first();
                    $mostPopularType = array_search($mostPopular, $planStats);
                @endphp
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    پلن <strong>{{ $plans[$mostPopularType]['name'] }}</strong> با {{ number_format($mostPopular['total_subscriptions']) }} اشتراک، محبوب‌ترین پلن است.
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">پربازده‌ترین پلن</h3>
                @php
                    $mostRevenue = collect($planStats)->sortByDesc('total_revenue')->first();
                    $mostRevenueType = array_search($mostRevenue, $planStats);
                @endphp
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    پلن <strong>{{ $plans[$mostRevenueType]['name'] }}</strong> با {{ number_format($mostRevenue['total_revenue']) }} تومان درآمد، پربازده‌ترین پلن است.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
