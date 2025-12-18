@extends('admin.layouts.app')

@section('title', 'ویرایش معلم')
@section('page-title', 'ویرایش معلم')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ویرایش معلم</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات معلم را ویرایش کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">کاربر *</label>
                <div id="teacher-user-search" data-user-search='{"placeholder": "جستجو بر اساس شماره موبایل...", "apiEndpoint": "/admin/users/search"}'></div>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Institution Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-2">نام مؤسسه *</label>
                    <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $teacher->institution_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('institution_name') border-red-500 @enderror" placeholder="نام مؤسسه آموزشی">
                    @error('institution_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="institution_type" class="block text-sm font-medium text-gray-700 mb-2">نوع مؤسسه *</label>
                    <select name="institution_type" id="institution_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('institution_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع مؤسسه</option>
                        <option value="public_school" {{ old('institution_type', $teacher->institution_type) == 'public_school' ? 'selected' : '' }}>مدرسه دولتی</option>
                        <option value="private_school" {{ old('institution_type', $teacher->institution_type) == 'private_school' ? 'selected' : '' }}>مدرسه خصوصی</option>
                        <option value="university" {{ old('institution_type', $teacher->institution_type) == 'university' ? 'selected' : '' }}>دانشگاه</option>
                        <option value="college" {{ old('institution_type', $teacher->institution_type) == 'college' ? 'selected' : '' }}>کالج</option>
                        <option value="homeschool" {{ old('institution_type', $teacher->institution_type) == 'homeschool' ? 'selected' : '' }}>خانه‌آموزی</option>
                        <option value="other" {{ old('institution_type', $teacher->institution_type) == 'other' ? 'selected' : '' }}>سایر</option>
                    </select>
                    @error('institution_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Teaching Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="teaching_subject" class="block text-sm font-medium text-gray-700 mb-2">رشته تدریس *</label>
                    <input type="text" name="teaching_subject" id="teaching_subject" value="{{ old('teaching_subject', $teacher->teaching_subject) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('teaching_subject') border-red-500 @enderror" placeholder="مثال: ریاضی، علوم، ادبیات">
                    @error('teaching_subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="years_of_experience" class="block text-sm font-medium text-gray-700 mb-2">سال‌های تجربه *</label>
                    <input type="number" name="years_of_experience" id="years_of_experience" value="{{ old('years_of_experience', $teacher->years_of_experience) }}" min="0" max="50" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('years_of_experience') border-red-500 @enderror" placeholder="0">
                    @error('years_of_experience')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Certification Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="certification_number" class="block text-sm font-medium text-gray-700 mb-2">شماره گواهینامه</label>
                    <input type="text" name="certification_number" id="certification_number" value="{{ old('certification_number', $teacher->certification_number) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('certification_number') border-red-500 @enderror" placeholder="شماره گواهینامه">
                    @error('certification_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="certification_authority" class="block text-sm font-medium text-gray-700 mb-2">مرجع صدور</label>
                    <input type="text" name="certification_authority" id="certification_authority" value="{{ old('certification_authority', $teacher->certification_authority) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('certification_authority') border-red-500 @enderror" placeholder="مرجع صدور گواهینامه">
                    @error('certification_authority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="certification_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ صدور</label>
                    <input type="date" name="certification_date" id="certification_date" value="{{ old('certification_date', $teacher->certification_date?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('certification_date') border-red-500 @enderror">
                    @error('certification_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Student Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="student_count" class="block text-sm font-medium text-gray-700 mb-2">تعداد دانش‌آموزان فعلی *</label>
                    <input type="number" name="student_count" id="student_count" value="{{ old('student_count', $teacher->student_count) }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('student_count') border-red-500 @enderror" placeholder="0">
                    @error('student_count')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_student_licenses" class="block text-sm font-medium text-gray-700 mb-2">حداکثر مجوز دانش‌آموز *</label>
                    <input type="number" name="max_student_licenses" id="max_student_licenses" value="{{ old('max_student_licenses', $teacher->max_student_licenses) }}" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('max_student_licenses') border-red-500 @enderror" placeholder="1">
                    @error('max_student_licenses')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Discount and Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="discount_rate" class="block text-sm font-medium text-gray-700 mb-2">نرخ تخفیف (%) *</label>
                    <input type="number" name="discount_rate" id="discount_rate" value="{{ old('discount_rate', $teacher->discount_rate) }}" min="0" max="100" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('discount_rate') border-red-500 @enderror" placeholder="0">
                    @error('discount_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="pending" {{ old('status', $teacher->status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="active" {{ old('status', $teacher->status) == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="suspended" {{ old('status', $teacher->status) == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="expired" {{ old('status', $teacher->status) == 'expired' ? 'selected' : '' }}>منقضی</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انقضا</label>
                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $teacher->expires_at?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Verification Documents -->
            @if($teacher->verification_documents && count($teacher->verification_documents) > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید فعلی</label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($teacher->verification_documents as $index => $document)
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
                            <a href="{{ Storage::url($document) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">مشاهده</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- New Verification Documents -->
            <div>
                <label for="verification_documents" class="block text-sm font-medium text-gray-700 mb-2">اسناد تأیید جدید</label>
                <input type="file" name="verification_documents[]" id="verification_documents" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('verification_documents') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500">فایل‌های PDF، JPG، JPEG یا PNG (حداکثر 10MB هر فایل)</p>
                @error('verification_documents')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.teachers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    به‌روزرسانی معلم
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
    @if($teacher->user)
        const userSearchManager = new UserSearchManager('teacher-user-search', {
            placeholder: 'جستجو کاربر برای معلم...',
            apiEndpoint: '/admin/users/search'
        });
        
        // Set the selected user
        userSearchManager.setSelectedUser({
            id: {{ $teacher->user->id }},
            first_name: '{{ $teacher->user->first_name }}',
            last_name: '{{ $teacher->user->last_name }}',
            email: '{{ $teacher->user->email }}',
            phone: '{{ $teacher->user->phone }}'
        });
    @endif
});
</script>
@endpush
@endsection
