<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت موفق - سروکست</title>
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
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-900 mb-2">پرداخت موفق!</h2>
                <p class="text-lg text-gray-600 mb-8">پرداخت شما با موفقیت انجام شد</p>
                
                @if(isset($payment))
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">جزئیات پرداخت</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">شماره تراکنش:</span>
                                <span class="font-medium">{{ $payment->transaction_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">مبلغ:</span>
                                <span class="font-medium">{{ number_format($payment->amount) }} ریال</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">درگاه پرداخت:</span>
                                <span class="font-medium">{{ $payment->payment_method === 'zarinpal' ? 'زرین‌پال' : 'پی‌ایر' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ پرداخت:</span>
                                <span class="font-medium">{{ $payment->paid_at->format('Y/m/d H:i') }}</span>
                            </div>
                            @if($payment->subscription)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">نوع اشتراک:</span>
                                    <span class="font-medium">{{ $payment->subscription->plan_id === 'monthly' ? 'ماهانه' : 'سالانه' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">انقضا:</span>
                                    <span class="font-medium">{{ $payment->subscription->end_date->format('Y/m/d') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                
                <div class="space-y-4">
                    <a href="{{ url('/') }}" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        بازگشت به صفحه اصلی
                    </a>
                    
                    <a href="{{ url('/admin') }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        پنل مدیریت
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
