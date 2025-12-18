@extends('admin.layouts.app')

@section('title', 'پروفایل کاربری')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">پروفایل کاربری</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center ml-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">اطلاعات شخصی</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">اطلاعات حساب کاربری شما</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.profile.info.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نام</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('first_name') border-red-500 @enderror"
                                   placeholder="نام خود را وارد کنید" required>
                            @error('first_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نام خانوادگی</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('last_name') border-red-500 @enderror"
                                   placeholder="نام خانوادگی خود را وارد کنید" required>
                            @error('last_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ایمیل</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('email') border-red-500 @enderror"
                               placeholder="ایمیل خود را وارد کنید" required>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">شماره تلفن</label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('phone_number') border-red-500 @enderror"
                               placeholder="شماره تلفن خود را وارد کنید" required>
                        @error('phone_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                            به‌روزرسانی اطلاعات
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Password Change -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center ml-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">تغییر رمز عبور</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">رمز عبور خود را تغییر دهید</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.profile.password.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">رمز عبور فعلی</label>
                        <input type="password" name="current_password" id="current_password" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('current_password') border-red-500 @enderror"
                               placeholder="رمز عبور فعلی خود را وارد کنید" required>
                        @error('current_password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">رمز عبور جدید</label>
                        <input type="password" name="password" id="password" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password') border-red-500 @enderror"
                               placeholder="رمز عبور جدید خود را وارد کنید" required>
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                            رمز عبور باید حداقل 8 کاراکتر و شامل حروف بزرگ، کوچک، اعداد و نمادهای خاص باشد.
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تأیید رمز عبور جدید</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password_confirmation') border-red-500 @enderror"
                               placeholder="رمز عبور جدید را دوباره وارد کنید" required>
                        @error('password_confirmation')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                            تغییر رمز عبور
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Information -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center ml-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">اطلاعات حساب</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">جزئیات حساب کاربری شما</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">نقش کاربری</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ $user->role }}</div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">وضعیت حساب</div>
                <div class="text-lg font-semibold text-green-600 dark:text-green-400 capitalize">{{ $user->status }}</div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">تاریخ عضویت</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">@jalali($user->created_at, 'Y/m/d')</div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">آخرین ورود</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $user->last_login_at ? \App\Helpers\JalaliHelper::formatForDisplay($user->last_login_at, 'Y/m/d H:i') : 'هرگز' }}
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">شناسه کاربری</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">#{{ $user->id }}</div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for phone number formatting -->
<script>
    document.getElementById('phone_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Allow 11-digit Iranian phone numbers starting with 0
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        
        // Ensure it starts with 0 for Iranian numbers
        if (value.length > 0 && !value.startsWith('0')) {
            value = '0' + value;
        }
        
        e.target.value = value;
    });
</script>
@endsection
