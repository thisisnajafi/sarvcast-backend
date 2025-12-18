@extends('admin.layouts.app')

@section('title', 'مشاهده سؤال کویز')
@section('page-title', 'مشاهده سؤال کویز')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">سؤال کویز</h1>
            <p class="text-gray-600">
                @if($quiz->story)
                    {{ $quiz->story->title }}
                    @if($quiz->episode)
                        - {{ $quiz->episode->title }}
                    @endif
                @else
                    سؤال عمومی
                @endif
            </p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.quiz.edit', $quiz) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.quiz.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($quiz->is_active)
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
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نوع سؤال</p>
                    @php
                        $typeLabels = [
                            'multiple_choice' => 'چندگزینه‌ای',
                            'true_false' => 'درست/غلط',
                            'fill_blank' => 'جای خالی',
                            'matching' => 'تطبیق',
                            'essay' => 'انشایی'
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $typeLabels[$quiz->question_type] ?? ucfirst($quiz->question_type) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">سطح دشواری</p>
                    @php
                        $difficultyLabels = [
                            'easy' => 'آسان',
                            'medium' => 'متوسط',
                            'hard' => 'سخت'
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $difficultyLabels[$quiz->difficulty_level] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">امتیاز</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $quiz->points }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Question Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Question Text -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">متن سؤال</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $quiz->question_text }}</p>
                </div>
            </div>

            <!-- Question Options (for multiple choice) -->
            @if($quiz->question_type === 'multiple_choice' && $quiz->options)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">گزینه‌ها</h2>
                </div>
                <div class="p-6">
                    @php
                        $options = json_decode($quiz->options, true);
                        $correctOptions = json_decode($quiz->correct_options, true);
                    @endphp
                    <div class="space-y-3">
                        @foreach($options as $index => $option)
                        <div class="flex items-center p-3 border rounded-lg {{ in_array($index, $correctOptions) ? 'border-green-300 bg-green-50' : 'border-gray-200' }}">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ in_array($index, $correctOptions) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                {{ chr(65 + $index) }}
                            </div>
                            <div class="mr-3 flex-1">
                                <p class="text-gray-900">{{ $option }}</p>
                            </div>
                            @if(in_array($index, $correctOptions))
                                <div class="flex-shrink-0">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        پاسخ صحیح
                                    </span>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Correct Answer -->
            @if($quiz->question_type !== 'multiple_choice')
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">پاسخ صحیح</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 font-medium">{{ $quiz->correct_answer }}</p>
                </div>
            </div>
            @endif

            <!-- Explanation -->
            @if($quiz->explanation)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">توضیح پاسخ</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $quiz->explanation }}</p>
                </div>
            </div>
            @endif

            <!-- Tags -->
            @if($quiz->tags)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">برچسب‌ها</h2>
                </div>
                <div class="p-6">
                    @php
                        $tags = json_decode($quiz->tags, true);
                    @endphp
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-sm rounded-full">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Question Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات سؤال</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">نوع سؤال</dt>
                        @php
                            $typeLabels = [
                                'multiple_choice' => 'چندگزینه‌ای',
                                'true_false' => 'درست/غلط',
                                'fill_blank' => 'جای خالی',
                                'matching' => 'تطبیق',
                                'essay' => 'انشایی'
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                {{ $typeLabels[$quiz->question_type] ?? ucfirst($quiz->question_type) }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">سطح دشواری</dt>
                        @php
                            $difficultyColors = [
                                'easy' => 'bg-green-100 text-green-800',
                                'medium' => 'bg-yellow-100 text-yellow-800',
                                'hard' => 'bg-red-100 text-red-800'
                            ];
                            $difficultyLabels = [
                                'easy' => 'آسان',
                                'medium' => 'متوسط',
                                'hard' => 'سخت'
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $difficultyColors[$quiz->difficulty_level] }}">
                                {{ $difficultyLabels[$quiz->difficulty_level] }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">امتیاز</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quiz->points }}</dd>
                    </div>

                    @if($quiz->time_limit)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">محدودیت زمانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quiz->time_limit }} ثانیه</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                        <dd class="mt-1">
                            @if($quiz->is_active)
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
                        <dd class="mt-1 text-sm text-gray-900">{{ $quiz->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quiz->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    <form method="POST" action="{{ route('admin.quiz.toggle', $quiz) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full {{ $quiz->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg transition-colors">
                            {{ $quiz->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.quiz.duplicate', $quiz) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            کپی سؤال
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.quiz.destroy', $quiz) }}" class="w-full" onsubmit="return confirm('آیا از حذف این سؤال اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف سؤال
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quiz Attempts -->
            @if($quiz->quizAttempts && $quiz->quizAttempts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">تلاش‌های کویز</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($quiz->quizAttempts->take(5) as $attempt)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $attempt->user->first_name }} {{ $attempt->user->last_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $attempt->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">{{ $attempt->score }}/{{ $quiz->points }}</p>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $attempt->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $attempt->is_correct ? 'صحیح' : 'غلط' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($quiz->quizAttempts->count() > 5)
                        <p class="text-xs text-gray-500 mt-3 text-center">و {{ $quiz->quizAttempts->count() - 5 }} تلاش دیگر...</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
