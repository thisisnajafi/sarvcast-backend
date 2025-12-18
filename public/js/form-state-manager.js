// Form State Management for Episode and Story Creation
// This script handles form data persistence across page reloads and errors

class FormStateManager {
    constructor(formId, storageKey) {
        this.formId = formId;
        this.storageKey = storageKey;
        this.form = document.getElementById(formId);
        this.init();
    }

    init() {
        if (!this.form) return;
        
        // Restore form data on page load
        this.restoreFormData();
        
        // Save form data on input changes
        this.attachEventListeners();
        
        // Clear storage on successful submission
        this.handleFormSubmission();
    }

    attachEventListeners() {
        // Save data on input, change, and keyup events
        const events = ['input', 'change', 'keyup'];
        events.forEach(event => {
            this.form.addEventListener(event, (e) => {
                // Debounce the save operation
                clearTimeout(this.saveTimeout);
                this.saveTimeout = setTimeout(() => {
                    this.saveFormData();
                }, 500);
            });
        });

        // Save data when file inputs change
        const fileInputs = this.form.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.saveFormData();
            });
        });
    }

    saveFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        // Convert FormData to regular object
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                // Handle multiple values (like checkboxes)
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        // Handle special cases for complex form elements
        this.handleSpecialElements(data);

        // Save to localStorage
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(data));
            console.log('Form data saved to localStorage');
        } catch (error) {
            console.warn('Could not save form data to localStorage:', error);
        }
    }

    handleSpecialElements(data) {
        // Handle voice actors data
        const voiceActorsData = document.getElementById('voice-actors-data');
        if (voiceActorsData && voiceActorsData.value) {
            try {
                data.voice_actors_data = JSON.parse(voiceActorsData.value);
            } catch (e) {
                data.voice_actors_data = voiceActorsData.value;
            }
        }

        // Handle image timeline data
        const imageTimelineData = document.getElementById('image-timeline-data');
        if (imageTimelineData && imageTimelineData.value) {
            try {
                data.image_timeline_data = JSON.parse(imageTimelineData.value);
            } catch (e) {
                data.image_timeline_data = imageTimelineData.value;
            }
        }

        // Handle dynamic counters
        if (typeof voiceActorCounter !== 'undefined') {
            data.voiceActorCounter = voiceActorCounter;
        }
        if (typeof imageTimelineCounter !== 'undefined') {
            data.imageTimelineCounter = imageTimelineCounter;
        }
    }

    restoreFormData() {
        try {
            const savedData = localStorage.getItem(this.storageKey);
            if (!savedData) return;

            const data = JSON.parse(savedData);
            this.populateForm(data);
            console.log('Form data restored from localStorage');
        } catch (error) {
            console.warn('Could not restore form data from localStorage:', error);
        }
    }

    populateForm(data) {
        // Restore regular form fields
        Object.keys(data).forEach(key => {
            const element = this.form.querySelector(`[name="${key}"]`);
            if (!element) return;

            if (element.type === 'checkbox') {
                element.checked = data[key] === '1' || data[key] === true;
            } else if (element.type === 'radio') {
                element.checked = element.value === data[key];
            } else if (element.tagName === 'SELECT') {
                element.value = data[key];
            } else if (element.tagName === 'TEXTAREA') {
                element.value = data[key];
            } else if (element.type === 'text' || element.type === 'number' || element.type === 'date' || element.type === 'datetime-local') {
                element.value = data[key];
            }
        });

        // Restore special elements
        this.restoreSpecialElements(data);

        // Trigger change events to update dependent elements
        this.form.dispatchEvent(new Event('change', { bubbles: true }));
    }

    restoreSpecialElements(data) {
        // Restore voice actors data
        if (data.voice_actors_data) {
            const voiceActorsData = document.getElementById('voice-actors-data');
            if (voiceActorsData) {
                voiceActorsData.value = typeof data.voice_actors_data === 'string' 
                    ? data.voice_actors_data 
                    : JSON.stringify(data.voice_actors_data);
                
                // Restore voice actors UI
                if (typeof restoreVoiceActorsUI === 'function') {
                    restoreVoiceActorsUI(data.voice_actors_data);
                }
            }
        }

        // Restore image timeline data
        if (data.image_timeline_data) {
            const imageTimelineData = document.getElementById('image-timeline-data');
            if (imageTimelineData) {
                imageTimelineData.value = typeof data.image_timeline_data === 'string' 
                    ? data.image_timeline_data 
                    : JSON.stringify(data.image_timeline_data);
                
                // Restore image timeline UI
                if (typeof restoreImageTimelineUI === 'function') {
                    restoreImageTimelineUI(data.image_timeline_data);
                }
            }
        }

        // Restore counters
        if (data.voiceActorCounter && typeof voiceActorCounter !== 'undefined') {
            voiceActorCounter = data.voiceActorCounter;
        }
        if (data.imageTimelineCounter && typeof imageTimelineCounter !== 'undefined') {
            imageTimelineCounter = data.imageTimelineCounter;
        }
    }

    handleFormSubmission() {
        this.form.addEventListener('submit', () => {
            // Clear the stored data on successful submission
            setTimeout(() => {
                this.clearStoredData();
            }, 1000);
        });
    }

    clearStoredData() {
        try {
            localStorage.removeItem(this.storageKey);
            console.log('Form data cleared from localStorage');
        } catch (error) {
            console.warn('Could not clear form data from localStorage:', error);
        }
    }

    // Method to manually clear data (useful for cancel buttons)
    clearData() {
        this.clearStoredData();
    }
}

// Auto-save functionality for file inputs
class FileInputManager {
    constructor() {
        this.init();
    }

    init() {
        // Handle audio file restoration
        this.handleAudioFileRestoration();
        
        // Handle image file restoration
        this.handleImageFileRestoration();
    }

    handleAudioFileRestoration() {
        const audioInput = document.getElementById('audio_file');
        if (!audioInput) return;

        // Check if there's a saved audio file
        const savedAudioData = localStorage.getItem('episode_audio_file');
        if (savedAudioData) {
            try {
                const audioData = JSON.parse(savedAudioData);
                this.restoreAudioFile(audioData);
            } catch (error) {
                console.warn('Could not restore audio file:', error);
            }
        }

        // Save audio file data when changed
        audioInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.saveAudioFileData(file);
            }
        });
    }

    handleImageFileRestoration() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        imageInputs.forEach(input => {
            const storageKey = `episode_${input.id}_file`;
            const savedImageData = localStorage.getItem(storageKey);
            
            if (savedImageData) {
                try {
                    const imageData = JSON.parse(savedImageData);
                    this.restoreImageFile(input, imageData);
                } catch (error) {
                    console.warn('Could not restore image file:', error);
                }
            }

            // Save image file data when changed
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.saveImageFileData(input.id, file);
                }
            });
        });
    }

    saveAudioFileData(file) {
        const fileData = {
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified
        };

        try {
            localStorage.setItem('episode_audio_file', JSON.stringify(fileData));
            
            // Update the file name display
            const fileNameElement = document.getElementById('audio-file-name');
            if (fileNameElement) {
                fileNameElement.textContent = file.name;
            }
        } catch (error) {
            console.warn('Could not save audio file data:', error);
        }
    }

    saveImageFileData(inputId, file) {
        const fileData = {
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified
        };

        try {
            localStorage.setItem(`episode_${inputId}_file`, JSON.stringify(fileData));
            
            // Update the file name display
            const fileNameElement = document.getElementById(`${inputId}-file-name`);
            if (fileNameElement) {
                fileNameElement.textContent = file.name;
            }
        } catch (error) {
            console.warn('Could not save image file data:', error);
        }
    }

    restoreAudioFile(audioData) {
        const fileNameElement = document.getElementById('audio-file-name');
        if (fileNameElement) {
            fileNameElement.textContent = audioData.name;
        }
    }

    restoreImageFile(input, imageData) {
        const fileNameElement = document.getElementById(`${input.id}-file-name`);
        if (fileNameElement) {
            fileNameElement.textContent = imageData.name;
        }
    }
}

// Enhanced form validation with better error handling
class FormValidator {
    constructor(formId) {
        this.formId = formId;
        this.form = document.getElementById(formId);
        this.init();
    }

    init() {
        if (!this.form) return;
        
        // Add real-time validation
        this.addRealTimeValidation();
        
        // Add custom error display
        this.addCustomErrorDisplay();
    }

    addRealTimeValidation() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Clear previous errors
        this.clearFieldError(field);
        
        // Basic validation rules
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'این فیلد الزامی است');
            return false;
        }
        
        // Email validation
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            this.showFieldError(field, 'ایمیل نامعتبر است');
            return false;
        }
        
        // Number validation
        if (field.type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            
            if (min && parseFloat(value) < parseFloat(min)) {
                this.showFieldError(field, `مقدار باید حداقل ${min} باشد`);
                return false;
            }
            
            if (max && parseFloat(value) > parseFloat(max)) {
                this.showFieldError(field, `مقدار باید حداکثر ${max} باشد`);
                return false;
            }
        }
        
        return true;
    }

    showFieldError(field, message) {
        field.classList.add('border-red-500');
        
        // Create error message element
        const errorElement = document.createElement('p');
        errorElement.className = 'mt-1 text-sm text-red-600 field-error';
        errorElement.textContent = message;
        
        // Insert after the field
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500');
        
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    addCustomErrorDisplay() {
        // Add a global error container
        const errorContainer = document.createElement('div');
        errorContainer.id = 'form-errors';
        errorContainer.className = 'hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-6';
        
        this.form.insertBefore(errorContainer, this.form.firstChild);
    }
}

// Initialize form state management when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form state management for episode creation
    if (document.getElementById('episode-form')) {
        window.episodeFormManager = new FormStateManager('episode-form', 'episode_form_data');
        window.fileInputManager = new FileInputManager();
        window.episodeFormValidator = new FormValidator('episode-form');
    }
    
    // Initialize form state management for story creation
    if (document.querySelector('form[action*="stories"]')) {
        const storyForm = document.querySelector('form[action*="stories"]');
        storyForm.id = 'story-form';
        window.storyFormManager = new FormStateManager('story-form', 'story_form_data');
        window.storyFormValidator = new FormValidator('story-form');
    }
});

// Utility functions for voice actors and image timeline restoration
function restoreVoiceActorsUI(voiceActorsData) {
    if (!voiceActorsData || !Array.isArray(voiceActorsData)) return;
    
    const voiceActorsList = document.getElementById('voice-actors-list');
    if (!voiceActorsList) return;
    
    voiceActorsList.innerHTML = '';
    
    voiceActorsData.forEach((actor, index) => {
        addVoiceActorRow(actor, index);
    });
}

function restoreImageTimelineUI(imageTimelineData) {
    if (!imageTimelineData || !Array.isArray(imageTimelineData)) return;
    
    const imageTimelineList = document.getElementById('image-timeline-list');
    if (!imageTimelineList) return;
    
    imageTimelineList.innerHTML = '';
    
    imageTimelineData.forEach((timeline, index) => {
        addImageTimelineRow(timeline, index);
    });
}

// Enhanced error handling for form submission
function handleFormSubmissionError(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Check for validation errors
    const errorElements = document.querySelectorAll('.text-red-600, .field-error');
    if (errorElements.length > 0) {
        // Scroll to first error
        errorElements[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Show error notification
        showNotification('لطفاً خطاهای فرم را برطرف کنید', 'error');
    }
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'success' ? 'bg-green-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Export for global use
window.FormStateManager = FormStateManager;
window.FileInputManager = FileInputManager;
window.FormValidator = FormValidator;
