@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('page-title', 'داشبورد')

@section('content')
<!-- Welcome Section -->
<div class="mb-6 sm:mb-8">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold mb-1 sm:mb-2 truncate">
                    خوش آمدید به پنل مدیریت سروکست
                </h1>
                <p class="text-sm sm:text-base text-blue-100">
                    مدیریت کامل پلتفرم داستان‌های صوتی کودکان
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-4 sm:gap-6">
                    <div class="text-center min-w-[90px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_users']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">کل کاربران</div>
                    </div>
                    <div class="text-center min-w-[90px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_stories']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">کل داستان‌ها</div>
                    </div>
                    <div class="text-center min-w-[120px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_revenue']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">درآمد کل (تومان)</div>
                    </div>
                </div>
            </div>
            <div class="hidden md:block flex-shrink-0">
                <div class="w-16 h-16 lg:w-20 lg:h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 lg:w-10 lg:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Statistics Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Total Users Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">کل کاربران</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ number_format($stats['total_users']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium">{{ number_format($stats['active_users']) }} فعال</span>
                    <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">{{ number_format($stats['pending_users']) }} در انتظار</span>
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">{{ number_format($stats['blocked_users']) }} مسدود</span>
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
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['user_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">امروز</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['new_users_today']) }} کاربر جدید</span>
            </div>
        </div>
    </div>

    <!-- Total Stories Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">کل داستان‌ها</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_stories']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['published_stories']) }} منتشر شده</span>
                    <span class="text-sm text-yellow-600 font-medium">{{ number_format($stats['pending_stories']) }} در انتظار</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">{{ number_format($stats['draft_stories']) }} پیش‌نویس</span>
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
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['story_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">کل اپیزودها</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['total_episodes']) }}</span>
            </div>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">درآمد کل</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_revenue']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['total_payments']) }} پرداخت موفق</span>
                    <span class="text-sm text-yellow-600 font-medium">{{ number_format($stats['pending_payments']) }} در انتظار</span>
                    <span class="text-sm text-red-600 font-medium">{{ number_format($stats['failed_payments']) }} ناموفق</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['revenue_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">کمیسیون‌ها</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['total_commissions']) }} تومان</span>
            </div>
        </div>
    </div>

    <!-- Engagement Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">تعامل کاربران</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_comments']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-blue-600 font-medium">{{ number_format($stats['total_ratings']) }} امتیاز</span>
                    <span class="text-sm text-purple-600 font-medium">{{ number_format($stats['total_favorites']) }} علاقه‌مندی</span>
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['total_play_history']) }} پخش</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">میانگین امتیاز</span>
                <span class="text-yellow-600 font-medium">{{ number_format($stats['average_rating'], 1) }}/5</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">اشتراک‌گذاری</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['total_content_shares']) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Statistics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Subscriptions -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">اشتراک‌ها</h3>
            <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-green-600">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">کل اشتراک‌ها</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">فعال</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['active_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">منقضی شده</span>
                <span class="font-semibold text-red-600">{{ number_format($stats['expired_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">لغو شده</span>
                <span class="font-semibold text-gray-600 dark:text-gray-300">{{ number_format($stats['cancelled_subscriptions']) }}</span>
            </div>
        </div>
    </div>

    <!-- Partnerships -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">شراکت‌ها</h3>
            <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">شریک‌های وابسته</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_affiliate_partners']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">حمایت‌های شرکتی</span>
                <span class="font-semibold text-blue-600">{{ number_format($stats['total_corporate_sponsorships']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">شراکت‌های مدرسه</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['total_school_partnerships']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">کمپین‌های اینفلوئنسر</span>
                <span class="font-semibold text-purple-600">{{ number_format($stats['total_influencer_campaigns']) }}</span>
            </div>
        </div>
    </div>

    <!-- Marketing & Activities -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">بازاریابی</h3>
            <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13a3 3 0 100-6M18 13a3 3 0 100-6M5.436 13a3 3 0 100-6"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">کدهای تخفیف</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_coupon_codes']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">استفاده از کدها</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['total_coupon_usage']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">پیامک ارسالی</span>
                <span class="font-semibold text-blue-600">{{ number_format($stats['total_sms_sent']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">معرفی‌ها</span>
                <span class="font-semibold text-purple-600">{{ number_format($stats['total_referrals']) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Plan Sales Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Plan Sales Today -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 sm:p-6 border border-blue-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-blue-800 mb-1 whitespace-normal leading-snug">فروش امروز</p>
                <p class="text-xl sm:text-2xl font-bold text-blue-900">{{ number_format($stats['plan_sales_today']) }}</p>
                <p class="text-xs text-blue-700 mt-1">اشتراک فروخته شده</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Plan Sales This Week -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 sm:p-6 border border-green-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-green-800 mb-1 whitespace-normal leading-snug">فروش این هفته</p>
                <p class="text-xl sm:text-2xl font-bold text-green-900">{{ number_format($stats['plan_sales_this_week']) }}</p>
                <p class="text-xs text-green-700 mt-1">اشتراک فروخته شده</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Plan Sales Growth -->
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 sm:p-6 border border-purple-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-purple-800 mb-1 whitespace-normal leading-snug">رشد فروش</p>
                <p class="text-xl sm:text-2xl font-bold text-purple-900">+{{ $stats['plan_sales_growth_rate'] }}%</p>
                <p class="text-xs text-purple-700 mt-1">نسبت به ماه قبل</p>
            </div>
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">عملیات سریع</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4">
            <a href="{{ route('admin.stories.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    داستان جدید
                </span>
            </a>
            <a href="{{ route('admin.episodes.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    اپیزود جدید
                </span>
            </a>
            <a href="{{ route('admin.categories.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    دسته‌بندی جدید
                </span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    مدیریت کاربران
                </span>
            </a>
            <a href="{{ route('admin.payments.index') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    پرداخت‌ها
                </span>
            </a>
            <a href="{{ route('admin.analytics') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    آمار و تحلیل
                </span>
            </a>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Recent Stories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین داستان‌ها</h3>
                <a href="{{ route('admin.stories.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentStories->count() > 0)
                <div class="space-y-4">
                    @foreach($recentStories as $story)
                    <div class="flex items-center space-x-4 space-x-reverse p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-xl object-cover shadow-sm flex-shrink-0" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
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
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین کاربران</h3>
                <a href="{{ route('admin.users.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentUsers->count() > 0)
                <div class="space-y-4">
                    @foreach($recentUsers as $user)
                    <div class="flex items-center space-x-4 space-x-reverse p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="relative">
                            <img src="{{ $user->profile_image_url ?: '/images/placeholder-user.jpg' }}" alt="{{ $user->first_name }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover shadow-sm flex-shrink-0" loading="lazy">
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white
                                @if($user->status === 'active') bg-green-500
                                @elseif($user->status === 'pending') bg-yellow-500
                                @else bg-red-500
                                @endif">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->first_name }} {{ $user->last_name }}</p>
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

    <!-- Recent Payments -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-yellow-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین پرداخت‌ها</h3>
                <a href="{{ route('admin.payments.index') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentPayments->count() > 0)
                <div class="space-y-4">
                    @foreach($recentPayments as $payment)
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($payment->amount) }} تومان</p>
                                <p class="text-sm text-gray-500">{{ $payment->user->first_name ?? 'کاربر ناشناس' }}</p>
                                <p class="text-xs text-gray-400 mt-1">@jalaliRelative($payment->created_at)</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($payment->status === 'completed') bg-green-100 text-green-800
                                @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ پرداختی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Plan Sales Details Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Top Selling Plans -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">پرفروش‌ترین پلن‌ها</h3>
                <a href="{{ route('admin.subscription-plans.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topSellingPlans->count() > 0)
                <div class="space-y-4">
                    @foreach($topSellingPlans as $plan)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ number_format($plan->price) }} تومان</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $plan->subscriptions_count }}</div>
                                <div class="text-xs text-gray-500">فروش</div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $plan->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ پلنی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Plan Sales Analytics -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار فروش پلن‌ها</h3>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($planSalesData->count() > 0)
                <div class="space-y-4">
                    @foreach($planSalesData as $planSale)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    @if($planSale->type === '1month') یک ماهه
                                    @elseif($planSale->type === '3months') سه ماهه
                                    @elseif($planSale->type === '6months') شش ماهه
                                    @elseif($planSale->type === '1year') یک ساله
                                    @else {{ $planSale->type }}
                                    @endif
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ number_format($planSale->count) }} فروش</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($planSale->total_revenue) }}</div>
                            <div class="text-xs text-gray-500">تومان درآمد</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ فروشی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>
    <!-- Top Categories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">محبوب‌ترین دسته‌بندی‌ها</h3>
                <a href="{{ route('admin.categories.index') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topCategories->count() > 0)
                <div class="space-y-4">
                    @foreach($topCategories as $category)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" style="background-color: {{ $category->color }}20">
                                <div class="w-6 h-6 rounded-full" style="background-color: {{ $category->color }}"></div>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->name }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ $category->stories_count }} داستان</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->stories_count }}</div>
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

    <!-- Top Rated Stories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-yellow-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">بهترین داستان‌ها</h3>
                <a href="{{ route('admin.stories.index') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topRatedStories->count() > 0)
                <div class="space-y-4">
                    @foreach($topRatedStories as $story)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-cover shadow-sm flex-shrink-0" loading="lazy">
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ Str::limit($story->title, 30) }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ $story->ratings_count }} امتیاز</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($story->ratings_avg_rating, 1) }}</div>
                                <div class="text-xs text-gray-500">امتیاز</div>
                            </div>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $story->ratings_avg_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ داستان امتیازدهی شده‌ای یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Analytics Chart Section -->
<div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار و تحلیل‌ها</h3>
    </div>
    <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
            <!-- Conversion Rate -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-800 mb-1">نرخ تبدیل</p>
                        <p class="text-3xl font-bold text-green-900">{{ number_format(($stats['active_subscriptions'] / max($stats['total_users'], 1)) * 100, 1) }}%</p>
                        <p class="text-xs text-green-700 mt-1">اشتراک فعال / کل کاربران</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Session Time -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-800 mb-1">میانگین زمان پخش</p>
                        <p class="text-3xl font-bold text-blue-900">{{ number_format($stats['total_play_history'] / max($stats['total_users'], 1), 1) }}</p>
                        <p class="text-xs text-blue-700 mt-1">پخش به ازای هر کاربر</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- User Satisfaction -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-800 mb-1">رضایت کاربران</p>
                        <p class="text-3xl font-bold text-purple-900">{{ number_format($stats['average_rating'], 1) }}/5</p>
                        <p class="text-xs text-purple-700 mt-1">میانگین امتیاز</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Revenue per User -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-xl border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-yellow-800 mb-1">درآمد به ازای کاربر</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ number_format($stats['total_revenue'] / max($stats['total_users'], 1)) }}</p>
                        <p class="text-xs text-yellow-700 mt-1">تومان</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Placeholder -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 sm:p-6 lg:p-8 text-center">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-2">نمودارهای تحلیلی</h4>
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mb-4">نمودارهای پیشرفته آمار کاربران، درآمد و تعاملات</p>
            <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
                <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-indigo-100 text-indigo-800 text-xs sm:text-sm font-medium">
                    📊 نمودار درآمد ماهانه
                </span>
                <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-green-100 text-green-800 text-xs sm:text-sm font-medium">
                    👥 رشد کاربران روزانه
                </span>
                <span class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-purple-100 text-purple-800 text-xs sm:text-sm font-medium">
                    📈 تحلیل تعاملات
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reports Section -->
@if($recentReports->count() > 0)
<div class="mt-6 sm:mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
    <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-red-100 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین گزارش‌ها</h3>
            <a href="{{ route('admin.reports.index') }}" class="text-red-600 hover:text-red-800 text-sm font-medium">مشاهده همه</a>
        </div>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($recentReports as $report)
            <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ Str::limit($report->title ?? 'گزارش بدون عنوان', 50) }}</p>
                        <p class="text-sm text-gray-500">{{ $report->user->first_name ?? 'کاربر ناشناس' }}</p>
                        <p class="text-xs text-gray-400 mt-1">@jalaliRelative($report->created_at)</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($report->status === 'resolved') bg-green-100 text-green-800
                        @elseif($report->status === 'pending') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($report->status) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
