@extends('admin.layouts.app')

@section('title', 'ایجاد پشتیبان جدید')
@section('page-title', 'ایجاد پشتیبان جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ایجاد پشتیبان جدید</h1>
            <p class="mt-1 text-sm text-gray-600">تنظیمات پشتیبان‌گیری جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.backup.store') }}" class="p-6 space-y-6">
            @csrf

            <!-- Name and Description -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام پشتیبان *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('name') border-red-500 @enderror" placeholder="نام پشتیبان...">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع پشتیبان *</label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="">انتخاب نوع پشتیبان</option>
                        <option value="database" {{ old('type') == 'database' ? 'selected' : '' }}>پایگاه داده</option>
                        <option value="files" {{ old('type') == 'files' ? 'selected' : '' }}>فایل‌ها</option>
                        <option value="full" {{ old('type') == 'full' ? 'selected' : '' }}>کامل (پایگاه داده + فایل‌ها)</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات پشتیبان...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- File Selection (for files and full backups) -->
            <div id="file-selection" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="include_files" class="block text-sm font-medium text-gray-700 mb-2">شامل فایل‌ها</label>
                        <textarea name="include_files[]" id="include_files" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('include_files') border-red-500 @enderror" placeholder="مسیرهای فایل‌هایی که باید پشتیبان‌گیری شوند (هر خط یک مسیر)&#10;مثال:&#10;/storage/app/uploads&#10;/storage/app/images&#10;/public/css"></textarea>
                        @error('include_files')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">مسیرهای فایل‌هایی که باید پشتیبان‌گیری شوند (هر خط یک مسیر)</p>
                    </div>

                    <div>
                        <label for="exclude_files" class="block text-sm font-medium text-gray-700 mb-2">حذف فایل‌ها</label>
                        <textarea name="exclude_files[]" id="exclude_files" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('exclude_files') border-red-500 @enderror" placeholder="مسیرهای فایل‌هایی که نباید پشتیبان‌گیری شوند (هر خط یک مسیر)&#10;مثال:&#10;/storage/app/temp&#10;/storage/logs&#10;*.tmp"></textarea>
                        @error('exclude_files')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">مسیرهای فایل‌هایی که نباید پشتیبان‌گیری شوند (هر خط یک مسیر)</p>
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">گزینه‌های پشتیبان‌گیری</label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="compression" id="compression" value="1" {{ old('compression', true) ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <label for="compression" class="mr-2 text-sm font-medium text-gray-700">فشرده‌سازی</label>
                            <span class="text-xs text-gray-500">(کاهش حجم فایل پشتیبان)</span>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="encryption" id="encryption" value="1" {{ old('encryption') ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <label for="encryption" class="mr-2 text-sm font-medium text-gray-700">رمزگذاری</label>
                            <span class="text-xs text-gray-500">(امنیت بالاتر)</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="schedule" class="block text-sm font-medium text-gray-700 mb-2">برنامه‌ریزی</label>
                    <select name="schedule" id="schedule" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('schedule') border-red-500 @enderror">
                        <option value="">بدون برنامه‌ریزی</option>
                        <option value="daily" {{ old('schedule') == 'daily' ? 'selected' : '' }}>روزانه</option>
                        <option value="weekly" {{ old('schedule') == 'weekly' ? 'selected' : '' }}>هفتگی</option>
                        <option value="monthly" {{ old('schedule') == 'monthly' ? 'selected' : '' }}>ماهانه</option>
                    </select>
                    @error('schedule')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">برای پشتیبان‌گیری خودکار</p>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش تنظیمات</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>نوع:</strong> <span id="preview-type">-</span></p>
                    <p><strong>فشرده‌سازی:</strong> <span id="preview-compression">-</span></p>
                    <p><strong>رمزگذاری:</strong> <span id="preview-encryption">-</span></p>
                    <p><strong>برنامه‌ریزی:</strong> <span id="preview-schedule">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.backup.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                    ایجاد پشتیبان
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Show/hide file selection based on backup type
document.getElementById('type').addEventListener('change', function() {
    const fileSelection = document.getElementById('file-selection');
    const includeFiles = document.getElementById('include_files');
    const excludeFiles = document.getElementById('exclude_files');
    
    if (this.value === 'files' || this.value === 'full') {
        fileSelection.style.display = 'block';
        if (this.value === 'files') {
            includeFiles.required = true;
        }
    } else {
        fileSelection.style.display = 'none';
        includeFiles.required = false;
    }
    
    updatePreview();
});

// Update preview when form changes
function updatePreview() {
    const type = document.getElementById('type').value;
    const compression = document.getElementById('compression').checked;
    const encryption = document.getElementById('encryption').checked;
    const schedule = document.getElementById('schedule').value;
    
    const typeLabels = {
        'database': 'پایگاه داده',
        'files': 'فایل‌ها',
        'full': 'کامل (پایگاه داده + فایل‌ها)'
    };
    
    const scheduleLabels = {
        'daily': 'روزانه',
        'weekly': 'هفتگی',
        'monthly': 'ماهانه'
    };
    
    document.getElementById('preview-type').textContent = typeLabels[type] || '-';
    document.getElementById('preview-compression').textContent = compression ? 'فعال' : 'غیرفعال';
    document.getElementById('preview-encryption').textContent = encryption ? 'فعال' : 'غیرفعال';
    document.getElementById('preview-schedule').textContent = scheduleLabels[schedule] || 'بدون برنامه‌ریزی';
}

// Add event listeners for preview updates
document.getElementById('compression').addEventListener('change', updatePreview);
document.getElementById('encryption').addEventListener('change', updatePreview);
document.getElementById('schedule').addEventListener('change', updatePreview);

// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>
@endsection
