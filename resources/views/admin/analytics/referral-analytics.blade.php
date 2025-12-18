@extends('admin.layouts.app')

@section('title', 'آمار سیستم ارجاع')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">آمار سیستم ارجاع</h1>
        <p class="text-gray-600">تحلیل جامع عملکرد سیستم ارجاع و رشد ارگانیک</p>
    </div>

    <!-- System Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">کل کدهای ارجاع</h3>
                    <p class="text-2xl font-bold text-blue-600" id="total-referral-codes">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">ارجاعات تکمیل شده</h3>
                    <p class="text-2xl font-bold text-green-600" id="completed-referrals">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">نرخ تبدیل</h3>
                    <p class="text-2xl font-bold text-yellow-600" id="conversion-rate">0%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">درآمد کل ارجاعات</h3>
                    <p class="text-2xl font-bold text-purple-600" id="total-referral-revenue">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Referral Trends Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">روند ارجاعات</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="referral-trends-chart"></canvas>
            </div>
        </div>

        <!-- Referral Sources Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">منابع ارجاع</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="referral-sources-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Funnel Analysis and Geographic Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Referral Funnel -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">تحلیل قیف ارجاع</h2>
            <div id="funnel-analysis" class="space-y-4">
                <!-- Funnel analysis will be loaded here -->
            </div>
        </div>

        <!-- Geographic Distribution -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">توزیع جغرافیایی</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="geographic-distribution-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Performance by Timeframe -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">عملکرد بر اساس بازه زمانی</h2>
        </div>
        <div class="p-6">
            <div id="performance-by-timeframe" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Performance metrics will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">برترین ارجاع‌دهندگان</h2>
        </div>
        <div class="p-6">
            <div id="top-referrers-list" class="space-y-4">
                <!-- Top referrers will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Revenue Analysis -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">تحلیل درآمد</h2>
        </div>
        <div class="p-6">
            <div id="revenue-analysis" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Revenue analysis will be loaded here -->
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">سلامت سیستم ارجاع</h2>
        </div>
        <div class="p-6">
            <div id="system-health-details" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- System health details will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/admin-referral-analytics.js') }}"></script>
@endsection
