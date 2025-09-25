<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحه یافت نشد - سروکست</title>
    <meta name="description" content="صفحه مورد نظر شما یافت نشد. لطفاً آدرس را بررسی کنید یا به صفحه اصلی بازگردید.">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'vazir': ['Vazir', 'Tahoma', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .pulse-slow {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <!-- Error Code -->
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400">
                    404
                </h1>
            </div>

            <!-- Error Message -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    صفحه یافت نشد
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 max-w-2xl mx-auto">
                    متأسفانه صفحه‌ای که به دنبال آن هستید وجود ندارد یا ممکن است حذف شده باشد.
                </p>
            </div>

            <!-- Illustration -->
            <div class="mb-12 flex justify-center">
                <div class="relative">
                    <div class="w-64 h-64 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 rounded-full flex items-center justify-center floating">
                        <svg class="w-32 h-32 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <!-- Floating elements -->
                    <div class="absolute -top-4 -right-4 w-8 h-8 bg-yellow-400 rounded-full pulse-slow"></div>
                    <div class="absolute -bottom-4 -left-4 w-6 h-6 bg-pink-400 rounded-full pulse-slow" style="animation-delay: 1s;"></div>
                    <div class="absolute top-1/2 -left-8 w-4 h-4 bg-green-400 rounded-full pulse-slow" style="animation-delay: 2s;"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                <a href="{{ url('/') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    بازگشت به صفحه اصلی
                </a>
                
                <button onclick="history.back()" class="inline-flex items-center px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-600 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    صفحه قبلی
                </button>
            </div>

            <!-- Helpful Links -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">صفحات محبوب</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ url('/') }}" class="flex items-center p-4 rounded-xl bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-800 dark:hover:to-blue-700 transition-all duration-200 group">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center ml-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <h4 class="font-semibold text-gray-900 dark:text-white">صفحه اصلی</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">داشبورد اصلی</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.auth.login') }}" class="flex items-center p-4 rounded-xl bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 hover:from-green-100 hover:to-green-200 dark:hover:from-green-800 dark:hover:to-green-700 transition-all duration-200 group">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center ml-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <h4 class="font-semibold text-gray-900 dark:text-white">ورود</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">ورود به پنل مدیریت</p>
                        </div>
                    </a>
                    
                    <a href="{{ url('/contact') }}" class="flex items-center p-4 rounded-xl bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 hover:from-purple-100 hover:to-purple-200 dark:hover:from-purple-800 dark:hover:to-purple-700 transition-all duration-200 group">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center ml-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <h4 class="font-semibold text-gray-900 dark:text-white">تماس با ما</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">پشتیبانی و راهنمایی</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center">
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    © {{ date('Y') }} سروکست. تمامی حقوق محفوظ است.
                </p>
            </div>
        </div>
    </div>

    <!-- Dark mode toggle -->
    <button onclick="toggleDarkMode()" class="fixed top-4 left-4 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 z-50">
        <svg id="sun-icon" class="w-6 h-6 text-yellow-500 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <svg id="moon-icon" class="w-6 h-6 text-gray-700 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>

    <script>
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
