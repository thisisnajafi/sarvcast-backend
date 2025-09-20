@extends('admin.layouts.app')

@section('title', 'افزودن کمپین اینفلوئنسر جدید')
@section('page-title', 'افزودن کمپین اینفلوئنسر جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">افزودن کمپین اینفلوئنسر جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات کمپین اینفلوئنسر جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.influencers.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">کاربر *</label>
                <div id="influencer-user-search" data-user-search='{"placeholder": "جستجو بر اساس شماره موبایل...", "apiEndpoint": "/admin/users/search"}'></div>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Campaign Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-2">نام کمپین *</label>
                    <input type="text" name="campaign_name" id="campaign_name" value="{{ old('campaign_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('campaign_name') border-red-500 @enderror" placeholder="نام کمپین اینفلوئنسر">
                    @error('campaign_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700 mb-2">پلتفرم *</label>
                    <select name="platform" id="platform" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('platform') border-red-500 @enderror">
                        <option value="">انتخاب پلتفرم</option>
                        <option value="instagram" {{ old('platform') == 'instagram' ? 'selected' : '' }}>اینستاگرام</option>
                        <option value="youtube" {{ old('platform') == 'youtube' ? 'selected' : '' }}>یوتیوب</option>
                        <option value="tiktok" {{ old('platform') == 'tiktok' ? 'selected' : '' }}>تیک‌تاک</option>
                        <option value="twitter" {{ old('platform') == 'twitter' ? 'selected' : '' }}>توییتر</option>
                        <option value="facebook" {{ old('platform') == 'facebook' ? 'selected' : '' }}>فیس‌بوک</option>
                        <option value="linkedin" {{ old('platform') == 'linkedin' ? 'selected' : '' }}>لینکدین</option>
                        <option value="other" {{ old('platform') == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('platform')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Platform Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="platform_username" class="block text-sm font-medium text-gray-700 mb-2">نام کاربری پلتفرم *</label>
                    <input type="text" name="platform_username" id="platform_username" value="{{ old('platform_username') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('platform_username') border-red-500 @enderror" placeholder="@username">
                    @error('platform_username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="platform_url" class="block text-sm font-medium text-gray-700 mb-2">لینک پلتفرم</label>
                    <input type="url" name="platform_url" id="platform_url" value="{{ old('platform_url') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('platform_url') border-red-500 @enderror" placeholder="https://instagram.com/username">
                    @error('platform_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="follower_count" class="block text-sm font-medium text-gray-700 mb-2">تعداد فالوور *</label>
                    <input type="number" name="follower_count" id="follower_count" value="{{ old('follower_count') }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('follower_count') border-red-500 @enderror" placeholder="10000">
                    @error('follower_count')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="engagement_rate" class="block text-sm font-medium text-gray-700 mb-2">نرخ درگیری (%) *</label>
                    <input type="number" name="engagement_rate" id="engagement_rate" value="{{ old('engagement_rate') }}" min="0" max="100" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('engagement_rate') border-red-500 @enderror" placeholder="3.5">
                    @error('engagement_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Content Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="content_type" class="block text-sm font-medium text-gray-700 mb-2">نوع محتوا *</label>
                    <select name="content_type" id="content_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('content_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع محتوا</option>
                        <option value="story" {{ old('content_type') == 'story' ? 'selected' : '' }}>استوری</option>
                        <option value="post" {{ old('content_type') == 'post' ? 'selected' : '' }}>پست</option>
                        <option value="video" {{ old('content_type') == 'video' ? 'selected' : '' }}>ویدیو</option>
                        <option value="live" {{ old('content_type') == 'live' ? 'selected' : '' }}>لایو</option>
                        <option value="reel" {{ old('content_type') == 'reel' ? 'selected' : '' }}>ریل</option>
                        <option value="other" {{ old('content_type') == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('content_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">نرخ کمیسیون (%) *</label>
                    <input type="number" name="commission_rate" id="commission_rate" value="{{ old('commission_rate') }}" min="0" max="100" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('commission_rate') border-red-500 @enderror" placeholder="10">
                    @error('commission_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Target Audience -->
            <div>
                <label for="target_audience" class="block text-sm font-medium text-gray-700 mb-2">مخاطب هدف *</label>
                <textarea name="target_audience" id="target_audience" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('target_audience') border-red-500 @enderror" placeholder="توضیح مخاطب هدف کمپین...">{{ old('target_audience') }}</textarea>
                @error('target_audience')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status and Expiry -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>منقضی</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انقضا</label>
                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Verification Documents -->
            <div>
                <label for="verification_documents" class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید</label>
                <input type="file" name="verification_documents[]" id="verification_documents" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('verification_documents') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500">فایل‌های PDF، JPG، JPEG یا PNG (حداکثر 10MB هر فایل)</p>
                @error('verification_documents')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.influencers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    ایجاد کمپین
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/admin/user-search-manager.js') }}"></script>
@endpush
@endsection
