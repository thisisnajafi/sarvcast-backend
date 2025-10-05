<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت ناموفق - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
        
        .sad-animation {
            animation: sad-bounce 2s ease-in-out infinite;
        }
        
        @keyframes sad-bounce {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(0.95) rotate(-2deg); }
        }
        
        .encouragement-animation {
            animation: encouragement 3s ease-in-out infinite;
        }
        
        @keyframes encouragement {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl card-shadow max-w-md w-full p-8 text-center relative overflow-hidden">
        <!-- Sad but Encouraging Icon -->
        <div class="sad-animation mb-6">
            <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
        </div>

        <!-- Encouraging Message -->
        <h1 class="text-3xl font-bold text-gray-800 mb-4">
            😔 متأسفیم! پرداخت انجام نشد
        </h1>
        
        <p class="text-lg text-gray-600 mb-6">
            مشکلی در پردازش پرداخت شما پیش آمده، اما نگران نباشید! می‌تونید دوباره تلاش کنید.
        </p>

        <!-- Encouragement Box -->
        <div class="encouragement-animation bg-blue-50 rounded-2xl p-6 mb-6 border border-blue-200">
            <div class="text-4xl mb-3">💪</div>
            <h3 class="text-lg font-semibold text-blue-800 mb-2">نکات مهم:</h3>
            <ul class="text-right text-blue-700 space-y-2">
                <li class="flex items-center justify-end">
                    <span class="mr-2">💳</span>
                    بررسی کنید که کارت بانکی شما فعال باشد
                </li>
                <li class="flex items-center justify-end">
                    <span class="mr-2">💰</span>
                    موجودی کافی در حساب داشته باشید
                </li>
                <li class="flex items-center justify-end">
                    <span class="mr-2">📱</span>
                    اتصال اینترنت شما پایدار باشد
                </li>
                <li class="flex items-center justify-end">
                    <span class="mr-2">🔄</span>
                    می‌تونید دوباره تلاش کنید
                </li>
            </ul>
        </div>

        <!-- Error Details (if available) -->
        @if(session('error'))
        <div class="bg-red-50 rounded-2xl p-4 mb-6 border border-red-200">
            <div class="flex items-center justify-center mb-2">
                <span class="text-red-600 text-lg mr-2">⚠️</span>
                <span class="text-red-800 font-semibold">پیام خطا:</span>
            </div>
            <p class="text-red-700 text-sm">{{ session('error') }}</p>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="space-y-4">
            <a href="#" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-2xl transition duration-300">
                📞 تماس با پشتیبانی
            </a>
            
            <button onclick="returnToApp()" class="block w-full bg-green-100 hover:bg-green-200 text-green-700 font-semibold py-3 px-6 rounded-2xl transition duration-300">
                🏠 بازگشت به اپلیکیشن
            </button>
        </div>

        <!-- Support Information -->
        <div class="mt-8 p-4 bg-gray-50 rounded-2xl">
            <h4 class="text-gray-800 font-semibold mb-3">🆘 نیاز به کمک دارید؟</h4>
            <div class="text-sm text-gray-600 space-y-2">
                <p>📧 ایمیل: support@sarvcast.ir</p>
                <p>📱 تلگرام: @sarvcast_support</p>
                <p>🕐 ساعات کاری: ۹ صبح تا ۶ عصر</p>
            </div>
        </div>

        <!-- Encouraging Footer -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 mb-2">
                نگران نباشید، ما اینجا هستیم تا کمکتون کنیم! 🤗
            </p>
            <div class="mt-2">
                <span class="text-2xl">💝🌈🎈</span>
            </div>
        </div>
    </div>

    <script>
        function returnToApp() {
            // Get failure data for app
            const failureData = {
                success: false,
                error: '{{ session("error") ?? "پرداخت ناموفق" }}',
                timestamp: new Date().toISOString()
            };
            
            // Try multiple methods to return to app
            const appSchemes = [
                'sarvcast://payment/failure', // Your app's custom scheme
                'sarvcast://subscription/failure',
                'sarvcast://home',
                'sarvcast://'
            ];
            
            // Add failure data as query parameters
            const dataString = encodeURIComponent(JSON.stringify(failureData));
            
            // Try each scheme
            for (let scheme of appSchemes) {
                const url = `${scheme}?data=${dataString}`;
                
                // Create hidden iframe to try opening the app
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = url;
                document.body.appendChild(iframe);
                
                // Remove iframe after attempt
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }
            
            // Show fallback message
            setTimeout(() => {
                alert('اگر اپلیکیشن باز نشد، لطفاً به صورت دستی به اپلیکیشن بازگردید.');
            }, 2000);
        }
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to buttons
            const buttons = document.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.tagName === 'BUTTON') {
                        // Create ripple effect for buttons
                        const ripple = document.createElement('span');
                        ripple.classList.add('ripple');
                        this.appendChild(ripple);
                        
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    }
                });
            });
            
            // Show encouraging message after 5 seconds
            setTimeout(() => {
                const encouragementDiv = document.querySelector('.encouragement-animation');
                if (encouragementDiv) {
                    encouragementDiv.style.animation = 'encouragement 1s ease-in-out';
                }
            }, 5000);
        });
    </script>
</body>
</html>