@extends('admin.layouts.app')

@section('title', 'آپلود فایل صوتی جدید')
@section('page-title', 'آپلود فایل صوتی جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">آپلود فایل صوتی جدید</h1>
            <p class="mt-1 text-sm text-gray-600">فایل صوتی جدید را آپلود کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.audio-management.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- File Upload -->
            <div>
                <label for="audio_file" class="block text-sm font-medium text-gray-700 mb-2">فایل صوتی *</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-purple-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="audio_file" class="relative cursor-pointer bg-white rounded-md font-medium text-purple-600 hover:text-purple-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-purple-500">
                                <span>انتخاب فایل</span>
                                <input id="audio_file" name="audio_file" type="file" class="sr-only" accept="audio/*" required>
                            </label>
                            <p class="pr-1">یا کشیدن و رها کردن</p>
                        </div>
                        <p class="text-xs text-gray-500">MP3, WAV, FLAC, AAC, M4A تا 100MB</p>
                    </div>
                </div>
                @error('audio_file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- File Preview -->
            <div id="file-preview" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">پیش‌نمایش فایل</label>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900" id="file-name">-</div>
                            <div class="text-sm text-gray-500" id="file-size">-</div>
                            <div class="text-sm text-gray-500" id="file-type">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Title and Episode -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان فایل *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان فایل صوتی...">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="episode_id" class="block text-sm font-medium text-gray-700 mb-2">اپیزود *</label>
                    <select name="episode_id" id="episode_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('episode_id') border-red-500 @enderror">
                        <option value="">انتخاب اپیزود</option>
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

            <!-- Quality and Description -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="quality" class="block text-sm font-medium text-gray-700 mb-2">کیفیت *</label>
                    <select name="quality" id="quality" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('quality') border-red-500 @enderror">
                        <option value="">انتخاب کیفیت</option>
                        <option value="low" {{ old('quality') == 'low' ? 'selected' : '' }}>کم (64 kbps)</option>
                        <option value="medium" {{ old('quality') == 'medium' ? 'selected' : '' }}>متوسط (128 kbps)</option>
                        <option value="high" {{ old('quality') == 'high' ? 'selected' : '' }}>بالا (320 kbps)</option>
                    </select>
                    @error('quality')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">گزینه‌های پردازش</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="auto_process" id="auto_process" value="1" {{ old('auto_process') ? 'checked' : '' }} class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <label for="auto_process" class="mr-2 text-sm font-medium text-gray-700">پردازش خودکار</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات فایل صوتی...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش تنظیمات</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>عنوان:</strong> <span id="preview-title">-</span></p>
                    <p><strong>اپیزود:</strong> <span id="preview-episode">-</span></p>
                    <p><strong>کیفیت:</strong> <span id="preview-quality">-</span></p>
                    <p><strong>پردازش خودکار:</strong> <span id="preview-process">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.audio-management.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    آپلود فایل
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File preview functionality
document.getElementById('audio_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const filePreview = document.getElementById('file-preview');
    
    if (file) {
        filePreview.classList.remove('hidden');
        
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = formatFileSize(file.size);
        document.getElementById('file-type').textContent = file.type || 'نوع نامشخص';
        
        // Auto-fill title if empty
        if (!document.getElementById('title').value) {
            const fileName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            document.getElementById('title').value = fileName;
            updatePreview();
        }
    } else {
        filePreview.classList.add('hidden');
    }
});

// Update preview when form changes
function updatePreview() {
    const title = document.getElementById('title').value;
    const episodeSelect = document.getElementById('episode_id');
    const episode = episodeSelect.options[episodeSelect.selectedIndex].text;
    const qualitySelect = document.getElementById('quality');
    const quality = qualitySelect.options[qualitySelect.selectedIndex].text;
    const autoProcess = document.getElementById('auto_process').checked;
    
    document.getElementById('preview-title').textContent = title || '-';
    document.getElementById('preview-episode').textContent = episode || '-';
    document.getElementById('preview-quality').textContent = quality || '-';
    document.getElementById('preview-process').textContent = autoProcess ? 'فعال' : 'غیرفعال';
}

// Add event listeners for preview updates
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('episode_id').addEventListener('change', updatePreview);
document.getElementById('quality').addEventListener('change', updatePreview);
document.getElementById('auto_process').addEventListener('change', updatePreview);

// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
@endsection
