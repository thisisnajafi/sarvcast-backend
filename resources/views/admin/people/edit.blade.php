@extends('admin.layouts.app')

@section('title', 'ویرایش فرد')
@section('page-title', 'ویرایش فرد')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">ویرایش فرد: {{ $person->name }}</h1>
        <a href="{{ route('admin.people.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
            بازگشت
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <form action="{{ route('admin.people.update', $person) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            نام <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $person->name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror"
                               placeholder="نام فرد را وارد کنید" required>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">بیوگرافی</label>
                        <textarea name="bio" id="bio" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('bio') border-red-500 @enderror"
                                  placeholder="بیوگرافی فرد را وارد کنید">{{ old('bio', $person->bio) }}</textarea>
                        @error('bio')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            نقش‌ها <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($roles as $role)
                            <div class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $role }}" 
                                       id="role_{{ $role }}"
                                       class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                       {{ in_array($role, old('roles', $person->roles)) ? 'checked' : '' }}>
                                <label for="role_{{ $role }}" class="mr-2 text-sm font-medium text-gray-900 dark:text-gray-300">
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

                    <div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_verified" value="1" 
                                   id="is_verified"
                                   class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                   {{ old('is_verified', $person->is_verified) ? 'checked' : '' }}>
                            <label for="is_verified" class="mr-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                تأیید شده
                            </label>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                            افراد تأیید شده در نتایج جستجو اولویت دارند
                        </p>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="space-y-6">
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تصویر</label>
                        <input type="file" name="image" id="image" accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('image') border-red-500 @enderror">
                        @error('image')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                            فرمت‌های مجاز: JPEG, PNG, JPG, WebP - حداکثر 2 مگابایت
                        </p>
                        
                        @if($person->image_url)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تصویر فعلی:</label>
                                <div class="relative inline-block">
                                    <img src="{{ $person->image_url }}" alt="{{ $person->name }}" 
                                         class="w-32 h-32 object-cover rounded-lg shadow-sm">
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Statistics -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">آمار فرد</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $person->total_stories }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">تعداد داستان‌ها</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $person->total_episodes }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">تعداد قسمت‌ها</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($person->average_rating, 1) }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">امتیاز متوسط</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">@jalali($person->created_at, 'Y/m/d')</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">تاریخ ایجاد</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 space-x-reverse mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.people.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                    بازگشت
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    به‌روزرسانی
                </button>
            </div>
        </form>
    </div>
</div>
@endsection