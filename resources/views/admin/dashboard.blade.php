@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('page-title', 'داشبورد')

@section('content')
<!-- Welcome Section -->
<div class="mb-8">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">خوش آمدید به پنل مدیریت سروکست</h1>
                <p class="text-blue-100 text-lg">مدیریت کامل پلتفرم داستان‌های صوتی کودکان</p>
            </div>
            <div class="hidden md:block">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users Card -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">کل کاربران</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['active_users']) }} فعال</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">نرخ رشد</span>
                <span class="text-green-600 font-medium">+12.5%</span>
            </div>
        </div>
    </div>
    
    <!-- Total Stories Card -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">کل داستان‌ها</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_stories']) }}</p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['published_stories']) }} منتشر شده</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-green-500 to-green-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">نرخ رشد</span>
                <span class="text-green-600 font-medium">+8.3%</span>
            </div>
        </div>
    </div>
    
    <!-- Active Subscriptions Card -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">اشتراک‌های فعال</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['active_subscriptions']) }}</p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['total_subscriptions']) }} کل اشتراک‌ها</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">نرخ رشد</span>
                <span class="text-green-600 font-medium">+15.2%</span>
            </div>
        </div>
    </div>
    
    <!-- Episodes Card -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">کل اپیزودها</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_episodes']) }}</p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['published_episodes']) }} منتشر شده</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">نرخ رشد</span>
                <span class="text-green-600 font-medium">+6.7%</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">عملیات سریع</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.stories.create') }}" class="flex flex-col items-center p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">داستان جدید</span>
            </a>
            <a href="{{ route('admin.episodes.create') }}" class="flex flex-col items-center p-4 rounded-lg bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">اپیزود جدید</span>
            </a>
            <a href="{{ route('admin.categories.create') }}" class="flex flex-col items-center p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">دسته‌بندی جدید</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex flex-col items-center p-4 rounded-lg bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700">مدیریت کاربران</span>
            </a>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Stories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">آخرین داستان‌ها</h3>
                <a href="{{ route('admin.stories.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentStories->count() > 0)
                <div class="space-y-4">
                    @foreach($recentStories as $story)
                    <div class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-14 h-14 rounded-xl object-cover shadow-sm">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $story->title }}</p>
                            <p class="text-sm text-gray-500">{{ $story->category->name ?? 'بدون دسته‌بندی' }}</p>
                            <p class="text-xs text-gray-400 mt-1">@jalaliRelative($story->created_at)</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                @if($story->status === 'published') bg-green-100 text-green-800
                                @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($story->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ داستانی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">آخرین کاربران</h3>
                <a href="{{ route('admin.users.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentUsers->count() > 0)
                <div class="space-y-4">
                    @foreach($recentUsers as $user)
                    <div class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="relative">
                            <img src="{{ $user->profile_image_url ?: '/images/placeholder-user.jpg' }}" alt="{{ $user->first_name }}" class="w-12 h-12 rounded-full object-cover shadow-sm">
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white
                                @if($user->status === 'active') bg-green-500
                                @elseif($user->status === 'pending') bg-yellow-500
                                @else bg-red-500
                                @endif">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->phone_number }}</p>
                            <p class="text-xs text-gray-400 mt-1">@jalaliRelative($user->created_at)</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                @if($user->status === 'active') bg-green-100 text-green-800
                                @elseif($user->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ کاربری یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Top Categories -->
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">محبوب‌ترین دسته‌بندی‌ها</h3>
            <a href="{{ route('admin.categories.index') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">مشاهده همه</a>
        </div>
    </div>
    <div class="p-6">
        @if($topCategories->count() > 0)
            <div class="space-y-4">
                @foreach($topCategories as $category)
                <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" style="background-color: {{ $category->color }}20">
                            <div class="w-6 h-6 rounded-full" style="background-color: {{ $category->color }}"></div>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-900">{{ $category->name }}</span>
                            <p class="text-xs text-gray-500 mt-1">{{ $category->stories_count }} داستان</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">{{ $category->stories_count }}</div>
                            <div class="text-xs text-gray-500">داستان</div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <p class="text-gray-500">هیچ دسته‌بندی‌ای یافت نشد</p>
            </div>
        @endif
    </div>
</div>

<!-- Analytics Chart Section -->
<div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">آمار و تحلیل‌ها</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Chart Placeholder -->
            <div class="md:col-span-2">
                <div class="h-64 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 font-medium">نمودار آمار کاربران</p>
                        <p class="text-sm text-gray-500 mt-1">در حال توسعه...</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="space-y-4">
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">نرخ تبدیل</p>
                            <p class="text-2xl font-bold text-gray-900">24.5%</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">میانگین زمان</p>
                            <p class="text-2xl font-bold text-gray-900">3.2h</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">رضایت کاربران</p>
                            <p class="text-2xl font-bold text-gray-900">4.8/5</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
