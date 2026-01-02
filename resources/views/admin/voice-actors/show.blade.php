@extends('admin.layouts.app')

@section('title', 'پروفایل صداپیشه')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">پروفایل صداپیشه</h1>
        <div class="flex space-x-2 space-x-reverse">
            <a href="{{ route('admin.voice-actors.edit', $voiceActor) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                ویرایش پروفایل
            </a>
            <a href="{{ route('admin.voice-actors.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="text-center">
                    @if($voiceActor->profile_image_url)
                        <img src="{{ $voiceActor->profile_image_url }}" alt="{{ $voiceActor->first_name }}" class="w-32 h-32 rounded-full mx-auto object-cover mb-4">
                    @else
                        <div class="w-32 h-32 bg-gray-200 dark:bg-gray-600 rounded-full mx-auto flex items-center justify-center mb-4">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $voiceActor->first_name }} {{ $voiceActor->last_name }}
                    </h2>
                    
                    <div class="mb-4">
                        @if($voiceActor->role == 'super_admin')
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                ادمین کل
                            </span>
                        @elseif($voiceActor->role == 'admin')
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                مدیر
                            </span>
                        @else
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200">
                                صداپیشه
                            </span>
                        @endif
                        
                        @if($voiceActor->status == 'active')
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mr-2">
                                فعال
                            </span>
                        @elseif($voiceActor->status == 'pending')
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 mr-2">
                                در انتظار
                            </span>
                        @else
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mr-2">
                                مسدود
                            </span>
                        @endif
                    </div>

                    <!-- Change Role Form -->
                    <form method="POST" action="{{ route('admin.voice-actors.change-role', $voiceActor) }}" class="mb-4">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تغییر نقش</label>
                        <select name="role" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="voice_actor" {{ $voiceActor->role == 'voice_actor' ? 'selected' : '' }}>صداپیشه</option>
                            <option value="admin" {{ $voiceActor->role == 'admin' ? 'selected' : '' }}>مدیر</option>
                            <option value="super_admin" {{ $voiceActor->role == 'super_admin' ? 'selected' : '' }}>ادمین کل</option>
                        </select>
                    </form>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 mt-6 pt-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">شماره تلفن</label>
                        <p class="text-gray-900 dark:text-white">{{ $voiceActor->phone_number ?? 'ثبت نشده' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">تاریخ عضویت</label>
                        <p class="text-gray-900 dark:text-white">{{ $voiceActor->created_at->format('Y/m/d') }}</p>
                    </div>
                    @if($voiceActor->last_login_at)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">آخرین ورود</label>
                        <p class="text-gray-900 dark:text-white">{{ $voiceActor->last_login_at->format('Y/m/d H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics and Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">شخصیت‌ها</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_characters']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">داستان‌های روایت شده</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_stories_narrated']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">داستان‌های نوشته شده</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_stories_authored']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">اپیزودها</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_episodes']) }}</div>
                </div>
            </div>

            <!-- Characters -->
            @if($voiceActor->characters->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">شخصیت‌های اختصاص یافته</h3>
                <div class="space-y-3">
                    @foreach($voiceActor->characters->take(10) as $character)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $character->name }}</p>
                                @if($character->story)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $character->story->title }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if($voiceActor->characters->count() > 10)
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">و {{ $voiceActor->characters->count() - 10 }} شخصیت دیگر...</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Stories Narrated -->
            @if($voiceActor->storiesAsNarrator->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">داستان‌های روایت شده</h3>
                <div class="space-y-3">
                    @foreach($voiceActor->storiesAsNarrator->take(10) as $story)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $story->title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $story->status }}</p>
                            </div>
                            <a href="{{ route('admin.stories.show', $story) }}" class="text-primary hover:text-primary/80">
                                مشاهده
                            </a>
                        </div>
                    @endforeach
                    @if($voiceActor->storiesAsNarrator->count() > 10)
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">و {{ $voiceActor->storiesAsNarrator->count() - 10 }} داستان دیگر...</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Stories Authored -->
            @if($voiceActor->storiesAsAuthor->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">داستان‌های نوشته شده</h3>
                <div class="space-y-3">
                    @foreach($voiceActor->storiesAsAuthor->take(10) as $story)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $story->title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $story->status }}</p>
                            </div>
                            <a href="{{ route('admin.stories.show', $story) }}" class="text-primary hover:text-primary/80">
                                مشاهده
                            </a>
                        </div>
                    @endforeach
                    @if($voiceActor->storiesAsAuthor->count() > 10)
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">و {{ $voiceActor->storiesAsAuthor->count() - 10 }} داستان دیگر...</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

