@extends('admin.layouts.app')

@section('title', 'ایجاد هشدار جدید')
@section('page-title', 'ایجاد هشدار جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ایجاد هشدار جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات هشدار جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.performance-monitoring.store-alert') }}" class="p-6 space-y-6">
            @csrf

            <!-- Title and Source -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان هشدار *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان هشدار...">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700 mb-2">منبع *</label>
                    <input type="text" name="source" id="source" value="{{ old('source') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('source') border-red-500 @enderror" placeholder="منبع هشدار...">
                    @error('source')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Severity and Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-700 mb-2">شدت *</label>
                    <select name="severity" id="severity" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('severity') border-red-500 @enderror">
                        <option value="">انتخاب شدت</option>
                        <option value="info" {{ old('severity') == 'info' ? 'selected' : '' }}>اطلاعاتی</option>
                        <option value="warning" {{ old('severity') == 'warning' ? 'selected' : '' }}>هشدار</option>
                        <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>بحرانی</option>
                    </select>
                    @error('severity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="unresolved" {{ old('status', 'unresolved') == 'unresolved' ? 'selected' : '' }}>حل نشده</option>
                        <option value="resolved" {{ old('status') == 'resolved' ? 'selected' : '' }}>حل شده</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Message -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">پیام هشدار *</label>
                <textarea name="message" id="message" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('message') border-red-500 @enderror" placeholder="توضیحات کامل هشدار...">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Threshold Configuration -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-3">تنظیمات آستانه (اختیاری)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="threshold_value" class="block text-sm font-medium text-gray-700 mb-2">مقدار آستانه</label>
                        <input type="number" name="threshold_value" id="threshold_value" value="{{ old('threshold_value') }}" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('threshold_value') border-red-500 @enderror" placeholder="مقدار آستانه...">
                        @error('threshold_value')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="threshold_operator" class="block text-sm font-medium text-gray-700 mb-2">عملگر مقایسه</label>
                        <select name="threshold_operator" id="threshold_operator" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('threshold_operator') border-red-500 @enderror">
                            <option value="">انتخاب عملگر</option>
                            <option value=">" {{ old('threshold_operator') == '>' ? 'selected' : '' }}>بزرگتر از (>)</option>
                            <option value="<" {{ old('threshold_operator') == '<' ? 'selected' : '' }}>کوچکتر از (<)</option>
                            <option value=">=" {{ old('threshold_operator') == '>=' ? 'selected' : '' }}>بزرگتر یا مساوی (>=)</option>
                            <option value="<=" {{ old('threshold_operator') == '<=' ? 'selected' : '' }}>کوچکتر یا مساوی (<=)</option>
                            <option value="=" {{ old('threshold_operator') == '=' ? 'selected' : '' }}>مساوی (=)</option>
                            <option value="!=" {{ old('threshold_operator') == '!=' ? 'selected' : '' }}>نامساوی (!=)</option>
                        </select>
                        @error('threshold_operator')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-500">این تنظیمات برای هشدارهای خودکار استفاده می‌شود.</p>
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش هشدار</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>عنوان:</strong> <span id="preview-title">-</span></p>
                    <p><strong>شدت:</strong> <span id="preview-severity">-</span></p>
                    <p><strong>منبع:</strong> <span id="preview-source">-</span></p>
                    <p><strong>وضعیت:</strong> <span id="preview-status">-</span></p>
                    <p><strong>آستانه:</strong> <span id="preview-threshold">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.performance-monitoring.alerts') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    ایجاد هشدار
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Update preview when form changes
function updatePreview() {
    const title = document.getElementById('title').value;
    const severity = document.getElementById('severity').value;
    const source = document.getElementById('source').value;
    const status = document.getElementById('status').value;
    const thresholdValue = document.getElementById('threshold_value').value;
    const thresholdOperator = document.getElementById('threshold_operator').value;
    
    const severityLabels = {
        'info': 'اطلاعاتی',
        'warning': 'هشدار',
        'critical': 'بحرانی'
    };
    
    const statusLabels = {
        'unresolved': 'حل نشده',
        'resolved': 'حل شده'
    };
    
    const operatorLabels = {
        '>': 'بزرگتر از',
        '<': 'کوچکتر از',
        '>=': 'بزرگتر یا مساوی',
        '<=': 'کوچکتر یا مساوی',
        '=': 'مساوی',
        '!=': 'نامساوی'
    };
    
    document.getElementById('preview-title').textContent = title || '-';
    document.getElementById('preview-severity').textContent = severityLabels[severity] || '-';
    document.getElementById('preview-source').textContent = source || '-';
    document.getElementById('preview-status').textContent = statusLabels[status] || '-';
    
    if (thresholdValue && thresholdOperator) {
        document.getElementById('preview-threshold').textContent = `${operatorLabels[thresholdOperator]} ${thresholdValue}`;
    } else {
        document.getElementById('preview-threshold').textContent = '-';
    }
}

// Add event listeners for preview updates
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('severity').addEventListener('change', updatePreview);
document.getElementById('source').addEventListener('input', updatePreview);
document.getElementById('status').addEventListener('change', updatePreview);
document.getElementById('threshold_value').addEventListener('input', updatePreview);
document.getElementById('threshold_operator').addEventListener('change', updatePreview);

// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>
@endsection
