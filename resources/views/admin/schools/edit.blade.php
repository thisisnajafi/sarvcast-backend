@extends('admin.layouts.app')

@section('title', 'ویرایش مشارکت مدرسه')
@section('page-title', 'ویرایش مشارکت مدرسه')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ویرایش مشارکت مدرسه</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات مشارکت مدرسه را ویرایش کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.schools.update', $school) }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">کاربر *</label>
                <div id="school-user-search" data-user-search='{"placeholder": "جستجو بر اساس شماره موبایل...", "apiEndpoint": "/admin/users/search"}'></div>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- School Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="school_name" class="block text-sm font-medium text-gray-700 mb-2">نام مدرسه *</label>
                    <input type="text" name="school_name" id="school_name" value="{{ old('school_name', $school->school_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_name') border-red-500 @enderror" placeholder="نام مدرسه">
                    @error('school_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_type" class="block text-sm font-medium text-gray-700 mb-2">نوع مدرسه *</label>
                    <select name="school_type" id="school_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع مدرسه</option>
                        <option value="public_school" {{ old('school_type', $school->school_type) == 'public_school' ? 'selected' : '' }}>مدرسه دولتی</option>
                        <option value="private_school" {{ old('school_type', $school->school_type) == 'private_school' ? 'selected' : '' }}>مدرسه خصوصی</option>
                        <option value="international_school" {{ old('school_type', $school->school_type) == 'international_school' ? 'selected' : '' }}>مدرسه بین‌المللی</option>
                        <option value="charter_school" {{ old('school_type', $school->school_type) == 'charter_school' ? 'selected' : '' }}>مدرسه چارتر</option>
                        <option value="homeschool_coop" {{ old('school_type', $school->school_type) == 'homeschool_coop' ? 'selected' : '' }}>تعاونی خانه‌آموزی</option>
                        <option value="other" {{ old('school_type', $school->school_type) == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('school_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- School Address -->
            <div>
                <label for="school_address" class="block text-sm font-medium text-gray-700 mb-2">آدرس مدرسه *</label>
                <textarea name="school_address" id="school_address" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_address') border-red-500 @enderror" placeholder="آدرس کامل مدرسه">{{ old('school_address', $school->school_address) }}</textarea>
                @error('school_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Location Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="school_city" class="block text-sm font-medium text-gray-700 mb-2">شهر *</label>
                    <input type="text" name="school_city" id="school_city" value="{{ old('school_city', $school->school_city) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_city') border-red-500 @enderror" placeholder="شهر">
                    @error('school_city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_state" class="block text-sm font-medium text-gray-700 mb-2">استان *</label>
                    <input type="text" name="school_state" id="school_state" value="{{ old('school_state', $school->school_state) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_state') border-red-500 @enderror" placeholder="استان">
                    @error('school_state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_country" class="block text-sm font-medium text-gray-700 mb-2">کشور *</label>
                    <input type="text" name="school_country" id="school_country" value="{{ old('school_country', $school->school_country) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_country') border-red-500 @enderror" placeholder="کشور">
                    @error('school_country')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- School Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="school_phone" class="block text-sm font-medium text-gray-700 mb-2">تلفن مدرسه</label>
                    <input type="tel" name="school_phone" id="school_phone" value="{{ old('school_phone', $school->school_phone) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_phone') border-red-500 @enderror" placeholder="021-12345678">
                    @error('school_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل مدرسه</label>
                    <input type="email" name="school_email" id="school_email" value="{{ old('school_email', $school->school_email) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_email') border-red-500 @enderror" placeholder="info@school.com">
                    @error('school_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_website" class="block text-sm font-medium text-gray-700 mb-2">وب‌سایت مدرسه</label>
                    <input type="url" name="school_website" id="school_website" value="{{ old('school_website', $school->school_website) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('school_website') border-red-500 @enderror" placeholder="https://www.school.com">
                    @error('school_website')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Principal Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="principal_name" class="block text-sm font-medium text-gray-700 mb-2">نام مدیر *</label>
                    <input type="text" name="principal_name" id="principal_name" value="{{ old('principal_name', $school->principal_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('principal_name') border-red-500 @enderror" placeholder="نام مدیر مدرسه">
                    @error('principal_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="principal_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل مدیر *</label>
                    <input type="email" name="principal_email" id="principal_email" value="{{ old('principal_email', $school->principal_email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('principal_email') border-red-500 @enderror" placeholder="principal@school.com">
                    @error('principal_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="principal_phone" class="block text-sm font-medium text-gray-700 mb-2">تلفن مدیر</label>
                    <input type="tel" name="principal_phone" id="principal_phone" value="{{ old('principal_phone', $school->principal_phone) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('principal_phone') border-red-500 @enderror" placeholder="09123456789">
                    @error('principal_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- School Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="student_count" class="block text-sm font-medium text-gray-700 mb-2">تعداد دانش‌آموزان *</label>
                    <input type="number" name="student_count" id="student_count" value="{{ old('student_count', $school->student_count) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('student_count') border-red-500 @enderror" placeholder="500">
                    @error('student_count')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="teacher_count" class="block text-sm font-medium text-gray-700 mb-2">تعداد معلمان *</label>
                    <input type="number" name="teacher_count" id="teacher_count" value="{{ old('teacher_count', $school->teacher_count) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('teacher_count') border-red-500 @enderror" placeholder="25">
                    @error('teacher_count')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Educational Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="grade_levels" class="block text-sm font-medium text-gray-700 mb-2">سطوح تحصیلی *</label>
                    <input type="text" name="grade_levels" id="grade_levels" value="{{ old('grade_levels', $school->grade_levels) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('grade_levels') border-red-500 @enderror" placeholder="مثال: ابتدایی، راهنمایی، دبیرستان">
                    @error('grade_levels')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="curriculum_type" class="block text-sm font-medium text-gray-700 mb-2">نوع برنامه درسی *</label>
                    <input type="text" name="curriculum_type" id="curriculum_type" value="{{ old('curriculum_type', $school->curriculum_type) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('curriculum_type') border-red-500 @enderror" placeholder="مثال: ملی، بین‌المللی، ترکیبی">
                    @error('curriculum_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Partnership Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="partnership_type" class="block text-sm font-medium text-gray-700 mb-2">نوع مشارکت *</label>
                    <select name="partnership_type" id="partnership_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('partnership_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع مشارکت</option>
                        <option value="full_access" {{ old('partnership_type', $school->partnership_type) == 'full_access' ? 'selected' : '' }}>دسترسی کامل</option>
                        <option value="limited_access" {{ old('partnership_type', $school->partnership_type) == 'limited_access' ? 'selected' : '' }}>دسترسی محدود</option>
                        <option value="trial_access" {{ old('partnership_type', $school->partnership_type) == 'trial_access' ? 'selected' : '' }}>دسترسی آزمایشی</option>
                    </select>
                    @error('partnership_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discount_rate" class="block text-sm font-medium text-gray-700 mb-2">نرخ تخفیف (%) *</label>
                    <input type="number" name="discount_rate" id="discount_rate" value="{{ old('discount_rate', $school->discount_rate) }}" min="0" max="100" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('discount_rate') border-red-500 @enderror" placeholder="15">
                    @error('discount_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="pending" {{ old('status', $school->status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="active" {{ old('status', $school->status) == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="suspended" {{ old('status', $school->status) == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="expired" {{ old('status', $school->status) == 'expired' ? 'selected' : '' }}>منقضی</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Expiry Date -->
            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انقضا</label>
                <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $school->expires_at?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Current Verification Documents -->
            @if($school->verification_documents && count($school->verification_documents) > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید فعلی</label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($school->verification_documents as $index => $document)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">سند {{ $index + 1 }}</p>
                                    <p class="text-xs text-gray-500">{{ basename($document) }}</p>
                                </div>
                            </div>
                            <a href="{{ Storage::url($document) }}" target="_blank" class="text-green-600 hover:text-green-800 text-sm">مشاهده</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- New Verification Documents -->
            <div>
                <label for="verification_documents" class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید جدید</label>
                <input type="file" name="verification_documents[]" id="verification_documents" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('verification_documents') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500">فایل‌های PDF، JPG، JPEG یا PNG (حداکثر 10MB هر فایل)</p>
                @error('verification_documents')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.schools.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    به‌روزرسانی مشارکت مدرسه
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/admin/user-search-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set the selected user if editing
    @if($school->user)
        const userSearchManager = new UserSearchManager('school-user-search', {
            placeholder: 'جستجو بر اساس شماره موبایل...',
            apiEndpoint: '/admin/users/search'
        });

        // Set the selected user
        userSearchManager.setSelectedUser({
            id: {{ $school->user->id }},
            first_name: '{{ $school->user->first_name }}',
            last_name: '{{ $school->user->last_name }}',
            email: '{{ $school->user->email }}',
            phone_number: '{{ $school->user->phone_number }}'
        });
    @endif
});
</script>
@endpush
@endsection
