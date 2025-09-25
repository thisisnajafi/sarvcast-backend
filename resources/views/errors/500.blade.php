<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطای سرور - سروکست</title>
    <meta name="description" content="خطای داخلی سرور. تیم فنی ما در حال بررسی و رفع مشکل است.">
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .shake {
            animation: shake 0.5s ease-in-out infinite;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <!-- Error Code -->
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-pink-600 dark:from-red-400 dark:to-pink-400">
                    500
                </h1>
            </div>

            <!-- Error Message -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    خطای داخلی سرور
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 max-w-2xl mx-auto">
                    متأسفانه خطایی در سرور رخ داده است. تیم فنی ما در حال بررسی و رفع مشکل است.
                </p>
            </div>

            <!-- Illustration -->
            <div class="mb-12 flex justify-center">
                <div class="relative">
                    <div class="w-64 h-64 bg-gradient-to-br from-red-100 to-pink-100 dark:from-red-900 dark:to-pink-900 rounded-full flex items-center justify-center floating">
                        <svg class="w-32 h-32 text-red-600 dark:text-red-400 shake" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <!-- Floating elements -->
                    <div class="absolute -top-4 -right-4 w-8 h-8 bg-red-400 rounded-full pulse-slow"></div>
                    <div class="absolute -bottom-4 -left-4 w-6 h-6 bg-orange-400 rounded-full pulse-slow" style="animation-delay: 1s;"></div>
                    <div class="absolute top-1/2 -left-8 w-4 h-4 bg-yellow-400 rounded-full pulse-slow" style="animation-delay: 2s;"></div>
                </div>
            </div>

            <!-- Status Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-200 dark:border-gray-700 mb-8">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center ml-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">وضعیت سیستم</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 rounded-xl bg-red-50 dark:bg-red-900">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">خطا</div>
                        <div class="text-sm text-red-700 dark:text-red-300">سرور</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-yellow-50 dark:bg-yellow-900">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">در حال بررسی</div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-300">تیم فنی</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-green-50 dark:bg-green-900">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">فعال</div>
                        <div class="text-sm text-green-700 dark:text-green-300">پشتیبانی</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                <button onclick="location.reload()" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    تلاش مجدد
                </button>
                
                <a href="{{ url('/') }}" class="inline-flex items-center px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-600 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    صفحه اصلی
                </button>
            </div>

            <!-- Helpful Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">چه کاری می‌توانید انجام دهید؟</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="text-right">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">اقدامات فوری</h4>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                چند دقیقه صبر کنید و دوباره تلاش کنید
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                صفحه را رفرش کنید
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                اتصال اینترنت خود را بررسی کنید
                            </li>
                        </ul>
                    </div>
                    <div class="text-right">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">در صورت ادامه مشکل</h4>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                با تیم پشتیبانی تماس بگیرید
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                مشکل را گزارش دهید
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                بعداً دوباره امتحان کنید
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center">
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    © {{ date('Y') }} سروکست. تمامی حقوق محفوظ است.
                </p>
                <p class="text-gray-400 dark:text-gray-500 text-xs mt-2">
                    خطای شناسایی شده: {{ date('Y-m-d H:i:s') }}
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

        // Auto retry after 30 seconds
        setTimeout(function() {
            if (confirm('آیا می‌خواهید دوباره تلاش کنید؟')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
