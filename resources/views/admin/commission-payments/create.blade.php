@extends('admin.layouts.app')

@section('title', 'افزودن پرداخت کمیسیون')
@section('page-title', 'افزودن پرداخت کمیسیون')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'افزودن پرداخت کمیسیون',
        'subtitle' => 'ایجاد پرداخت کمیسیون جدید برای شریک وابسته',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
        'iconBg' => 'bg-green-100',
        'iconColor' => 'text-green-600',
        'actions' => '<a href="' . route('admin.commission-payments.index') . '" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>بازگشت</a>'
    ])

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('admin.commission-payments.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Affiliate Partner Selection -->
                <div class="md:col-span-2">
                    <label for="affiliate_partner_id" class="block text-sm font-medium text-gray-700 mb-2">
                        شریک وابسته <span class="text-red-500">*</span>
                    </label>
                    <select name="affiliate_partner_id" id="affiliate_partner_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('affiliate_partner_id') border-red-500 @enderror">
                        <option value="">انتخاب شریک وابسته</option>
                        @foreach($affiliatePartners as $partner)
                            <option value="{{ $partner->id }}" {{ old('affiliate_partner_id') == $partner->id ? 'selected' : '' }}>
                                {{ $partner->name }} ({{ $partner->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('affiliate_partner_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        مبلغ <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required
                           value="{{ old('amount') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('amount') border-red-500 @enderror"
                           placeholder="مبلغ پرداخت">
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                        ارز <span class="text-red-500">*</span>
                    </label>
                    <select name="currency" id="currency" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('currency') border-red-500 @enderror">
                        <option value="">انتخاب ارز</option>
                        <option value="IRT" {{ old('currency') == 'IRT' ? 'selected' : '' }}>تومان (IRT)</option>
                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>دلار (USD)</option>
                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>یورو (EUR)</option>
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Type -->
                <div>
                    <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-2">
                        نوع پرداخت <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_type" id="payment_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('payment_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع پرداخت</option>
                        <option value="commission" {{ old('payment_type') == 'commission' ? 'selected' : '' }}>کمیسیون</option>
                        <option value="bonus" {{ old('payment_type') == 'bonus' ? 'selected' : '' }}>پاداش</option>
                        <option value="refund" {{ old('payment_type') == 'refund' ? 'selected' : '' }}>بازپرداخت</option>
                    </select>
                    @error('payment_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                        روش پرداخت <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_method" id="payment_method" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('payment_method') border-red-500 @enderror">
                        <option value="">انتخاب روش پرداخت</option>
                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>انتقال بانکی</option>
                        <option value="paypal" {{ old('payment_method') == 'paypal' ? 'selected' : '' }}>پی‌پال</option>
                        <option value="zarinpal" {{ old('payment_method') == 'zarinpal' ? 'selected' : '' }}>زرین‌پال</option>
                        <option value="crypto" {{ old('payment_method') == 'crypto' ? 'selected' : '' }}>ارز دیجیتال</option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        وضعیت <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="processing" {{ old('status') == 'processing' ? 'selected' : '' }}>در حال پردازش</option>
                        <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                        <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>ناموفق</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Reference -->
                <div>
                    <label for="payment_reference" class="block text-sm font-medium text-gray-700 mb-2">
                        مرجع پرداخت
                    </label>
                    <input type="text" name="payment_reference" id="payment_reference"
                           value="{{ old('payment_reference') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('payment_reference') border-red-500 @enderror"
                           placeholder="شماره مرجع پرداخت">
                    @error('payment_reference')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        یادداشت‌ها
                    </label>
                    <textarea name="notes" id="notes" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('notes') border-red-500 @enderror"
                              placeholder="یادداشت‌های مربوط به پرداخت">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.commission-payments.index') }}"
                   class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 text-center">
                    انصراف
                </a>
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    ایجاد پرداخت کمیسیون
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-end">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="mr-3">
                <h3 class="text-sm font-medium text-blue-800">راهنمای ایجاد پرداخت کمیسیون</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>اطمینان حاصل کنید که شریک وابسته انتخاب شده فعال است</li>
                        <li>مبلغ پرداخت باید مثبت باشد</li>
                        <li>مرجع پرداخت برای ردیابی پرداخت‌ها استفاده می‌شود</li>
                        <li>یادداشت‌ها برای توضیحات اضافی در مورد پرداخت است</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill payment reference based on payment method
    const paymentMethodSelect = document.getElementById('payment_method');
    const paymentReferenceInput = document.getElementById('payment_reference');
    
    paymentMethodSelect.addEventListener('change', function() {
        const method = this.value;
        const timestamp = new Date().getTime().toString().slice(-6);
        
        switch(method) {
            case 'bank_transfer':
                paymentReferenceInput.placeholder = 'شماره پیگیری بانکی';
                break;
            case 'paypal':
                paymentReferenceInput.placeholder = 'Transaction ID';
                break;
            case 'zarinpal':
                paymentReferenceInput.placeholder = 'Authority Code';
                break;
            case 'crypto':
                paymentReferenceInput.placeholder = 'Transaction Hash';
                break;
            default:
                paymentReferenceInput.placeholder = 'شماره مرجع پرداخت';
        }
    });
});
</script>
@endsection
