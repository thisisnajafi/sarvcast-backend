@extends('admin.layouts.app')

@section('title', 'ویرایش اپیزود')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ویرایش اپیزود: {{ $episode->title }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.episodes.show', $episode) }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                مشاهده
            </a>
            <a href="{{ route('admin.episodes.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان اپیزود</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $episode->title) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror"
                           placeholder="عنوان اپیزود را وارد کنید" required>
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Episode Number -->
                <div>
                    <label for="episode_number" class="block text-sm font-medium text-gray-700 mb-2">شماره اپیزود</label>
                    <input type="number" name="episode_number" id="episode_number" value="{{ old('episode_number', $episode->episode_number) }}" min="1" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('episode_number') border-red-500 @enderror"
                           placeholder="شماره اپیزود" required>
                    @error('episode_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Story Selection -->
            <div class="mb-6">
                <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                <select name="story_id" id="story_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('story_id') border-red-500 @enderror" required>
                    <option value="">انتخاب داستان</option>
                    @foreach($stories as $story)
                        <option value="{{ $story->id }}" {{ old('story_id', $episode->story_id) == $story->id ? 'selected' : '' }}>
                            {{ $story->title }}
                        </option>
                    @endforeach
                </select>
                @error('story_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="توضیحات اپیزود را وارد کنید">{{ old('description', $episode->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Episode Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Duration -->
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">مدت زمان (دقیقه)</label>
                    <input type="number" name="duration" id="duration" value="{{ old('duration', $episode->duration) }}" min="1" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('duration') border-red-500 @enderror"
                           placeholder="مدت زمان اپیزود" required>
                    @error('duration')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Size -->
                <div>
                    <label for="file_size" class="block text-sm font-medium text-gray-700 mb-2">حجم فایل (مگابایت)</label>
                    <input type="number" name="file_size" id="file_size" value="{{ old('file_size', $episode->file_size) }}" min="0" step="0.1" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('file_size') border-red-500 @enderror"
                           placeholder="حجم فایل صوتی">
                    @error('file_size')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">ترتیب</label>
                    <input type="number" name="order" id="order" value="{{ old('order', $episode->order) }}" min="1" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('order') border-red-500 @enderror"
                           placeholder="ترتیب نمایش">
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Audio File -->
            @if($episode->audio_url)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">فایل صوتی فعلی</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ basename($episode->audio_url) }}</p>
                                <p class="text-sm text-gray-500">{{ $episode->duration }} دقیقه</p>
                            </div>
                            <audio controls class="w-64">
                                <source src="{{ $episode->audio_url }}" type="audio/mpeg">
                                مرورگر شما از پخش فایل صوتی پشتیبانی نمی‌کند.
                            </audio>
                        </div>
                    </div>
                </div>
            @endif

            <!-- New Audio File -->
            <div class="mb-6">
                <label for="audio_file" class="block text-sm font-medium text-gray-700 mb-2">فایل صوتی جدید</label>
                <input type="file" name="audio_file" id="audio_file" accept="audio/*" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('audio_file') border-red-500 @enderror">
                @error('audio_file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">حداکثر 100 مگابایت، فرمت‌های مجاز: MP3, WAV, M4A</p>
            </div>

            <!-- Current Image -->
            @if($episode->image_url)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصویر فعلی</h3>
                    <img src="{{ $episode->image_url }}" alt="Episode Image" class="w-full h-48 object-cover rounded-lg border">
                </div>
            @endif

            <!-- New Image -->
            <div class="mb-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر جدید</label>
                <input type="file" name="image" id="image" accept="image/*" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">حداکثر 5 مگابایت، فرمت‌های مجاز: JPG, PNG, WebP</p>
            </div>

            <!-- Status and Options -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                    <select name="status" id="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror" required>
                        <option value="draft" {{ old('status', $episode->status) == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="pending" {{ old('status', $episode->status) == 'pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                        <option value="approved" {{ old('status', $episode->status) == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                        <option value="published" {{ old('status', $episode->status) == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="rejected" {{ old('status', $episode->status) == 'rejected' ? 'selected' : '' }}>رد شده</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Published At -->
                <div>
                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انتشار</label>
                    <input type="datetime-local" name="published_at" id="published_at" 
                           value="{{ old('published_at', $episode->published_at ? $episode->published_at->format('Y-m-d\TH:i') : '') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('published_at') border-red-500 @enderror">
                    @error('published_at')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="mb-6">
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_premium" id="is_premium" value="1" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('is_premium', $episode->is_premium) ? 'checked' : '' }}>
                        <label for="is_premium" class="mr-2 text-sm text-gray-700">اپیزود پولی</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_free" id="is_free" value="1" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('is_free', $episode->is_free) ? 'checked' : '' }}>
                        <label for="is_free" class="mr-2 text-sm text-gray-700">رایگان</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4 space-x-reverse">
                <a href="{{ route('admin.episodes.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    انصراف
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    به‌روزرسانی اپیزود
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview audio file
    document.getElementById('audio_file').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
            
            // Update file size field
            document.getElementById('file_size').value = fileSize;
            
            // Create audio preview
            const audio = document.createElement('audio');
            audio.controls = true;
            audio.src = URL.createObjectURL(file);
            
            // Remove existing preview
            const existingPreview = document.getElementById('audio_preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Add new preview
            audio.id = 'audio_preview';
            audio.className = 'mt-2 w-full';
            this.parentNode.appendChild(audio);
        }
    });
    
    // Preview image upload
    document.getElementById('image').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById('image_preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = 'image_preview';
                    preview.className = 'mt-2 w-32 h-32 object-cover rounded-lg border';
                    document.getElementById('image').parentNode.appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>
@endsection

