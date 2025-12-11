@extends('admin.layouts.app')

@section('title', 'داشبورد داستان‌ها')
@section('page-title', 'داشبورد داستان‌ها')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'داشبورد داستان‌ها',
        'subtitle' => 'آمار و تحلیل عملکرد داستان‌ها',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>',
        'iconBg' => 'bg-blue-100',
        'iconColor' => 'text-blue-600',
        'actions' => '<div class="flex flex-wrap gap-2 sm:gap-3 space-x-reverse">
            <select id="dateRange" class="w-full sm:w-auto min-w-[120px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="7" ' . ($dateRange == 7 ? 'selected' : '') . '>7 روز گذشته</option>
                <option value="30" ' . ($dateRange == 30 ? 'selected' : '') . '>30 روز گذشته</option>
                <option value="90" ' . ($dateRange == 90 ? 'selected' : '') . '>90 روز گذشته</option>
            </select>
            <button onclick="exportData()" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 min-h-[44px] bg-green-600 border border-transparent rounded-lg font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                خروجی
            </button>
        </div>'
    ])

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">کل داستان‌ها</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['total_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-green-100 text-green-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">داستان‌های منتشر شده</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['published_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">کل پخش‌ها</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['total_plays']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">میانگین امتیاز</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['avg_rating'], 1) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Episode Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">کل اپیزودها</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['total_episodes']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-amber-100 text-amber-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">اپیزودهای پولی</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['premium_episodes']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">اپیزودهای رایگان</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['free_episodes']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="p-3 rounded-full bg-teal-100 text-teal-600 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 whitespace-normal leading-snug">اپیزودهای منتشر شده</p>
                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 truncate">{{ number_format($stats['published_episodes']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل نظرات</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_comments']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نظرات تایید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['approved_comments']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">در انتظار تایید</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['pending_comments']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نظرات سنجاق شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['pinned_comments']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Plays Chart -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">پخش‌های روزانه</h3>
            <div id="dailyPlaysChart" class="h-56 sm:h-64 md:h-72"></div>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع دسته‌بندی‌ها</h3>
            <div id="categoryChart" class="h-56 sm:h-64 md:h-72"></div>
        </div>
    </div>

    <!-- Episode Distribution Chart -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع اپیزودها</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="min-h-[12rem] sm:min-h-[14rem] md:min-h-[16rem]">
                <h4 class="text-md font-medium text-gray-700 mb-3">اپیزودها بر اساس نوع</h4>
                <div id="episodePremiumChart" class="h-56 sm:h-64 md:h-72"></div>
            </div>
            <div class="min-h-[12rem] sm:min-h-[14rem] md:min-h-[16rem]">
                <h4 class="text-md font-medium text-gray-700 mb-3">اپیزودها بر اساس وضعیت</h4>
                <div id="episodeStatusChart" class="h-56 sm:h-64 md:h-72"></div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-6">معیارهای عملکرد</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
            <div class="text-center space-y-1 sm:space-y-2 max-w-full">
                <div class="text-2xl sm:text-3xl font-bold text-blue-600 break-words">
                    {{ number_format($performanceMetrics['avg_plays_per_story'], 0) }}
                </div>
                <div class="text-xs sm:text-sm text-gray-600 max-w-full">
                    میانگین پخش per داستان
                </div>
            </div>
            <div class="text-center space-y-1 sm:space-y-2 max-w-full">
                <div class="text-2xl sm:text-3xl font-bold text-green-600 break-words">
                    {{ number_format($performanceMetrics['avg_rating_per_story'], 1) }}
                </div>
                <div class="text-xs sm:text-sm text-gray-600 max-w-full">
                    میانگین امتیاز per داستان
                </div>
            </div>
            <div class="text-center space-y-1 sm:space-y-2 max-w-full">
                <div class="text-2xl sm:text-3xl font-bold text-purple-600 break-words">
                    {{ $performanceMetrics['completion_rate'] }}%
                </div>
                <div class="text-xs sm:text-sm text-gray-600 max-w-full">
                    نرخ تکمیل
                </div>
            </div>
            <div class="text-center space-y-1 sm:space-y-2 max-w-full">
                <div class="text-2xl sm:text-3xl font-bold text-yellow-600 break-words">
                    {{ $performanceMetrics['engagement_rate'] }}%
                </div>
                <div class="text-xs sm:text-sm text-gray-600 max-w-full">
                    نرخ تعامل
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Chart -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">نظرات در طول زمان</h3>
        <div class="h-56 sm:h-64 md:h-72">
            <canvas id="commentsChart"></canvas>
        </div>
    </div>

    <!-- Top Stories and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performing Stories -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">داستان‌های پربازدید</h3>
            <div class="space-y-4">
                @foreach($topStories as $story)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg space-x-3 space-x-reverse">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate" title="{{ $story->title }}">
                            {{ $story->title }}
                        </h4>
                        <p class="text-sm text-gray-600 truncate" title="{{ $story->category->name ?? 'بدون دسته' }}">
                            {{ $story->category->name ?? 'بدون دسته' }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ number_format($story->plays_count) }} پخش
                        </div>
                        <div class="text-xs text-gray-500 whitespace-nowrap">
                            امتیاز: {{ number_format($story->ratings_avg_rating ?? 0, 1) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Stories -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">داستان‌های اخیر</h3>
            <div class="space-y-4">
                @foreach($recentStories as $story)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg space-x-3 space-x-reverse">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate" title="{{ $story->title }}">
                            {{ $story->title }}
                        </h4>
                        <p class="text-sm text-gray-600 whitespace-nowrap">
                            {{ $story->created_at->format('Y/m/d H:i') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ $story->episodes_count ?? 0 }} قسمت
                        </div>
                        <div class="text-xs text-gray-500">
                            <span class="px-2 py-1 rounded-full text-xs {{ $story->status == 'published' ? 'bg-green-100 text-green-800' : ($story->status == 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $story->status == 'published' ? 'منتشر شده' : ($story->status == 'draft' ? 'پیش‌نویس' : ucfirst($story->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Plays Chart
const dailyPlaysCtx = document.getElementById('dailyPlaysChart').getContext('2d');
new Chart(dailyPlaysCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyPlays->pluck('date')) !!},
        datasets: [{
            label: 'پخش‌ها',
            data: {!! json_encode($dailyPlays->pluck('plays')) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
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

// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($categoryStats->pluck('name')) !!},
        datasets: [{
            data: {!! json_encode($categoryStats->pluck('stories_count')) !!},
            backgroundColor: [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
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

// Episode Premium Chart
const episodePremiumCtx = document.getElementById('episodePremiumChart').getContext('2d');
new Chart(episodePremiumCtx, {
    type: 'doughnut',
    data: {
        labels: ['رایگان', 'پولی'],
        datasets: [{
            data: [{{ $stats['free_episodes'] }}, {{ $stats['premium_episodes'] }}],
            backgroundColor: ['#10B981', '#F59E0B']
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

// Episode Status Chart
const episodeStatusCtx = document.getElementById('episodeStatusChart').getContext('2d');
new Chart(episodeStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['منتشر شده', 'پیش‌نویس', 'در انتظار', 'تایید شده', 'رد شده'],
        datasets: [{
            data: [
                {{ $stats['published_episodes'] }},
                {{ $stats['draft_episodes'] }},
                {{ \App\Models\Episode::where('status', 'pending')->count() }},
                {{ \App\Models\Episode::where('status', 'approved')->count() }},
                {{ \App\Models\Episode::where('status', 'rejected')->count() }}
            ],
            backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444']
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

// Comments Chart
const commentsCtx = document.getElementById('commentsChart').getContext('2d');
new Chart(commentsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyComments->pluck('date')) !!},
        datasets: [{
            label: 'نظرات',
            data: {!! json_encode($dailyComments->pluck('comments')) !!},
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
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
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Date range change handler
document.getElementById('dateRange').addEventListener('change', function() {
    const dateRange = this.value;
    window.location.href = `{{ route('admin.dashboards.stories') }}?date_range=${dateRange}`;
});

// Export function
function exportData() {
    // Implementation for data export
    alert('قابلیت خروجی در حال توسعه است');
}
</script>
@endsection
