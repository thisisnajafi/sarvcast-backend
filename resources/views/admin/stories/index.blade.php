@extends('admin.layouts.app')

@section('title', 'مدیریت داستان‌ها')
@section('page-title', 'داستان‌ها')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل داستان‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">منتشر شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['published'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">در انتظار</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">پریمیوم</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['premium'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل پخش‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_plays']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">داستان‌ها</h3>
                <div class="flex space-x-3 space-x-reverse">
                    <a href="{{ route('admin.stories.statistics') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition duration-200">
                        آمار
                    </a>
                    <a href="{{ route('admin.stories.export', request()->query()) }}" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition duration-200">
                        خروجی CSV
                    </a>
                    <a href="{{ route('admin.stories.export-json', request()->query()) }}" class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg hover:bg-emerald-200 transition duration-200">
                        خروجی JSON (داستان + اپیزود + تایم‌لاین)
                    </a>
                    <form action="{{ route('admin.stories.import-json') }}" method="POST" enctype="multipart/form-data" class="flex items-center space-x-2 space-x-reverse">
                        @csrf
                        <label class="inline-flex items-center bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg cursor-pointer hover:bg-indigo-100 transition duration-200">
                            <span>ورودی JSON</span>
                            <input type="file" name="file" accept=".json,application/json" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                    <a href="{{ route('admin.stories.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        افزودن داستان جدید
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Advanced Filters -->
        <div class="px-6 py-4 border-b border-gray-200">
            <form method="GET" id="filterForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه دسته‌بندی‌ها</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>رد شده</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع محتوا</label>
                        <select name="is_premium" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه انواع</option>
                            <option value="1" {{ request('is_premium') == '1' ? 'selected' : '' }}>پریمیوم</option>
                            <option value="0" {{ request('is_premium') == '0' ? 'selected' : '' }}>رایگان</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">گروه سنی</label>
                        <select name="age_group" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه گروه‌های سنی</option>
                            <option value="3-5" {{ request('age_group') == '3-5' ? 'selected' : '' }}>3-5 سال</option>
                            <option value="6-8" {{ request('age_group') == '6-8' ? 'selected' : '' }}>6-8 سال</option>
                            <option value="9-12" {{ request('age_group') == '9-12' ? 'selected' : '' }}>9-12 سال</option>
                            <option value="13+" {{ request('age_group') == '13+' ? 'selected' : '' }}>13+ سال</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">کارگردان</label>
                        <select name="director_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه کارگردانان</option>
                            @foreach($directors as $director)
                                <option value="{{ $director->id }}" {{ request('director_id') == $director->id ? 'selected' : '' }}>
                                    {{ $director->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">راوی</label>
                        <select name="narrator_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">همه راویان</option>
                            @foreach($narrators as $narrator)
                                <option value="{{ $narrator->id }}" {{ request('narrator_id') == $narrator->id ? 'selected' : '' }}>
                                    {{ $narrator->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ از</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ تا</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان، زیرعنوان، توضیحات..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div class="flex items-end space-x-2 space-x-reverse">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                            فیلتر
                        </button>
                        <a href="{{ route('admin.stories.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition duration-200">
                            پاک کردن
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <form method="POST" action="{{ route('admin.stories.bulk-action') }}" id="bulkActionForm">
                @csrf
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300" onchange="selectAll(this)">
                        <label for="selectAll" class="mr-2 text-sm text-gray-700">انتخاب همه</label>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <select name="action" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">عملیات گروهی</option>
                            <option value="publish">انتشار</option>
                            <option value="unpublish">لغو انتشار</option>
                            <option value="change_status">تغییر وضعیت</option>
                            <option value="change_category">تغییر دسته‌بندی</option>
                            <option value="delete">حذف</option>
                        </select>
                        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" style="display: none;">
                            <option value="">انتخاب وضعیت</option>
                            <option value="published">منتشر شده</option>
                            <option value="pending">در انتظار</option>
                            <option value="draft">پیش‌نویس</option>
                            <option value="approved">تأیید شده</option>
                            <option value="rejected">رد شده</option>
                        </select>
                        <select name="category_id" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" style="display: none;">
                            <option value="">انتخاب دسته‌بندی</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200" onclick="return confirmBulkAction()">
                            اجرا
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto relative" style="scrollbar-width: thin;">
            <!-- Scroll indicator hint for mobile -->
            <div class="md:hidden absolute top-0 right-0 bg-gradient-to-l from-gray-100 to-transparent w-8 h-full pointer-events-none z-10 flex items-center justify-end pr-2">
                <svg class="w-4 h-4 text-gray-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="rounded border-gray-300" onchange="selectAll(this)">
                        </th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => request('sort') == 'title' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                عنوان
                                @if(request('sort') == 'title')
                                    @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                @endif
                            </a>
                        </th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دسته‌بندی</th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                وضعیت
                                @if(request('sort') == 'status')
                                    @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                @endif
                            </a>
                        </th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'rating', 'direction' => request('sort') == 'rating' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                امتیاز
                                @if(request('sort') == 'rating')
                                    @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                @endif
                            </a>
                        </th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'play_count', 'direction' => request('sort') == 'play_count' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700">
                                پخش‌ها
                                @if(request('sort') == 'play_count')
                                    @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                @endif
                            </a>
                        </th>
                        <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($stories as $story)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <input type="checkbox" name="story_ids[]" value="{{ $story->id }}" class="rounded border-gray-300 story-checkbox">
                        </td>
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-12 h-12 rounded-lg object-cover">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $story->title }}</div>
                            <div class="text-sm text-gray-500">{{ $story->subtitle }}</div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $story->total_episodes_count }} قسمت • {{ $story->formatted_duration }}
                            </div>
                        </td>
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $story->category->name ?? 'بدون دسته‌بندی' }}
                            </span>
                        </td>
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                @if($story->status === 'published') bg-green-100 text-green-800
                                @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($story->status === 'approved') bg-blue-100 text-blue-800
                                @elseif($story->status === 'rejected') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                @switch($story->status)
                                    @case('published')
                                        منتشر شده
                                        @break
                                    @case('pending')
                                        در انتظار
                                        @break
                                    @case('approved')
                                        تأیید شده
                                        @break
                                    @case('rejected')
                                        رد شده
                                        @break
                                    @default
                                        پیش‌نویس
                                @endswitch
                            </span>
                            @if($story->is_premium)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-1">
                                    پریمیوم
                                </span>
                            @endif
                        </td>
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-yellow-500">★</span>
                                <span class="text-sm text-gray-600 mr-1">{{ number_format($story->rating, 1) }}</span>
                                <span class="text-xs text-gray-500">({{ $story->ratings()->count() }})</span>
                            </div>
                        </td>
                        <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ number_format($story->play_count) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.stories.show', $story) }}" class="text-green-600 hover:text-green-900" title="مشاهده">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.stories.edit', $story) }}" class="text-indigo-600 hover:text-indigo-900" title="ویرایش">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @if($story->status !== 'published')
                                    <form method="POST" action="{{ route('admin.stories.publish', $story) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900" title="انتشار" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این داستان را منتشر کنید؟')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.stories.duplicate', $story) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-900" title="کپی" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این داستان را کپی کنید؟')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.stories.destroy', $story) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="حذف" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این داستان را حذف کنید؟')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            هیچ داستانی یافت نشد
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($stories->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    نمایش {{ $stories->firstItem() }} تا {{ $stories->lastItem() }} از {{ $stories->total() }} نتیجه
                </div>
                <div class="flex space-x-2 space-x-reverse">
                    @if($stories->previousPageUrl())
                        <a href="{{ $stories->previousPageUrl() }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">قبلی</a>
                    @endif
                    
                    @foreach($stories->getUrlRange(1, $stories->lastPage()) as $page => $url)
                        @if($page == $stories->currentPage())
                            <span class="px-3 py-2 text-sm bg-primary text-white rounded-lg">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">{{ $page }}</a>
                        @endif
                    @endforeach
                    
                    @if($stories->nextPageUrl())
                        <a href="{{ $stories->nextPageUrl() }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">بعدی</a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/admin/story-manager.js') }}"></script>
@endsection