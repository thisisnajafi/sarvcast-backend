@extends('admin.layouts.app')

@section('title', 'ویرایش تایم‌لاین')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">ویرایش تایم‌لاین</h1>
            <p class="text-gray-600 mt-1">{{ $episode->title }} - {{ $episode->story->title }}</p>
            <div class="mt-2 text-sm text-gray-500">
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                    مدت اپیزود: {{ gmdate('i:s', $episode->duration) }}
                </span>
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs mr-2">
                    تایم‌لاین #{{ $timeline->image_order }}
                </span>
            </div>
        </div>
        <a href="{{ route('admin.episodes.timeline.index', $episode) }}" 
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            بازگشت
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="POST" action="{{ route('admin.episodes.timeline.update', [$episode, $timeline]) }}" 
              enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Time Range -->
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            بازه زمانی
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">زمان شروع (ثانیه)</label>
                            <input type="number" name="start_time" value="{{ old('start_time', $timeline->start_time) }}" 
                                   min="0" max="{{ $episode->duration }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                            @error('start_time')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">زمان پایان (ثانیه)</label>
                            <input type="number" name="end_time" value="{{ old('end_time', $timeline->end_time) }}" 
                                   min="1" max="{{ $episode->duration }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                            @error('end_time')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ترتیب تصویر</label>
                        <input type="number" name="image_order" value="{{ old('image_order', $timeline->image_order) }}" 
                               min="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                        @error('image_order')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Timeline Preview -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">پیش‌نمایش تایم‌لاین</h4>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ (($timeline->end_time - $timeline->start_time) / $episode->duration) * 100 }}%" id="timeline-preview"></div>
                            </div>
                            <span class="text-xs text-gray-500" id="timeline-duration">{{ $timeline->end_time - $timeline->start_time }} ثانیه</span>
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تصویر
                        </h3>
                    </div>
                    
                    <!-- Current Image -->
                    @if($timeline->image_url)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">تصویر فعلی</label>
                            <div class="relative">
                                <img src="{{ $timeline->getImageUrlFromPath($timeline->image_url) }}" alt="Current Timeline Image" 
                                     class="w-full h-48 object-cover rounded-lg border border-gray-200">
                                <div class="absolute top-2 left-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-xs">
                                    تصویر فعلی
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تصویر جدید (اختیاری)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="image_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                        <span>آپلود فایل جدید</span>
                                        <input id="image_file" name="image_file" type="file" accept="image/*" class="sr-only">
                                    </label>
                                    <p class="pr-1">یا کشیدن و رها کردن</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, JPEG, WebP تا 10MB</p>
                            </div>
                        </div>
                        @error('image_file')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Image Preview -->
                    <div id="image-preview" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">پیش‌نمایش تصویر جدید</label>
                        <img id="preview-img" class="w-full h-48 object-cover rounded-lg border border-gray-200">
                    </div>

                    <!-- Image Processing Options -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">گزینه‌های پردازش تصویر</h4>
                        
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="resize_image" value="1" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="mr-2 text-sm text-gray-700">تغییر اندازه تصویر</span>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">عرض (پیکسل)</label>
                                    <input type="number" name="image_width" value="800" min="100" max="2000" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ارتفاع (پیکسل)</label>
                                    <input type="number" name="image_height" value="600" min="100" max="2000" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scene Details -->
            <div class="border-t border-gray-200 pt-6">
                <div class="border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        جزئیات صحنه
                    </h3>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات صحنه</label>
                        <textarea name="scene_description" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                  placeholder="توضیحات صحنه...">{{ old('scene_description', $timeline->scene_description) }}</textarea>
                        @error('scene_description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نوع انتقال</label>
                            <select name="transition_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    required>
                                <option value="fade" {{ old('transition_type', $timeline->transition_type) == 'fade' ? 'selected' : '' }}>محو شدن</option>
                                <option value="cut" {{ old('transition_type', $timeline->transition_type) == 'cut' ? 'selected' : '' }}>برش</option>
                                <option value="dissolve" {{ old('transition_type', $timeline->transition_type) == 'dissolve' ? 'selected' : '' }}>حل شدن</option>
                                <option value="slide" {{ old('transition_type', $timeline->transition_type) == 'slide' ? 'selected' : '' }}>لغزش</option>
                            </select>
                            @error('transition_type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_key_frame" value="1" 
                                       {{ old('is_key_frame', $timeline->is_key_frame) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="mr-2 text-sm text-gray-700">فریم کلیدی</span>
                            </label>
                            <span class="text-xs text-gray-500 mr-2">(نشان‌دهنده لحظه مهم)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.episodes.timeline.index', $episode) }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    انصراف
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    به‌روزرسانی تایم‌لاین
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.querySelector('input[name="start_time"]');
    const endTimeInput = document.querySelector('input[name="end_time"]');
    const previewBar = document.getElementById('timeline-preview');
    const durationSpan = document.getElementById('timeline-duration');
    const imageInput = document.getElementById('image_file');
    const imagePreview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    function updateTimelinePreview() {
        const startTime = parseInt(startTimeInput.value) || 0;
        const endTime = parseInt(endTimeInput.value) || 0;
        const episodeDuration = {{ $episode->duration }};
        
        if (endTime > startTime) {
            const duration = endTime - startTime;
            const percentage = (duration / episodeDuration) * 100;
            previewBar.style.width = percentage + '%';
            durationSpan.textContent = duration + ' ثانیه';
        } else {
            previewBar.style.width = '0%';
            durationSpan.textContent = '0 ثانیه';
        }
    }

    startTimeInput.addEventListener('input', updateTimelinePreview);
    endTimeInput.addEventListener('input', updateTimelinePreview);

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endsection
