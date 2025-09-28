@extends('admin.layouts.app')

@section('title', 'افزودن اپیزود جدید')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">افزودن اپیزود جدید</h1>
        <p class="text-gray-600 mt-2">اطلاعات اپیزود جدید را وارد کنید</p>
    </div>

    <form method="POST" action="{{ route('admin.episodes.store') }}" enctype="multipart/form-data" class="space-y-6" id="episode-form">
        @csrf
        
        <!-- Audio File Upload - MOVED TO TOP -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">فایل صوتی *</h2>
            
            <div class="space-y-4">
                <!-- Audio File Upload -->
                <div>
                    <label for="audio_file" class="block text-sm font-medium text-gray-700 mb-2">آپلود فایل صوتی</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="audio_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                    <span>آپلود فایل صوتی</span>
                                    <input id="audio_file" name="audio_file" type="file" accept="audio/*" required class="sr-only" onchange="handleAudioUpload(this)">
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

                <!-- Audio Player (Hidden initially) -->
                <div id="audio-player-section" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-md font-medium text-gray-900 mb-3">پخش کننده صوتی</h3>
                        <audio id="audio-player" controls class="w-full mb-4">
                            <source id="audio-source" src="" type="audio/mpeg">
                            مرورگر شما از پخش کننده صوتی پشتیبانی نمی‌کند.
                        </audio>
                        
                        <!-- Playback Speed Controls -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">سرعت پخش</label>
                            <div class="flex items-center space-x-4 space-x-reverse">
                                <button type="button" id="speed-0.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(0.5)">0.5x</button>
                                <button type="button" id="speed-1x" class="px-3 py-1 text-sm border border-gray-300 rounded bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1)">1x</button>
                                <button type="button" id="speed-1.25x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.25)">1.25x</button>
                                <button type="button" id="speed-1.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.5)">1.5x</button>
                                <button type="button" id="speed-2x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(2)">2x</button>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span id="current-time">00:00</span>
                            <span id="total-duration">00:00</span>
                        </div>
                        <div class="mt-2">
                            <input type="range" id="time-slider" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
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
                                {{ $story->title }} - {{ $story->category->name ?? 'بدون دسته' }} 
                                @if($story->status)
                                    ({{ ucfirst($story->status) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('story_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">تمام داستان‌ها (منتشر شده، تایید شده، پیش‌نویس، در انتظار، رد شده)</p>
                </div>

                <!-- Episode Title -->
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان اپیزود *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان اپیزود را وارد کنید">
                    @error('title')
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

                <!-- Episode Number -->
                <div>
                    <label for="episode_number" class="block text-sm font-medium text-gray-700 mb-2">شماره اپیزود *</label>
                    <input type="number" name="episode_number" id="episode_number" value="{{ old('episode_number') }}" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('episode_number') border-red-500 @enderror" placeholder="1">
                    @error('episode_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Duration - Auto-filled from audio -->
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">مدت زمان (ثانیه) *</label>
                    <input type="number" name="duration" id="duration" value="{{ old('duration') }}" required min="1" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-primary focus:border-transparent @error('duration') border-red-500 @enderror" placeholder="خودکار از فایل صوتی">
                    <p class="text-xs text-gray-500 mt-1">مدت زمان به صورت خودکار از فایل صوتی استخراج می‌شود</p>
                    @error('duration')
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

        <!-- Voice Actors Management -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">مدیریت صداپیشه‌ها</h2>
            
            <div class="space-y-4">
                <!-- Add Voice Actor Button -->
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">صداپیشه‌ها و زمان‌بندی آن‌ها را تعریف کنید</p>
                    <button type="button" id="add-voice-actor" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        افزودن صداپیشه
                    </button>
                </div>

                <!-- Voice Actors List -->
                <div id="voice-actors-list" class="space-y-4">
                    <!-- Voice actors will be added here dynamically -->
                </div>

                <!-- Hidden inputs for voice actors data -->
                <input type="hidden" name="voice_actors_data" id="voice-actors-data">
            </div>
        </div>

        <!-- Image Timeline Management -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">مدیریت تصاویر بر اساس زمان</h2>
            
            <div class="space-y-4">
                <!-- Image Timeline List -->
                <div id="image-timeline-list" class="space-y-4">
                    <!-- Image timelines will be added here dynamically -->
                </div>

                <!-- Add Image Timeline Button - MOVED TO BOTTOM -->
                <div class="flex justify-center pt-4 border-t border-gray-200">
                    <button type="button" id="add-image-timeline" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2 space-x-reverse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>افزودن تصویر در زمان فعلی</span>
                    </button>
                </div>

                <!-- Hidden inputs for image timeline data -->
                <input type="hidden" name="image_timeline_data" id="image-timeline-data">
            </div>
        </div>

        <!-- People Selection -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">افراد مرتبط</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="people" class="block text-sm font-medium text-gray-700 mb-2">انتخاب افراد</label>
                    <select name="people[]" id="people" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('people') border-red-500 @enderror" size="6">
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ in_array($person->id, old('people', [])) ? 'selected' : '' }}>
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
        </div>

        <!-- Cover Image -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">تصویر کاور</h2>
            
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

        <!-- Settings -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">تنظیمات</h2>
            
            <div class="space-y-4">
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
        <div class="flex justify-between items-center">
            <div class="flex space-x-2 space-x-reverse">
                <button type="button" onclick="clearFormData()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                    پاک کردن داده‌های ذخیره شده
                </button>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <a href="{{ route('admin.episodes.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    انصراف
                </a>
                <button type="submit" id="submit-episode-btn" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    ایجاد اپیزود
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Global variables
let audioPlayer = null;
let currentAudioFile = null;
let voiceActorsData = [];
let imageTimelineData = [];
let voiceActorCounter = 0;
let imageTimelineCounter = 0;

// Available voice actors (from server)
const availableVoiceActors = @json($narrators);

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAudioPlayer();
    initializeVoiceActorManagement();
    initializeImageTimelineManagement();
});

// Audio Player Functions
function initializeAudioPlayer() {
    audioPlayer = document.getElementById('audio-player');
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
            const currentTime = audioPlayer.currentTime;
            currentTimeSpan.textContent = formatTime(currentTime);
            timeSlider.value = currentTime;
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

// Show notification function
function showNotification(message, type = 'info') {
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
}

function handleAudioUpload(input) {
    const file = input.files[0];
    if (file) {
        document.getElementById('audio-file-name').textContent = file.name;
        
        // Create object URL for the audio file
        const audioURL = URL.createObjectURL(file);
        const audioSource = document.getElementById('audio-source');
        const audioPlayerSection = document.getElementById('audio-player-section');
        const durationField = document.getElementById('duration');
        
        audioSource.src = audioURL;
        audioPlayer.load();
        
        // Show audio player
        audioPlayerSection.classList.remove('hidden');
        
        // Show loading state for duration
        durationField.value = 'در حال بارگذاری...';
        durationField.classList.add('text-blue-600');
        
        // Update duration field when metadata is loaded
        audioPlayer.addEventListener('loadedmetadata', function() {
            const duration = Math.floor(audioPlayer.duration);
            durationField.value = duration;
            durationField.classList.remove('text-blue-600');
            
            // Update the time slider max value
            const timeSlider = document.getElementById('time-slider');
            timeSlider.max = duration;
            
            // Show success message
            showDurationSuccess(duration);
            
            // Initialize first image timeline automatically
            if (typeof initializeFirstImageTimeline === 'function') {
                initializeFirstImageTimeline();
            }
        });
        
        // Handle loading errors
        audioPlayer.addEventListener('error', function() {
            durationField.value = '';
            durationField.classList.remove('text-blue-600');
            durationField.classList.add('text-red-600');
            durationField.placeholder = 'خطا در بارگذاری فایل صوتی';
        });
        
        currentAudioFile = file;
    }
}

function showDurationSuccess(duration) {
    const durationField = document.getElementById('duration');
    const minutes = Math.floor(duration / 60);
    const seconds = duration % 60;
    const formattedDuration = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    // Create a temporary success message
    const successMessage = document.createElement('div');
    successMessage.className = 'text-green-600 text-xs mt-1';
    successMessage.textContent = `مدت زمان استخراج شد: ${formattedDuration}`;
    
    // Remove any existing success message
    const existingMessage = durationField.parentNode.querySelector('.text-green-600');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Add the success message
    durationField.parentNode.appendChild(successMessage);
    
    // Remove the message after 3 seconds
    setTimeout(() => {
        if (successMessage.parentNode) {
            successMessage.remove();
        }
    }, 3000);
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}

function updateImageFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('image-file-name').textContent = fileName;
}
</script>

<script src="{{ asset('js/form-state-manager.js') }}"></script>
<script src="{{ asset('js/admin-episode-management.js') }}"></script>

<script>
// Enhanced form handling with state persistence
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form state management
    if (window.episodeFormManager) {
        console.log('Episode form state management initialized');
    }
    
    // Debug button state
    const submitButton = document.getElementById('submit-episode-btn');
    if (submitButton) {
        console.log('Submit button found:', submitButton);
        console.log('Button disabled:', submitButton.disabled);
        console.log('Button style:', submitButton.style.cssText);
        console.log('Button classes:', submitButton.className);
        
        // Force enable button if it's disabled
        if (submitButton.disabled) {
            console.log('Button was disabled, enabling it...');
            submitButton.disabled = false;
        }
        
        // Add click event listener for debugging
        submitButton.addEventListener('click', function(e) {
            console.log('Submit button clicked!');
            console.log('Form valid:', document.getElementById('episode-form').checkValidity());
        });
    }
    
    // Handle form submission with better error handling
    const form = document.getElementById('episode-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            // Update timeline and voice actors data before submission
            console.log('Updating timeline and voice actors data...');
            updateImageTimelineData();
            updateVoiceActorsData();
            
            // Show loading state
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'در حال ایجاد...';
            submitButton.disabled = true;
            
            // Re-enable button after 10 seconds (in case of timeout)
            setTimeout(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }, 10000);
        });
    }
    
    // Add auto-save indicator
    addAutoSaveIndicator();
});

function addAutoSaveIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'auto-save-indicator';
    indicator.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300';
    indicator.textContent = '✓ ذخیره خودکار';
    document.body.appendChild(indicator);
    
    // Show indicator when form is saved
    const form = document.getElementById('episode-form');
    if (form) {
        form.addEventListener('input', () => {
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        });
    }
}

// Enhanced voice actor management with persistence
function addVoiceActorRow(data = {}) {
    const voiceActorsList = document.getElementById('voice-actors-list');
    if (!voiceActorsList) return;
    
    const row = document.createElement('div');
    row.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
    row.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">صداپیشه</label>
                <select name="voice_actor_${voiceActorCounter}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">انتخاب صداپیشه</option>
                    ${availableVoiceActors.map(actor => 
                        `<option value="${actor.id}" ${data.narrator_id == actor.id ? 'selected' : ''}>${actor.name}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="removeVoiceActorRow(this)" class="w-full px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    حذف
                </button>
            </div>
        </div>
    `;
    
    voiceActorsList.appendChild(row);
    voiceActorCounter++;
    updateVoiceActorsData();
}

// Enhanced image timeline management with persistence and auto-linking
function addImageTimelineRow(data = {}) {
    const imageTimelineList = document.getElementById('image-timeline-list');
    if (!imageTimelineList) return;
    
    // Get the last image's end time to set as start time for new image
    const existingRows = imageTimelineList.querySelectorAll('.bg-gray-50');
    let suggestedStartTime = '';
    
    if (existingRows.length > 0) {
        const lastRow = existingRows[existingRows.length - 1];
        const lastEndTimeInput = lastRow.querySelector('input[name^="timeline_end_"]');
        if (lastEndTimeInput && lastEndTimeInput.value) {
            suggestedStartTime = lastEndTimeInput.value;
        }
    }
    
    const row = document.createElement('div');
    row.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
    row.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تصویر</label>
                <input type="file" name="timeline_image_${imageTimelineCounter}" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" onchange="previewImage(this)">
                <div class="mt-2 image-preview-container" style="display: none;">
                    <img class="w-full h-32 object-cover rounded-lg border border-gray-300" alt="پیش‌نمایش تصویر">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شروع (ثانیه)</label>
                <input type="number" name="timeline_start_${imageTimelineCounter}" value="${data.start_time || suggestedStartTime}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0" onchange="updatePreviousImageEndTime(this)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">پایان (ثانیه)</label>
                <input type="number" name="timeline_end_${imageTimelineCounter}" value="${data.end_time || ''}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="removeImageTimelineRow(this)" class="w-full px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    حذف
                </button>
            </div>
        </div>
    `;
    
    imageTimelineList.appendChild(row);
    imageTimelineCounter++;
    updateImageTimelineData();
}

// Preview image function
function previewImage(input) {
    const file = input.files[0];
    const previewContainer = input.parentElement.querySelector('.image-preview-container');
    const previewImg = previewContainer.querySelector('img');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
}

// Update previous image's end time when current image's start time changes
function updatePreviousImageEndTime(currentStartTimeInput) {
    const imageTimelineList = document.getElementById('image-timeline-list');
    if (!imageTimelineList) return;
    
    const currentRow = currentStartTimeInput.closest('.bg-gray-50');
    const allRows = Array.from(imageTimelineList.querySelectorAll('.bg-gray-50'));
    const currentIndex = allRows.indexOf(currentRow);
    
    // If this is not the first image, update the previous image's end time
    if (currentIndex > 0) {
        const previousRow = allRows[currentIndex - 1];
        const previousEndTimeInput = previousRow.querySelector('input[name^="timeline_end_"]');
        
        if (previousEndTimeInput && currentStartTimeInput.value) {
            previousEndTimeInput.value = currentStartTimeInput.value;
            updateImageTimelineData();
        }
    }
}

// Update voice actors data for form submission
function updateVoiceActorsData() {
    const voiceActorsData = [];
    const voiceActorsList = document.getElementById('voice-actors-list');
    
    voiceActorsList.querySelectorAll('.bg-gray-50').forEach((row, index) => {
        const narratorSelect = row.querySelector('select[name^="voice_actor_"]');
        
        if (narratorSelect && narratorSelect.value) {
            voiceActorsData.push({
                narrator_id: narratorSelect.value
            });
        }
    });
    
    const hiddenInput = document.getElementById('voice-actors-data');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(voiceActorsData);
    }
}

// Update image timeline data for form submission
function updateImageTimelineData() {
    const imageTimelineData = [];
    const imageTimelineList = document.getElementById('image-timeline-list');
    
    imageTimelineList.querySelectorAll('.bg-gray-50').forEach((row, index) => {
        const imageInput = row.querySelector('input[type="file"]');
        const startTimeInput = row.querySelector('input[name^="timeline_start_"]');
        const endTimeInput = row.querySelector('input[name^="timeline_end_"]');
        
        if (imageInput && startTimeInput && endTimeInput) {
            imageTimelineData.push({
                image_file: imageInput.files[0] ? imageInput.files[0].name : '',
                start_time: startTimeInput.value,
                end_time: endTimeInput.value
            });
        }
    });
    
    const hiddenInput = document.getElementById('image-timeline-data');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(imageTimelineData);
    }
}

// Remove voice actor row
function removeVoiceActorRow(button) {
    button.closest('.bg-gray-50').remove();
    updateVoiceActorsData();
}

// Remove image timeline row
function removeImageTimelineRow(button) {
    button.closest('.bg-gray-50').remove();
    updateImageTimelineData();
}

// Initialize voice actor management
function initializeVoiceActorManagement() {
    const addButton = document.getElementById('add-voice-actor');
    if (addButton) {
        addButton.addEventListener('click', () => {
            addVoiceActorRow();
        });
    }
}

// Initialize image timeline management
function initializeImageTimelineManagement() {
    const addButton = document.getElementById('add-image-timeline');
    if (addButton) {
        addButton.addEventListener('click', () => {
            addImageTimelineAtCurrentTime();
        });
    }
}

// Add image timeline at current audio time
function addImageTimelineAtCurrentTime() {
    const audioPlayer = document.getElementById('audio-player');
    const durationField = document.getElementById('duration');
    
    if (!audioPlayer || !durationField.value) {
        alert('لطفاً ابتدا فایل صوتی را آپلود کنید');
        return;
    }
    
    const currentTime = audioPlayer.currentTime || 0;
    const audioDuration = parseFloat(durationField.value);
    
    if (currentTime >= audioDuration) {
        alert('زمان فعلی نمی‌تواند بیشتر یا مساوی مدت زمان کل فایل صوتی باشد');
        return;
    }
    
    // Get the last image timeline
    const imageTimelineList = document.getElementById('image-timeline-list');
    const existingRows = imageTimelineList.querySelectorAll('.bg-gray-50');
    
    if (existingRows.length > 0) {
        // Update the last image's end time to current audio time
        const lastRow = existingRows[existingRows.length - 1];
        const lastEndTimeInput = lastRow.querySelector('input[name^="timeline_end_"]');
        if (lastEndTimeInput) {
            lastEndTimeInput.value = currentTime.toFixed(1);
        }
    }
    
    // Create new image timeline with current time as start and audio duration as end
    addImageTimelineRow({
        start_time: currentTime.toFixed(1),
        end_time: audioDuration.toFixed(1)
    });
}

// Initialize first image timeline automatically when audio is loaded
function initializeFirstImageTimeline() {
    const imageTimelineList = document.getElementById('image-timeline-list');
    const durationField = document.getElementById('duration');
    
    if (imageTimelineList && imageTimelineList.children.length === 0 && durationField.value) {
        // Create first image with start time 0 and end time = audio duration
        addImageTimelineRow({
            start_time: 0,
            end_time: durationField.value
        });
    }
}

// Clear form data function
function clearFormData() {
    if (confirm('آیا از پاک کردن تمام داده‌های ذخیره شده اطمینان دارید؟')) {
        // Clear localStorage data
        if (window.episodeFormManager) {
            window.episodeFormManager.clearData();
        }
        
        // Clear file data
        localStorage.removeItem('episode_audio_file');
        localStorage.removeItem('episode_cover_image_file');
        
        // Reset form
        const form = document.getElementById('episode-form');
        if (form) {
            form.reset();
        }
        
        // Clear dynamic elements
        const voiceActorsList = document.getElementById('voice-actors-list');
        if (voiceActorsList) {
            voiceActorsList.innerHTML = '';
        }
        
        const imageTimelineList = document.getElementById('image-timeline-list');
        if (imageTimelineList) {
            imageTimelineList.innerHTML = '';
        }
        
        // Reset counters
        voiceActorCounter = 0;
        imageTimelineCounter = 0;
        
        // Clear file name displays
        const audioFileName = document.getElementById('audio-file-name');
        if (audioFileName) {
            audioFileName.textContent = '';
        }
        
        const imageFileName = document.getElementById('image-file-name');
        if (imageFileName) {
            imageFileName.textContent = '';
        }
        
        // Hide audio player
        const audioPlayerSection = document.getElementById('audio-player-section');
        if (audioPlayerSection) {
            audioPlayerSection.classList.add('hidden');
        }
        
        showNotification('داده‌های فرم پاک شد', 'success');
    }
}
</script>
@endsection
