@extends('user.layouts.app')

@section('title', 'آزمون قسمت')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Episode Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2" id="episode-title">آزمون قسمت</h1>
                <p class="text-gray-600" id="episode-description">پاسخ به سوالات و کسب سکه</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">سکه قابل کسب</div>
                <div class="text-2xl font-bold text-yellow-600" id="total-coins">0</div>
            </div>
        </div>
    </div>

    <!-- Quiz Progress -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">پیشرفت آزمون</h2>
            <span class="text-sm text-gray-500" id="progress-text">0 از 0</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <!-- Quiz Questions Container -->
    <div id="quiz-container" class="space-y-8">
        <!-- Questions will be loaded here -->
    </div>

    <!-- Quiz Actions -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <span id="answered-count">0</span> سوال پاسخ داده شده
            </div>
            <div class="flex space-x-4">
                <button id="submit-quiz" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    ارسال پاسخ‌ها
                </button>
                <button id="reset-quiz" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    شروع مجدد
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quiz Result Modal -->
<div id="quiz-result-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" id="result-icon">
                    <!-- Icon will be set dynamically -->
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2" id="result-title">نتیجه آزمون</h3>
                <p class="text-gray-600 mb-4" id="result-message">پیام نتیجه</p>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-600" id="correct-answers">0</div>
                            <div class="text-sm text-gray-500">پاسخ صحیح</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-600" id="earned-coins">0</div>
                            <div class="text-sm text-gray-500">سکه کسب شده</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button id="view-explanations" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        مشاهده توضیحات
                    </button>
                    <button id="close-result-modal" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Explanation Modal -->
<div id="explanation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">توضیحات سوالات</h3>
                <button id="close-explanation-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="explanations-container" class="space-y-6">
                <!-- Explanations will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/episode-quiz.js') }}"></script>
@endsection

@section('styles')
<style>
.question-card {
    @apply bg-white rounded-lg shadow-md p-6 transition-all duration-300;
}

.question-card:hover {
    @apply shadow-lg;
}

.question-card.answered {
    @apply border-l-4 border-green-500;
}

.question-card.incorrect {
    @apply border-l-4 border-red-500;
}

.option-button {
    @apply w-full p-4 text-right border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer;
}

.option-button.selected {
    @apply bg-blue-100 border-blue-500 text-blue-700;
}

.option-button.correct {
    @apply bg-green-100 border-green-500 text-green-700;
}

.option-button.incorrect {
    @apply bg-red-100 border-red-500 text-red-700;
}

.option-button.disabled {
    @apply cursor-not-allowed opacity-50;
}

.coin-animation {
    animation: coinFlip 0.6s ease-in-out;
}

@keyframes coinFlip {
    0% { transform: rotateY(0deg); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(360deg); }
}

.progress-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endsection
