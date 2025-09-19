@extends('admin.layouts.app')

@section('title', 'افزودن حمایت شرکتی جدید')
@section('page-title', 'افزودن حمایت شرکتی جدید')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">افزودن حمایت شرکتی جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات حمایت شرکتی جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.corporate.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">کاربر *</label>
                <select name="user_id" id="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('user_id') border-red-500 @enderror">
                    <option value="">انتخاب کاربر</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->first_name }} {{ $user->last_name }} - {{ $user->email }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Company Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">نام شرکت *</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_name') border-red-500 @enderror" placeholder="نام شرکت">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_type" class="block text-sm font-medium text-gray-700 mb-2">نوع شرکت *</label>
                    <select name="company_type" id="company_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع شرکت</option>
                        <option value="startup" {{ old('company_type') == 'startup' ? 'selected' : '' }}>استارتاپ</option>
                        <option value="small_business" {{ old('company_type') == 'small_business' ? 'selected' : '' }}>کسب‌وکار کوچک</option>
                        <option value="medium_business" {{ old('company_type') == 'medium_business' ? 'selected' : '' }}>کسب‌وکار متوسط</option>
                        <option value="large_corporation" {{ old('company_type') == 'large_corporation' ? 'selected' : '' }}>شرکت بزرگ</option>
                        <option value="non_profit" {{ old('company_type') == 'non_profit' ? 'selected' : '' }}>غیرانتفاعی</option>
                        <option value="government" {{ old('company_type') == 'government' ? 'selected' : '' }}>دولتی</option>
                        <option value="other" {{ old('company_type') == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('company_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Company Address -->
            <div>
                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2">آدرس شرکت *</label>
                <textarea name="company_address" id="company_address" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_address') border-red-500 @enderror" placeholder="آدرس کامل شرکت">{{ old('company_address') }}</textarea>
                @error('company_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Location Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="company_city" class="block text-sm font-medium text-gray-700 mb-2">شهر *</label>
                    <input type="text" name="company_city" id="company_city" value="{{ old('company_city') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_city') border-red-500 @enderror" placeholder="شهر">
                    @error('company_city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_state" class="block text-sm font-medium text-gray-700 mb-2">استان *</label>
                    <input type="text" name="company_state" id="company_state" value="{{ old('company_state') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_state') border-red-500 @enderror" placeholder="استان">
                    @error('company_state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_country" class="block text-sm font-medium text-gray-700 mb-2">کشور *</label>
                    <input type="text" name="company_country" id="company_country" value="{{ old('company_country') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_country') border-red-500 @enderror" placeholder="کشور">
                    @error('company_country')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Company Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2">تلفن شرکت</label>
                    <input type="tel" name="company_phone" id="company_phone" value="{{ old('company_phone') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_phone') border-red-500 @enderror" placeholder="021-12345678">
                    @error('company_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل شرکت</label>
                    <input type="email" name="company_email" id="company_email" value="{{ old('company_email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_email') border-red-500 @enderror" placeholder="info@company.com">
                    @error('company_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2">وب‌سایت شرکت</label>
                    <input type="url" name="company_website" id="company_website" value="{{ old('company_website') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_website') border-red-500 @enderror" placeholder="https://www.company.com">
                    @error('company_website')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Company Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="company_size" class="block text-sm font-medium text-gray-700 mb-2">اندازه شرکت *</label>
                    <select name="company_size" id="company_size" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('company_size') border-red-500 @enderror">
                        <option value="">انتخاب اندازه شرکت</option>
                        <option value="1-10" {{ old('company_size') == '1-10' ? 'selected' : '' }}>1-10 نفر</option>
                        <option value="11-50" {{ old('company_size') == '11-50' ? 'selected' : '' }}>11-50 نفر</option>
                        <option value="51-200" {{ old('company_size') == '51-200' ? 'selected' : '' }}>51-200 نفر</option>
                        <option value="201-500" {{ old('company_size') == '201-500' ? 'selected' : '' }}>201-500 نفر</option>
                        <option value="501-1000" {{ old('company_size') == '501-1000' ? 'selected' : '' }}>501-1000 نفر</option>
                        <option value="1000+" {{ old('company_size') == '1000+' ? 'selected' : '' }}>بیش از 1000 نفر</option>
                    </select>
                    @error('company_size')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">صنعت *</label>
                    <input type="text" name="industry" id="industry" value="{{ old('industry') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('industry') border-red-500 @enderror" placeholder="مثال: فناوری، آموزش، سلامت">
                    @error('industry')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Contact Person Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="contact_person_name" class="block text-sm font-medium text-gray-700 mb-2">نام شخص تماس *</label>
                    <input type="text" name="contact_person_name" id="contact_person_name" value="{{ old('contact_person_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('contact_person_name') border-red-500 @enderror" placeholder="نام شخص تماس">
                    @error('contact_person_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_person_title" class="block text-sm font-medium text-gray-700 mb-2">سمت شخص تماس *</label>
                    <input type="text" name="contact_person_title" id="contact_person_title" value="{{ old('contact_person_title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('contact_person_title') border-red-500 @enderror" placeholder="مثال: مدیر بازاریابی">
                    @error('contact_person_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_person_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل شخص تماس *</label>
                    <input type="email" name="contact_person_email" id="contact_person_email" value="{{ old('contact_person_email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('contact_person_email') border-red-500 @enderror" placeholder="contact@company.com">
                    @error('contact_person_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Contact Person Phone -->
            <div>
                <label for="contact_person_phone" class="block text-sm font-medium text-gray-700 mb-2">تلفن شخص تماس</label>
                <input type="tel" name="contact_person_phone" id="contact_person_phone" value="{{ old('contact_person_phone') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('contact_person_phone') border-red-500 @enderror" placeholder="09123456789">
                @error('contact_person_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sponsorship Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="sponsorship_type" class="block text-sm font-medium text-gray-700 mb-2">نوع حمایت *</label>
                    <select name="sponsorship_type" id="sponsorship_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('sponsorship_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع حمایت</option>
                        <option value="financial" {{ old('sponsorship_type') == 'financial' ? 'selected' : '' }}>مالی</option>
                        <option value="product" {{ old('sponsorship_type') == 'product' ? 'selected' : '' }}>محصول</option>
                        <option value="service" {{ old('sponsorship_type') == 'service' ? 'selected' : '' }}>خدمات</option>
                        <option value="media" {{ old('sponsorship_type') == 'media' ? 'selected' : '' }}>رسانه</option>
                        <option value="event" {{ old('sponsorship_type') == 'event' ? 'selected' : '' }}>رویداد</option>
                        <option value="educational" {{ old('sponsorship_type') == 'educational' ? 'selected' : '' }}>آموزشی</option>
                        <option value="research" {{ old('sponsorship_type') == 'research' ? 'selected' : '' }}>پژوهشی</option>
                        <option value="other" {{ old('sponsorship_type') == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('sponsorship_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sponsorship_amount" class="block text-sm font-medium text-gray-700 mb-2">مبلغ حمایت *</label>
                    <input type="number" name="sponsorship_amount" id="sponsorship_amount" value="{{ old('sponsorship_amount') }}" min="0" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('sponsorship_amount') border-red-500 @enderror" placeholder="1000000">
                    @error('sponsorship_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('status') border-red-500 @enderror">
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
            </div>

            <!-- Sponsorship Duration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="sponsorship_duration" class="block text-sm font-medium text-gray-700 mb-2">مدت حمایت *</label>
                    <input type="number" name="sponsorship_duration" id="sponsorship_duration" value="{{ old('sponsorship_duration') }}" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('sponsorship_duration') border-red-500 @enderror" placeholder="12">
                    @error('sponsorship_duration')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sponsorship_duration_unit" class="block text-sm font-medium text-gray-700 mb-2">واحد مدت *</label>
                    <select name="sponsorship_duration_unit" id="sponsorship_duration_unit" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('sponsorship_duration_unit') border-red-500 @enderror">
                        <option value="">انتخاب واحد</option>
                        <option value="days" {{ old('sponsorship_duration_unit') == 'days' ? 'selected' : '' }}>روز</option>
                        <option value="weeks" {{ old('sponsorship_duration_unit') == 'weeks' ? 'selected' : '' }}>هفته</option>
                        <option value="months" {{ old('sponsorship_duration_unit') == 'months' ? 'selected' : '' }}>ماه</option>
                        <option value="years" {{ old('sponsorship_duration_unit') == 'years' ? 'selected' : '' }}>سال</option>
                    </select>
                    @error('sponsorship_duration_unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Benefits and Target Audience -->
            <div>
                <label for="benefits_offered" class="block text-sm font-medium text-gray-700 mb-2">مزایای ارائه شده *</label>
                <textarea name="benefits_offered" id="benefits_offered" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('benefits_offered') border-red-500 @enderror" placeholder="توضیح مزایای ارائه شده توسط شرکت...">{{ old('benefits_offered') }}</textarea>
                @error('benefits_offered')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="target_audience" class="block text-sm font-medium text-gray-700 mb-2">مخاطب هدف *</label>
                <textarea name="target_audience" id="target_audience" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('target_audience') border-red-500 @enderror" placeholder="توضیح مخاطب هدف حمایت...">{{ old('target_audience') }}</textarea>
                @error('target_audience')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Marketing Materials -->
            <div>
                <label for="marketing_materials" class="block text-sm font-medium text-gray-700 mb-2">مواد بازاریابی</label>
                <textarea name="marketing_materials" id="marketing_materials" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('marketing_materials') border-red-500 @enderror" placeholder="توضیح مواد بازاریابی ارائه شده...">{{ old('marketing_materials') }}</textarea>
                @error('marketing_materials')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Expiry Date -->
            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انقضا</label>
                <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verification Documents -->
            <div>
                <label for="verification_documents" class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید</label>
                <input type="file" name="verification_documents[]" id="verification_documents" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('verification_documents') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500">فایل‌های PDF، JPG، JPEG یا PNG (حداکثر 10MB هر فایل)</p>
                @error('verification_documents')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.corporate.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                    ایجاد حمایت شرکتی
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
