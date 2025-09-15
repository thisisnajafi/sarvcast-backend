<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت ناموفق - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4A90E2',
                        success: '#6BCF7F',
                        error: '#EF5350'
                    },
                    fontFamily: {
                        'iran': ['IRANSans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-iran">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <!-- Error Icon -->
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
                    <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-900 mb-2">پرداخت ناموفق!</h2>
                <p class="text-lg text-gray-600 mb-8">متأسفانه پرداخت شما انجام نشد</p>
                
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                        <p class="text-red-800">{{ session('error') }}</p>
                    </div>
                @endif
                
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">علل احتمالی</h3>
                    <ul class="text-right space-y-2 text-gray-600">
                        <li>• عدم تأیید پرداخت توسط بانک</li>
                        <li>• عدم کفایت موجودی حساب</li>
                        <li>• انقضای کارت بانکی</li>
                        <li>• مشکل در شبکه اینترنت</li>
                        <li>• لغو پرداخت توسط کاربر</li>
                    </ul>
                </div>
                
                <div class="space-y-4">
                    <a href="{{ url('/') }}" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        تلاش مجدد
                    </a>
                    
                    <a href="{{ url('/admin') }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        پنل مدیریت
                    </a>
                </div>
                
                <div class="mt-8 text-sm text-gray-500">
                    <p>در صورت بروز مشکل، با پشتیبانی تماس بگیرید</p>
                    <p class="mt-2">
                        <a href="mailto:support@sarvcast.com" class="text-primary hover:underline">support@sarvcast.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
