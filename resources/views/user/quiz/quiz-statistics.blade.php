@extends('user.layouts.app')

@section('title', 'آمار آزمون‌ها')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">آمار آزمون‌ها</h1>
        <p class="text-gray-600">تحلیل عملکرد و پیشرفت در آزمون‌ها</p>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">کل آزمون‌ها</h3>
                    <p class="text-2xl font-bold text-blue-600" id="total-quizzes">0</p>
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
                    <h3 class="text-lg font-semibold text-gray-900">پاسخ صحیح</h3>
                    <p class="text-2xl font-bold text-green-600" id="total-correct">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">سکه کسب شده</h3>
                    <p class="text-2xl font-bold text-yellow-600" id="total-coins">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">میانگین نمره</h3>
                    <p class="text-2xl font-bold text-purple-600" id="average-score">0%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Performance Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">نمودار عملکرد</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="performance-chart"></canvas>
            </div>
        </div>

        <!-- Score Distribution -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">توزیع نمرات</h2>
            <div class="h-64 flex items-center justify-center">
                <canvas id="score-distribution-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Episode Performance -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">عملکرد در قسمت‌ها</h2>
        </div>
        <div class="p-6">
            <div id="episode-performance" class="space-y-4">
                <!-- Episode performance will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">فعالیت‌های اخیر</h2>
        </div>
        <div class="p-6">
            <div id="recent-activity" class="space-y-4">
                <!-- Recent activity will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Achievements -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">دستاوردها</h2>
        </div>
        <div class="p-6">
            <div id="achievements" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Achievements will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/quiz-statistics.js') }}"></script>
@endsection

@section('styles')
<style>
.achievement-card {
    @apply bg-white border border-gray-200 rounded-lg p-4 text-center transition-all duration-300;
}

.achievement-card:hover {
    @apply shadow-md transform scale-105;
}

.achievement-card.unlocked {
    @apply border-green-500 bg-green-50;
}

.achievement-card.locked {
    @apply border-gray-300 bg-gray-50 opacity-60;
}

.achievement-icon {
    @apply w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center;
}

.achievement-icon.unlocked {
    @apply bg-green-100 text-green-600;
}

.achievement-icon.locked {
    @apply bg-gray-100 text-gray-400;
}

.progress-bar {
    @apply w-full bg-gray-200 rounded-full h-2;
}

.progress-fill {
    @apply bg-blue-600 h-2 rounded-full transition-all duration-300;
}

.stat-card {
    @apply bg-white rounded-lg shadow-md p-6 transition-all duration-300;
}

.stat-card:hover {
    @apply shadow-lg transform translateY(-2px);
}

.chart-container {
    @apply relative h-64;
}
</style>
@endsection
