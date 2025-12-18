<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نمایش صفحات پرداخت - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8">
            🎨 نمایش صفحات پرداخت سروکست
        </h1>
        
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Success Page Preview -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-green-600 mb-4 text-center">
                    ✅ صفحه پرداخت موفق
                </h2>
                
                <div class="space-y-4">
                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-2">ویژگی‌ها:</h3>
                        <ul class="text-green-700 space-y-1 text-sm">
                            <li>🎉 انیمیشن جشن و کانفتی</li>
                            <li>📋 نمایش جزئیات پرداخت</li>
                            <li>🎧 دکمه‌های عملیات</li>
                            <li>🌟 پیام‌های تشویقی</li>
                            <li>📱 طراحی ریسپانسیو</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2">اطلاعات نمایش داده شده:</h3>
                        <ul class="text-gray-700 space-y-1 text-sm">
                            <li>💰 مبلغ پرداخت</li>
                            <li>📅 تاریخ و زمان</li>
                            <li>🆔 شماره تراکنش</li>
                            <li>📚 نوع اشتراک</li>
                            <li>⏰ تاریخ انقضا</li>
                        </ul>
                    </div>
                    
                    <a href="/payment/success" target="_blank" 
                       class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-300">
                        👀 مشاهده صفحه موفق
                    </a>
                </div>
            </div>
            
            <!-- Failure Page Preview -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-orange-600 mb-4 text-center">
                    ❌ صفحه پرداخت ناموفق
                </h2>
                
                <div class="space-y-4">
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h3 class="font-semibold text-orange-800 mb-2">ویژگی‌ها:</h3>
                        <ul class="text-orange-700 space-y-1 text-sm">
                            <li>😔 پیام‌های همدردی</li>
                            <li>💪 نکات مفید و راهنما</li>
                            <li>🔄 دکمه تلاش مجدد</li>
                            <li>📞 اطلاعات پشتیبانی</li>
                            <li>🤗 لحن مثبت و تشویقی</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2">راهنمایی‌های ارائه شده:</h3>
                        <ul class="text-gray-700 space-y-1 text-sm">
                            <li>💳 بررسی فعال بودن کارت</li>
                            <li>💰 بررسی موجودی کافی</li>
                            <li>📱 بررسی اتصال اینترنت</li>
                            <li>🔄 امکان تلاش مجدد</li>
                        </ul>
                    </div>
                    
                    <a href="/payment/failure" target="_blank" 
                       class="block w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-300">
                        👀 مشاهده صفحه ناموفق
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Technical Details -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">
                🔧 جزئیات فنی
            </h2>
            
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">🎨 طراحی</h3>
                    <ul class="text-blue-700 space-y-1 text-sm">
                        <li>Tailwind CSS</li>
                        <li>انیمیشن‌های CSS</li>
                        <li>طراحی ریسپانسیو</li>
                        <li>رنگ‌بندی کودکانه</li>
                        <li>فونت Vazirmatn</li>
                    </ul>
                </div>
                
                <div class="bg-purple-50 rounded-lg p-4">
                    <h3 class="font-semibold text-purple-800 mb-2">⚙️ عملکرد</h3>
                    <ul class="text-purple-700 space-y-1 text-sm">
                        <li>Laravel Blade Templates</li>
                        <li>PaymentCallbackController</li>
                        <li>Zarinpal Integration</li>
                        <li>Route Management</li>
                        <li>Session Handling</li>
                    </ul>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <h3 class="font-semibold text-green-800 mb-2">🧪 تست</h3>
                    <ul class="text-green-700 space-y-1 text-sm">
                        <li>PaymentCallbackTest</li>
                        <li>Unit Tests</li>
                        <li>Feature Tests</li>
                        <li>Integration Tests</li>
                        <li>UI/UX Testing</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Routes Information -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">
                🛣️ مسیرهای پرداخت
            </h2>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between bg-white rounded-lg p-3">
                        <span class="font-mono text-sm text-gray-600">/payment/success</span>
                        <span class="text-green-600 font-semibold">صفحه موفق</span>
                    </div>
                    
                    <div class="flex items-center justify-between bg-white rounded-lg p-3">
                        <span class="font-mono text-sm text-gray-600">/payment/failure</span>
                        <span class="text-orange-600 font-semibold">صفحه ناموفق</span>
                    </div>
                    
                    <div class="flex items-center justify-between bg-white rounded-lg p-3">
                        <span class="font-mono text-sm text-gray-600">/payment/retry</span>
                        <span class="text-blue-600 font-semibold">تلاش مجدد</span>
                    </div>
                    
                    <div class="flex items-center justify-between bg-white rounded-lg p-3">
                        <span class="font-mono text-sm text-gray-600">/payment/zarinpal/callback</span>
                        <span class="text-purple-600 font-semibold">کالبک زرین‌پال</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-gray-600">
                🎭 سروکست - داستان‌های جذاب برای کودکان
            </p>
            <div class="mt-2">
                <span class="text-2xl">📚✨🎈</span>
            </div>
        </div>
    </div>
</body>
</html>
