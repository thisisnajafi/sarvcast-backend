@extends('admin.layouts.app')

@section('title', 'ایجاد تایم‌لاین جدید')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">ایجاد تایم‌لاین جدید</h1>
            <p class="text-gray-600 mt-1">{{ $story->title }}</p>
            <div class="mt-2 text-sm text-gray-500">
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                    {{ $story->episodes->count() }} اپیزود
                </span>
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                    مدت کل: {{ gmdate('i:s', $story->episodes->sum('duration')) }}
                </span>
            </div>
        </div>
        <a href="{{ route('admin.stories.timeline.index', $story) }}"
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            بازگشت
        </a>
    </div>

    <!-- Audio Player Section -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
            </svg>
            انتخاب اپیزود برای پخش
        </h3>

        @if($story->episodes->count() > 0)
            <div class="space-y-4">
                <!-- Episode Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">انتخاب اپیزود:</label>
                    <select id="episode-selector" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">انتخاب کنید...</option>
                        @foreach($story->episodes as $episode)
                            <option value="{{ $episode->id }}" data-audio-url="{{ $episode->audio_url }}" data-duration="{{ $episode->duration }}">
                                اپیزود {{ $episode->episode_number }}: {{ $episode->title }} ({{ gmdate('i:s', $episode->duration) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Audio Player -->
                <div id="audio-player-container" class="bg-gray-50 p-4 rounded-lg" style="display: none;">
                    <audio id="audio-player" controls class="w-full mb-4">
                        مرورگر شما از پخش فایل صوتی پشتیبانی نمی‌کند.
                    </audio>

                    <!-- Playback Speed Controls -->
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm font-medium text-gray-700">سرعت پخش:</span>
                        <button type="button" id="speed-0.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(0.5)">0.5x</button>
                        <button type="button" id="speed-1x" class="px-3 py-1 text-sm border border-gray-300 rounded bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1)">1x</button>
                        <button type="button" id="speed-1.25x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.25)">1.25x</button>
                        <button type="button" id="speed-1.5x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(1.5)">1.5x</button>
                        <button type="button" id="speed-2x" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="setPlaybackSpeed(2)">2x</button>
                    </div>

                    <!-- Time Display -->
                    <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                        <span id="current-time">00:00</span>
                        <span id="duration">00:00</span>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <p>هیچ اپیزودی برای این داستان موجود نیست</p>
            </div>
        @endif
    </div>

    <!-- Image Timeline Management -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            مدیریت تصاویر بر اساس زمان
        </h3>

        <div class="space-y-4">
            <!-- Image Timeline List -->
            <div id="image-timeline-list" class="space-y-4">
                <!-- Image timelines will be added here dynamically -->
            </div>

            <!-- Add Image Timeline Button -->
            <div class="flex justify-center pt-4 border-t border-gray-200">
                <button type="button" id="add-image-timeline" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2 space-x-reverse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>افزودن تصویر در زمان فعلی</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="POST" action="{{ route('admin.stories.timeline.store', $story) }}"
              enctype="multipart/form-data" class="space-y-6" id="timeline-form">
            @csrf

            <!-- Hidden inputs for timeline data -->
            <input type="hidden" name="image_timeline_data" id="image-timeline-data">
            <input type="hidden" name="selected_episode_id" id="selected-episode-id">

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.stories.timeline.index', $story) }}"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    انصراف
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    ایجاد تایم‌لاین‌ها
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let audioPlayer = null;
let imageTimelineCounter = 0;
let currentEpisodeDuration = 0;

document.addEventListener('DOMContentLoaded', function() {
    initializeEpisodeSelector();
    initializeImageTimelineManagement();
    checkUploadLimit();
});

// Episode Selector Functions
function initializeEpisodeSelector() {
    const episodeSelector = document.getElementById('episode-selector');
    if (episodeSelector) {
        episodeSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const audioUrl = selectedOption.dataset.audioUrl;
                const duration = parseInt(selectedOption.dataset.duration);

                // Set the selected episode ID in hidden input
                document.getElementById('selected-episode-id').value = selectedOption.value;

                if (audioUrl) {
                    loadAudioPlayer(audioUrl, duration);
                } else {
                    hideAudioPlayer();
                }
            } else {
                hideAudioPlayer();
                document.getElementById('selected-episode-id').value = '';
            }
        });
    }
}

function loadAudioPlayer(audioUrl, duration) {
    const audioPlayerContainer = document.getElementById('audio-player-container');
    const audioPlayer = document.getElementById('audio-player');

    if (audioPlayer && audioPlayerContainer) {
        // Ensure audioUrl is a valid absolute URL
        if (!audioUrl) {
            console.error('Audio URL is empty');
            hideAudioPlayer();
            return;
        }

        // Remove any existing error message
        const existingError = audioPlayerContainer.querySelector('.audio-error-message');
        if (existingError) {
            existingError.remove();
        }

        // If the URL is relative, make it absolute
        if (!audioUrl.startsWith('http://') && !audioUrl.startsWith('https://')) {
            // If it starts with /, use current origin
            if (audioUrl.startsWith('/')) {
                audioUrl = window.location.origin + audioUrl;
            } else {
                // Otherwise, prepend current origin with /
                audioUrl = window.location.origin + '/' + audioUrl;
            }
        }

        // Clear previous source and reset
        audioPlayer.pause();
        audioPlayer.src = '';
        audioPlayer.load();

        // Set audio source
        audioPlayer.src = audioUrl;
        currentEpisodeDuration = duration;

        // Show player
        audioPlayerContainer.style.display = 'block';

        // Set default playback speed to 1x
        audioPlayer.playbackRate = 1;

        // Update duration display
        document.getElementById('duration').textContent = formatTime(duration);

        // Remove existing event listeners by cloning (clean slate)
        const newAudioPlayer = audioPlayer.cloneNode(true);
        audioPlayer.parentNode.replaceChild(newAudioPlayer, audioPlayer);
        const player = newAudioPlayer;
        player.src = audioUrl;
        player.playbackRate = 1;

        // Add error handling
        player.addEventListener('error', function(e) {
            console.error('Audio loading error:', e);
            const errorCode = player.error ? player.error.code : 'unknown';
            const errorMessages = {
                1: 'MEDIA_ERR_ABORTED - بارگذاری فایل صوتی متوقف شد',
                2: 'MEDIA_ERR_NETWORK - خطای شبکه در بارگذاری فایل صوتی',
                3: 'MEDIA_ERR_DECODE - خطا در رمزگشایی فایل صوتی',
                4: 'MEDIA_ERR_SRC_NOT_SUPPORTED - فرمت فایل صوتی پشتیبانی نمی‌شود'
            };

            const errorMsg = errorMessages[errorCode] || 'خطای ناشناخته در بارگذاری فایل صوتی';
            console.error('Audio error details:', {
                code: errorCode,
                message: errorMsg,
                src: player.src,
                networkState: player.networkState,
                readyState: player.readyState
            });

            // Show error message to user
            const errorDiv = document.createElement('div');
            errorDiv.className = 'audio-error-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-2';
            errorDiv.innerHTML = `
                <strong>خطا در بارگذاری فایل صوتی:</strong><br>
                ${errorMsg}<br>
                <small>URL: ${player.src}</small><br>
                <small>لطفاً مطمئن شوید فایل صوتی در مسیر صحیح قرار دارد و دسترسی به آن امکان‌پذیر است.</small>
            `;
            audioPlayerContainer.appendChild(errorDiv);
        });

        // Add load event listeners
        player.addEventListener('loadedmetadata', function() {
            const actualDuration = player.duration || duration;
            document.getElementById('duration').textContent = formatTime(actualDuration);
            console.log('Audio metadata loaded:', {
                duration: actualDuration,
                src: player.src
            });
        });

        player.addEventListener('canplay', function() {
            console.log('Audio can play:', player.src);
            // Remove error message if audio loads successfully
            const existingError = audioPlayerContainer.querySelector('.audio-error-message');
            if (existingError) {
                existingError.remove();
            }
        });

        player.addEventListener('loadstart', function() {
            console.log('Audio loading started:', player.src);
        });

        player.addEventListener('stalled', function() {
            console.warn('Audio loading stalled:', player.src);
        });

        player.addEventListener('suspend', function() {
            console.warn('Audio loading suspended:', player.src);
        });

        // Update time display
        player.addEventListener('timeupdate', function() {
            const currentTime = player.currentTime;
            document.getElementById('current-time').textContent = formatTime(currentTime);
        });

        // Store reference for other functions
        window.currentAudioPlayer = player;
    }
}

function hideAudioPlayer() {
    const audioPlayerContainer = document.getElementById('audio-player-container');
    if (audioPlayerContainer) {
        audioPlayerContainer.style.display = 'none';
    }
}

// Set playback speed function
function setPlaybackSpeed(speed) {
    const audioPlayer = window.currentAudioPlayer || document.getElementById('audio-player');
    if (audioPlayer) {
        audioPlayer.playbackRate = speed;

        // Update button styles
        document.querySelectorAll('[id^="speed-"]').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white');
            btn.classList.add('hover:bg-gray-100');
        });

        document.getElementById(`speed-${speed}x`).classList.add('bg-blue-500', 'text-white');
        document.getElementById(`speed-${speed}x`).classList.remove('hover:bg-gray-100');
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
    const audioPlayer = window.currentAudioPlayer || document.getElementById('audio-player');

    if (!audioPlayer || !audioPlayer.src) {
        alert('لطفاً ابتدا یک اپیزود را انتخاب کنید');
        return;
    }

    const currentTime = audioPlayer.currentTime || 0;

    if (currentTime >= currentEpisodeDuration) {
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

    // Create new image timeline with current time as start and episode duration as end
    addImageTimelineRow({
        start_time: currentTime.toFixed(1),
        end_time: currentEpisodeDuration.toFixed(1)
    });
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تصویر</label>
                <input type="file" name="timeline_image_${imageTimelineCounter}" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" onchange="previewImage(this)">
                <div class="mt-2 image-preview-container" style="display: none;">
                    <img class="w-full h-32 object-cover rounded-lg border border-gray-300" alt="پیش‌نمایش تصویر">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیح صحنه</label>
                <input type="text" name="timeline_scene_${imageTimelineCounter}" value="${data.scene_description || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="توضیح صحنه">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شروع (ثانیه)</label>
                <input type="number" name="timeline_start_${imageTimelineCounter}" value="${data.start_time || suggestedStartTime}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0" onchange="updatePreviousImageEndTime(this)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">پایان (ثانیه)</label>
                <input type="number" name="timeline_end_${imageTimelineCounter}" value="${data.end_time || ''}" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع انتقال</label>
                <select name="timeline_transition_${imageTimelineCounter}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="fade" ${data.transition_type === 'fade' ? 'selected' : ''}>محو</option>
                    <option value="slide" ${data.transition_type === 'slide' ? 'selected' : ''}>اسلاید</option>
                    <option value="cut" ${data.transition_type === 'cut' ? 'selected' : ''}>برش</option>
                    <option value="dissolve" ${data.transition_type === 'dissolve' ? 'selected' : ''}>حل شدن</option>
                </select>
            </div>
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" name="timeline_keyframe_${imageTimelineCounter}" ${data.is_key_frame ? 'checked' : ''} class="mr-2">
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
    checkUploadLimit();
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

// Remove image timeline row
function removeImageTimelineRow(button) {
    const row = button.closest('.bg-gray-50');
    if (row) {
        row.remove();
        updateImageTimelineData();
        checkUploadLimit();
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
        const sceneDescriptionInput = row.querySelector('input[name^="timeline_scene_"]');
        const transitionTypeSelect = row.querySelector('select[name^="timeline_transition_"]');
        const isKeyFrameCheckbox = row.querySelector('input[name^="timeline_keyframe_"]');

        // Only include if we have the required time inputs and an image file
        if (startTimeInput && endTimeInput && imageInput && imageInput.files && imageInput.files[0]) {
            const timelineData = {
                start_time: parseFloat(startTimeInput.value) || 0,
                end_time: parseFloat(endTimeInput.value) || 0,
                scene_description: sceneDescriptionInput ? sceneDescriptionInput.value : '',
                transition_type: transitionTypeSelect ? transitionTypeSelect.value : 'fade',
                is_key_frame: isKeyFrameCheckbox ? isKeyFrameCheckbox.checked : false,
                image_order: index + 1,
                image_file_name: imageInput.files[0].name,
                image_file_size: imageInput.files[0].size,
                image_file_type: imageInput.files[0].type
            };

            // Only add if we have valid time values
            if (timelineData.start_time >= 0 && timelineData.end_time > timelineData.start_time) {
                imageTimelineData.push(timelineData);
            }
        }
    });

    const hiddenInput = document.getElementById('image-timeline-data');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(imageTimelineData);
        console.log('Timeline data updated:', imageTimelineData); // Debug log
    }
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Clear file error indicators when file is selected
function clearFileError(input) {
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    const errorDiv = input.parentNode.querySelector('.file-error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Check PHP upload limit and show warning
function checkUploadLimit() {
    const timelineRows = document.querySelectorAll('#image-timeline-list .bg-gray-50');
    const phpMaxFileUploads = {{ config('timeline.max_file_uploads', 300) }};

    // Remove existing warning
    const existingWarning = document.getElementById('upload-limit-warning');
    if (existingWarning) {
        existingWarning.remove();
    }

    if (timelineRows.length > phpMaxFileUploads) {
        const warningDiv = document.createElement('div');
        warningDiv.id = 'upload-limit-warning';
        warningDiv.className = 'alert alert-warning mt-3';
        warningDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>هشدار:</strong> تعداد تصاویر (${timelineRows.length}) از محدودیت PHP (${phpMaxFileUploads} فایل) بیشتر است.
            <br><small>لطفاً تعداد تصاویر را کاهش دهید یا با مدیر سیستم تماس بگیرید.</small>
        `;

        const form = document.getElementById('timeline-form');
        form.insertBefore(warningDiv, form.firstChild);
    }
}

// Add event listeners to file inputs to clear errors
document.addEventListener('change', function(e) {
    if (e.target.type === 'file' && e.target.name.includes('timeline_image')) {
        clearFileError(e.target);
    }
});

// Handle form submission
document.getElementById('timeline-form').addEventListener('submit', function(e) {
    updateImageTimelineData();

    const imageTimelineData = JSON.parse(document.getElementById('image-timeline-data').value || '[]');
    console.log('Form submission - Timeline data:', imageTimelineData);
    console.log('Form submission - Number of timeline entries:', imageTimelineData.length);

    if (imageTimelineData.length === 0) {
        e.preventDefault();
        alert('لطفاً حداقل یک تصویر برای تایم‌لاین اضافه کنید');
        return;
    }

    // Check if episode is selected
    const selectedEpisodeId = document.getElementById('selected-episode-id').value;
    if (!selectedEpisodeId) {
        e.preventDefault();
        alert('لطفاً ابتدا یک اپیزود را انتخاب کنید');
        return;
    }

    console.log('Selected episode ID:', selectedEpisodeId);

    // Debug: Check if all rows have images
    const imageTimelineList = document.getElementById('image-timeline-list');
    const allRows = imageTimelineList.querySelectorAll('.bg-gray-50');
    console.log('Form submission - Total rows found:', allRows.length);

    allRows.forEach((row, index) => {
        const imageInput = row.querySelector('input[type="file"]');
        const startTimeInput = row.querySelector('input[name^="timeline_start_"]');
        const endTimeInput = row.querySelector('input[name^="timeline_end_"]');

        console.log(`Row ${index}:`, {
            hasImage: imageInput && imageInput.files && imageInput.files[0],
            startTime: startTimeInput ? startTimeInput.value : 'N/A',
            endTime: endTimeInput ? endTimeInput.value : 'N/A',
            imageName: imageInput && imageInput.files && imageInput.files[0] ? imageInput.files[0].name : 'N/A'
        });
    });

    // Create FormData to handle file uploads properly
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('image_timeline_data', document.getElementById('image-timeline-data').value);
    formData.append('selected_episode_id', selectedEpisodeId);

    console.log('FormData contents:', {
        token: document.querySelector('input[name="_token"]').value,
        timelineData: document.getElementById('image-timeline-data').value,
        selectedEpisodeId: selectedEpisodeId
    });

    // Add all image files - use the same validation logic as updateImageTimelineData()
    let fileIndex = 0;
    imageTimelineList.querySelectorAll('.bg-gray-50').forEach((row, index) => {
        const imageInput = row.querySelector('input[type="file"]');
        const startTimeInput = row.querySelector('input[name^="timeline_start_"]');
        const endTimeInput = row.querySelector('input[name^="timeline_end_"]');

        // Use the same validation logic as updateImageTimelineData()
        if (startTimeInput && endTimeInput && imageInput && imageInput.files && imageInput.files[0]) {
            const startTime = parseFloat(startTimeInput.value) || 0;
            const endTime = parseFloat(endTimeInput.value) || 0;

            // Only add if we have valid time values (same as timeline data)
            if (startTime >= 0 && endTime > startTime) {
                formData.append(`timeline_image_${fileIndex}`, imageInput.files[0]);
                console.log(`Added file ${fileIndex}:`, imageInput.files[0].name);
                fileIndex++;
            }
        }
    });

    // Check PHP upload limit
    const phpMaxFileUploads = {{ config('timeline.max_file_uploads', 300) }};
    if (imageTimelineData.length > phpMaxFileUploads) {
        e.preventDefault();
        alert(`تعداد تصاویر تایم‌لاین (${imageTimelineData.length}) از محدودیت PHP (${phpMaxFileUploads} فایل در هر درخواست) بیشتر است.\n\nراه‌حل:\n1. تعداد تصاویر را کاهش دهید\n2. با مدیر سیستم تماس بگیرید تا محدودیت افزایش یابد\n3. تصاویر را در چند مرحله آپلود کنید`);
        return;
    }

    // Validate that file count matches timeline entries count
    console.log('File count:', fileIndex, 'Timeline entries:', imageTimelineData.length);
    if (fileIndex !== imageTimelineData.length) {
        e.preventDefault();

        // Highlight missing file inputs
        imageTimelineList.querySelectorAll('input[type="file"]').forEach((input, index) => {
            if (!input.files[0]) {
                input.style.borderColor = '#dc3545';
                input.style.backgroundColor = '#fff5f5';

                // Add error message
                let errorDiv = input.parentNode.querySelector('.file-error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'file-error-message text-danger small mt-1';
                    input.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = 'فایل تصویر الزامی است';
            } else {
                input.style.borderColor = '';
                input.style.backgroundColor = '';
                const errorDiv = input.parentNode.querySelector('.file-error-message');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        });

        alert(`تعداد فایل‌های آپلود شده (${fileIndex}) با تعداد ورودی‌های تایم‌لاین (${imageTimelineData.length}) مطابقت ندارد. لطفاً همه فیلدهای تصویر را پر کنید.`);
        return;
    }

    // Submit via fetch
    e.preventDefault();

    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            window.location.href = response.url || '{{ route("admin.stories.timeline.index", $story) }}';
        } else {
            return response.text().then(html => {
                document.body.innerHTML = html;
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در ارسال فرم: ' + error.message);
    });
});
</script>
@endsection
