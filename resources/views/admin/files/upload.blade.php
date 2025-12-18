@extends('admin.layouts.app')

@section('title', 'آپلود فایل')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">آپلود فایل</h1>
        <p class="text-gray-600 mt-2">فایل‌های خود را آپلود کنید</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Image Upload -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">آپلود تصویر</h2>
            
            <form id="image-upload-form" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label for="image-file" class="block text-sm font-medium text-gray-700 mb-2">انتخاب تصویر</label>
                    <input type="file" id="image-file" name="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label for="image-directory" class="block text-sm font-medium text-gray-700 mb-2">پوشه مقصد</label>
                    <select id="image-directory" name="directory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="stories/images">تصاویر داستان‌ها</option>
                        <option value="episodes/images">تصاویر اپیزودها</option>
                        <option value="users/images">تصاویر کاربران</option>
                        <option value="categories/images">تصاویر دسته‌بندی‌ها</option>
                    </select>
                </div>

                <div>
                    <label for="image-disk" class="block text-sm font-medium text-gray-700 mb-2">فضای ذخیره‌سازی</label>
                    <select id="image-disk" name="disk" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="public">محلی (عمومی)</option>
                        <option value="s3">AWS S3</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    آپلود تصویر
                </button>
            </form>

            <div id="image-result" class="mt-4 hidden">
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h3 class="text-sm font-medium text-green-800">آپلود موفق</h3>
                    <div id="image-result-content" class="mt-2 text-sm text-green-700"></div>
                </div>
            </div>
        </div>

        <!-- Audio Upload -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">آپلود فایل صوتی</h2>
            
            <form id="audio-upload-form" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label for="audio-file" class="block text-sm font-medium text-gray-700 mb-2">انتخاب فایل صوتی</label>
                    <input type="file" id="audio-file" name="audio" accept="audio/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label for="audio-directory" class="block text-sm font-medium text-gray-700 mb-2">پوشه مقصد</label>
                    <select id="audio-directory" name="directory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="episodes/audio">فایل‌های صوتی اپیزودها</option>
                        <option value="episodes/previews">پیش‌نمایش‌های صوتی</option>
                    </select>
                </div>

                <div>
                    <label for="audio-disk" class="block text-sm font-medium text-gray-700 mb-2">فضای ذخیره‌سازی</label>
                    <select id="audio-disk" name="disk" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="public">محلی (عمومی)</option>
                        <option value="s3">AWS S3</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    آپلود فایل صوتی
                </button>
            </form>

            <div id="audio-result" class="mt-4 hidden">
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h3 class="text-sm font-medium text-green-800">آپلود موفق</h3>
                    <div id="audio-result-content" class="mt-2 text-sm text-green-700"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Management -->
    <div class="mt-8 bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">مدیریت فایل‌ها</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900">فضای ذخیره‌سازی</h3>
                <p class="text-sm text-gray-500 mt-1">پیش‌فرض: محلی</p>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900">حداکثر اندازه فایل</h3>
                <p class="text-sm text-gray-500 mt-1">تصاویر: 10MB، صوتی: 100MB</p>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900">فرمت‌های پشتیبانی شده</h3>
                <p class="text-sm text-gray-500 mt-1">تصاویر: JPEG, PNG, JPG, GIF</p>
                <p class="text-sm text-gray-500">صوتی: MP3, WAV, M4A, AAC, OGG</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image upload form
    document.getElementById('image-upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const resultDiv = document.getElementById('image-result');
        const resultContent = document.getElementById('image-result-content');
        
        fetch('/api/v1/files/upload/image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultContent.innerHTML = `
                    <p><strong>نام فایل:</strong> ${data.data.original.filename}</p>
                    <p><strong>اندازه:</strong> ${formatFileSize(data.data.original.size)}</p>
                    <p><strong>URL:</strong> <a href="${data.data.original.url}" target="_blank" class="text-blue-600 hover:underline">${data.data.original.url}</a></p>
                `;
                resultDiv.classList.remove('hidden');
                this.reset();
            } else {
                alert('خطا در آپلود: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در آپلود فایل');
        });
    });

    // Audio upload form
    document.getElementById('audio-upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const resultDiv = document.getElementById('audio-result');
        const resultContent = document.getElementById('audio-result-content');
        
        fetch('/api/v1/files/upload/audio', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultContent.innerHTML = `
                    <p><strong>نام فایل:</strong> ${data.data.filename}</p>
                    <p><strong>اندازه:</strong> ${formatFileSize(data.data.size)}</p>
                    <p><strong>URL:</strong> <a href="${data.data.url}" target="_blank" class="text-blue-600 hover:underline">${data.data.url}</a></p>
                `;
                resultDiv.classList.remove('hidden');
                this.reset();
            } else {
                alert('خطا در آپلود: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در آپلود فایل');
        });
    });
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
@endsection
