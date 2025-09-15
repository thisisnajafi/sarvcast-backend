@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('page-title', 'داشبورد')

@section('content')
<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">کل کاربران</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">{{ number_format($stats['active_users']) }} کاربر فعال</span>
        </div>
    </div>
    
    <!-- Total Stories Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">کل داستان‌ها</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_stories']) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">{{ number_format($stats['published_stories']) }} منتشر شده</span>
        </div>
    </div>
    
    <!-- Active Subscriptions Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">اشتراک‌های فعال</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_subscriptions']) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">{{ number_format($stats['total_subscriptions']) }} کل اشتراک‌ها</span>
        </div>
    </div>
    
    <!-- Episodes Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">کل اپیزودها</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_episodes']) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">{{ number_format($stats['published_episodes']) }} منتشر شده</span>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Stories -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">آخرین داستان‌ها</h3>
        </div>
        <div class="p-6">
            @if($recentStories->count() > 0)
                <div class="space-y-4">
                    @foreach($recentStories as $story)
                    <div class="flex items-center space-x-4">
                        <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-12 h-12 rounded-lg object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $story->title }}</p>
                            <p class="text-sm text-gray-500">{{ $story->category->name ?? 'بدون دسته‌بندی' }}</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
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
                <p class="text-gray-500 text-center py-4">هیچ داستانی یافت نشد</p>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">آخرین کاربران</h3>
        </div>
        <div class="p-6">
            @if($recentUsers->count() > 0)
                <div class="space-y-4">
                    @foreach($recentUsers as $user)
                    <div class="flex items-center space-x-4">
                        <img src="{{ $user->profile_image_url ?: '/images/placeholder-user.jpg' }}" alt="{{ $user->first_name }}" class="w-10 h-10 rounded-full object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
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
                <p class="text-gray-500 text-center py-4">هیچ کاربری یافت نشد</p>
            @endif
        </div>
    </div>
</div>

<!-- Top Categories -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">محبوب‌ترین دسته‌بندی‌ها</h3>
    </div>
    <div class="p-6">
        @if($topCategories->count() > 0)
            <div class="space-y-4">
                @foreach($topCategories as $category)
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $category->color }}"></div>
                        <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">{{ $category->stories_count }} داستان</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">هیچ دسته‌بندی‌ای یافت نشد</p>
        @endif
    </div>
</div>
@endsection
