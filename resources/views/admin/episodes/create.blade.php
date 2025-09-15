@extends('admin.layouts.app')

@section('title', 'افزودن اپیزود جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">افزودن اپیزود جدید</h1>
        <p class="text-gray-600 mt-2">اطلاعات اپیزود جدید را وارد کنید</p>
    </div>

    <form method="POST" action="{{ route('admin.episodes.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات اصلی</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Story Selection -->
                <div class="md:col-span-2">
                    <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان *</label>
                    <select name="story_id" id="story_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('story_id') border-red-500 @enderror">
                        <option value="">انتخاب داستان</option>
                        @foreach($stories as $story)
                            <option value="{{ $story->id }}" {{ old('story_id') == $story->id ? 'selected' : '' }}>
                                {{ $story->title }} - {{ $story->category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('story_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Episode Title -->
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان اپیزود *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان اپیزود را وارد کنید">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Episode Number -->
                <div>
                    <label for="episode_number" class="block text-sm font-medium text-gray-700 mb-2">شماره اپیزود *</label>
                    <input type="number" name="episode_number" id="episode_number" value="{{ old('episode_number') }}" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('episode_number') border-red-500 @enderror" placeholder="1">
                    @error('episode_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Duration -->
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">مدت زمان (ثانیه) *</label>
                    <input type="number" name="duration" id="duration" value="{{ old('duration') }}" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('duration') border-red-500 @enderror" placeholder="300">
                    @error('duration')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Narrator -->
                <div>
                    <label for="narrator_id" class="block text-sm font-medium text-gray-700 mb-2">راوی</label>
                    <select name="narrator_id" id="narrator_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('narrator_id') border-red-500 @enderror">
                        <option value="">انتخاب راوی</option>
                        @foreach($narrators as $narrator)
                            <option value="{{ $narrator->id }}" {{ old('narrator_id') == $narrator->id ? 'selected' : '' }}>
                                {{ $narrator->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('narrator_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Release Date -->
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انتشار</label>
                    <input type="date" name="release_date" id="release_date" value="{{ old('release_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('release_date') border-red-500 @enderror">
                    @error('release_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات اپیزود را وارد کنید">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Media Files -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">فایل‌های رسانه</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Audio File -->
                <div>
                    <label for="audio_file" class="block text-sm font-medium text-gray-700 mb-2">فایل صوتی *</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="audio_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                    <span>آپلود فایل صوتی</span>
                                    <input id="audio_file" name="audio_file" type="file" accept="audio/*" required class="sr-only" onchange="updateAudioFileName(this)">
                                </label>
                                <p class="pr-1">یا کشیدن و رها کردن</p>
                            </div>
                            <p class="text-xs text-gray-500">MP3, WAV, M4A تا 100MB</p>
                            <p id="audio-file-name" class="text-sm text-gray-900 mt-2"></p>
                        </div>
                    </div>
                    @error('audio_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cover Image -->
                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-2">تصویر کاور</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="cover_image" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                    <span>آپلود تصویر</span>
                                    <input id="cover_image" name="cover_image" type="file" accept="image/*" class="sr-only" onchange="updateImageFileName(this)">
                                </label>
                                <p class="pr-1">یا کشیدن و رها کردن</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, JPEG تا 5MB</p>
                            <p id="image-file-name" class="text-sm text-gray-900 mt-2"></p>
                        </div>
                    </div>
                    @error('cover_image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">تنظیمات</h2>
            
            <div class="space-y-4">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>آرشیو شده</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Premium Status -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_premium" id="is_premium" value="1" {{ old('is_premium') ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_premium" class="mr-2 block text-sm text-gray-900">
                        اپیزود پولی
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 space-x-reverse">
            <a href="{{ route('admin.episodes.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                انصراف
            </a>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                ایجاد اپیزود
            </button>
        </div>
    </form>
</div>

<script>
function updateAudioFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('audio-file-name').textContent = fileName;
}

function updateImageFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('image-file-name').textContent = fileName;
}
</script>
@endsection
