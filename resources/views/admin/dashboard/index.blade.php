@extends('admin.layouts.app')

@section('title', 'داشبورد مدیریت')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">داشبورد مدیریت</h1>
        <p class="text-gray-600">مدیریت کامل سیستم سکه، کوپن‌ها و پرداخت کمیسیون</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">کل کاربران</h3>
                    <p class="text-2xl font-bold text-blue-600" id="total-users">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-coins text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">سکه در گردش</h3>
                    <p class="text-2xl font-bold text-green-600" id="total-coins">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-ticket-alt text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">کدهای کوپن فعال</h3>
                    <p class="text-2xl font-bold text-yellow-600" id="active-coupons">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">پرداخت‌های در انتظار</h3>
                    <p class="text-2xl font-bold text-purple-600" id="pending-payments">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Coin Management -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">مدیریت سیستم سکه</h2>
                    <a href="{{ route('admin.coins.index') }}" class="text-blue-600 hover:text-blue-800">
                        مشاهده همه
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کاربران دارای سکه</span>
                        <span class="font-semibold" id="users-with-coins">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کل سکه‌های کسب شده</span>
                        <span class="font-semibold" id="coins-earned">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کل سکه‌های خرج شده</span>
                        <span class="font-semibold" id="coins-spent">0</span>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.coins.index') }}" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center block">
                        مدیریت سکه‌ها
                    </a>
                </div>
            </div>
        </div>

        <!-- Coupon Management -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">مدیریت کدهای کوپن</h2>
                    <a href="{{ route('admin.coupons.index') }}" class="text-blue-600 hover:text-blue-800">
                        مشاهده همه
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کل کدهای کوپن</span>
                        <span class="font-semibold" id="total-coupons">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کل استفاده‌ها</span>
                        <span class="font-semibold" id="total-usage">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">کل کمیسیون</span>
                        <span class="font-semibold" id="total-commission">0</span>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.coupons.index') }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-center block">
                        مدیریت کوپن‌ها
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Payments -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">پرداخت‌های کمیسیون</h2>
                <a href="{{ route('admin.commission-payments.index') }}" class="text-blue-600 hover:text-blue-800">
                    مشاهده همه
                </a>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600" id="pending-count">0</div>
                    <div class="text-sm text-gray-600">در انتظار</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600" id="processing-count">0</div>
                    <div class="text-sm text-gray-600">در حال پردازش</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600" id="paid-count">0</div>
                    <div class="text-sm text-gray-600">پرداخت شده</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600" id="total-amount">0</div>
                    <div class="text-sm text-gray-600">کل مبلغ</div>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.commission-payments.index') }}" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-center block">
                    مدیریت پرداخت‌ها
                </a>
            </div>
        </div>
    </div>

    <!-- Analytics Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center">
                <div class="bg-blue-100 p-3 rounded-full w-16 h-16 mx-auto mb-4">
                    <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">آمار سکه</h3>
                <p class="text-gray-600 mb-4">تحلیل جامع سیستم سکه و رفتار کاربران</p>
                <a href="{{ route('admin.analytics.coin') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    مشاهده آمار
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center">
                <div class="bg-green-100 p-3 rounded-full w-16 h-16 mx-auto mb-4">
                    <i class="fas fa-share-alt text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">آمار ارجاع</h3>
                <p class="text-gray-600 mb-4">تحلیل جامع سیستم ارجاع و رشد ارگانیک</p>
                <a href="{{ route('admin.analytics.referral') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    مشاهده آمار
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center">
                <div class="bg-purple-100 p-3 rounded-full w-16 h-16 mx-auto mb-4">
                    <i class="fas fa-handshake text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">مدیریت شرکا</h3>
                <p class="text-gray-600 mb-4">مدیریت شرکا، اینفلوئنسرها و معلمان</p>
                <a href="{{ route('admin.affiliate.dashboard') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    مدیریت شرکا
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/admin-dashboard.js') }}"></script>
@endsection
