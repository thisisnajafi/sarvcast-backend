/**
 * Timeline Creation Form Handler
 * Handles timeline creation form with comprehensive error handling
 */

class TimelineCreationForm {
    constructor() {
        this.form = null;
        this.timelineData = [];
        this.currentIndex = 0;
        this.init();
    }

    init() {
        this.setupForm();
        this.setupEventListeners();
        this.loadExistingData();
    }

    setupForm() {
        this.form = document.getElementById('timeline-creation-form');
        if (!this.form) {
            console.error('Timeline creation form not found');
            return;
        }

        this.form.classList.add('timeline-creation-form');
    }

    setupEventListeners() {
        if (!this.form) return;

        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });

        // Add timeline entry button
        const addBtn = document.getElementById('add-timeline-entry');
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addTimelineEntry();
            });
        }

        // Remove timeline entry buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-timeline-entry')) {
                e.preventDefault();
                this.removeTimelineEntry(e.target.dataset.index);
            }
        });

        // Image preview
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.name.includes('timeline_image')) {
                this.previewImage(e.target);
            }
        });

        // Real-time validation
        document.addEventListener('input', (e) => {
            if (e.target.name.includes('timeline')) {
                this.validateField(e.target);
            }
        });
    }

    loadExistingData() {
        // Load any existing timeline data from session or form
        const existingData = document.getElementById('existing-timeline-data');
        if (existingData) {
            try {
                this.timelineData = JSON.parse(existingData.textContent);
                this.renderTimelineEntries();
            } catch (error) {
                console.error('Error loading existing timeline data:', error);
            }
        }
    }

    addTimelineEntry() {
        const template = document.getElementById('timeline-entry-template');
        if (!template) {
            console.error('Timeline entry template not found');
            return;
        }

        const clone = template.content.cloneNode(true);
        const entryIndex = this.currentIndex++;

        // Update field names and IDs
        clone.querySelectorAll('input, textarea, select').forEach(field => {
            const name = field.name.replace('INDEX', entryIndex);
            field.name = name;
            field.id = field.id.replace('INDEX', entryIndex);
        });

        // Update button data attributes
        const removeBtn = clone.querySelector('.remove-timeline-entry');
        if (removeBtn) {
            removeBtn.dataset.index = entryIndex;
        }

        // Add to container
        const container = document.getElementById('timeline-entries-container');
        if (container) {
            container.appendChild(clone);
        }

        // Initialize new entry
        this.initializeTimelineEntry(entryIndex);
    }

    removeTimelineEntry(index) {
        const entry = document.querySelector(`[data-timeline-index="${index}"]`);
        if (entry) {
            entry.remove();
        }

        // Remove from data array
        this.timelineData = this.timelineData.filter((_, i) => i !== parseInt(index));
    }

    initializeTimelineEntry(index) {
        // Set default values
        const startTimeField = document.getElementById(`timeline_${index}_start_time`);
        const endTimeField = document.getElementById(`timeline_${index}_end_time`);
        const orderField = document.getElementById(`timeline_${index}_image_order`);

        if (orderField) {
            orderField.value = index + 1;
        }

        // Set default transition type
        const transitionField = document.getElementById(`timeline_${index}_transition_type`);
        if (transitionField) {
            transitionField.value = 'fade';
        }

        // Add change listeners for time validation
        if (startTimeField && endTimeField) {
            [startTimeField, endTimeField].forEach(field => {
                field.addEventListener('change', () => {
                    this.validateTimeRange(index);
                });
            });
        }
    }

    validateTimeRange(index) {
        const startTimeField = document.getElementById(`timeline_${index}_start_time`);
        const endTimeField = document.getElementById(`timeline_${index}_end_time`);

        if (!startTimeField || !endTimeField) return;

        const startTime = parseInt(startTimeField.value) || 0;
        const endTime = parseInt(endTimeField.value) || 0;

        // Clear previous errors
        this.clearFieldError(startTimeField);
        this.clearFieldError(endTimeField);

        if (endTime <= startTime) {
            this.showFieldError(endTimeField, 'زمان پایان باید بیشتر از زمان شروع باشد');
        }

        // Check episode duration
        const episodeDuration = parseInt(document.getElementById('episode-duration')?.value) || 0;
        if (endTime > episodeDuration) {
            this.showFieldError(endTimeField, 'زمان پایان نمی‌تواند بیشتر از مدت اپیزود باشد');
        }
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;

        // Clear previous errors
        this.clearFieldError(field);

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'این فیلد الزامی است');
            return false;
        }

        // Numeric validation
        if (field.type === 'number' && value && isNaN(value)) {
            this.showFieldError(field, 'لطفاً عدد معتبر وارد کنید');
            return false;
        }

        // URL validation
        if (fieldName.includes('image_url') && value) {
            try {
                new URL(value);
            } catch {
                this.showFieldError(field, 'آدرس تصویر نامعتبر است');
                return false;
            }
        }

        return true;
    }

    showFieldError(field, message) {
        field.classList.add('border-red-500', 'bg-red-50');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'timeline-field-error text-red-500 text-sm mt-1';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500', 'bg-red-50');
        
        const errorDiv = field.parentNode.querySelector('.timeline-field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    previewImage(input) {
        const file = input.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            this.showFieldError(input, 'فرمت فایل پشتیبانی نمی‌شود');
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            this.showFieldError(input, 'حجم فایل نمی‌تواند بیشتر از 10 مگابایت باشد');
            return;
        }

        // Show preview
        const preview = input.parentNode.querySelector('.image-preview');
        if (preview) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="max-w-full h-32 object-cover rounded">
                    <p class="text-sm text-gray-600 mt-1">${file.name}</p>
                `;
            };
            reader.readAsDataURL(file);
        }
    }

    collectTimelineData() {
        const entries = document.querySelectorAll('[data-timeline-index]');
        const timelineData = [];

        entries.forEach((entry, index) => {
            const data = {
                start_time: parseInt(entry.querySelector(`[name*="start_time"]`)?.value) || 0,
                end_time: parseInt(entry.querySelector(`[name*="end_time"]`)?.value) || 0,
                image_url: entry.querySelector(`[name*="image_url"]`)?.value || '',
                image_order: parseInt(entry.querySelector(`[name*="image_order"]`)?.value) || index + 1,
                scene_description: entry.querySelector(`[name*="scene_description"]`)?.value || '',
                transition_type: entry.querySelector(`[name*="transition_type"]`)?.value || 'fade',
                is_key_frame: entry.querySelector(`[name*="is_key_frame"]`)?.checked || false
            };

            timelineData.push(data);
        });

        return timelineData;
    }

    validateTimelineData(timelineData) {
        const errors = [];

        if (timelineData.length === 0) {
            errors.push('لطفاً حداقل یک ورودی تایم‌لاین اضافه کنید');
            return errors;
        }

        // Sort by start time for validation
        const sortedData = [...timelineData].sort((a, b) => a.start_time - b.start_time);

        sortedData.forEach((entry, index) => {
            if (!entry.start_time && entry.start_time !== 0) {
                errors.push(`ورودی ${index + 1}: زمان شروع الزامی است`);
            }

            if (!entry.end_time && entry.end_time !== 0) {
                errors.push(`ورودی ${index + 1}: زمان پایان الزامی است`);
            }

            if (!entry.image_url) {
                errors.push(`ورودی ${index + 1}: آدرس تصویر الزامی است`);
            }

            if (!entry.transition_type) {
                errors.push(`ورودی ${index + 1}: نوع انتقال الزامی است`);
            }

            // Check for overlaps
            if (index > 0) {
                const prevEntry = sortedData[index - 1];
                if (entry.start_time < prevEntry.end_time) {
                    errors.push(`تداخل زمانی بین ورودی ${index} و ${index + 1}`);
                }
            }
        });

        return errors;
    }

    renderTimelineEntries() {
        const container = document.getElementById('timeline-entries-container');
        if (!container) return;

        container.innerHTML = '';

        this.timelineData.forEach((entry, index) => {
            this.addTimelineEntry();
            this.populateTimelineEntry(index, entry);
        });
    }

    populateTimelineEntry(index, data) {
        const entry = document.querySelector(`[data-timeline-index="${index}"]`);
        if (!entry) return;

        Object.keys(data).forEach(key => {
            const field = entry.querySelector(`[name*="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key];
                } else {
                    field.value = data[key];
                }
            }
        });
    }

    async handleSubmit() {
        // Clear previous errors
        timelineErrorHandler.clearErrors();

        // Collect and validate data
        const timelineData = this.collectTimelineData();
        const validationErrors = this.validateTimelineData(timelineData);

        if (validationErrors.length > 0) {
            timelineErrorHandler.showErrors([], 'خطا در اعتبارسنجی داده‌ها', validationErrors);
            return;
        }

        // Show loading
        timelineErrorHandler.showLoading('در حال ایجاد تایم‌لاین...');

        try {
            const formData = new FormData(this.form);
            formData.append('image_timeline', JSON.stringify(timelineData));

            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                timelineErrorHandler.showSuccess(data.message, data.data);
                this.clearForm();
            } else {
                timelineErrorHandler.showErrors(data.errors || [], data.message, data.warnings || []);
            }
        } catch (error) {
            timelineErrorHandler.showError('خطا در ارتباط با سرور: ' + error.message);
        }
    }

    clearForm() {
        this.form.reset();
        this.timelineData = [];
        this.currentIndex = 0;
        
        const container = document.getElementById('timeline-entries-container');
        if (container) {
            container.innerHTML = '';
        }

        // Add one default entry
        this.addTimelineEntry();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TimelineCreationForm();
});
