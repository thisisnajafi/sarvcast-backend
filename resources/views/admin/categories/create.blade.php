@extends('admin.layouts.app')

@section('title', 'افزودن دسته‌بندی جدید')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">افزودن دسته‌بندی جدید</h1>
        <a href="{{ route('admin.categories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
            بازگشت به لیست
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام دسته‌بندی</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="نام دسته‌بندی را وارد کنید" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">نامک (Slug)</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('slug') border-red-500 @enderror"
                           placeholder="نامک دسته‌بندی (اختیاری)">
                    @error('slug')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">اگر خالی باشد، به صورت خودکار از نام تولید می‌شود</p>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="توضیحات دسته‌بندی را وارد کنید">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Image -->
            <div class="mb-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر دسته‌بندی</label>
                <input type="file" name="image" id="image" accept="image/*" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">حداکثر 5 مگابایت، فرمت‌های مجاز: JPG, PNG, WebP</p>
            </div>

            <!-- Color -->
            <div class="mb-6">
                <label for="color" class="block text-sm font-medium text-gray-700 mb-2">رنگ دسته‌بندی</label>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <input type="color" name="color" id="color" value="{{ old('color', '#3B82F6') }}" 
                           class="w-16 h-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('color') border-red-500 @enderror">
                    <input type="text" name="color_hex" id="color_hex" value="{{ old('color', '#3B82F6') }}" 
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('color') border-red-500 @enderror"
                           placeholder="#3B82F6">
                </div>
                @error('color')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">رنگ برای نمایش در رابط کاربری</p>
            </div>

            <!-- Status and Options -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Status -->
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                    <select name="is_active" id="is_active" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('is_active') border-red-500 @enderror" required>
                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                    @error('is_active')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">ترتیب نمایش</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('sort_order') border-red-500 @enderror"
                           placeholder="ترتیب نمایش دسته‌بندی">
                    @error('sort_order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>


            <!-- Submit Button -->
            <div class="flex justify-end space-x-4 space-x-reverse">
                <a href="{{ route('admin.categories.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    انصراف
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    ایجاد دسته‌بندی
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value) {
            const slug = this.value
                .toLowerCase()
                .replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFFa-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            slugInput.value = slug;
        }
    });
    
    // Sync color picker and text input
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('color_hex');
    
    colorPicker.addEventListener('input', function() {
        colorText.value = this.value;
    });
    
    colorText.addEventListener('input', function() {
        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
            colorPicker.value = this.value;
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

