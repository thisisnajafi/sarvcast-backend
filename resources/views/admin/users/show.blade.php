@extends('admin.layouts.app')

@section('title', 'مشاهده کاربر - ' . $user->first_name . ' ' . $user->last_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</h1>
            <p class="text-gray-600 mt-1">{{ $user->email }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-warning text-white px-4 py-2 rounded-lg hover:bg-warning/90 transition-colors">
                ویرایش کاربر
            </a>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات شخصی</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">نام</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->first_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">نام خانوادگی</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->last_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">ایمیل</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">شماره تلفن</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->phone_number ?: 'ثبت نشده' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">نقش</label>
                        <p class="mt-1">
                            @if($user->role == 'admin')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">مدیر</span>
                            @elseif($user->role == 'parent')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">والد</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">کودک</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">وضعیت</label>
                        <p class="mt-1">
                            @if($user->status == 'active')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">فعال</span>
                            @elseif($user->status == 'inactive')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">غیرفعال</span>
                            @elseif($user->status == 'suspended')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">معلق</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">در انتظار</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subscription Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات اشتراک</h2>
                @if($user->activeSubscription)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">نوع اشتراک</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $user->activeSubscription->plan_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">وضعیت</label>
                            <p class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">فعال</span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">تاریخ شروع</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $user->activeSubscription->start_date->format('Y/m/d H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">تاریخ انقضا</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $user->activeSubscription->end_date->format('Y/m/d H:i') }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">هیچ اشتراک فعالی ندارد</p>
                @endif
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">فعالیت‌های اخیر</h2>
                @if($recentActivity->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center space-x-3 space-x-reverse p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h8a2 2 0 012 2v8a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $activity->episode->story->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $activity->episode->title }}</p>
                                </div>
                                <div class="flex-shrink-0 text-sm text-gray-500">
                                    {{ $activity->played_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">هیچ فعالیتی ثبت نشده است</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Profile Image -->
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                @if($user->profile_image_url)
                    <img src="{{ $user->profile_image_url }}" alt="{{ $user->first_name }}" class="w-24 h-24 rounded-full mx-auto object-cover">
                @else
                    <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                @endif
                <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</h3>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>

            <!-- Statistics -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">آمار</h3>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">تعداد پخش</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->playHistories->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">علاقه‌مندی‌ها</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->favorites->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">امتیازات</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->ratings->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">پرداخت‌ها</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->payments->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Child Profiles -->
            @if($user->role == 'parent' && $user->profiles->count() > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">پروفایل‌های کودکان</h3>
                    <div class="space-y-3">
                        @foreach($user->profiles as $profile)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-900">{{ $profile->name }}</p>
                                <p class="text-sm text-gray-500">سن: {{ $profile->age }} سال</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Account Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات حساب</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">تاریخ عضویت</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">آخرین ورود</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('Y/m/d H:i') : 'هیچ‌گاه' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">زبان</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->language }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">منطقه زمانی</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->timezone }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
