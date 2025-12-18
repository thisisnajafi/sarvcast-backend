@extends('admin.layouts.app')

@section('title', 'افزودن کاربر جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">افزودن کاربر جدید</h1>
        <p class="text-gray-600 mt-2">اطلاعات کاربر جدید را وارد کنید</p>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات شخصی</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">نام *</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('first_name') border-red-500 @enderror" placeholder="نام را وارد کنید">
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">نام خانوادگی *</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('last_name') border-red-500 @enderror" placeholder="نام خانوادگی را وارد کنید">
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل *</label>
                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone_number') border-red-500 @enderror" placeholder="شماره موبایل را وارد کنید">
                    @error('phone_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email (Optional) -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل (اختیاری)</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('email') border-red-500 @enderror" placeholder="ایمیل را وارد کنید">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات حساب کاربری</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور *</label>
                    <input type="password" name="password" id="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password') border-red-500 @enderror" placeholder="رمز عبور را وارد کنید">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تأیید رمز عبور *</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password_confirmation') border-red-500 @enderror" placeholder="رمز عبور را مجدداً وارد کنید">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">نقش *</label>
                    <select name="role" id="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('role') border-red-500 @enderror">
                        <option value="">انتخاب نقش</option>
                        <option value="basic" {{ old('role', 'basic') == 'basic' ? 'selected' : '' }}>کاربر عادی</option>
                        <option value="parent" {{ old('role') == 'parent' ? 'selected' : '' }}>والد</option>
                        <option value="child" {{ old('role') == 'child' ? 'selected' : '' }}>کودک</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>مدیر</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات اضافی</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Parent Selection (for child users) -->
                <div id="parent-selection" style="display: none;">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">والد</label>
                    <select name="parent_id" id="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('parent_id') border-red-500 @enderror">
                        <option value="">انتخاب والد</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->first_name }} {{ $parent->last_name }} ({{ $parent->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Language -->
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">زبان</label>
                    <select name="language" id="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('language') border-red-500 @enderror">
                        <option value="fa" {{ old('language', 'fa') == 'fa' ? 'selected' : '' }}>فارسی</option>
                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                    </select>
                    @error('language')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">منطقه زمانی</label>
                    <select name="timezone" id="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('timezone') border-red-500 @enderror">
                        <option value="Asia/Tehran" {{ old('timezone', 'Asia/Tehran') == 'Asia/Tehran' ? 'selected' : '' }}>Asia/Tehran</option>
                        <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC</option>
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 space-x-reverse">
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                انصراف
            </a>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                ایجاد کاربر
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    const parentSelection = document.getElementById('parent-selection');
    if (this.value === 'child') {
        parentSelection.style.display = 'block';
        document.getElementById('parent_id').required = true;
    } else {
        parentSelection.style.display = 'none';
        document.getElementById('parent_id').required = false;
    }
});

// Trigger on page load if role is already selected
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    if (roleSelect.value === 'child') {
        document.getElementById('parent-selection').style.display = 'block';
        document.getElementById('parent_id').required = true;
    }
});
</script>
@endsection
