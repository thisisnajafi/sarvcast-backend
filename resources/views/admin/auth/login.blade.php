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

            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.auth.login.post') }}" class="space-y-6">
                @csrf
                
                <!-- Phone Number -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                        شماره تلفن
                    </label>
                    <input 
                        type="tel" 
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

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        رمز عبور
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password') border-red-500 @enderror"
                        placeholder="رمز عبور خود را وارد کنید"
                        required
                    >
                    @error('password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-primary focus:ring-offset-2 transition duration-200 font-medium"
                >
                    ورود به پنل مدیریت
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    پلتفرم داستان‌های صوتی کودکان
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript for phone number formatting -->
    <script>
        document.getElementById('phone_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            
            if (value.length > 0 && !value.startsWith('9')) {
                value = '9' + value;
            }
            
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>
