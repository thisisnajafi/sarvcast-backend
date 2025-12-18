@extends('admin.layouts.app')

@section('title', 'ویرایش عنصر گیمیفیکیشن')
@section('page-title', 'ویرایش عنصر گیمیفیکیشن')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ویرایش عنصر گیمیفیکیشن</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات عنصر گیمیفیکیشن را ویرایش کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.gamification.update', $gamification) }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Title and Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $gamification->title) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان عنصر گیمیفیکیشن...">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع *</label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="">انتخاب نوع</option>
                        <option value="achievement" {{ old('type', $gamification->type) == 'achievement' ? 'selected' : '' }}>دستاورد</option>
                        <option value="badge" {{ old('type', $gamification->type) == 'badge' ? 'selected' : '' }}>نشان</option>
                        <option value="level" {{ old('type', $gamification->type) == 'level' ? 'selected' : '' }}>سطح</option>
                        <option value="reward" {{ old('type', $gamification->type) == 'reward' ? 'selected' : '' }}>پاداش</option>
                        <option value="challenge" {{ old('type', $gamification->type) == 'challenge' ? 'selected' : '' }}>چالش</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات عنصر گیمیفیکیشن...">{{ old('description', $gamification->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Story and Episode Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                    <select name="story_id" id="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('story_id') border-red-500 @enderror">
                        <option value="">انتخاب داستان (اختیاری)</option>
                        @foreach($stories as $story)
                            <option value="{{ $story->id }}" {{ (old('story_id', $gamification->story_id) == $story->id) ? 'selected' : '' }}>
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
                    <select name="episode_id" id="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('episode_id') border-red-500 @enderror">
                        <option value="">انتخاب اپیزود (اختیاری)</option>
                        @foreach($episodes as $episode)
                            <option value="{{ $episode->id }}" {{ (old('episode_id', $gamification->episode_id) == $episode->id) ? 'selected' : '' }}>
                                {{ $episode->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('episode_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Points and Rewards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="points_required" class="block text-sm font-medium text-gray-700 mb-2">امتیاز مورد نیاز *</label>
                    <input type="number" name="points_required" id="points_required" value="{{ old('points_required', $gamification->points_required) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('points_required') border-red-500 @enderror" placeholder="0">
                    @error('points_required')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_points" class="block text-sm font-medium text-gray-700 mb-2">پاداش امتیاز *</label>
                    <input type="number" name="reward_points" id="reward_points" value="{{ old('reward_points', $gamification->reward_points) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('reward_points') border-red-500 @enderror" placeholder="0">
                    @error('reward_points')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_coins" class="block text-sm font-medium text-gray-700 mb-2">پاداش سکه *</label>
                    <input type="number" name="reward_coins" id="reward_coins" value="{{ old('reward_coins', $gamification->reward_coins) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('reward_coins') border-red-500 @enderror" placeholder="0">
                    @error('reward_coins')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Images -->
            @if($gamification->icon || $gamification->badge_image)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($gamification->icon)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">آیکون فعلی</label>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <img src="{{ Storage::url($gamification->icon) }}" alt="{{ $gamification->title }}" class="w-16 h-16 rounded-lg object-cover">
                        <div>
                            <p class="text-sm text-gray-900">آیکون فعلی</p>
                            <p class="text-xs text-gray-500">برای تغییر، فایل جدید انتخاب کنید</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($gamification->badge_image)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">تصویر نشان فعلی</label>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <img src="{{ Storage::url($gamification->badge_image) }}" alt="{{ $gamification->title }}" class="w-16 h-16 rounded-lg object-cover">
                        <div>
                            <p class="text-sm text-gray-900">تصویر نشان فعلی</p>
                            <p class="text-xs text-gray-500">برای تغییر، فایل جدید انتخاب کنید</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- File Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">آیکون جدید</label>
                    <input type="file" name="icon" id="icon" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('icon') border-red-500 @enderror">
                    @error('icon')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">فرمت‌های مجاز: JPG, PNG, GIF, SVG (حداکثر 2MB)</p>
                </div>

                <div>
                    <label for="badge_image" class="block text-sm font-medium text-gray-700 mb-2">تصویر نشان جدید</label>
                    <input type="file" name="badge_image" id="badge_image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('badge_image') border-red-500 @enderror">
                    @error('badge_image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">فرمت‌های مجاز: JPG, PNG, GIF, SVG (حداکثر 2MB)</p>
                </div>
            </div>

            <!-- Conditions -->
            <div>
                <label for="conditions" class="block text-sm font-medium text-gray-700 mb-2">شرایط (JSON)</label>
                <textarea name="conditions" id="conditions" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('conditions') border-red-500 @enderror" placeholder='{"min_level": 5, "required_achievements": ["first_story", "quiz_master"]}'>{{ old('conditions', $gamification->conditions ? json_encode($gamification->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                @error('conditions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">شرایط کسب این عنصر گیمیفیکیشن را به صورت JSON وارد کنید.</p>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $gamification->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="mr-2 text-sm font-medium text-gray-700">فعال</label>
            </div>

            <!-- Current Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">اطلاعات فعلی</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p><strong>تاریخ ایجاد:</strong> {{ $gamification->created_at->format('Y/m/d H:i') }}</p>
                        <p><strong>آخرین به‌روزرسانی:</strong> {{ $gamification->updated_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div>
                        <p><strong>وضعیت فعلی:</strong> {{ $gamification->is_active ? 'فعال' : 'غیرفعال' }}</p>
                        <p><strong>نوع:</strong> 
                            @php
                                $typeLabels = [
                                    'achievement' => 'دستاورد',
                                    'badge' => 'نشان',
                                    'level' => 'سطح',
                                    'reward' => 'پاداش',
                                    'challenge' => 'چالش',
                                ];
                            @endphp
                            {{ $typeLabels[$gamification->type] ?? $gamification->type }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.gamification.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    به‌روزرسانی عنصر
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Validate JSON conditions
document.getElementById('conditions').addEventListener('blur', function() {
    const value = this.value.trim();
    if (value && value !== '') {
        try {
            JSON.parse(value);
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        } catch (e) {
            this.classList.remove('border-green-500');
            this.classList.add('border-red-500');
        }
    } else {
        this.classList.remove('border-red-500', 'border-green-500');
    }
});

// File preview
document.getElementById('icon').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add image preview here if needed
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('badge_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add image preview here if needed
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
