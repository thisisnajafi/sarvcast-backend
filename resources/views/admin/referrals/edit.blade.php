@extends('admin.layouts.app')

@section('title', 'ویرایش معرفی')
@section('page-title', 'ویرایش معرفی')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ویرایش معرفی</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات معرفی را ویرایش کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.referrals.update', $referral) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Referral Code (Read-only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">کد معرفی</label>
                <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                    {{ $referral->referral_code }}
                </div>
                <p class="mt-1 text-sm text-gray-500">کد معرفی قابل تغییر نیست.</p>
            </div>

            <!-- Referrer Selection -->
            <div>
                <label for="referrer_id" class="block text-sm font-medium text-gray-700 mb-2">معرف *</label>
                <select name="referrer_id" id="referrer_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referrer_id') border-red-500 @enderror">
                    <option value="">انتخاب معرف</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (old('referrer_id', $referral->referrer_id) == $user->id) ? 'selected' : '' }}>
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
                <input type="email" name="referred_email" id="referred_email" value="{{ old('referred_email', $referral->referred_email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referred_email') border-red-500 @enderror" placeholder="ایمیل شخص معرفی شده را وارد کنید...">
                @error('referred_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if($referral->referred)
                    <p class="mt-1 text-sm text-green-600">این ایمیل قبلاً ثبت‌نام کرده است: {{ $referral->referred->first_name }} {{ $referral->referred->last_name }}</p>
                @endif
            </div>

            <!-- Referral Type and Reward Amount -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="referral_type" class="block text-sm font-medium text-gray-700 mb-2">نوع معرفی *</label>
                    <select name="referral_type" id="referral_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('referral_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع معرفی</option>
                        <option value="user_registration" {{ old('referral_type', $referral->referral_type) == 'user_registration' ? 'selected' : '' }}>ثبت‌نام کاربر</option>
                        <option value="subscription_purchase" {{ old('referral_type', $referral->referral_type) == 'subscription_purchase' ? 'selected' : '' }}>خرید اشتراک</option>
                        <option value="content_engagement" {{ old('referral_type', $referral->referral_type) == 'content_engagement' ? 'selected' : '' }}>تعامل با محتوا</option>
                    </select>
                    @error('referral_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_amount" class="block text-sm font-medium text-gray-700 mb-2">مبلغ پاداش *</label>
                    <input type="number" name="reward_amount" id="reward_amount" value="{{ old('reward_amount', $referral->reward_amount) }}" min="0" step="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('reward_amount') border-red-500 @enderror" placeholder="1000">
                    @error('reward_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">مبلغ پاداش به سکه</p>
                </div>
            </div>

            <!-- Status and Reward Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="pending" {{ old('status', $referral->status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="completed" {{ old('status', $referral->status) == 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                        <option value="expired" {{ old('status', $referral->status) == 'expired' ? 'selected' : '' }}>منقضی شده</option>
                        <option value="cancelled" {{ old('status', $referral->status) == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت پاداش *</label>
                    <select name="reward_status" id="reward_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('reward_status') border-red-500 @enderror">
                        <option value="pending" {{ old('reward_status', $referral->reward_status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="paid" {{ old('reward_status', $referral->reward_status) == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                        <option value="cancelled" {{ old('reward_status', $referral->reward_status) == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                    @error('reward_status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Expiry Days -->
            <div>
                <label for="expiry_days" class="block text-sm font-medium text-gray-700 mb-2">مدت اعتبار (روز) *</label>
                <input type="number" name="expiry_days" id="expiry_days" value="{{ old('expiry_days', $referral->expiry_days) }}" min="1" max="365" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('expiry_days') border-red-500 @enderror" placeholder="30">
                @error('expiry_days')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">تعداد روزهای اعتبار کد معرفی</p>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات اضافی در مورد این معرفی...">{{ old('description', $referral->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Current Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">اطلاعات فعلی</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p><strong>تاریخ ایجاد:</strong> {{ $referral->created_at->format('Y/m/d H:i') }}</p>
                        <p><strong>تاریخ انقضا:</strong> {{ $referral->expires_at ? $referral->expires_at->format('Y/m/d H:i') : '-' }}</p>
                    </div>
                    <div>
                        <p><strong>تاریخ تکمیل:</strong> {{ $referral->completed_at ? $referral->completed_at->format('Y/m/d H:i') : '-' }}</p>
                        <p><strong>تاریخ پرداخت:</strong> {{ $referral->paid_at ? $referral->paid_at->format('Y/m/d H:i') : '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.referrals.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    به‌روزرسانی معرفی
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
