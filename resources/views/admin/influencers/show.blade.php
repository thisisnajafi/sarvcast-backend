@extends('admin.layouts.app')

@section('title', 'مشاهده کمپین اینفلوئنسر')
@section('page-title', 'مشاهده کمپین اینفلوئنسر')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $influencer->campaign_name }}</h1>
            <p class="text-gray-600">{{ $influencer->user->first_name }} {{ $influencer->user->last_name }} - {{ $influencer->platform_username }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.influencers.edit', $influencer) }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.influencers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت تأیید</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($influencer->is_verified)
                            <span class="text-green-600">تأیید شده</span>
                        @else
                            <span class="text-yellow-600">تأیید نشده</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">تعداد فالوور</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($influencer->follower_count) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">نرخ درگیری</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $influencer->engagement_rate }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">نرخ کمیسیون</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $influencer->commission_rate }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Influencer Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات پایه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام کامل</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->user->first_name }} {{ $influencer->user->last_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شماره تلفن</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->user->phone_number ?? 'ثبت نشده' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تاریخ عضویت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->user->created_at->format('Y/m/d') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Campaign Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات کمپین</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام کمپین</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->campaign_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">پلتفرم</dt>
                            @php
                                $platformLabels = [
                                    'instagram' => 'اینستاگرام',
                                    'youtube' => 'یوتیوب',
                                    'tiktok' => 'تیک‌تاک',
                                    'twitter' => 'توییتر',
                                    'facebook' => 'فیس‌بوک',
                                    'linkedin' => 'لینکدین',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <dd class="mt-1 text-sm text-gray-900">{{ $platformLabels[$influencer->platform] ?? ucfirst($influencer->platform) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام کاربری</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->platform_username }}</dd>
                        </div>
                        @if($influencer->platform_url)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">لینک پلتفرم</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ $influencer->platform_url }}" target="_blank" class="text-purple-600 hover:text-purple-800">{{ $influencer->platform_url }}</a>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">آمار و ارقام</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تعداد فالوور</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($influencer->follower_count) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نرخ درگیری</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->engagement_rate }}%</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع محتوا</dt>
                            @php
                                $contentLabels = [
                                    'story' => 'استوری',
                                    'post' => 'پست',
                                    'video' => 'ویدیو',
                                    'live' => 'لایو',
                                    'reel' => 'ریل',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <dd class="mt-1 text-sm text-gray-900">{{ $contentLabels[$influencer->content_type] ?? ucfirst($influencer->content_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نرخ کمیسیون</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $influencer->commission_rate }}%</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Target Audience -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مخاطب هدف</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-900">{{ $influencer->target_audience }}</p>
                </div>
            </div>

            <!-- Verification Documents -->
            @if($influencer->verification_documents && count($influencer->verification_documents) > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اسناد تأیید</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($influencer->verification_documents as $index => $document)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-gray-400 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">سند {{ $index + 1 }}</p>
                                        <p class="text-xs text-gray-500">{{ basename($document) }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($document) }}" target="_blank" class="text-purple-600 hover:text-purple-800 text-sm">مشاهده</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">وضعیت کمپین</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'active' => 'bg-green-100 text-green-800',
                                'suspended' => 'bg-red-100 text-red-800',
                                'expired' => 'bg-gray-100 text-gray-800'
                            ];
                            $statusLabels = [
                                'pending' => 'در انتظار',
                                'active' => 'فعال',
                                'suspended' => 'معلق',
                                'expired' => 'منقضی'
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$influencer->status] }}">
                                {{ $statusLabels[$influencer->status] }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تأیید شده</dt>
                        <dd class="mt-1">
                            @if($influencer->is_verified)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    تأیید شده
                                </span>
                                @if($influencer->verified_at)
                                    <p class="text-xs text-gray-500 mt-1">{{ $influencer->verified_at->format('Y/m/d H:i') }}</p>
                                @endif
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    تأیید نشده
                                </span>
                            @endif
                        </dd>
                    </div>

                    @if($influencer->expires_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ انقضا</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $influencer->expires_at->format('Y/m/d') }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ ایجاد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $influencer->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $influencer->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if(!$influencer->is_verified)
                        <form method="POST" action="{{ route('admin.influencers.verify', $influencer) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید کمپین
                            </button>
                        </form>
                    @endif

                    @if($influencer->status === 'active')
                        <form method="POST" action="{{ route('admin.influencers.suspend', $influencer) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                تعلیق کمپین
                            </button>
                        </form>
                    @elseif($influencer->status === 'suspended')
                        <form method="POST" action="{{ route('admin.influencers.activate', $influencer) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                فعال‌سازی کمپین
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.influencers.destroy', $influencer) }}" class="w-full" onsubmit="return confirm('آیا از حذف این کمپین اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف کمپین
                        </button>
                    </form>
                </div>
            </div>

            <!-- Campaign Posts -->
            @if($influencer->campaignPosts && $influencer->campaignPosts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">پست‌های کمپین</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($influencer->campaignPosts as $post)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $post->title ?? 'پست بدون عنوان' }}</p>
                                    <p class="text-xs text-gray-500">{{ $post->created_at->format('Y/m/d') }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $post->is_published ? 'منتشر شده' : 'پیش‌نویس' }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
