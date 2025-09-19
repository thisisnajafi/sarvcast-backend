@extends('admin.layouts.app')

@section('title', 'مشاهده هشدار')
@section('page-title', 'مشاهده هشدار')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده هشدار</h1>
            <p class="text-gray-600">{{ $alert->title }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            @if($alert->status === 'unresolved')
                <form method="POST" action="{{ route('admin.performance-monitoring.resolve-alert', $alert) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        حل کردن
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.performance-monitoring.alerts') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    @php
                        $statusColors = [
                            'unresolved' => 'text-red-600',
                            'resolved' => 'text-green-600'
                        ];
                        $statusLabels = [
                            'unresolved' => 'حل نشده',
                            'resolved' => 'حل شده'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $statusColors[$alert->status] }}">{{ $statusLabels[$alert->status] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">شدت</p>
                    @php
                        $severityLabels = [
                            'critical' => 'بحرانی',
                            'warning' => 'هشدار',
                            'info' => 'اطلاعاتی',
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $severityLabels[$alert->severity] ?? ucfirst($alert->severity) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">منبع</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $alert->source }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">تاریخ ایجاد</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $alert->created_at->format('Y/m/d') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Alert Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Alert Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات هشدار</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">عنوان</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $alert->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شدت</dt>
                            <dd class="mt-1">
                                @php
                                    $severityColors = [
                                        'critical' => 'bg-red-100 text-red-800',
                                        'warning' => 'bg-yellow-100 text-yellow-800',
                                        'info' => 'bg-blue-100 text-blue-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $severityColors[$alert->severity] }}">
                                    {{ $severityLabels[$alert->severity] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">منبع</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $alert->source }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'unresolved' => 'bg-red-100 text-red-800',
                                        'resolved' => 'bg-green-100 text-green-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$alert->status] }}">
                                    {{ $statusLabels[$alert->status] }}
                                </span>
                            </dd>
                        </div>
                        @if($alert->threshold_value && $alert->threshold_operator)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">آستانه</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @php
                                    $operatorLabels = [
                                        '>' => 'بزرگتر از',
                                        '<' => 'کوچکتر از',
                                        '>=' => 'بزرگتر یا مساوی',
                                        '<=' => 'کوچکتر یا مساوی',
                                        '=' => 'مساوی',
                                        '!=' => 'نامساوی'
                                    ];
                                @endphp
                                {{ $operatorLabels[$alert->threshold_operator] ?? $alert->threshold_operator }} {{ $alert->threshold_value }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Message -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">پیام هشدار</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $alert->message }}</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">زمان‌بندی</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">ایجاد هشدار</p>
                                <p class="text-xs text-gray-500">{{ $alert->created_at->format('Y/m/d H:i:s') }}</p>
                            </div>
                        </div>

                        @if($alert->resolved_at)
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">حل شدن هشدار</p>
                                <p class="text-xs text-gray-500">{{ $alert->resolved_at->format('Y/m/d H:i:s') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if($alert->status === 'unresolved')
                        <form method="POST" action="{{ route('admin.performance-monitoring.resolve-alert', $alert) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                حل کردن هشدار
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.performance-monitoring.destroy-alert', $alert) }}" class="w-full" onsubmit="return confirm('آیا از حذف این هشدار اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف هشدار
                        </button>
                    </form>
                </div>
            </div>

            <!-- Alert Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات هشدار</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">شناسه:</span>
                            <span class="text-gray-900">#{{ $alert->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">تاریخ ایجاد:</span>
                            <span class="text-gray-900">{{ $alert->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        @if($alert->resolved_at)
                        <div class="flex justify-between">
                            <span class="text-gray-500">تاریخ حل:</span>
                            <span class="text-gray-900">{{ $alert->resolved_at->format('Y/m/d H:i') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">مدت زمان:</span>
                            <span class="text-gray-900">
                                @if($alert->resolved_at)
                                    {{ $alert->created_at->diffForHumans($alert->resolved_at, true) }}
                                @else
                                    {{ $alert->created_at->diffForHumans() }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Alerts -->
            @if($relatedAlerts && $relatedAlerts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">هشدارهای مرتبط</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($relatedAlerts as $relatedAlert)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $relatedAlert->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $relatedAlert->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $relatedAlert->status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $statusLabels[$relatedAlert->status] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
