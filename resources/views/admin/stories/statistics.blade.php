@extends('admin.layouts.app')

@section('title', 'آمار و گزارش‌های داستان‌ها')
@section('page-title', 'آمار و گزارش‌های داستان‌ها')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'آمار و گزارش‌های داستان‌ها',
        'subtitle' => 'تحلیل جامع آمار و عملکرد داستان‌های پلتفرم',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
        'iconBg' => 'bg-blue-100',
        'iconColor' => 'text-blue-600',
        'actions' => '<a href="' . route('admin.stories.index') . '" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>بازگشت</a>'
    ])

    <!-- Main Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">کل داستان‌ها</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['total_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">منتشر شده</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['published_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">کل اپیزودها</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['total_episodes']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">میانگین امتیاز</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ $stats['avg_rating'] }}/5</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">کل مدت زمان</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['total_duration'] / 60, 1) }} ساعت</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">کل پخش‌ها</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['total_plays']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">پریمیوم</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['premium_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">رایگان</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">{{ number_format($stats['free_stories']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Stories by Category Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">داستان‌ها بر اساس دسته‌بندی</h3>
            <div class="space-y-3">
                @foreach($storiesByCategory as $category)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $category->name }}</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $category->count > 0 ? ($category->count / $storiesByCategory->max('count')) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $category->count }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Stories by Status Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">داستان‌ها بر اساس وضعیت</h3>
            <div class="space-y-3">
                @foreach($storiesByStatus as $status)
                @php
                    $statusLabels = [
                        'published' => ['label' => 'منتشر شده', 'class' => 'bg-green-600'],
                        'draft' => ['label' => 'پیش‌نویس', 'class' => 'bg-gray-600'],
                        'pending' => ['label' => 'در انتظار', 'class' => 'bg-yellow-600'],
                        'approved' => ['label' => 'تایید شده', 'class' => 'bg-blue-600'],
                        'rejected' => ['label' => 'رد شده', 'class' => 'bg-red-600']
                    ];
                    $statusInfo = $statusLabels[$status->status] ?? ['label' => $status->status, 'class' => 'bg-gray-600'];
                @endphp
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $statusInfo['label'] }}</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="{{ $statusInfo['class'] }} h-2 rounded-full" style="width: {{ $status->count > 0 ? ($status->count / $storiesByStatus->max('count')) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $status->count }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Age Group and Premium Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Stories by Age Group -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">داستان‌ها بر اساس گروه سنی</h3>
            <div class="space-y-3">
                @foreach($storiesByAgeGroup as $ageGroup)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $ageGroup->age_group }}</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $ageGroup->count > 0 ? ($ageGroup->count / $storiesByAgeGroup->max('count')) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $ageGroup->count }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Premium vs Free Distribution -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">توزیع پریمیوم و رایگان</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">پریمیوم</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $premiumStats['premium_count'] > 0 ? ($premiumStats['premium_count'] / array_sum($premiumStats)) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $premiumStats['premium_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">رایگان</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $premiumStats['free_count'] > 0 ? ($premiumStats['free_count'] / array_sum($premiumStats)) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $premiumStats['free_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">ترکیبی</span>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $premiumStats['mixed_count'] > 0 ? ($premiumStats['mixed_count'] / array_sum($premiumStats)) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $premiumStats['mixed_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Duration Statistics -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">آمار مدت زمان</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ number_format($durationStats['avg_duration'] / 60, 1) }}</div>
                <div class="text-sm text-gray-600">میانگین مدت (ساعت)</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ number_format($durationStats['min_duration'] / 60, 1) }}</div>
                <div class="text-sm text-gray-600">کوتاه‌ترین مدت (ساعت)</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ number_format($durationStats['max_duration'] / 60, 1) }}</div>
                <div class="text-sm text-gray-600">طولانی‌ترین مدت (ساعت)</div>
            </div>
        </div>
    </div>

    <!-- Top Stories Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Stories -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">جدیدترین داستان‌ها</h3>
            <div class="space-y-3">
                @foreach($recentStories as $story)
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $story->title }}</p>
                        <p class="text-xs text-gray-500">{{ $story->category->name ?? 'بدون دسته' }}</p>
                    </div>
                    <div class="text-xs text-gray-500">{{ $story->created_at->format('Y/m/d') }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Rated Stories -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">پربازدیدترین داستان‌ها</h3>
            <div class="space-y-3">
                @foreach($topRatedStories as $story)
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $story->title }}</p>
                        <p class="text-xs text-gray-500">{{ $story->category->name ?? 'بدون دسته' }}</p>
                    </div>
                    <div class="text-xs text-gray-500">{{ $story->rating }}/5</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Most Played Stories -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">پربازدیدترین داستان‌ها</h3>
            <div class="space-y-3">
                @foreach($mostPlayedStories as $story)
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $story->title }}</p>
                        <p class="text-xs text-gray-500">{{ $story->category->name ?? 'بدون دسته' }}</p>
                    </div>
                    <div class="text-xs text-gray-500">{{ number_format($story->play_count) }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Monthly Statistics Chart -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">آمار ماهانه داستان‌ها (12 ماه گذشته)</h3>
        <div class="space-y-3">
            @foreach($monthlyStats as $month)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $month->month }}</span>
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-64 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $month->count > 0 ? ($month->count / $monthlyStats->max('count')) * 100 : 0 }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 w-8 text-left">{{ $month->count }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Export and Actions -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            <div>
                <h3 class="text-lg font-medium text-gray-900">گزارش‌گیری و صادرات</h3>
                <p class="text-sm text-gray-500">دانلود گزارش‌های کامل آمار داستان‌ها</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                <a href="{{ route('admin.stories.export') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    صادرات گزارش
                </a>
                <a href="{{ route('admin.stories.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh statistics every 5 minutes
setInterval(function() {
    // You can add AJAX call here to refresh statistics
    console.log('Statistics refresh check...');
}, 300000); // 5 minutes
</script>
@endsection
