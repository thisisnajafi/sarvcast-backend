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

                <!-- Narrator -->
                <div>
                    <label for="narrator_id" class="block text-sm font-medium text-gray-700 mb-2">راوی</label>
                    <select name="narrator_id" id="narrator_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('narrator_id') border-red-500 @enderror">
                        <option value="">انتخاب راوی</option>
                        @foreach($narrators as $narrator)
                            <option value="{{ $narrator->id }}" {{ old('narrator_id', $episode->narrator_id) == $narrator->id ? 'selected' : '' }}>
                                {{ $narrator->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('narrator_id')
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
                            {{ $story->title }} - {{ $story->category->name ?? 'بدون دسته' }}
                            @if($story->status)
                                ({{ ucfirst($story->status) }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('story_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">تمام داستان‌ها (منتشر شده، تایید شده، پیش‌نویس، در انتظار، رد شده)</p>
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
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ basename($episode->audio_url) }}</p>
                                <p class="text-sm text-gray-500">{{ $episode->duration }} دقیقه</p>
                            </div>
                        </div>
                        
                        <!-- Audio Player with Speed Controls -->
                        <div class="space-y-4">
                            <audio id="audio-player" controls class="w-full">
                                <source src="{{ $episode->audio_url }}" type="audio/mpeg">
                                مرورگر شما از پخش فایل صوتی پشتیبانی نمی‌کند.
                            </audio>
                            
                            <!-- Playback Speed Controls -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">سرعت پخش</label>
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <button type="button" id="speed-0.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(0.5)">0.5x</button>
                                    <button type="button" id="speed-1x" class="px-3 py-1 text-sm border border-gray-300 rounded bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1)">1x</button>
                                    <button type="button" id="speed-1.25x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.25)">1.25x</button>
                                    <button type="button" id="speed-1.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.5)">1.5x</button>
                                    <button type="button" id="speed-2x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(2)">2x</button>
                                </div>
                            </div>
                            
                            <!-- Time Display -->
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span id="current-time">00:00</span>
                                <span id="total-duration">00:00</span>
                            </div>
                            
                            <!-- Time Slider -->
                            <input type="range" id="time-slider" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
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

            <!-- Current Images -->
            @if($episode->image_urls && count($episode->image_urls) > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصاویر فعلی</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($episode->image_urls as $index => $imageUrl)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <img src="{{ $episode->getImageUrlFromPath($imageUrl) }}" 
                                     alt="Episode Image {{ $index + 1 }}" 
                                     class="w-full h-48 object-cover rounded-lg">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Current Timeline Images -->
            @if($episode->imageTimelines && $episode->imageTimelines->count() > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصاویر زمان‌بندی فعلی</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($episode->imageTimelines as $timeline)
                            <div class="border border-gray-200 rounded-lg p-4">
                                @if($timeline->image_url)
                                    <img src="{{ $timeline->getImageUrlFromPath($timeline->image_url) }}" 
                                         alt="Timeline Image" 
                                         class="w-full h-32 object-cover rounded-lg mb-3">
                                @endif
                                <div class="text-sm text-gray-600">
                                    <p><strong>شروع:</strong> {{ $timeline->start_time }} ثانیه</p>
                                    <p><strong>پایان:</strong> {{ $timeline->end_time }} ثانیه</p>
                                    @if($timeline->scene_description)
                                        <p><strong>توضیحات:</strong> {{ $timeline->scene_description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- People Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">افراد مرتبط</h3>
                <div>
                    <label for="people" class="block text-sm font-medium text-gray-700 mb-2">انتخاب افراد</label>
                    <select name="people[]" id="people" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('people') border-red-500 @enderror" size="6">
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ in_array($person->id, old('people', $episode->people->pluck('id')->toArray())) ? 'selected' : '' }}>
                                {{ $person->name }} 
                                @if($person->roles)
                                    ({{ implode(', ', $person->roles) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('people')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">برای انتخاب چند نفر، کلید Ctrl را نگه دارید</p>
                </div>
            </div>

            <!-- Voice Actors Management -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">مدیریت صداپیشگان</h3>
                <div id="voice-actors-list" class="space-y-4">
                    <!-- Voice actors will be added here dynamically -->
                </div>
                <div class="flex justify-center pt-4 border-t border-gray-200">
                    <button type="button" onclick="addVoiceActorRow()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 space-x-reverse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>افزودن صداپیشه</span>
                    </button>
                </div>
            </div>

            <!-- Image Timeline Management -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">مدیریت تصاویر بر اساس زمان</h3>
                <div id="image-timeline-list" class="space-y-4">
                    <!-- Image timelines will be added here dynamically -->
                </div>
                <div class="flex justify-center pt-4 border-t border-gray-200">
                    <button type="button" onclick="addImageTimelineRow()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2 space-x-reverse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>افزودن تصویر</span>
                    </button>
                </div>
            </div>

            <!-- Hidden inputs for timeline and voice actors data -->
            <input type="hidden" name="image_timeline_data" id="image-timeline-data">
            <input type="hidden" name="voice_actors_data" id="voice-actors-data">

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
// Base URL for assets
const baseUrl = '{{ url('') }}';

// Global variables
let voiceActorsData = [];
let imageTimelineData = [];
let voiceActorCounter = 0;
let imageTimelineCounter = 0;

// Available voice actors (from server)
const availableVoiceActors = @json($narrators ?? []);

document.addEventListener('DOMContentLoaded', function() {
    try {
        // Preview audio file
        const audioFileInput = document.getElementById('audio_file');
        if (audioFileInput) {
            audioFileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
                    
                    // Update file size field
                    const fileSizeField = document.getElementById('file_size');
                    if (fileSizeField) {
                        fileSizeField.value = fileSize;
                    }
                    
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
        }
        
        // Handle form submission with timeline and people data
        const form = document.getElementById('episode-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                
                try {
                    // Update timeline and voice actors data before submission
                    console.log('Updating timeline and voice actors data...');
                    updateImageTimelineData();
                    updateVoiceActorsData();
                } catch (error) {
                    console.error('Error updating form data:', error);
                    showNotification('خطا در به‌روزرسانی داده‌های فرم', 'error');
                }
            });
        }
        
        // Initialize timeline and voice actors data from existing episode
        initializeExistingData();
        
    } catch (error) {
        console.error('Error initializing page:', error);
        showNotification('خطا در بارگذاری صفحه', 'error');
    }
});

// Show notification function
function showNotification(message, type = 'info') {
    try {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-sm max-w-sm transform transition-all duration-300 translate-x-full`;
        
        // Set background color based on type
        switch(type) {
            case 'success':
                notification.classList.add('bg-green-500');
                break;
            case 'error':
                notification.classList.add('bg-red-500');
                break;
            case 'warning':
                notification.classList.add('bg-yellow-500');
                break;
            default:
                notification.classList.add('bg-blue-500');
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    } catch (error) {
        console.error('Error showing notification:', error);
    }
}

// Preview image function
function previewImage(input) {
    try {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewContainer = input.parentNode.querySelector('.image-preview-container');
                if (previewContainer) {
                    const img = previewContainer.querySelector('img');
                    if (img) {
                        img.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                }
            };
            
            reader.readAsDataURL(file);
        }
    } catch (error) {
        console.error('Error previewing image:', error);
        showNotification('خطا در پیش‌نمایش تصویر', 'error');
    }
}

// Initialize existing timeline and voice actors data
function initializeExistingData() {
    // Initialize timeline data from existing episode
    const existingTimelines = @json($episode->imageTimelines);
    if (existingTimelines && existingTimelines.length > 0) {
        existingTimelines.forEach(timeline => {
            addImageTimelineRow({
                start_time: timeline.start_time,
                end_time: timeline.end_time,
                image_file: timeline.image_url
            });
        });
    }
    
    // Initialize voice actors data from existing episode
    const existingVoiceActors = @json($episode->voiceActors);
    if (existingVoiceActors && existingVoiceActors.length > 0) {
        existingVoiceActors.forEach(voiceActor => {
            addVoiceActorRow({
                person_id: voiceActor.person_id,
                role: voiceActor.role
            });
        });
    }
}

// Update image timeline data for form submission
function updateImageTimelineData() {
    const imageTimelineData = [];
    const imageTimelineList = document.getElementById('image-timeline-list');
    
    if (imageTimelineList) {
        imageTimelineList.querySelectorAll('.bg-gray-50').forEach((row, index) => {
            const imageInput = row.querySelector('input[type="file"]');
            const existingImagePath = row.querySelector('.existing-image-path');
            const startTimeInput = row.querySelector('input[name^="timeline_start_"]');
            const endTimeInput = row.querySelector('input[name^="timeline_end_"]');
            const sceneDescriptionInput = row.querySelector('input[name^="timeline_scene_"]');
            const transitionTypeSelect = row.querySelector('select[name^="timeline_transition_"]');
            const isKeyFrameCheckbox = row.querySelector('input[name^="timeline_keyframe_"]');
            
            if (startTimeInput && endTimeInput) {
                const timelineData = {
                    start_time: startTimeInput.value || 0,
                    end_time: endTimeInput.value || 0,
                    scene_description: sceneDescriptionInput ? sceneDescriptionInput.value : '',
                    transition_type: transitionTypeSelect ? transitionTypeSelect.value : 'fade',
                    is_key_frame: isKeyFrameCheckbox ? isKeyFrameCheckbox.checked : false,
                    image_order: index
                };
                
                // Check if there's a new file uploaded
                if (imageInput && imageInput.files[0]) {
                    timelineData.image_file = imageInput.files[0].name;
                } else if (existingImagePath && existingImagePath.value) {
                    // Preserve existing image if no new file is uploaded
                    // Use the relative path as image_file (same as create method)
                    timelineData.image_file = existingImagePath.value;
                }
                
                imageTimelineData.push(timelineData);
            }
        });
    }
    
    const hiddenInput = document.getElementById('image-timeline-data');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(imageTimelineData);
    }
}

// Update voice actors data for form submission
function updateVoiceActorsData() {
    const voiceActorsData = [];
    const voiceActorsList = document.getElementById('voice-actors-list');
    
    if (voiceActorsList) {
        voiceActorsList.querySelectorAll('.bg-gray-50').forEach((row, index) => {
            const narratorSelect = row.querySelector('select[name^="voice_actor_"]');
            const roleInput = row.querySelector('input[name^="voice_actor_role_"]');
            
            if (narratorSelect && roleInput && narratorSelect.value && roleInput.value.trim()) {
                voiceActorsData.push({
                    person_id: narratorSelect.value,
                    role: roleInput.value.trim()
                });
            }
        });
    }
    
    const hiddenInput = document.getElementById('voice-actors-data');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(voiceActorsData);
    }
}

// Add image timeline row
function addImageTimelineRow(data = {}) {
    try {
        const imageTimelineList = document.getElementById('image-timeline-list');
        if (!imageTimelineList) {
            console.warn('Image timeline list element not found');
            return;
        }
    
    const row = document.createElement('div');
    row.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
    row.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تصویر</label>
                <input type="file" name="timeline_image_${Date.now()}" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" onchange="previewImage(this)">
                ${data.existing_image_url ? '<input type="hidden" class="existing-image-path" value="' + data.existing_image_url + '">' : ''}
                <div class="mt-2 image-preview-container" style="display: ${data.existing_image_url ? 'block' : 'none'};">
                    <img class="w-full h-32 object-cover rounded-lg border border-gray-300" alt="پیش‌نمایش تصویر" src="${data.existing_image_url ? baseUrl + '/' + data.existing_image_url : ''}">
                    ${data.existing_image_url ? '<p class="text-xs text-gray-500 mt-1">تصویر موجود - برای تغییر، فایل جدید انتخاب کنید</p>' : ''}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیح صحنه</label>
                <input type="text" name="timeline_scene_${Date.now()}" value="${data.scene_description || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="توضیح صحنه">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شروع (ثانیه)</label>
                <input type="number" name="timeline_start_${Date.now()}" value="${data.start_time || ''}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">پایان (ثانیه)</label>
                <input type="number" name="timeline_end_${Date.now()}" value="${data.end_time || ''}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع انتقال</label>
                <select name="timeline_transition_${Date.now()}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="fade" ${data.transition_type === 'fade' ? 'selected' : ''}>محو</option>
                    <option value="slide" ${data.transition_type === 'slide' ? 'selected' : ''}>اسلاید</option>
                    <option value="cut" ${data.transition_type === 'cut' ? 'selected' : ''}>برش</option>
                </select>
            </div>
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" name="timeline_keyframe_${Date.now()}" ${data.is_key_frame ? 'checked' : ''} class="mr-2">
                    <span class="text-sm text-gray-700">فریم کلیدی</span>
                </label>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="removeImageTimelineRow(this)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                حذف
            </button>
        </div>
    `;
    
        imageTimelineList.appendChild(row);
        imageTimelineCounter++;
        updateImageTimelineData();
    } catch (error) {
        console.error('Error adding image timeline row:', error);
        showNotification('خطا در افزودن ردیف تایم‌لاین', 'error');
    }
}

// Add voice actor row
function addVoiceActorRow(data = {}) {
    try {
        const voiceActorsList = document.getElementById('voice-actors-list');
        if (!voiceActorsList) {
            console.warn('Voice actors list element not found');
            return;
        }
    
    const row = document.createElement('div');
    row.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
    row.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">صداپیشه</label>
                <select name="voice_actor_${Date.now()}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">انتخاب صداپیشه</option>
                    @foreach($narrators as $narrator)
                        <option value="{{ $narrator->id }}" ${data.person_id == {{ $narrator->id }} ? 'selected' : ''}>{{ $narrator->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نقش</label>
                <input type="text" name="voice_actor_role_${Date.now()}" value="${data.role || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="نقش صداپیشه">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="removeVoiceActorRow(this)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                حذف
            </button>
        </div>
    `;
    
    voiceActorsList.appendChild(row);
    voiceActorCounter++;
    updateVoiceActorsData();
    } catch (error) {
        console.error('Error adding voice actor row:', error);
        showNotification('خطا در افزودن ردیف صداپیشه', 'error');
    }
}

// Remove image timeline row
function removeImageTimelineRow(button) {
    try {
        const row = button.closest('.bg-gray-50');
        if (row) {
            row.remove();
            updateImageTimelineData();
        }
    } catch (error) {
        console.error('Error removing image timeline row:', error);
        showNotification('خطا در حذف ردیف تایم‌لاین', 'error');
    }
}

// Remove voice actor row
function removeVoiceActorRow(button) {
    try {
        const row = button.closest('.bg-gray-50');
        if (row) {
            row.remove();
            updateVoiceActorsData();
        }
    } catch (error) {
        console.error('Error removing voice actor row:', error);
        showNotification('خطا در حذف ردیف صداپیشه', 'error');
    }
}

// Audio player functionality
function initializeAudioPlayer() {
    const audioPlayer = document.getElementById('audio-player');
    const timeSlider = document.getElementById('time-slider');
    const currentTimeSpan = document.getElementById('current-time');
    const totalDurationSpan = document.getElementById('total-duration');

    if (audioPlayer) {
        // Set default playback speed to 1x
        audioPlayer.playbackRate = 1;
        
        audioPlayer.addEventListener('loadedmetadata', function() {
            const duration = audioPlayer.duration;
            totalDurationSpan.textContent = formatTime(duration);
            timeSlider.max = duration;
        });

        audioPlayer.addEventListener('timeupdate', function() {
            currentTimeSpan.textContent = formatTime(audioPlayer.currentTime);
            timeSlider.value = audioPlayer.currentTime;
        });

        timeSlider.addEventListener('input', function() {
            audioPlayer.currentTime = timeSlider.value;
        });
    }
}

// Set playback speed function
function setPlaybackSpeed(speed) {
    const audioPlayer = document.getElementById('audio-player');
    if (audioPlayer) {
        audioPlayer.playbackRate = speed;
        
        // Update button styles
        const speedButtons = ['speed-0.5x', 'speed-1x', 'speed-1.25x', 'speed-1.5x', 'speed-2x'];
        speedButtons.forEach(buttonId => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.classList.remove('bg-blue-500', 'text-white');
                button.classList.add('hover:bg-gray-100');
            }
        });
        
        // Highlight selected speed button
        const selectedButton = document.getElementById(`speed-${speed}x`);
        if (selectedButton) {
            selectedButton.classList.add('bg-blue-500', 'text-white');
            selectedButton.classList.remove('hover:bg-gray-100');
        }
        
        // Show notification
        showNotification(`سرعت پخش به ${speed}x تغییر کرد`, 'info');
    }
}

// Format time function
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize existing timeline data
function initializeExistingTimelineData() {
    @if($episode->imageTimelines && $episode->imageTimelines->count() > 0)
        @foreach($episode->imageTimelines as $timeline)
            addImageTimelineRow({
                start_time: {{ $timeline->start_time }},
                end_time: {{ $timeline->end_time }},
                scene_description: '{{ addslashes($timeline->scene_description) }}',
                transition_type: '{{ $timeline->transition_type }}',
                is_key_frame: {{ $timeline->is_key_frame ? 'true' : 'false' }},
                existing_image_url: '{{ $timeline->image_url }}'
            });
        @endforeach
    @endif
}

// Initialize audio player when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeAudioPlayer();
    initializeExistingTimelineData();
});
</script>
@endsection

