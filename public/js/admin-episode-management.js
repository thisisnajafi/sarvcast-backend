// Voice Actor Management Functions
function initializeVoiceActorManagement() {
    const addButton = document.getElementById('add-voice-actor');
    if (addButton) {
        addButton.addEventListener('click', addVoiceActor);
    }
}

function addVoiceActor() {
    voiceActorCounter++;
    const voiceActorHtml = createVoiceActorHtml(voiceActorCounter);
    document.getElementById('voice-actors-list').insertAdjacentHTML('beforeend', voiceActorHtml);
    
    // Add event listeners for the new voice actor
    const container = document.getElementById(`voice-actor-${voiceActorCounter}`);
    addVoiceActorEventListeners(container, voiceActorCounter);
}

function createVoiceActorHtml(index) {
    return `
        <div id="voice-actor-${index}" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-md font-medium text-gray-900">صداپیشه ${index}</h4>
                <button type="button" onclick="removeVoiceActor(${index})" class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">صداپیشه *</label>
                    <select name="voice_actor_${index}_person_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">انتخاب صداپیشه</option>
                        ${availableVoiceActors.map(actor => 
                            `<option value="${actor.id}">${actor.name}</option>`
                        ).join('')}
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نام شخصیت *</label>
                    <input type="text" name="voice_actor_${index}_character_name" placeholder="نام شخصیت" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نقش</label>
                    <input type="text" name="voice_actor_${index}_role" placeholder="مثال: راوی، شخصیت اصلی" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات صدا</label>
                    <input type="text" name="voice_actor_${index}_voice_description" placeholder="مثال: صدای گرم و دوستانه" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="voice_actor_${index}_is_primary" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="mr-2 text-sm text-gray-900">صداپیشه اصلی</span>
                    </label>
                </div>
            </div>
        </div>
    `;
}

function addVoiceActorEventListeners(container, index) {
    // Add any specific event listeners for voice actor fields
}

function removeVoiceActor(index) {
    const container = document.getElementById(`voice-actor-${index}`);
    if (container) {
        container.remove();
    }
}


// Image Timeline Management Functions
let lastImageEndTime = 0; // Track the last image's end time

function initializeImageTimelineManagement() {
    const addButton = document.getElementById('add-image-timeline');
    if (addButton) {
        addButton.addEventListener('click', addImageTimeline);
    }
}

function initializeFirstImageTimeline() {
    // Only add the first image timeline if none exist
    if (imageTimelineCounter === 0) {
        addImageTimeline();
        
        // Show instruction message
        const imageTimelineSection = document.getElementById('image-timeline-management');
        if (imageTimelineSection) {
            const instructionMessage = document.createElement('div');
            instructionMessage.className = 'mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg';
            instructionMessage.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="mr-3">
                        <h3 class="text-sm font-medium text-blue-800">راهنمای افزودن تصاویر</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>1. تصویر اول خودکار اضافه شده (شروع: 0)</p>
                            <p>2. فایل صوتی را پخش کنید و در زمان مناسب کلیک کنید: "زمان فعلی به عنوان پایان + افزودن تصویر بعدی"</p>
                            <p>3. تصویر بعدی خودکار اضافه می‌شود (شروع: پایان تصویر قبلی)</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert after the add button
            const addButton = document.getElementById('add-image-timeline');
            if (addButton) {
                addButton.parentNode.insertBefore(instructionMessage, addButton.nextSibling);
            }
        }
    }
}

function addImageTimeline() {
    imageTimelineCounter++;
    const imageTimelineHtml = createImageTimelineHtml(imageTimelineCounter);
    document.getElementById('image-timeline-list').insertAdjacentHTML('beforeend', imageTimelineHtml);
    
    // Add event listeners for the new image timeline
    const container = document.getElementById(`image-timeline-${imageTimelineCounter}`);
    addImageTimelineEventListeners(container, imageTimelineCounter);
    
    // Auto-set start time based on previous image's end time
    const startTimeInput = container.querySelector(`input[name="image_timeline_${imageTimelineCounter}_start_time"]`);
    const endTimeInput = container.querySelector(`input[name="image_timeline_${imageTimelineCounter}_end_time"]`);
    
    if (startTimeInput) {
        startTimeInput.value = lastImageEndTime;
    }
    
    // Auto-set end time to audio duration for the last image
    if (endTimeInput && audioPlayer && audioPlayer.duration) {
        endTimeInput.value = Math.floor(audioPlayer.duration);
    }
}

function createImageTimelineHtml(index) {
    return `
        <div id="image-timeline-${index}" class="border border-gray-200 rounded-lg p-4 bg-green-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-md font-medium text-gray-900">تصویر ${index}</h4>
                <button type="button" onclick="removeImageTimeline(${index})" class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">تصویر</label>
                    <input type="file" name="image_timeline_${index}_image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">زمان شروع (ثانیه)</label>
                    <input type="number" name="image_timeline_${index}_start_time" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">زمان پایان (ثانیه)</label>
                    <input type="number" name="image_timeline_${index}_end_time" min="0" placeholder="60" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب تصویر</label>
                    <input type="number" name="image_timeline_${index}_image_order" min="1" placeholder="${index}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع انتقال</label>
                    <select name="image_timeline_${index}_transition_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="fade">محو شدن</option>
                        <option value="slide">اسلاید</option>
                        <option value="zoom">زوم</option>
                        <option value="none">بدون انتقال</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات صحنه</label>
                    <textarea name="image_timeline_${index}_scene_description" rows="2" placeholder="توضیحات صحنه یا رویداد در این زمان" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="image_timeline_${index}_is_key_frame" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="mr-2 text-sm text-gray-900">فریم کلیدی</span>
                    </label>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="flex space-x-2 space-x-reverse mb-2">
                    <button type="button" onclick="setCurrentTimeAsImageStart(${index})" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        زمان فعلی به عنوان شروع
                    </button>
                    <button type="button" onclick="setCurrentTimeAsImageEnd(${index})" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200">
                        زمان فعلی به عنوان پایان + افزودن تصویر بعدی
                    </button>
                </div>
                <p class="text-xs text-gray-600">
                    💡 برای تصویر اول: شروع خودکار از 0 | برای تصاویر بعدی: شروع از پایان تصویر قبلی
                </p>
            </div>
        </div>
    `;
}

function addImageTimelineEventListeners(container, index) {
    // Add any specific event listeners for image timeline fields
}

function removeImageTimeline(index) {
    const container = document.getElementById(`image-timeline-${index}`);
    if (container) {
        container.remove();
    }
}

function setCurrentTimeAsImageStart(index) {
    if (audioPlayer && audioPlayer.currentTime !== undefined) {
        const startTimeInput = document.querySelector(`input[name="image_timeline_${index}_start_time"]`);
        if (startTimeInput) {
            startTimeInput.value = Math.floor(audioPlayer.currentTime);
        }
    }
}

function setCurrentTimeAsImageEnd(index) {
    if (audioPlayer && audioPlayer.currentTime !== undefined) {
        const currentTime = Math.floor(audioPlayer.currentTime);
        const endTimeInput = document.querySelector(`input[name="image_timeline_${index}_end_time"]`);
        
        if (endTimeInput) {
            endTimeInput.value = currentTime;
            // Update the last image end time
            lastImageEndTime = currentTime;
            
            // Create a new image frame automatically
            addImageTimeline();
            
            // Show success message
            showImageTimelineSuccess(index, currentTime);
        }
    }
}

function showImageTimelineSuccess(index, endTime) {
    const container = document.getElementById(`image-timeline-${index}`);
    if (container) {
        // Remove any existing success message
        const existingMessage = container.querySelector('.image-timeline-success');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create success message
        const successMessage = document.createElement('div');
        successMessage.className = 'image-timeline-success mt-2 p-2 bg-green-100 text-green-800 rounded text-sm';
        successMessage.textContent = `تصویر ${index} تا زمان ${formatTime(endTime)} تنظیم شد. تصویر بعدی اضافه شد.`;
        
        // Add the message after the buttons
        const buttonsContainer = container.querySelector('.mt-4.flex.space-x-2');
        if (buttonsContainer) {
            buttonsContainer.parentNode.insertBefore(successMessage, buttonsContainer.nextSibling);
        }
        
        // Remove the message after 5 seconds
        setTimeout(() => {
            if (successMessage.parentNode) {
                successMessage.remove();
            }
        }, 5000);
    }
}

// Form submission
document.getElementById('episode-form').addEventListener('submit', function(e) {
    // Collect voice actors data
    voiceActorsData = [];
    for (let i = 1; i <= voiceActorCounter; i++) {
        const container = document.getElementById(`voice-actor-${i}`);
        if (container) {
            const personId = container.querySelector(`select[name="voice_actor_${i}_person_id"]`).value;
            const role = container.querySelector(`input[name="voice_actor_${i}_role"]`).value;
            const characterName = container.querySelector(`input[name="voice_actor_${i}_character_name"]`).value;
            const voiceDescription = container.querySelector(`input[name="voice_actor_${i}_voice_description"]`).value;
            const isPrimary = container.querySelector(`input[name="voice_actor_${i}_is_primary"]`).checked;
            
            if (personId && characterName) {
                voiceActorsData.push({
                    person_id: personId,
                    role: role,
                    character_name: characterName,
                    voice_description: voiceDescription,
                    is_primary: isPrimary
                });
            }
        }
    }
    
    // Collect image timeline data
    imageTimelineData = [];
    for (let i = 1; i <= imageTimelineCounter; i++) {
        const container = document.getElementById(`image-timeline-${i}`);
        if (container) {
            const imageFile = container.querySelector(`input[name="image_timeline_${i}_image"]`).files[0];
            const startTime = container.querySelector(`input[name="image_timeline_${i}_start_time"]`).value;
            const endTime = container.querySelector(`input[name="image_timeline_${i}_end_time"]`).value;
            const imageOrder = container.querySelector(`input[name="image_timeline_${i}_image_order"]`).value;
            const transitionType = container.querySelector(`select[name="image_timeline_${i}_transition_type"]`).value;
            const sceneDescription = container.querySelector(`textarea[name="image_timeline_${i}_scene_description"]`).value;
            const isKeyFrame = container.querySelector(`input[name="image_timeline_${i}_is_key_frame"]`).checked;
            
            if (imageFile && startTime && endTime) {
                imageTimelineData.push({
                    image_file: imageFile,
                    start_time: parseInt(startTime),
                    end_time: parseInt(endTime),
                    image_order: parseInt(imageOrder) || i,
                    transition_type: transitionType,
                    scene_description: sceneDescription,
                    is_key_frame: isKeyFrame
                });
            }
        }
    }
    
    // Set hidden inputs
    document.getElementById('voice-actors-data').value = JSON.stringify(voiceActorsData);
    document.getElementById('image-timeline-data').value = JSON.stringify(imageTimelineData);
});
