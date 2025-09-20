@extends('admin.layouts.app')

@section('title', 'ویرایش داستان')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ویرایش داستان: {{ $story->title }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.stories.show', $story) }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                مشاهده
            </a>
            <a href="{{ route('admin.stories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.stories.update', $story) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان داستان</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $story->title) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror"
                           placeholder="عنوان داستان را وارد کنید" required>
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subtitle -->
                <div>
                    <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">زیرعنوان</label>
                    <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle', $story->subtitle) }}" 
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
                          placeholder="توضیحات داستان را وارد کنید" required>{{ old('description', $story->description) }}</textarea>
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
                            <option value="{{ $category->id }}" {{ old('category_id', $story->category_id) == $category->id ? 'selected' : '' }}>
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
                        <option value="3-5" {{ old('age_group', $story->age_group) == '3-5' ? 'selected' : '' }}>3-5 سال</option>
                        <option value="6-8" {{ old('age_group', $story->age_group) == '6-8' ? 'selected' : '' }}>6-8 سال</option>
                        <option value="9-12" {{ old('age_group', $story->age_group) == '9-12' ? 'selected' : '' }}>9-12 سال</option>
                        <option value="13+" {{ old('age_group', $story->age_group) == '13+' ? 'selected' : '' }}>13+ سال</option>
                    </select>
                    @error('age_group')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- People -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Director -->
                <div>
                    <label for="director_id" class="block text-sm font-medium text-gray-700 mb-2">کارگردان</label>
                    <select name="director_id" id="director_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('director_id') border-red-500 @enderror">
                        <option value="">انتخاب کارگردان</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ old('director_id', $story->director_id) == $person->id ? 'selected' : '' }}>
                                {{ $person->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('director_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Writer -->
                <div>
                    <label for="writer_id" class="block text-sm font-medium text-gray-700 mb-2">نویسنده</label>
                    <select name="writer_id" id="writer_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('writer_id') border-red-500 @enderror">
                        <option value="">انتخاب نویسنده</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ old('writer_id', $story->writer_id) == $person->id ? 'selected' : '' }}>
                                {{ $person->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('writer_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Author -->
                <div>
                    <label for="author_id" class="block text-sm font-medium text-gray-700 mb-2">مؤلف</label>
                    <select name="author_id" id="author_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('author_id') border-red-500 @enderror">
                        <option value="">انتخاب مؤلف</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ old('author_id', $story->author_id) == $person->id ? 'selected' : '' }}>
                                {{ $person->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('author_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Narrator -->
                <div>
                    <label for="narrator_id" class="block text-sm font-medium text-gray-700 mb-2">راوی</label>
                    <select name="narrator_id" id="narrator_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('narrator_id') border-red-500 @enderror">
                        <option value="">انتخاب راوی</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ old('narrator_id', $story->narrator_id) == $person->id ? 'selected' : '' }}>
                                {{ $person->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('narrator_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Images -->
            @if($story->cover_image_url || $story->image_url)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصاویر فعلی</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @if($story->cover_image_url)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تصویر جلد فعلی</label>
                                <img src="{{ $story->cover_image_url }}" alt="Cover Image" class="w-full h-48 object-cover rounded-lg border">
                            </div>
                        @endif
                        
                        @if($story->image_url)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تصویر داستان فعلی</label>
                                <img src="{{ $story->image_url }}" alt="Story Image" class="w-full h-48 object-cover rounded-lg border">
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- New Images -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Cover Image -->
                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-2">تصویر جلد جدید</label>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('cover_image') border-red-500 @enderror">
                    @error('cover_image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">حداکثر 5 مگابایت، فرمت‌های مجاز: JPG, PNG, WebP</p>
                </div>

                <!-- Story Image -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر داستان جدید</label>
                    <input type="file" name="image" id="image" accept="image/*" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">حداکثر 5 مگابایت، فرمت‌های مجاز: JPG, PNG, WebP</p>
                </div>
            </div>

            <!-- Story Statistics (Read-only) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">مدت زمان کل (محاسبه شده)</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ $story->formatted_duration }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">مدت زمان بر اساس مجموع اپیزودهای منتشر شده محاسبه می‌شود (فرمت: دقیقه:ثانیه)</p>
                </div>

                <!-- Total Episodes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">تعداد کل اپیزودها (محاسبه شده)</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ $story->total_episodes_count }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">تعداد کل اپیزودها به صورت خودکار محاسبه می‌شود</p>
                </div>

                <!-- Free Episodes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">اپیزودهای رایگان (محاسبه شده)</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ $story->free_episodes_count }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">تعداد اپیزودهای رایگان منتشر شده</p>
                </div>
            </div>

            <!-- Additional Statistics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">اپیزودهای پولی (محاسبه شده)</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ $story->premium_episodes_count }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">تعداد اپیزودهای پولی منتشر شده</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">اپیزودهای منتشر شده</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ $story->published_episodes_count }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">تعداد اپیزودهای منتشر شده</p>
                </div>
            </div>

            <!-- Tags -->
            <div class="mb-6">
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">برچسب‌ها</label>
                <input type="text" name="tags" id="tags" value="{{ old('tags') ? (is_array(old('tags')) ? implode(', ', old('tags')) : old('tags')) : (is_array($story->tags) ? implode(', ', $story->tags) : $story->tags) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('tags') border-red-500 @enderror"
                       placeholder="برچسب‌ها را با کاما جدا کنید">
                @error('tags')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">مثال: ماجراجویی، دوستی، خانواده</p>
            </div>

            <!-- Status and Options -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                    <select name="status" id="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror" required>
                        <option value="draft" {{ old('status', $story->status) == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="pending" {{ old('status', $story->status) == 'pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                        <option value="approved" {{ old('status', $story->status) == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                        <option value="published" {{ old('status', $story->status) == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="rejected" {{ old('status', $story->status) == 'rejected' ? 'selected' : '' }}>رد شده</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Published At -->
                <div>
                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انتشار</label>
                    <input type="datetime-local" name="published_at" id="published_at" 
                           value="{{ old('published_at', $story->published_at ? $story->published_at->format('Y-m-d\TH:i') : '') }}" 
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
                               {{ old('is_premium', $story->is_premium) ? 'checked' : '' }}>
                        <label for="is_premium" class="mr-2 text-sm text-gray-700">داستان پولی</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_completely_free" id="is_completely_free" value="1" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('is_completely_free', $story->is_completely_free) ? 'checked' : '' }}>
                        <label for="is_completely_free" class="mr-2 text-sm text-gray-700">کاملاً رایگان</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4 space-x-reverse">
                <a href="{{ route('admin.stories.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    انصراف
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    به‌روزرسانی داستان
                </button>
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
</script>
@endsection

