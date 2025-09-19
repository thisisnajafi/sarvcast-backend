@extends('admin.layouts.app')

@section('title', 'ویرایش سؤال کویز')
@section('page-title', 'ویرایش سؤال کویز')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ویرایش سؤال کویز</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات سؤال کویز را ویرایش کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.quiz.update', $quiz) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Story and Episode Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                    <select name="story_id" id="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('story_id') border-red-500 @enderror">
                        <option value="">انتخاب داستان (اختیاری)</option>
                        @foreach($stories as $story)
                            <option value="{{ $story->id }}" {{ (old('story_id', $quiz->story_id) == $story->id) ? 'selected' : '' }}>
                                {{ $story->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('story_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="episode_id" class="block text-sm font-medium text-gray-700 mb-2">اپیزود</label>
                    <select name="episode_id" id="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('episode_id') border-red-500 @enderror">
                        <option value="">انتخاب اپیزود (اختیاری)</option>
                        @foreach($episodes as $episode)
                            <option value="{{ $episode->id }}" {{ (old('episode_id', $quiz->episode_id) == $episode->id) ? 'selected' : '' }}>
                                {{ $episode->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('episode_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Question Text -->
            <div>
                <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">متن سؤال *</label>
                <textarea name="question_text" id="question_text" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('question_text') border-red-500 @enderror" placeholder="متن سؤال را وارد کنید...">{{ old('question_text', $quiz->question_text) }}</textarea>
                @error('question_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Question Type and Difficulty -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">نوع سؤال *</label>
                    <select name="question_type" id="question_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('question_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع سؤال</option>
                        <option value="multiple_choice" {{ old('question_type', $quiz->question_type) == 'multiple_choice' ? 'selected' : '' }}>چندگزینه‌ای</option>
                        <option value="true_false" {{ old('question_type', $quiz->question_type) == 'true_false' ? 'selected' : '' }}>درست/غلط</option>
                        <option value="fill_blank" {{ old('question_type', $quiz->question_type) == 'fill_blank' ? 'selected' : '' }}>جای خالی</option>
                        <option value="matching" {{ old('question_type', $quiz->question_type) == 'matching' ? 'selected' : '' }}>تطبیق</option>
                        <option value="essay" {{ old('question_type', $quiz->question_type) == 'essay' ? 'selected' : '' }}>انشایی</option>
                    </select>
                    @error('question_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="difficulty_level" class="block text-sm font-medium text-gray-700 mb-2">سطح دشواری *</label>
                    <select name="difficulty_level" id="difficulty_level" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('difficulty_level') border-red-500 @enderror">
                        <option value="">انتخاب سطح دشواری</option>
                        <option value="easy" {{ old('difficulty_level', $quiz->difficulty_level) == 'easy' ? 'selected' : '' }}>آسان</option>
                        <option value="medium" {{ old('difficulty_level', $quiz->difficulty_level) == 'medium' ? 'selected' : '' }}>متوسط</option>
                        <option value="hard" {{ old('difficulty_level', $quiz->difficulty_level) == 'hard' ? 'selected' : '' }}>سخت</option>
                    </select>
                    @error('difficulty_level')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Points and Time Limit -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="points" class="block text-sm font-medium text-gray-700 mb-2">امتیاز *</label>
                    <input type="number" name="points" id="points" value="{{ old('points', $quiz->points) }}" min="1" max="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('points') border-red-500 @enderror" placeholder="10">
                    @error('points')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="time_limit" class="block text-sm font-medium text-gray-700 mb-2">محدودیت زمانی (ثانیه)</label>
                    <input type="number" name="time_limit" id="time_limit" value="{{ old('time_limit', $quiz->time_limit) }}" min="10" max="3600" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('time_limit') border-red-500 @enderror" placeholder="60">
                    @error('time_limit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Multiple Choice Options -->
            <div id="multiple-choice-options" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">گزینه‌های چندگزینه‌ای *</label>
                <div id="options-container">
                    <div class="space-y-3">
                        @php
                            $options = $quiz->options ? json_decode($quiz->options, true) : [];
                            $correctOptions = $quiz->correct_options ? json_decode($quiz->correct_options, true) : [];
                        @endphp
                        @for($i = 0; $i < max(4, count($options)); $i++)
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <input type="text" name="options[]" value="{{ $options[$i] ?? '' }}" placeholder="گزینه {{ $i + 1 }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <input type="checkbox" name="correct_options[]" value="{{ $i }}" {{ in_array($i, $correctOptions) ? 'checked' : '' }} class="rounded">
                            <span class="text-sm text-gray-600">پاسخ صحیح</span>
                            @if($i >= 4)
                                <button type="button" onclick="removeOption(this)" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                            @endif
                        </div>
                        @endfor
                    </div>
                    <button type="button" onclick="addOption()" class="mt-3 bg-gray-200 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                        افزودن گزینه
                    </button>
                </div>
            </div>

            <!-- Correct Answer -->
            <div id="correct-answer-section">
                <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-2">پاسخ صحیح *</label>
                <input type="text" name="correct_answer" id="correct_answer" value="{{ old('correct_answer', $quiz->correct_answer) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('correct_answer') border-red-500 @enderror" placeholder="پاسخ صحیح را وارد کنید...">
                @error('correct_answer')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Explanation -->
            <div>
                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-2">توضیح پاسخ</label>
                <textarea name="explanation" id="explanation" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('explanation') border-red-500 @enderror" placeholder="توضیح پاسخ صحیح...">{{ old('explanation', $quiz->explanation) }}</textarea>
                @error('explanation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tags -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">برچسب‌ها</label>
                @php
                    $tags = $quiz->tags ? json_decode($quiz->tags, true) : [];
                @endphp
                <input type="text" name="tags_input" id="tags_input" value="{{ implode(', ', $tags) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="برچسب‌ها را با کاما جدا کنید...">
                <p class="mt-1 text-sm text-gray-500">مثال: آموزش، سرگرمی، داستان</p>
                <div id="tags-display" class="mt-2 flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $quiz->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <label for="is_active" class="mr-2 text-sm font-medium text-gray-700">فعال</label>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.quiz.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    به‌روزرسانی سؤال
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let optionCount = {{ max(4, count($quiz->options ? json_decode($quiz->options, true) : [])) }};

function addOption() {
    const container = document.getElementById('options-container');
    const newOption = document.createElement('div');
    newOption.className = 'flex items-center space-x-3 space-x-reverse';
    newOption.innerHTML = `
        <input type="text" name="options[]" placeholder="گزینه ${optionCount + 1}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
        <input type="checkbox" name="correct_options[]" value="${optionCount}" class="rounded">
        <span class="text-sm text-gray-600">پاسخ صحیح</span>
        <button type="button" onclick="removeOption(this)" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
    `;
    container.appendChild(newOption);
    optionCount++;
}

function removeOption(button) {
    button.parentElement.remove();
}

// Show/hide multiple choice options based on question type
document.getElementById('question_type').addEventListener('change', function() {
    const multipleChoiceOptions = document.getElementById('multiple-choice-options');
    const correctAnswerSection = document.getElementById('correct-answer-section');
    
    if (this.value === 'multiple_choice') {
        multipleChoiceOptions.style.display = 'block';
        correctAnswerSection.style.display = 'none';
    } else {
        multipleChoiceOptions.style.display = 'none';
        correctAnswerSection.style.display = 'block';
    }
});

// Handle tags input
document.getElementById('tags_input').addEventListener('input', function() {
    const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag);
    const tagsDisplay = document.getElementById('tags-display');
    
    tagsDisplay.innerHTML = '';
    tags.forEach((tag, index) => {
        const tagElement = document.createElement('span');
        tagElement.className = 'px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full';
        tagElement.textContent = tag;
        tagsDisplay.appendChild(tagElement);
    });
    
    // Update hidden input for form submission
    const hiddenInput = document.querySelector('input[name="tags"]');
    if (!hiddenInput) {
        const form = document.querySelector('form');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tags';
        form.appendChild(input);
    }
    document.querySelector('input[name="tags"]').value = JSON.stringify(tags);
});

// Initialize form based on current values
document.addEventListener('DOMContentLoaded', function() {
    const questionType = document.getElementById('question_type');
    if (questionType.value === 'multiple_choice') {
        document.getElementById('multiple-choice-options').style.display = 'block';
        document.getElementById('correct-answer-section').style.display = 'none';
    }
    
    // Initialize tags
    const tagsInput = document.getElementById('tags_input');
    if (tagsInput.value) {
        tagsInput.dispatchEvent(new Event('input'));
    }
});
</script>
@endsection
