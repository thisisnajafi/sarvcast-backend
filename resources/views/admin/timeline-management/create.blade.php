@extends('admin.layouts.app')

@section('title', 'ایجاد تایم‌لاین جدید')
@section('page-title', 'ایجاد تایم‌لاین جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ایجاد تایم‌لاین جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات تایم‌لاین جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.timeline-management.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- Title and Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان تایم‌لاین *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان تایم‌لاین...">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع تایم‌لاین *</label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="">انتخاب نوع</option>
                        <option value="story" {{ old('type') == 'story' ? 'selected' : '' }}>داستان</option>
                        <option value="episode" {{ old('type') == 'episode' ? 'selected' : '' }}>اپیزود</option>
                        <option value="character" {{ old('type') == 'character' ? 'selected' : '' }}>شخصیت</option>
                        <option value="event" {{ old('type') == 'event' ? 'selected' : '' }}>رویداد</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status and Priority -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">اولویت</label>
                    <select name="priority" id="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('priority') border-red-500 @enderror">
                        <option value="">انتخاب اولویت</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ old('priority') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Story and Episode Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                    <select name="story_id" id="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('story_id') border-red-500 @enderror">
                        <option value="">انتخاب داستان (اختیاری)</option>
                        @foreach($stories as $story)
                            <option value="{{ $story->id }}" {{ old('story_id') == $story->id ? 'selected' : '' }}>
                                {{ $story->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('story_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="episode_id" class="block text-sm font-medium text-gray-700 mb-2">اپیزود</label>
                    <select name="episode_id" id="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('episode_id') border-red-500 @enderror">
                        <option value="">انتخاب اپیزود (اختیاری)</option>
                        @foreach($episodes as $episode)
                            <option value="{{ $episode->id }}" {{ old('episode_id') == $episode->id ? 'selected' : '' }}>
                                {{ $episode->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('episode_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ شروع</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('start_date') border-red-500 @enderror">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ پایان</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('end_date') border-red-500 @enderror">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات تایم‌لاین...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-cyan-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-cyan-600 hover:text-cyan-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-cyan-500">
                                <span>انتخاب تصویر</span>
                                <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                            </label>
                            <p class="pr-1">یا کشیدن و رها کردن</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, GIF تا 10MB</p>
                    </div>
                </div>
                @error('image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color and Tags -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-2">رنگ</label>
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <input type="color" name="color" id="color" value="{{ old('color', '#06b6d4') }}" class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                        <input type="text" value="{{ old('color', '#06b6d4') }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="#06b6d4" readonly>
                    </div>
                    @error('color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">برچسب‌ها</label>
                    <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent @error('tags') border-red-500 @enderror" placeholder="برچسب‌ها را با کاما جدا کنید...">
                    @error('tags')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">برچسب‌ها را با کاما جدا کنید</p>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش تایم‌لاین</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>عنوان:</strong> <span id="preview-title">-</span></p>
                    <p><strong>نوع:</strong> <span id="preview-type">-</span></p>
                    <p><strong>وضعیت:</strong> <span id="preview-status">-</span></p>
                    <p><strong>اولویت:</strong> <span id="preview-priority">-</span></p>
                    <p><strong>رنگ:</strong> <span id="preview-color">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.timeline-management.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition-colors">
                    ایجاد تایم‌لاین
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Update preview when form changes
function updatePreview() {
    const title = document.getElementById('title').value;
    const typeSelect = document.getElementById('type');
    const type = typeSelect.options[typeSelect.selectedIndex].text;
    const statusSelect = document.getElementById('status');
    const status = statusSelect.options[statusSelect.selectedIndex].text;
    const prioritySelect = document.getElementById('priority');
    const priority = prioritySelect.options[prioritySelect.selectedIndex].text;
    const color = document.getElementById('color').value;
    
    document.getElementById('preview-title').textContent = title || '-';
    document.getElementById('preview-type').textContent = type || '-';
    document.getElementById('preview-status').textContent = status || '-';
    document.getElementById('preview-priority').textContent = priority || '-';
    document.getElementById('preview-color').textContent = color || '-';
}

// Add event listeners for preview updates
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('type').addEventListener('change', updatePreview);
document.getElementById('status').addEventListener('change', updatePreview);
document.getElementById('priority').addEventListener('change', updatePreview);
document.getElementById('color').addEventListener('change', updatePreview);

// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>
@endsection
