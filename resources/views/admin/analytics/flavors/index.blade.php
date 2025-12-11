@extends('admin.layouts.app')

@section('title', 'آمار و تحلیل پلتفرم‌ها')
@section('page-title', 'آمار و تحلیل پلتفرم‌ها')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'آمار و تحلیل پلتفرم‌ها',
        'subtitle' => 'تحلیل جامع عملکرد هر پلتفرم (وب‌سایت، کافه‌بازار، مایکت)',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
        'iconBg' => 'bg-blue-100',
        'iconColor' => 'text-blue-600',
    ])

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Separate Analytics -->
        <a href="{{ route('admin.analytics.flavors.separate') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-lg font-medium text-gray-900">تحلیل جداگانه</h3>
                    <p class="text-sm text-gray-500">مشاهده آمار هر پلتفرم به صورت جداگانه</p>
                </div>
            </div>
        </a>

        <!-- Combined Analytics -->
        <a href="{{ route('admin.analytics.flavors.combined') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-lg font-medium text-gray-900">تحلیل ترکیبی</h3>
                    <p class="text-sm text-gray-500">مقایسه آمار تمام پلتفرم‌ها</p>
                </div>
            </div>
        </a>

        <!-- Comprehensive Analytics -->
        <a href="{{ route('admin.analytics.flavors.comprehensive') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-lg font-medium text-gray-900">تحلیل جامع</h3>
                    <p class="text-sm text-gray-500">تحلیل کامل و پیشرفته تمام پلتفرم‌ها</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">آمار سریع (30 روز گذشته)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $startDate = \Carbon\Carbon::now()->subDays(30);
                $endDate = \Carbon\Carbon::now();
                $flavors = ['website', 'cafebazaar', 'myket'];
                $quickStats = [];
                foreach ($flavors as $flavor) {
                    $quickStats[$flavor] = [
                        'subscriptions' => \App\Models\Subscription::where('billing_platform', $flavor)
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->count(),
                        'revenue' => \App\Models\Payment::whereHas('subscription', function($q) use ($flavor) {
                                $q->where('billing_platform', $flavor);
                            })
                            ->where('status', 'completed')
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->sum('amount'),
                    ];
                }
            @endphp

            @foreach(['website' => 'وب‌سایت', 'cafebazaar' => 'کافه‌بازار', 'myket' => 'مایکت'] as $flavor => $label)
            <div class="p-4 border border-gray-200 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $label }}</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">اشتراک‌ها:</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($quickStats[$flavor]['subscriptions']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">درآمد:</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($quickStats[$flavor]['revenue']) }} تومان</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

