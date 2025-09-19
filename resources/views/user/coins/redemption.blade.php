@extends('user.layouts.app')

@section('title', 'تبدیل سکه')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">تبدیل سکه</h1>
        <p class="text-gray-600">سکه‌های خود را به پاداش‌های ارزشمند تبدیل کنید</p>
    </div>

    <!-- Current Balance -->
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">موجودی فعلی</h2>
                <div class="text-4xl font-bold" id="current-balance">0</div>
                <p class="text-yellow-100 mt-2">سکه در دسترس</p>
            </div>
            <div class="text-right">
                <div class="text-lg">کل سکه‌های کسب شده</div>
                <div class="text-2xl font-bold" id="total-earned">0</div>
                <div class="text-lg mt-2">کل سکه‌های خرج شده</div>
                <div class="text-2xl font-bold" id="total-spent">0</div>
            </div>
        </div>
    </div>

    <!-- Redemption Categories -->
    <div class="mb-8">
        <div class="flex space-x-4 mb-6">
            <button class="redemption-category-btn active" data-category="all">همه</button>
            <button class="redemption-category-btn" data-category="premium">پریمیوم</button>
            <button class="redemption-category-btn" data-category="discount">تخفیف</button>
            <button class="redemption-category-btn" data-category="gift">هدیه</button>
            <button class="redemption-category-btn" data-category="special">ویژه</button>
        </div>
    </div>

    <!-- Redemption Options Grid -->
    <div id="redemption-options" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Redemption options will be loaded here -->
    </div>

    <!-- Redemption History -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">تاریخچه تبدیل‌ها</h2>
        </div>
        <div class="p-6">
            <div id="redemption-history" class="space-y-4">
                <!-- Redemption history will be loaded here -->
            </div>
            <div class="text-center mt-6">
                <button id="load-more-history" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    مشاهده بیشتر
                </button>
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

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">تأیید تبدیل</h3>
                <button id="close-confirmation-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="confirmation-content">
                <!-- Confirmation content will be loaded here -->
            </div>
            <div class="flex space-x-4 mt-6">
                <button id="confirm-redemption" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    تأیید تبدیل
                </button>
                <button id="cancel-redemption" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    لغو
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/coin-redemption.js') }}"></script>
@endsection

@section('styles')
<style>
.redemption-category-btn {
    @apply px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors;
}

.redemption-category-btn.active {
    @apply bg-blue-600 text-white border-blue-600;
}

.redemption-option-card {
    @apply bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer;
}

.redemption-option-card:hover {
    @apply transform scale-105;
}

.redemption-option-card.disabled {
    @apply opacity-50 cursor-not-allowed;
}

.redemption-option-card.disabled:hover {
    @apply transform-none;
}
</style>
@endsection
