@extends('admin.layouts.app')

@section('title', 'آمار و گزارشات تایم‌لاین‌ها')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">آمار و گزارشات تایم‌لاین‌ها</h1>
            <p class="text-gray-600 mt-1">آمار کلی و تحلیل تایم‌لاین‌های تصویری</p>
        </div>
        <a href="{{ route('admin.timelines.index') }}" 
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            بازگشت
        </a>
    </div>

    <!-- Overview Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">کل تایم‌لاین‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_timelines']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">اپیزودهای دارای تایم‌لاین</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_episodes_with_timelines']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">فریم‌های کلیدی</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['key_frames_count']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-500">انواع انتقال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['transition_types']->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transition Types Distribution -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">توزیع انواع انتقال</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($stats['transition_types'] as $transition)
                @php
                    $colors = [
                        'fade' => 'bg-blue-100 text-blue-800',
                        'cut' => 'bg-red-100 text-red-800',
                        'dissolve' => 'bg-purple-100 text-purple-800',
                        'slide' => 'bg-green-100 text-green-800'
                    ];
                    $labels = [
                        'fade' => 'محو شدن',
                        'cut' => 'برش',
                        'dissolve' => 'حل شدن',
                        'slide' => 'لغزش'
                    ];
                @endphp
                <div class="text-center p-4 border border-gray-200 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ $transition->count }}</div>
                    <div class="text-sm text-gray-600">{{ $labels[$transition->transition_type] ?? $transition->transition_type }}</div>
                    <div class="mt-2">
                        <span class="px-2 py-1 text-xs rounded-full {{ $colors[$transition->transition_type] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $transition->transition_type }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Timelines -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">آخرین تایم‌لاین‌های ایجاد شده</h3>
        @if($stats['recent_timelines']->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اپیزود</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">انتقال</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($stats['recent_timelines'] as $timeline)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="relative">
                                        <img src="{{ $timeline->image_url }}" alt="Timeline Image" 
                                             class="w-16 h-12 rounded-lg object-cover border border-gray-200">
                                        @if($timeline->is_key_frame)
                                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium">{{ $timeline->episode->title }}</div>
                                        <div class="text-gray-500">{{ $timeline->episode->story->title }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div>{{ gmdate('i:s', $timeline->start_time) }} - {{ gmdate('i:s', $timeline->end_time) }}</div>
                                        <div class="text-xs text-gray-500">{{ $timeline->end_time - $timeline->start_time }} ثانیه</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $transitionColors = [
                                            'fade' => 'bg-blue-100 text-blue-800',
                                            'cut' => 'bg-red-100 text-red-800',
                                            'dissolve' => 'bg-purple-100 text-purple-800',
                                            'slide' => 'bg-green-100 text-green-800'
                                        ];
                                        $transitionLabels = [
                                            'fade' => 'محو شدن',
                                            'cut' => 'برش',
                                            'dissolve' => 'حل شدن',
                                            'slide' => 'لغزش'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full {{ $transitionColors[$timeline->transition_type] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $transitionLabels[$timeline->transition_type] ?? $timeline->transition_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $timeline->created_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.episodes.timeline.edit', [$timeline->episode, $timeline]) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h4 class="text-lg font-medium text-gray-900 mb-2">هیچ تایم‌لاینی وجود ندارد</h4>
                <p class="text-gray-600">هنوز هیچ تایم‌لاینی ایجاد نشده است.</p>
            </div>
        @endif
    </div>

    <!-- Episodes with Most Timelines -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">اپیزودهای با بیشترین تایم‌لاین</h3>
        @if($stats['episodes_with_most_timelines']->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">رتبه</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اپیزود</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">داستان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد تایم‌لاین</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($stats['episodes_with_most_timelines'] as $index => $episode)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        @if($index < 3)
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold
                                                {{ $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-400' : 'bg-orange-500') }}">
                                                {{ $index + 1 }}
                                            </div>
                                        @else
                                            <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-bold">
                                                {{ $index + 1 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ $episode->title }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $episode->story->title }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                        {{ $episode->image_timelines_count }} تایم‌لاین
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.episodes.timeline.index', $episode) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">مشاهده تایم‌لاین‌ها</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h4 class="text-lg font-medium text-gray-900 mb-2">هیچ اپیزودی با تایم‌لاین وجود ندارد</h4>
                <p class="text-gray-600">هنوز هیچ اپیزودی تایم‌لاین ندارد.</p>
            </div>
        @endif
    </div>
</div>
@endsection
