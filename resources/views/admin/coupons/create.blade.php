@extends('admin.layouts.app')

@section('title', 'افزودن کد تخفیف')
@section('page-title', 'افزودن کد تخفیف')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'افزودن کد تخفیف',
        'subtitle' => 'ایجاد کد تخفیف جدید',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>',
        'iconBg' => 'bg-purple-100',
        'iconColor' => 'text-purple-600',
        'actions' => '<a href="' . route('admin.coupons.index') . '" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>بازگشت</a>'
    ])

    <!-- Form -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        کد تخفیف <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           value="{{ old('code') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('code') border-red-500 @enderror"
                           placeholder="مثال: WELCOME20"
                           required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        نوع تخفیف <span class="text-red-500">*</span>
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('type') border-red-500 @enderror"
                            required>
                        <option value="">انتخاب کنید</option>
                        <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>درصدی</option>
                        <option value="fixed_amount" {{ old('type') == 'fixed_amount' ? 'selected' : '' }}>مبلغ ثابت</option>
                        <option value="free_coins" {{ old('type') == 'free_coins' ? 'selected' : '' }}>سکه رایگان</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Value -->
                <div>
                    <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                        مقدار <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="value" 
                           name="value" 
                           value="{{ old('value') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('value') border-red-500 @enderror"
                           placeholder="مثال: 20 (برای درصد) یا 50000 (برای مبلغ)"
                           min="0"
                           step="0.01"
                           required>
                    @error('value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">
                        برای درصد: عدد بین 0-100، برای مبلغ: مقدار به تومان، برای سکه: تعداد سکه
                    </p>
                </div>

                <!-- Usage Limit -->
                <div>
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">
                        محدودیت استفاده
                    </label>
                    <input type="number" 
                           id="usage_limit" 
                           name="usage_limit" 
                           value="{{ old('usage_limit') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('usage_limit') border-red-500 @enderror"
                           placeholder="خالی = نامحدود"
                           min="1">
                    @error('usage_limit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expires At -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                        تاریخ انقضا
                    </label>
                    <input type="datetime-local" 
                           id="expires_at" 
                           name="expires_at" 
                           value="{{ old('expires_at') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                        وضعیت <span class="text-red-500">*</span>
                    </label>
                    <select id="is_active" 
                            name="is_active" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('is_active') border-red-500 @enderror"
                            required>
                        <option value="">انتخاب کنید</option>
                        <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    توضیحات
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="توضیحات اختیاری در مورد کد تخفیف">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.coupons.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    انصراف
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-purple-600 border border-transparent rounded-lg font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                    ایجاد کد تخفیف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate code suggestion
document.getElementById('code').addEventListener('input', function(e) {
    let value = e.target.value;
    if (value && !value.includes(' ')) {
        e.target.value = value.toUpperCase();
    }
});

// Show/hide value help based on type
document.getElementById('type').addEventListener('change', function(e) {
    const valueInput = document.getElementById('value');
    const helpText = valueInput.nextElementSibling;
    
    switch(e.target.value) {
        case 'percentage':
            helpText.textContent = 'برای درصد: عدد بین 0-100';
            valueInput.placeholder = 'مثال: 20 (20% تخفیف)';
            valueInput.max = '100';
            break;
        case 'fixed_amount':
            helpText.textContent = 'برای مبلغ: مقدار به تومان';
            valueInput.placeholder = 'مثال: 50000 (50,000 تومان تخفیف)';
            valueInput.removeAttribute('max');
            break;
        case 'free_coins':
            helpText.textContent = 'برای سکه: تعداد سکه';
            valueInput.placeholder = 'مثال: 100 (100 سکه رایگان)';
            valueInput.removeAttribute('max');
            break;
        default:
            helpText.textContent = 'برای درصد: عدد بین 0-100، برای مبلغ: مقدار به تومان، برای سکه: تعداد سکه';
            valueInput.placeholder = 'مثال: 20 (برای درصد) یا 50000 (برای مبلغ)';
            valueInput.removeAttribute('max');
    }
});
</script>
@endsection
