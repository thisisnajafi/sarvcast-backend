@extends('admin.layouts.app')

@section('title', 'افزودن فرد جدید')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">افزودن فرد جدید</h1>
        <a href="{{ route('admin.people.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
            بازگشت به لیست
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('admin.people.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="نام فرد را وارد کنید" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Image -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">تصویر</label>
                    <input type="file" name="image" id="image" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">فرمت‌های مجاز: JPEG, PNG, JPG, WebP - حداکثر 2 مگابایت</p>
                </div>
            </div>

            <!-- Bio -->
            <div class="mb-6">
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">بیوگرافی</label>
                <textarea name="bio" id="bio" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('bio') border-red-500 @enderror"
                          placeholder="بیوگرافی فرد را وارد کنید">{{ old('bio') }}</textarea>
                @error('bio')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Roles and Verification -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Roles -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">نقش‌ها <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($roles as $role)
                        <div class="flex items-center">
                            <input type="checkbox" name="roles[]" value="{{ $role }}" 
                                   id="role_{{ $role }}"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   {{ in_array($role, old('roles', [])) ? 'checked' : '' }}>
                            <label for="role_{{ $role }}" class="mr-2 text-sm text-gray-700">
                                @switch($role)
                                    @case('voice_actor') صداپیشه @break
                                    @case('director') کارگردان @break
                                    @case('writer') نویسنده @break
                                    @case('producer') تهیه‌کننده @break
                                    @case('author') نویسنده اصلی @break
                                    @case('narrator') گوینده @break
                                    @default {{ $role }}
                                @endswitch
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Verification -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">وضعیت تأیید</label>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_verified" value="1" 
                               id="is_verified"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                               {{ old('is_verified') ? 'checked' : '' }}>
                        <label for="is_verified" class="mr-2 text-sm text-gray-700">
                            تأیید شده
                        </label>
                    </div>
                    <p class="text-gray-500 text-sm mt-1">افراد تأیید شده در نتایج جستجو اولویت دارند</p>
                </div>
            </div>
            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.people.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    بازگشت
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
