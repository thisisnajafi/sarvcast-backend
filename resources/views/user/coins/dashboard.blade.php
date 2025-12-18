@extends('user.layouts.app')

@section('title', 'داشبورد سکه‌ها')

@section('content')
<div class="container mx-auto px-4 py-6 sm:py-8">
    <!-- Header -->
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">داشبورد سکه‌ها</h1>
        <p class="text-sm sm:text-base text-gray-600">مدیریت سکه‌ها، تراکنش‌ها و گزینه‌های تبدیل</p>
    </div>

    <!-- Coin Balance Card -->
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-xl p-4 sm:p-6 mb-6 sm:mb-8 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2">موجودی سکه‌ها</h2>
                <div class="text-3xl sm:text-4xl font-bold" id="coin-balance">0</div>
                <p class="text-xs sm:text-sm text-yellow-100 mt-1 sm:mt-2">سکه در دسترس</p>
            </div>
            <div class="text-right space-y-1 sm:space-y-2">
                <div class="text-sm sm:text-lg">کل سکه‌های کسب شده</div>
                <div class="text-xl sm:text-2xl font-bold" id="total-earned">0</div>
                <div class="text-sm sm:text-lg mt-1 sm:mt-2">کل سکه‌های خرج شده</div>
                <div class="text-xl sm:text-2xl font-bold" id="total-spent">0</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="bg-green-100 p-3 rounded-full flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">کسب سکه</h3>
                    <p class="text-sm text-gray-600">از طریق ارجاع و آزمون</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="bg-blue-100 p-3 rounded-full flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">تراکنش‌ها</h3>
                    <p class="text-sm text-gray-600">تاریخچه کامل</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="bg-purple-100 p-3 rounded-full flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">تبدیل سکه</h3>
                    <p class="text-sm text-gray-600">گزینه‌های تبدیل</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">تراکنش‌های اخیر</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <div id="recent-transactions" class="space-y-3 sm:space-y-4">
                <!-- Transactions will be loaded here -->
            </div>
            <div class="text-center mt-4 sm:mt-6">
                <button id="load-more-transactions" class="bg-blue-600 text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    مشاهده بیشتر
                </button>
            </div>
        </div>
    </div>

    <!-- Redemption Options -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">گزینه‌های تبدیل سکه</h2>
        </div>
        <div class="px-4 sm:px-6 py-4">
            <div id="redemption-options" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Redemption options will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Redemption Modal -->
<div id="redemption-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">تبدیل سکه</h3>
                <button id="close-redemption-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="redemption-form">
                <!-- Redemption form will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/coin-dashboard.js') }}"></script>
@endsection
