@extends('admin.layouts.app')

@section('title', 'مدیریت پرداخت کمیسیون')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">مدیریت پرداخت کمیسیون</h1>
                <p class="text-gray-600">پردازش و مدیریت پرداخت‌های کمیسیون شرکا</p>
            </div>
            <div class="flex space-x-4">
                <button onclick="openManualPaymentModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    پرداخت دستی
                </button>
                <button onclick="bulkProcessPayments()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-cogs mr-2"></i>
                    پردازش دسته‌ای
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">در انتظار</h3>
                    <p class="text-2xl font-bold text-yellow-600" id="pending-payments">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-cogs text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">در حال پردازش</h3>
                    <p class="text-2xl font-bold text-blue-600" id="processing-payments">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">پرداخت شده</h3>
                    <p class="text-2xl font-bold text-green-600" id="paid-payments">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">کل مبلغ</h3>
                    <p class="text-2xl font-bold text-purple-600" id="total-amount">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">فیلترها</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت پرداخت</label>
                <select id="status-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending">در انتظار</option>
                    <option value="processing">در حال پردازش</option>
                    <option value="paid">پرداخت شده</option>
                    <option value="failed">ناموفق</option>
                    <option value="cancelled">لغو شده</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع پرداخت</label>
                <select id="payment-type-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">همه انواع</option>
                    <option value="coupon_commission">کمیسیون کوپن</option>
                    <option value="referral_commission">کمیسیون ارجاع</option>
                    <option value="manual">دستی</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ از</label>
                <input type="date" id="date-from-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex items-end">
                <button onclick="applyFilters()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    اعمال فیلتر
                </button>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">لیست پرداخت‌ها</h2>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">انتخاب همه</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">انتخاب</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شریک</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع پرداخت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody id="payments-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Payments will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            <div id="pagination" class="flex justify-between items-center">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Manual Payment Modal -->
<div id="manual-payment-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">ایجاد پرداخت دستی</h3>
            </div>
            <form id="manual-payment-form" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">شریک</label>
                    <select name="partner_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">انتخاب شریک</option>
                        <!-- Partners will be loaded here -->
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ</label>
                        <input type="number" name="amount" required min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">واحد پول</label>
                        <select name="currency" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="IRR">ریال ایران</option>
                            <option value="USD">دلار آمریکا</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">روش پرداخت</label>
                    <select name="payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="bank_transfer">انتقال بانکی</option>
                        <option value="digital_wallet">کیف پول دیجیتال</option>
                        <option value="manual">دستی</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeManualPaymentModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        لغو
                    </button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        ایجاد پرداخت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Actions Modal -->
<div id="payment-actions-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">عملیات پرداخت</h3>
            </div>
            <div class="p-6 space-y-4">
                <div id="payment-details">
                    <!-- Payment details will be loaded here -->
                </div>
                <div class="flex justify-end space-x-4">
                    <button onclick="closePaymentActionsModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        بستن
                    </button>
                    <button onclick="processPayment()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        پردازش
                    </button>
                    <button onclick="markAsPaid()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        پرداخت شده
                    </button>
                    <button onclick="markAsFailed()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        ناموفق
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/admin-commission-payments.js') }}"></script>
@endsection
