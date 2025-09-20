@extends('admin.layouts.app')

@section('title', 'افزودن نسخه جدید')
@section('page-title', 'افزودن نسخه جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">افزودن نسخه جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات نسخه جدید اپلیکیشن را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.versions.store') }}" class="p-6 space-y-6">
            @csrf

            <!-- Version Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 mb-2">نسخه *</label>
                    <input type="text" name="version" id="version" value="{{ old('version') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('version') border-red-500 @enderror" placeholder="1.0.0">
                    @error('version')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="build_number" class="block text-sm font-medium text-gray-700 mb-2">شماره بیلد</label>
                    <input type="text" name="build_number" id="build_number" value="{{ old('build_number') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('build_number') border-red-500 @enderror" placeholder="100">
                    @error('build_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Platform and Update Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700 mb-2">پلتفرم *</label>
                    <select name="platform" id="platform" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('platform') border-red-500 @enderror">
                        <option value="">انتخاب پلتفرم</option>
                        <option value="android" {{ old('platform') == 'android' ? 'selected' : '' }}>اندروید</option>
                        <option value="ios" {{ old('platform') == 'ios' ? 'selected' : '' }}>iOS</option>
                        <option value="web" {{ old('platform') == 'web' ? 'selected' : '' }}>وب</option>
                        <option value="all" {{ old('platform') == 'all' ? 'selected' : '' }}>همه پلتفرم‌ها</option>
                    </select>
                    @error('platform')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="update_type" class="block text-sm font-medium text-gray-700 mb-2">نوع به‌روزرسانی *</label>
                    <select name="update_type" id="update_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('update_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع</option>
                        <option value="optional" {{ old('update_type') == 'optional' ? 'selected' : '' }}>اختیاری</option>
                        <option value="forced" {{ old('update_type') == 'forced' ? 'selected' : '' }}>اجباری</option>
                        <option value="maintenance" {{ old('update_type') == 'maintenance' ? 'selected' : '' }}>تعمیرات</option>
                    </select>
                    @error('update_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Title and Description -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان نسخه">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="توضیحات کوتاه در مورد نسخه">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Changelog and Update Notes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="changelog" class="block text-sm font-medium text-gray-700 mb-2">تغییرات</label>
                    <textarea name="changelog" id="changelog" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('changelog') border-red-500 @enderror" placeholder="لیست تغییرات نسخه">{{ old('changelog') }}</textarea>
                    @error('changelog')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="update_notes" class="block text-sm font-medium text-gray-700 mb-2">یادداشت‌های به‌روزرسانی</label>
                    <textarea name="update_notes" id="update_notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('update_notes') border-red-500 @enderror" placeholder="یادداشت‌های مهم برای کاربران">{{ old('update_notes') }}</textarea>
                    @error('update_notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Download URL and OS Version -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="download_url" class="block text-sm font-medium text-gray-700 mb-2">لینک دانلود</label>
                    <input type="url" name="download_url" id="download_url" value="{{ old('download_url') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('download_url') border-red-500 @enderror" placeholder="https://example.com/download">
                    @error('download_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="minimum_os_version" class="block text-sm font-medium text-gray-700 mb-2">حداقل نسخه سیستم عامل</label>
                    <input type="text" name="minimum_os_version" id="minimum_os_version" value="{{ old('minimum_os_version') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('minimum_os_version') border-red-500 @enderror" placeholder="Android 7.0, iOS 12.0">
                    @error('minimum_os_version')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انتشار</label>
                    <input type="date" name="release_date" id="release_date" value="{{ old('release_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('release_date') border-red-500 @enderror">
                    @error('release_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="force_update_date" class="block text-sm font-medium text-gray-700 mb-2">تاریخ اجباری شدن به‌روزرسانی</label>
                    <input type="date" name="force_update_date" id="force_update_date" value="{{ old('force_update_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('force_update_date') border-red-500 @enderror">
                    @error('force_update_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Priority and Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">اولویت (0-100)</label>
                    <input type="number" name="priority" id="priority" value="{{ old('priority', 50) }}" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('priority') border-red-500 @enderror">
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="mr-2 block text-sm text-gray-900">فعال</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_latest" id="is_latest" value="1" {{ old('is_latest') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_latest" class="mr-2 block text-sm text-gray-900">آخرین نسخه</label>
                    </div>
                </div>
            </div>

            <!-- Compatibility -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">سازگاری</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="compatibility[]" id="compatibility_android" value="android" {{ in_array('android', old('compatibility', [])) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="compatibility_android" class="mr-2 block text-sm text-gray-900">اندروید</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="compatibility[]" id="compatibility_ios" value="ios" {{ in_array('ios', old('compatibility', [])) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="compatibility_ios" class="mr-2 block text-sm text-gray-900">iOS</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="compatibility[]" id="compatibility_web" value="web" {{ in_array('web', old('compatibility', [])) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="compatibility_web" class="mr-2 block text-sm text-gray-900">وب</label>
                    </div>
                </div>
                @error('compatibility')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.versions.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    ایجاد نسخه
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
