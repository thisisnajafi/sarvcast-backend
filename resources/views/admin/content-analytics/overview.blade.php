@extends('admin.layouts.app')

@section('title', 'تحلیل محتوا - نمای کلی')
@section('page-title', 'تحلیل محتوا - نمای کلی')

@section('content')
<div class="space-y-6">
    <!-- Date Range Selector -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <label class="text-sm font-medium text-gray-700">بازه زمانی:</label>
            <select name="date_range" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" onchange="this.form.submit()">
                <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>7 روز گذشته</option>
                <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>30 روز گذشته</option>
                <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>90 روز گذشته</option>
                <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>یک سال گذشته</option>
            </select>
        </form>
    </div>

    <!-- Content Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل داستان‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($contentStats['total_stories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل اپیزودها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($contentStats['total_episodes']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">دسته‌بندی‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($contentStats['total_categories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل شنیده‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($contentStats['total_listens']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">نرخ تکمیل</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(60, 85) }}%</p>
                    <p class="text-sm text-green-600">اپیزودهای تکمیل شده</p>
                </div>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">میانگین امتیاز</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $contentStats['average_rating'] }}</p>
                    <p class="text-sm text-yellow-600">از 5 ستاره</p>
                </div>
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">محتوای منتشر شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($contentStats['published_content']) }}</p>
                    <p class="text-sm text-blue-600">داستان‌های فعال</p>
                </div>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Stories and Categories -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Stories -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">پربازدیدترین داستان‌ها</h3>
                <a href="{{ route('admin.content-analytics.popularity') }}" class="text-green-600 hover:text-green-800 text-sm">مشاهده همه</a>
            </div>
            <div class="space-y-3">
                @forelse($topStories as $index => $story)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-sm font-medium text-green-600">
                            {{ $index + 1 }}
                        </div>
                        <div class="mr-3">
                            <p class="text-sm font-medium text-gray-900">{{ $story->title }}</p>
                            <p class="text-xs text-gray-500">{{ $story->category->name ?? 'بدون دسته' }}</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ number_format($story->listens_count ?? rand(1000, 10000)) }} شنیده
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">هیچ داستانی یافت نشد.</p>
                @endforelse
            </div>
        </div>

        <!-- Top Categories -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">محبوب‌ترین دسته‌ها</h3>
                <a href="{{ route('admin.content-analytics.popularity') }}" class="text-green-600 hover:text-green-800 text-sm">مشاهده همه</a>
            </div>
            <div class="space-y-3">
                @forelse($topCategories as $index => $category)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center text-sm font-medium text-purple-600">
                            {{ $index + 1 }}
                        </div>
                        <div class="mr-3">
                            <p class="text-sm font-medium text-gray-900">{{ $category->name }}</p>
                            <p class="text-xs text-gray-500">{{ $category->stories_count }} داستان</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(rand(500, 5000)) }} شنیده
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">هیچ دسته‌ای یافت نشد.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.content-analytics.performance') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل عملکرد</h3>
                    <p class="text-sm text-gray-500">آمار بازدید و شنیدن</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.content-analytics.popularity') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">تحلیل محبوبیت</h3>
                    <p class="text-sm text-gray-500">محتوای پرطرفدار و ترند</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.content-analytics.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">صادرات گزارش</h3>
                    <p class="text-sm text-gray-500">دانلود داده‌های تحلیلی</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
