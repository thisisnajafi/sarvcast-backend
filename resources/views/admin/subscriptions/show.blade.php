@extends('admin.layouts.app')

@section('title', 'جزئیات اشتراک')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">جزئیات اشتراک #{{ $subscription->id }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Subscription Details -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اطلاعات اشتراک</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">شناسه اشتراک</label>
                        <p class="text-sm text-gray-900 dark:text-white">#{{ $subscription->id }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نوع پلن</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $plans[$subscription->type]['name'] ?? ($subscription->type ?? 'نامشخص') }}
                        </span>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">وضعیت</label>
                        @if($subscription->status == 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                فعال
                            </span>
                        @elseif($subscription->status == 'expired')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                منقضی
                            </span>
                        @elseif($subscription->status == 'cancelled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                لغو شده
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                {{ $subscription->status }}
                            </span>
                        @endif
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">قیمت</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ number_format($subscription->price) }} ریال</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاریخ شروع</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->start_date, 'Y/m/d H:i') }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاریخ پایان</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->end_date, 'Y/m/d H:i') }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاریخ ایجاد</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->created_at, 'Y/m/d H:i') }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاریخ آخرین بروزرسانی</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->updated_at, 'Y/m/d H:i') }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تمدید خودکار</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $subscription->auto_renew ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                            {{ $subscription->auto_renew ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                    
                    @if($subscription->cancelled_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">تاریخ لغو</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->cancelled_at, 'Y/m/d H:i') }}</p>
                    </div>
                    @endif
                    
                    @if($subscription->cancellation_reason)
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">دلیل لغو</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $subscription->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تاریخچه پرداخت‌ها</h2>
                
                @if($subscription->payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">شناسه پرداخت</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">وضعیت</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($subscription->payments as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    #{{ $payment->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($payment->amount) }} ریال
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($payment->status == 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            تکمیل شده
                                        </span>
                                    @elseif($payment->status == 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            در انتظار
                                        </span>
                                    @elseif($payment->status == 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            ناموفق
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                            {{ $payment->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ \App\Helpers\JalaliHelper::formatForDisplay($payment->created_at, 'Y/m/d H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">هیچ پرداختی یافت نشد</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">برای این اشتراک هیچ پرداختی ثبت نشده است.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- User Information & Actions -->
        <div class="space-y-6">
            <!-- User Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اطلاعات کاربر</h2>
                
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center ml-4">
                        <span class="text-white font-semibold text-lg">{{ substr($subscription->user?->first_name ?? 'U', 0, 1) }}</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $subscription->user?->first_name ?? 'کاربر' }} {{ $subscription->user?->last_name ?? 'نامشخص' }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->user?->phone_number ?? 'شماره تلفن نامشخص' }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">شماره تلفن</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $subscription->user?->phone_number ?? 'ثبت نشده' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">تاریخ عضویت</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $subscription->user?->created_at ? \App\Helpers\JalaliHelper::formatForDisplay($subscription->user->created_at, 'Y/m/d') : 'نامشخص' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">وضعیت حساب</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($subscription->user?->status ?? 'inactive') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ ($subscription->user?->status ?? 'inactive') === 'active' ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @if($subscription->user)
                        <a href="{{ route('admin.users.show', $subscription->user) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            مشاهده پروفایل کاربر →
                        </a>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">کاربر یافت نشد</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">عملیات</h2>
                
                <div class="space-y-3">
                    @if($subscription->status == 'active')
                        <button onclick="cancelSubscription({{ $subscription->id }})" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200">
                            لغو اشتراک
                        </button>
                    @elseif($subscription->status == 'cancelled')
                        <button onclick="reactivateSubscription({{ $subscription->id }})" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                            فعال‌سازی مجدد
                        </button>
                    @endif
                    
                    <a href="{{ route('admin.subscriptions.export', ['user_id' => $subscription->user_id]) }}" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 text-center block">
                        خروجی Excel
                    </a>
                </div>
            </div>

            <!-- Subscription Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">زمان‌بندی اشتراک</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">اشتراک ایجاد شد</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->created_at, 'Y/m/d H:i') }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">اشتراک فعال شد</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->start_date, 'Y/m/d H:i') }}</p>
                        </div>
                    </div>
                    
                    @if($subscription->cancelled_at)
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">اشتراک لغو شد</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->cancelled_at, 'Y/m/d H:i') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    <div class="flex items-center">
                        <div class="w-3 h-3 {{ $subscription->status == 'active' ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-3"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">تاریخ پایان</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Helpers\JalaliHelper::formatForDisplay($subscription->end_date, 'Y/m/d H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Subscription Modal -->
<div id="cancel-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="flex justify-center items-center h-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">لغو اشتراک</h3>
                <form id="cancel-form" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">دلیل لغو</label>
                        <textarea name="reason" id="reason" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="دلیل لغو اشتراک را وارد کنید" required></textarea>
                    </div>
                    <div class="flex justify-end space-x-4 space-x-reverse">
                        <button type="button" onclick="closeCancelModal()" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            انصراف
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                            لغو اشتراک
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function cancelSubscription(subscriptionId) {
    document.getElementById('cancel-form').action = `/admin/subscriptions/${subscriptionId}/cancel`;
    document.getElementById('cancel-modal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancel-modal').classList.add('hidden');
    document.getElementById('reason').value = '';
}

function reactivateSubscription(subscriptionId) {
    if (confirm('آیا مطمئن هستید که می‌خواهید این اشتراک را فعال کنید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/subscriptions/${subscriptionId}/reactivate`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
