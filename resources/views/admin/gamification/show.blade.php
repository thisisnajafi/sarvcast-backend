@extends('admin.layouts.app')

@section('title', 'مشاهده عنصر گیمیفیکیشن')
@section('page-title', 'مشاهده عنصر گیمیفیکیشن')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده عنصر گیمیفیکیشن</h1>
            <p class="text-gray-600">{{ $gamification->title }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.gamification.edit', $gamification) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.gamification.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
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
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($gamification->is_active)
                            <span class="text-green-600">فعال</span>
                        @else
                            <span class="text-gray-600">غیرفعال</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نوع</p>
                    @php
                        $typeLabels = [
                            'achievement' => 'دستاورد',
                            'badge' => 'نشان',
                            'level' => 'سطح',
                            'reward' => 'پاداش',
                            'challenge' => 'چالش',
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $typeLabels[$gamification->type] ?? ucfirst($gamification->type) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">امتیاز مورد نیاز</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($gamification->points_required) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">پاداش</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($gamification->reward_points) }} امتیاز</p>
                    <p class="text-sm text-gray-500">{{ number_format($gamification->reward_coins) }} سکه</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Gamification Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title and Description -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات کلی</h2>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $gamification->title }}</h3>
                    @if($gamification->description)
                        <p class="text-gray-900 leading-relaxed">{{ $gamification->description }}</p>
                    @endif
                </div>
            </div>

            <!-- Images -->
            @if($gamification->icon || $gamification->badge_image)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">تصاویر</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($gamification->icon)
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">آیکون</h3>
                            <img src="{{ Storage::url($gamification->icon) }}" alt="{{ $gamification->title }}" class="w-24 h-24 mx-auto rounded-lg object-cover">
                        </div>
                        @endif

                        @if($gamification->badge_image)
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">تصویر نشان</h3>
                            <img src="{{ Storage::url($gamification->badge_image) }}" alt="{{ $gamification->title }}" class="w-24 h-24 mx-auto rounded-lg object-cover">
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Conditions -->
            @if($gamification->conditions)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">شرایط کسب</h2>
                </div>
                <div class="p-6">
                    <pre class="bg-gray-100 p-4 rounded-lg text-sm text-gray-800 overflow-x-auto">{{ json_encode($gamification->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            <!-- Story and Episode Information -->
            @if($gamification->story || $gamification->episode)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مربوط به محتوا</h2>
                </div>
                <div class="p-6">
                    @if($gamification->story)
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-1">داستان</h3>
                            <p class="text-gray-900">{{ $gamification->story->title }}</p>
                        </div>
                    @endif

                    @if($gamification->episode)
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-1">اپیزود</h3>
                            <p class="text-gray-900">{{ $gamification->episode->title }}</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Gamification Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">نوع</dt>
                        @php
                            $typeLabels = [
                                'achievement' => 'دستاورد',
                                'badge' => 'نشان',
                                'level' => 'سطح',
                                'reward' => 'پاداش',
                                'challenge' => 'چالش',
                            ];
                            $typeColors = [
                                'achievement' => 'bg-yellow-100 text-yellow-800',
                                'badge' => 'bg-purple-100 text-purple-800',
                                'level' => 'bg-blue-100 text-blue-800',
                                'reward' => 'bg-green-100 text-green-800',
                                'challenge' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $typeColors[$gamification->type] }}">
                                {{ $typeLabels[$gamification->type] }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">امتیاز مورد نیاز</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($gamification->points_required) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">پاداش امتیاز</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($gamification->reward_points) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">پاداش سکه</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($gamification->reward_coins) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                        <dd class="mt-1">
                            @if($gamification->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    فعال
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    غیرفعال
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ ایجاد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $gamification->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $gamification->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    <form method="POST" action="{{ route('admin.gamification.toggle', $gamification) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full {{ $gamification->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg transition-colors">
                            {{ $gamification->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.gamification.duplicate', $gamification) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            کپی عنصر
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.gamification.destroy', $gamification) }}" class="w-full" onsubmit="return confirm('آیا از حذف این عنصر اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف عنصر
                        </button>
                    </form>
                </div>
            </div>

            <!-- User Achievements -->
            @if($userAchievements && $userAchievements->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">کاربران کسب‌کننده</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($userAchievements as $achievement)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $achievement->user->first_name }} {{ $achievement->user->last_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $achievement->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">+{{ number_format($achievement->reward_points) }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($achievement->reward_coins) }} سکه</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($userAchievements->count() > 10)
                        <p class="text-xs text-gray-500 mt-3 text-center">و {{ $userAchievements->count() - 10 }} کاربر دیگر...</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
