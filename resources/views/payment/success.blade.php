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
                    <span class="font-semibold text-gray-800">{{ number_format($payment->amount) }} تومان</span>
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
            <a href="#" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-2xl transition duration-300 transform hover:scale-105">
                🎧 شروع گوش دادن به داستان‌ها
            </a>
            
            <a href="#" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold py-3 px-6 rounded-2xl transition duration-300">
                📱 بازگشت به اپلیکیشن
            </a>
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
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to buttons
            const buttons = document.querySelectorAll('a');
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
                // You can add auto-redirect logic here if needed
            }, 30000);
        });
    </script>
</body>
</html>