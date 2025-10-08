/**
 * Timeline Creation Error Handler
 * Handles timeline creation errors and displays them properly
 */

class TimelineErrorHandler {
    constructor() {
        this.errorContainer = null;
        this.init();
    }

    init() {
        this.setupErrorContainer();
        this.setupEventListeners();
    }

    setupErrorContainer() {
        // Create error container if it doesn't exist
        this.errorContainer = document.getElementById('timeline-error-container');
        if (!this.errorContainer) {
            this.errorContainer = document.createElement('div');
            this.errorContainer.id = 'timeline-error-container';
            this.errorContainer.className = 'timeline-error-container fixed top-4 right-4 z-50 max-w-md';
            document.body.appendChild(this.errorContainer);
        }
    }

    setupEventListeners() {
        // Listen for timeline creation form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('timeline-creation-form')) {
                this.handleFormSubmission(e);
            }
        });

        // Listen for AJAX timeline creation
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('create-timeline-btn')) {
                this.handleAjaxCreation(e);
            }
        });
    }

    handleFormSubmission(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ایجاد...';
        }

        // Clear previous errors
        this.clearErrors();

        // Add error handling for form submission
        form.addEventListener('submit', (e) => {
            // This will be handled by the server response
        }, { once: true });
    }

    handleAjaxCreation(e) {
        e.preventDefault();
        const btn = e.target;
        const form = btn.closest('form');
        
        if (!form) return;

        this.clearErrors();
        this.showLoading('در حال ایجاد تایم‌لاین...');

        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading();
            
            if (data.success) {
                this.showSuccess(data.message, data.data);
                this.clearForm(form);
            } else {
                this.showErrors(data.errors || [], data.message, data.warnings || []);
            }
        })
        .catch(error => {
            this.hideLoading();
            this.showError('خطا در ارتباط با سرور: ' + error.message);
        });
    }

    showErrors(errors, message, warnings = []) {
        let errorHtml = '';

        // Main error message
        if (message) {
            errorHtml += `
                <div class="bg-red-500 text-white p-4 rounded-lg mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span class="font-semibold">${message}</span>
                    </div>
                </div>
            `;
        }

        // Detailed errors
        if (errors && Object.keys(errors).length > 0) {
            errorHtml += `
                <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg mb-3">
                    <h4 class="font-semibold mb-2">جزئیات خطاها:</h4>
                    <ul class="list-disc list-inside space-y-1">
            `;

            Object.keys(errors).forEach(field => {
                const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                fieldErrors.forEach(error => {
                    errorHtml += `<li>${error}</li>`;
                });
            });

            errorHtml += `
                    </ul>
                </div>
            `;
        }

        // Warnings
        if (warnings && warnings.length > 0) {
            errorHtml += `
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 p-4 rounded-lg mb-3">
                    <h4 class="font-semibold mb-2">هشدارها:</h4>
                    <ul class="list-disc list-inside space-y-1">
            `;

            warnings.forEach(warning => {
                errorHtml += `<li>${warning}</li>`;
            });

            errorHtml += `
                    </ul>
                </div>
            `;
        }

        // Add close button
        errorHtml += `
            <div class="flex justify-end">
                <button onclick="timelineErrorHandler.clearErrors()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    بستن
                </button>
            </div>
        `;

        this.errorContainer.innerHTML = errorHtml;
        this.errorContainer.style.display = 'block';

        // Auto-hide after 10 seconds
        setTimeout(() => {
            this.clearErrors();
        }, 10000);
    }

    showError(message) {
        this.showErrors([], message);
    }

    showSuccess(message, data = null) {
        let successHtml = `
            <div class="bg-green-500 text-white p-4 rounded-lg mb-3">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="font-semibold">${message}</span>
                </div>
        `;

        if (data && data.timeline_count) {
            successHtml += `
                <div class="mt-2 text-sm">
                    تعداد تایم‌لاین ایجاد شده: ${data.timeline_count}
                </div>
            `;
        }

        successHtml += `
            </div>
            <div class="flex justify-end">
                <button onclick="timelineErrorHandler.clearErrors()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    بستن
                </button>
            </div>
        `;

        this.errorContainer.innerHTML = successHtml;
        this.errorContainer.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.clearErrors();
        }, 5000);
    }

    showLoading(message) {
        const loadingHtml = `
            <div class="bg-blue-500 text-white p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        this.errorContainer.innerHTML = loadingHtml;
        this.errorContainer.style.display = 'block';
    }

    hideLoading() {
        if (this.errorContainer.style.display === 'block' && 
            this.errorContainer.innerHTML.includes('fa-spinner')) {
            this.clearErrors();
        }
    }

    clearErrors() {
        if (this.errorContainer) {
            this.errorContainer.style.display = 'none';
            this.errorContainer.innerHTML = '';
        }

        // Clear form validation errors
        document.querySelectorAll('.timeline-field-error').forEach(error => {
            error.remove();
        });

        // Remove error classes from form fields
        document.querySelectorAll('.timeline-form input, .timeline-form textarea, .timeline-form select').forEach(field => {
            field.classList.remove('border-red-500', 'bg-red-50');
        });
    }

    clearForm(form) {
        if (form) {
            form.reset();
            
            // Clear any dynamic content
            const dynamicContent = form.querySelectorAll('.dynamic-timeline-entry');
            dynamicContent.forEach(element => {
                element.remove();
            });

            // Reset counters
            const counters = form.querySelectorAll('[data-counter]');
            counters.forEach(counter => {
                counter.textContent = '1';
            });
        }
    }

    // Method to handle server-side validation errors
    handleServerErrors(errors) {
        Object.keys(errors).forEach(field => {
            const fieldElement = document.querySelector(`[name="${field}"]`);
            if (fieldElement) {
                fieldElement.classList.add('border-red-500', 'bg-red-50');
                
                // Add error message below field
                const errorDiv = document.createElement('div');
                errorDiv.className = 'timeline-field-error text-red-500 text-sm mt-1';
                errorDiv.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                
                fieldElement.parentNode.appendChild(errorDiv);
            }
        });
    }

    // Method to validate timeline data before submission
    validateTimelineData(timelineData) {
        const errors = [];

        if (!timelineData || timelineData.length === 0) {
            errors.push('لطفاً حداقل یک ورودی تایم‌لاین اضافه کنید');
            return errors;
        }

        timelineData.forEach((entry, index) => {
            if (!entry.start_time || entry.start_time < 0) {
                errors.push(`ورودی ${index + 1}: زمان شروع نامعتبر است`);
            }

            if (!entry.end_time || entry.end_time < 0) {
                errors.push(`ورودی ${index + 1}: زمان پایان نامعتبر است`);
            }

            if (!entry.image_url) {
                errors.push(`ورودی ${index + 1}: آدرس تصویر الزامی است`);
            }

            if (!entry.transition_type) {
                errors.push(`ورودی ${index + 1}: نوع انتقال الزامی است`);
            }
        });

        return errors;
    }
}

// Initialize the error handler
const timelineErrorHandler = new TimelineErrorHandler();

// Make it globally available
window.timelineErrorHandler = timelineErrorHandler;
