<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود مدیر - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4A90E2',
                        secondary: '#FF6B6B',
                        accent: '#FFD93D',
                        success: '#6BCF7F',
                        warning: '#FFA726',
                        error: '#EF5350',
                        info: '#26C6DA'
                    },
                    fontFamily: {
                        'iran': ['IranSansWeb', 'IRANSans', 'Tahoma', 'sans-serif'],
                        'sans': ['IranSansWeb', 'IRANSans', 'Tahoma', 'ui-sans-serif', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'IranSansWeb', 'IRANSans', 'Tahoma', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">سروکست</h1>
                <p class="text-gray-600 mt-2">پنل مدیریت</p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('otp_sent'))
                <!-- OTP Verification Form -->
                <form method="POST" action="{{ route('admin.auth.login.post') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="phone_number" value="{{ session('phone_number') ?? old('phone_number') }}">
                    
                    <div class="text-center mb-4">
                        <p class="text-gray-600">کد تایید به شماره <strong>{{ session('phone_number') ?? old('phone_number') }}</strong> ارسال شد</p>
                    </div>

                    <!-- Verification Code -->
                    <div>
                        <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-2">
                            کد تایید
                        </label>
                        <input 
                            type="text" 
                            id="verification_code" 
                            name="verification_code" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-center text-2xl tracking-widest @error('verification_code') border-red-500 @enderror"
                            placeholder="000000"
                            maxlength="6"
                            required
                            autofocus
                            pattern="[0-9]{6}"
                        >
                        @error('verification_code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        @error('phone_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-primary focus:ring-offset-2 transition duration-200 font-medium"
                    >
                        تایید و ورود
                    </button>

                    <!-- Resend OTP Link -->
                    <div class="text-center">
                        <form method="POST" action="{{ route('admin.auth.send-otp') }}" class="inline">
                            @csrf
                            <input type="hidden" name="phone_number" value="{{ session('phone_number') ?? old('phone_number') }}">
                            <button 
                                type="submit" 
                                class="text-primary hover:text-blue-600 text-sm underline"
                            >
                                ارسال مجدد کد
                            </button>
                        </form>
                    </div>

                    <!-- Back to Phone Number -->
                    <div class="text-center">
                        <a href="{{ route('admin.auth.login') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                            تغییر شماره تلفن
                        </a>
                    </div>
                </form>
            @else
                <!-- Phone Number Submission Form -->
                <form method="POST" action="{{ route('admin.auth.send-otp') }}" class="space-y-6">
                    @csrf
                    
                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                            شماره تلفن مدیر
                        </label>
                        <input 
                            type="text" 
                            id="phone_number" 
                            name="phone_number" 
                            value="{{ old('phone_number') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone_number') border-red-500 @enderror"
                            placeholder="09123456789"
                            required
                            autofocus
                        >
                        @error('phone_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-primary focus:ring-offset-2 transition duration-200 font-medium"
                    >
                        ارسال کد تایید
                    </button>
                </form>
            @endif

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    پلتفرم داستان‌های صوتی کودکان
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript for phone number formatting and OTP input -->
    <script>
        // Phone number formatting
        const phoneInput = document.getElementById('phone_number');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
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
        }

        // OTP code input formatting
        const otpInput = document.getElementById('verification_code');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/\D/g, '');
                
                // Limit to 6 digits
                if (e.target.value.length > 6) {
                    e.target.value = e.target.value.substring(0, 6);
                }
            });

            // Auto-submit when 6 digits are entered
            otpInput.addEventListener('input', function(e) {
                if (e.target.value.length === 6) {
                    // Optional: auto-submit after a short delay
                    // setTimeout(() => {
                    //     e.target.form.submit();
                    // }, 500);
                }
            });
        }
    </script>
</body>
</html>
