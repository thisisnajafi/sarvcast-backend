<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت موفق - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
        
        .celebration-animation {
            animation: celebrate 2s ease-in-out infinite;
        }
        
        @keyframes celebrate {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f59e0b;
            animation: confetti-fall 3s linear infinite;
        }
        
        .confetti:nth-child(2) { background: #10b981; animation-delay: 0.5s; }
        .confetti:nth-child(3) { background: #3b82f6; animation-delay: 1s; }
        .confetti:nth-child(4) { background: #ef4444; animation-delay: 1.5s; }
        .confetti:nth-child(5) { background: #8b5cf6; animation-delay: 2s; }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }
        
        .success-icon {
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Confetti Animation -->
    <div class="fixed inset-0 pointer-events-none">
        <div class="confetti" style="left: 10%;"></div>
        <div class="confetti" style="left: 20%;"></div>
        <div class="confetti" style="left: 30%;"></div>
        <div class="confetti" style="left: 40%;"></div>
        <div class="confetti" style="left: 50%;"></div>
        <div class="confetti" style="left: 60%;"></div>
        <div class="confetti" style="left: 70%;"></div>
        <div class="confetti" style="left: 80%;"></div>
        <div class="confetti" style="left: 90%;"></div>
    </div>

    <div class="bg-white rounded-3xl card-shadow max-w-md w-full p-8 text-center relative overflow-hidden">
        <!-- Success Icon -->
        <div class="success-icon mb-6">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto celebration-animation">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-800 mb-4">
            🎉 عالی! پرداخت موفق بود
        </h1>
        
        <p class="text-lg text-gray-600 mb-6">
            اشتراک شما با موفقیت فعال شد و حالا می‌تونید از تمام داستان‌های جذاب سروکست لذت ببرید!
        </p>

        @if(isset($payment) && $payment)
        <!-- Payment Details -->
        <div class="bg-gray-50 rounded-2xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 جزئیات پرداخت</h3>
            
            <div class="space-y-3 text-right">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">💰 مبلغ پرداخت:</span>
                    <span class="font-semibold text-gray-800">{{ number_format($payment->amount) }} ریال</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">📅 تاریخ پرداخت:</span>
                    <span class="font-semibold text-gray-800">{{ $payment->paid_at ? $payment->paid_at->format('Y/m/d H:i') : 'نامشخص' }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">🆔 شماره تراکنش:</span>
                    <span class="font-semibold text-gray-800 text-sm">{{ $payment->transaction_id }}</span>
                </div>
                
                @if($payment->subscription)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">📚 نوع اشتراک:</span>
                    <span class="font-semibold text-gray-800">{{ $payment->subscription->type }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">⏰ اعتبار تا:</span>
                    <span class="font-semibold text-gray-800">{{ $payment->subscription->end_date ? $payment->subscription->end_date->format('Y/m/d') : 'نامشخص' }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="space-y-4">
            <button onclick="openApp()" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-2xl transition duration-300 transform hover:scale-105">
                🎧 شروع گوش دادن به داستان‌ها
            </button>
            
            <button onclick="returnToApp()" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold py-3 px-6 rounded-2xl transition duration-300">
                📱 بازگشت به اپلیکیشن
            </button>
        </div>

        <!-- Fun Message -->
        <div class="mt-8 p-4 bg-yellow-50 rounded-2xl border border-yellow-200">
            <p class="text-yellow-800 font-medium">
                🌟 حالا می‌تونید به هزاران داستان جذاب و آموزنده دسترسی داشته باشید!
            </p>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                با تشکر از اعتماد شما به سروکست
            </p>
            <div class="mt-2">
                <span class="text-2xl">📚✨🎭</span>
            </div>
        </div>
    </div>

    <script>
        // App return functionality
        function returnToApp() {
            // Get payment data for app
            const paymentData = {
                success: true,
                payment_id: {{ isset($payment) && $payment ? $payment->id : 'null' }},
                subscription_id: {{ isset($payment) && $payment && $payment->subscription ? $payment->subscription->id : 'null' }},
                amount: {{ isset($payment) && $payment ? $payment->amount : 'null' }},
                transaction_id: '{{ isset($payment) && $payment ? $payment->transaction_id : '' }}',
                timestamp: new Date().toISOString()
            };
            
            // Try multiple methods to return to app
            const appSchemes = [
                'sarvcast://payment/success', // Your app's custom scheme
                'sarvcast://subscription/success',
                'sarvcast://home',
                'sarvcast://'
            ];
            
            // Add payment data as query parameters
            const dataString = encodeURIComponent(JSON.stringify(paymentData));
            
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
        
        function openApp() {
            // Same as returnToApp but with different messaging
            returnToApp();
        }
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to buttons
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple');
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Auto redirect after 30 seconds (optional)
            setTimeout(() => {
                // Auto return to app after 30 seconds
                returnToApp();
            }, 30000);
        });
    </script>
</body>
</html>