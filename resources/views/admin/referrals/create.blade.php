@extends('admin.layouts.app')

@section('title', 'افزودن معرفی جدید')
@section('page-title', 'افزودن معرفی جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">افزودن معرفی جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات معرفی جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.referrals.store') }}" class="p-6 space-y-6">
            @csrf

            <!-- Referrer Selection -->
            <div>
                <label for="referrer_id" class="block text-sm font-medium text-gray-700 mb-2">معرف *</label>
                <select name="referrer_id" id="referrer_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referrer_id') border-red-500 @enderror">
                    <option value="">انتخاب معرف</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('referrer_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('referrer_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Referred Email -->
            <div>
                <label for="referred_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل معرفی شده *</label>
                <input type="email" name="referred_email" id="referred_email" value="{{ old('referred_email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referred_email') border-red-500 @enderror" placeholder="ایمیل شخص معرفی شده را وارد کنید...">
                @error('referred_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">اگر این ایمیل قبلاً ثبت‌نام کرده باشد، معرفی به صورت خودکار تکمیل خواهد شد.</p>
            </div>

            <!-- Referral Type and Reward Amount -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="referral_type" class="block text-sm font-medium text-gray-700 mb-2">نوع معرفی *</label>
                    <select name="referral_type" id="referral_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referral_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع معرفی</option>
                        <option value="user_registration" {{ old('referral_type') == 'user_registration' ? 'selected' : '' }}>ثبت‌نام کاربر</option>
                        <option value="subscription_purchase" {{ old('referral_type') == 'subscription_purchase' ? 'selected' : '' }}>خرید اشتراک</option>
                        <option value="content_engagement" {{ old('referral_type') == 'content_engagement' ? 'selected' : '' }}>تعامل با محتوا</option>
                    </select>
                    @error('referral_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_amount" class="block text-sm font-medium text-gray-700 mb-2">مبلغ پاداش *</label>
                    <input type="number" name="reward_amount" id="reward_amount" value="{{ old('reward_amount', 1000) }}" min="0" step="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('reward_amount') border-red-500 @enderror" placeholder="1000">
                    @error('reward_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">مبلغ پاداش به سکه</p>
                </div>
            </div>

            <!-- Expiry Days -->
            <div>
                <label for="expiry_days" class="block text-sm font-medium text-gray-700 mb-2">مدت اعتبار (روز) *</label>
                <input type="number" name="expiry_days" id="expiry_days" value="{{ old('expiry_days', 30) }}" min="1" max="365" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('expiry_days') border-red-500 @enderror" placeholder="30">
                @error('expiry_days')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">تعداد روزهای اعتبار کد معرفی</p>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات اضافی در مورد این معرفی...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش کد معرفی</h3>
                <div class="text-sm text-gray-600">
                    <p>کد معرفی: <span id="preview-code" class="font-mono bg-white px-2 py-1 rounded border">REF-XXXX-XXXX</span></p>
                    <p>تاریخ انقضا: <span id="preview-expiry" class="font-medium">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.referrals.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    ایجاد معرفی
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Generate preview referral code
function generatePreviewCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = 'REF-';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

// Update preview when form changes
document.addEventListener('DOMContentLoaded', function() {
    const expiryDaysInput = document.getElementById('expiry_days');
    const previewCode = document.getElementById('preview-code');
    const previewExpiry = document.getElementById('preview-expiry');
    
    // Set initial preview
    previewCode.textContent = generatePreviewCode();
    
    function updatePreview() {
        const days = parseInt(expiryDaysInput.value) || 30;
        const expiryDate = new Date();
        expiryDate.setDate(expiryDate.getDate() + days);
        previewExpiry.textContent = expiryDate.toLocaleDateString('fa-IR');
    }
    
    expiryDaysInput.addEventListener('input', updatePreview);
    updatePreview();
});

// Auto-generate new preview code every 5 seconds
setInterval(function() {
    document.getElementById('preview-code').textContent = generatePreviewCode();
}, 5000);
</script>
@endsection
