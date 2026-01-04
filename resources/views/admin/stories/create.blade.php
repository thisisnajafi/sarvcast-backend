@extends('admin.layouts.app')

@section('title', 'ایجاد داستان جدید')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ایجاد داستان جدید</h1>
        <a href="{{ route('admin.stories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
            بازگشت به لیست
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.stories.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان داستان</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror"
                           placeholder="عنوان داستان را وارد کنید" required>
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subtitle -->
                <div>
                    <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">زیرعنوان</label>
                    <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('subtitle') border-red-500 @enderror"
                           placeholder="زیرعنوان داستان را وارد کنید">
                    @error('subtitle')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="توضیحات داستان را وارد کنید" required>{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category and People -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                    <select name="category_id" id="category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('category_id') border-red-500 @enderror" required>
                        <option value="">انتخاب دسته‌بندی</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Age Group -->
                <div>
                    <label for="age_group" class="block text-sm font-medium text-gray-700 mb-2">گروه سنی</label>
                    <select name="age_group" id="age_group"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('age_group') border-red-500 @enderror" required>
                        <option value="">انتخاب گروه سنی</option>
                        <option value="3-5" {{ old('age_group') == '3-5' ? 'selected' : '' }}>3-5 سال</option>
                        <option value="6-8" {{ old('age_group') == '6-8' ? 'selected' : '' }}>6-8 سال</option>
                        <option value="9-12" {{ old('age_group') == '9-12' ? 'selected' : '' }}>9-12 سال</option>
                        <option value="13+" {{ old('age_group') == '13+' ? 'selected' : '' }}>13+ سال</option>
                    </select>
                    @error('age_group')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- People (Optional - Collapsible) -->
            <div class="mb-6">
                <button type="button" onclick="toggleSection('people-section')" class="flex items-center text-sm font-medium text-gray-700 mb-2 hover:text-primary">
                    <span id="people-section-icon">▼</span>
                    <span class="mr-2">اطلاعات افراد (اختیاری)</span>
                </button>
                <div id="people-section" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                        <!-- Director -->
                        <div>
                            <label for="director_id" class="block text-sm font-medium text-gray-700 mb-2">کارگردان</label>
                            <select name="director_id" id="director_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('director_id') border-red-500 @enderror">
                                <option value="">انتخاب کارگردان</option>
                                @foreach($people as $person)
                                    <option value="{{ $person->id }}" {{ old('director_id') == $person->id ? 'selected' : '' }}>
                                        {{ $person->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('director_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Author -->
                        <div>
                            <label for="author_id" class="block text-sm font-medium text-gray-700 mb-2">مؤلف (کاربر)</label>
                            <select name="author_id" id="author_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('author_id') border-red-500 @enderror">
                                <option value="">انتخاب مؤلف</option>
                                @foreach($eligibleUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('author_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->first_name }} {{ $user->last_name }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            </select>
                            @error('author_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Narrator -->
                        <div>
                            <label for="narrator_id" class="block text-sm font-medium text-gray-700 mb-2">راوی (کاربر)</label>
                            <select name="narrator_id" id="narrator_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('narrator_id') border-red-500 @enderror">
                                <option value="">انتخاب راوی</option>
                                @foreach($eligibleUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('narrator_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->first_name }} {{ $user->last_name }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            </select>
                            @error('narrator_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div class="mb-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر داستان *</label>
                <input type="file" name="image" id="image" accept="image/*" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">حداکثر 5 مگابایت، فرمت‌های مجاز: JPG, PNG, WebP (این تصویر برای تصویر جلد و تصویر داستان استفاده می‌شود)</p>
            </div>


            <!-- Script File and Workflow Status (Optional) -->
            <div class="mb-6">
                <button type="button" onclick="toggleSection('advanced-section')" class="flex items-center text-sm font-medium text-gray-700 mb-2 hover:text-primary">
                    <span id="advanced-section-icon">▼</span>
                    <span class="mr-2">گزینه‌های پیشرفته (اختیاری)</span>
                </button>
                <div id="advanced-section" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                        <!-- Workflow Status -->
                        <div>
                            <label for="workflow_status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت گردش کار</label>
                            <select name="workflow_status" id="workflow_status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('workflow_status') border-red-500 @enderror">
                                <option value="written" {{ old('workflow_status', 'written') == 'written' ? 'selected' : '' }}>نوشته شده</option>
                                <option value="characters_made" {{ old('workflow_status') == 'characters_made' ? 'selected' : '' }}>شخصیت‌ها ساخته شده</option>
                                <option value="recorded" {{ old('workflow_status') == 'recorded' ? 'selected' : '' }}>ضبط شده</option>
                                <option value="timeline_created" {{ old('workflow_status') == 'timeline_created' ? 'selected' : '' }}>تایم‌لاین ایجاد شده</option>
                                <option value="published" {{ old('workflow_status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                            </select>
                            @error('workflow_status')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="mb-4">
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">برچسب‌ها</label>
                        <input type="text" name="tags" id="tags" value="{{ is_array(old('tags')) ? implode(', ', old('tags')) : old('tags') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('tags') border-red-500 @enderror"
                               placeholder="برچسب‌ها را با کاما جدا کنید">
                        @error('tags')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">مثال: ماجراجویی، دوستی، خانواده</p>
                    </div>
                </div>
            </div>

            <!-- Status and Options -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror" required>
                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>رد شده</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Premium Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">گزینه‌های دسترسی</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_premium" id="is_premium" value="1"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                   {{ old('is_premium') ? 'checked' : '' }}>
                            <label for="is_premium" class="mr-2 text-sm text-gray-700">داستان پولی</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_completely_free" id="is_completely_free" value="1"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                   {{ old('is_completely_free') ? 'checked' : '' }}>
                            <label for="is_completely_free" class="mr-2 text-sm text-gray-700">کاملاً رایگان</label>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Submit Button -->
            <div class="flex justify-between items-center">
                <div class="flex space-x-2 space-x-reverse">
                    <button type="button" onclick="clearStoryFormData()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                        پاک کردن داده‌های ذخیره شده
                    </button>
                </div>
                <div class="flex space-x-4 space-x-reverse">
                    <a href="{{ route('admin.stories.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                        انصراف
                    </a>
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                        ایجاد داستان
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Preview image uploads
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById(previewId);
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = previewId;
                    preview.className = 'mt-2 w-32 h-32 object-cover rounded-lg border';
                    input.parentNode.appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('cover_image').addEventListener('change', function() {
        previewImage(this, 'cover_preview');
    });

    document.getElementById('image').addEventListener('change', function() {
        previewImage(this, 'image_preview');
    });
});

// Toggle collapsible sections
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const icon = document.getElementById(sectionId + '-icon');

    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        icon.textContent = '▲';
    } else {
        section.classList.add('hidden');
        icon.textContent = '▼';
    }
}
</script>
<script src="{{ asset('js/form-state-manager.js') }}"></script>

<script>
// Enhanced form handling with state persistence for Story creation
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form state management
    if (window.storyFormManager) {
        console.log('Story form state management initialized');
    }

    // Handle form submission with better error handling
    const form = document.querySelector('form[action*="stories"]');
    if (form) {
        form.addEventListener('submit', function(e) {
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

    // Enhanced image preview with persistence
    enhanceImagePreview();
});

function addAutoSaveIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'auto-save-indicator';
    indicator.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300';
    indicator.textContent = '✓ ذخیره خودکار';
    document.body.appendChild(indicator);

    // Show indicator when form is saved
    const form = document.querySelector('form[action*="stories"]');
    if (form) {
        form.addEventListener('input', () => {
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        });
    }
}

function enhanceImagePreview() {
    // Enhanced image preview with better error handling
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('حجم فایل نمی‌تواند بیشتر از 5 مگابایت باشد', 'error');
                input.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('فرمت فایل باید JPG، PNG یا WebP باشد', 'error');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById(previewId);
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = previewId;
                    preview.className = 'mt-2 w-32 h-32 object-cover rounded-lg border';
                    input.parentNode.appendChild(preview);
                }
                preview.src = e.target.result;

                // Save file info for restoration
                saveImageFileInfo(input.id, file);
            };
            reader.readAsDataURL(file);
        }
    }

    // Attach enhanced preview to image input
    document.getElementById('image').addEventListener('change', function() {
        previewImage(this, 'image_preview');
    });
}

function saveImageFileInfo(inputId, file) {
    const fileData = {
        name: file.name,
        size: file.size,
        type: file.type,
        lastModified: file.lastModified
    };

    try {
        localStorage.setItem(`story_${inputId}_file`, JSON.stringify(fileData));
    } catch (error) {
        console.warn('Could not save image file data:', error);
    }
}

// Enhanced validation for story form
function validateStoryForm() {
    const form = document.querySelector('form[action*="stories"]');
    if (!form) return false;

    let isValid = true;
    const errors = [];

    // Required field validation
    const requiredFields = ['title', 'description', 'category_id', 'age_group'];
    requiredFields.forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field && !field.value.trim()) {
            errors.push(`${fieldName} الزامی است`);
            isValid = false;
        }
    });


    // Show errors if any
    if (!isValid) {
        showNotification(errors.join('، '), 'error');
    }

    return isValid;
}

// Add form validation before submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="stories"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateStoryForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// Enhanced tag input with suggestions
function enhanceTagInput() {
    const tagInput = document.getElementById('tags');
    if (!tagInput) return;

    const commonTags = [
        'ماجراجویی', 'دوستی', 'خانواده', 'عشق', 'کمدی', 'درام', 'ترسناک',
        'علمی تخیلی', 'فانتزی', 'تاریخی', 'آموزشی', 'اخلاقی', 'اجتماعی'
    ];

    // Create tag suggestions container
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.id = 'tag-suggestions';
    suggestionsContainer.className = 'hidden absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg shadow-lg z-10 max-h-40 overflow-y-auto';
    tagInput.parentNode.style.position = 'relative';
    tagInput.parentNode.appendChild(suggestionsContainer);

    // Show suggestions on focus
    tagInput.addEventListener('focus', function() {
        showTagSuggestions();
    });

    // Hide suggestions on blur
    tagInput.addEventListener('blur', function() {
        setTimeout(() => {
            suggestionsContainer.classList.add('hidden');
        }, 200);
    });

    function showTagSuggestions() {
        const currentTags = tagInput.value.split(',').map(tag => tag.trim());
        const availableTags = commonTags.filter(tag =>
            !currentTags.includes(tag) &&
            tag.includes(tagInput.value.toLowerCase())
        );

        if (availableTags.length > 0) {
            suggestionsContainer.innerHTML = availableTags.map(tag =>
                `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" onclick="addTag('${tag}')">${tag}</div>`
            ).join('');
            suggestionsContainer.classList.remove('hidden');
        } else {
            suggestionsContainer.classList.add('hidden');
        }
    }

    // Update suggestions as user types
    tagInput.addEventListener('input', showTagSuggestions);
}

function addTag(tag) {
    const tagInput = document.getElementById('tags');
    const currentTags = tagInput.value.split(',').map(t => t.trim()).filter(t => t);

    if (!currentTags.includes(tag)) {
        currentTags.push(tag);
        tagInput.value = currentTags.join(', ');
    }

    document.getElementById('tag-suggestions').classList.add('hidden');
    tagInput.focus();
}

// Initialize enhanced features
document.addEventListener('DOMContentLoaded', function() {
    enhanceTagInput();
});

// Clear story form data function
function clearStoryFormData() {
    if (confirm('آیا از پاک کردن تمام داده‌های ذخیره شده اطمینان دارید؟')) {
        // Clear localStorage data
        if (window.storyFormManager) {
            window.storyFormManager.clearData();
        }

        // Clear file data
        localStorage.removeItem('story_image_file');

        // Reset form
        const form = document.querySelector('form[action*="stories"]');
        if (form) {
            form.reset();
        }

        // Clear image previews
        const imagePreview = document.getElementById('image_preview');
        if (imagePreview) {
            imagePreview.remove();
        }

        showNotification('داده‌های فرم پاک شد', 'success');
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
</script>
@endsection
