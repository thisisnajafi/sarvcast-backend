@extends('admin.layouts.app')

@section('title', 'مشاهده پرداخت کمیسیون')
@section('page-title', 'مشاهده پرداخت کمیسیون')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'مشاهده پرداخت کمیسیون',
        'subtitle' => 'جزئیات پرداخت کمیسیون شریک وابسته',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
        'iconBg' => 'bg-blue-100',
        'iconColor' => 'text-blue-600',
        'actions' => '<a href="' . route('admin.commission-payments.index') . '" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>بازگشت</a>'
    ])

    <!-- Commission Payment Details -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">جزئیات پرداخت کمیسیون</h3>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Affiliate Partner Info -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">شریک وابسته</label>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-sm font-medium text-indigo-600">
                                    {{ substr($commissionPayment->affiliatePartner->name ?? 'شریک', 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $commissionPayment->affiliatePartner->name ?? 'شریک' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $commissionPayment->affiliatePartner->email ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">مبلغ</label>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ number_format($commissionPayment->amount) }} {{ $commissionPayment->currency }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">نوع پرداخت</label>
                        @php
                            $types = [
                                'commission' => ['label' => 'کمیسیون', 'class' => 'bg-blue-100 text-blue-800'],
                                'bonus' => ['label' => 'پاداش', 'class' => 'bg-green-100 text-green-800'],
                                'refund' => ['label' => 'بازپرداخت', 'class' => 'bg-yellow-100 text-yellow-800']
                            ];
                            $type = $types[$commissionPayment->payment_type] ?? ['label' => $commissionPayment->payment_type, 'class' => 'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $type['class'] }}">
                            {{ $type['label'] }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">روش پرداخت</label>
                        @php
                            $methods = [
                                'bank_transfer' => ['label' => 'انتقال بانکی', 'class' => 'bg-blue-100 text-blue-800'],
                                'paypal' => ['label' => 'پی‌پال', 'class' => 'bg-purple-100 text-purple-800'],
                                'zarinpal' => ['label' => 'زرین‌پال', 'class' => 'bg-green-100 text-green-800'],
                                'crypto' => ['label' => 'ارز دیجیتال', 'class' => 'bg-yellow-100 text-yellow-800']
                            ];
                            $method = $methods[$commissionPayment->payment_method] ?? ['label' => $commissionPayment->payment_method, 'class' => 'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $method['class'] }}">
                            {{ $method['label'] }}
                        </span>
                    </div>
                </div>

                <!-- Status and Dates -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">وضعیت</label>
                        @php
                            $statuses = [
                                'pending' => ['label' => 'در انتظار', 'class' => 'bg-yellow-100 text-yellow-800'],
                                'processing' => ['label' => 'در حال پردازش', 'class' => 'bg-blue-100 text-blue-800'],
                                'paid' => ['label' => 'پرداخت شده', 'class' => 'bg-green-100 text-green-800'],
                                'failed' => ['label' => 'ناموفق', 'class' => 'bg-red-100 text-red-800'],
                                'cancelled' => ['label' => 'لغو شده', 'class' => 'bg-gray-100 text-gray-800']
                            ];
                            $status = $statuses[$commissionPayment->status] ?? ['label' => $commissionPayment->status, 'class' => 'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status['class'] }}">
                            {{ $status['label'] }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">مرجع پرداخت</label>
                        <p class="text-sm text-gray-900 font-mono">
                            {{ $commissionPayment->payment_reference ?? 'تعیین نشده' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">تاریخ ایجاد</label>
                        <p class="text-sm text-gray-900">
                            {{ $commissionPayment->created_at->format('Y/m/d H:i') }}
                        </p>
                    </div>

                    @if($commissionPayment->processed_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">تاریخ پردازش</label>
                        <p class="text-sm text-gray-900">
                            {{ $commissionPayment->processed_at->format('Y/m/d H:i') }}
                        </p>
                    </div>
                    @endif

                    @if($commissionPayment->paid_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">تاریخ پرداخت</label>
                        <p class="text-sm text-gray-900">
                            {{ $commissionPayment->paid_at->format('Y/m/d H:i') }}
                        </p>
                    </div>
                    @endif

                    @if($commissionPayment->processor)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">پردازش شده توسط</label>
                        <p class="text-sm text-gray-900">
                            {{ $commissionPayment->processor->first_name }} {{ $commissionPayment->processor->last_name }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            @if($commissionPayment->notes)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <label class="block text-sm font-medium text-gray-500 mb-2">یادداشت‌ها</label>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $commissionPayment->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between space-y-4 lg:space-y-0 lg:space-x-4 lg:space-x-reverse">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            <a href="{{ route('admin.commission-payments.edit', $commissionPayment) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                ویرایش
            </a>
            
            <button onclick="deleteCommissionPayment({{ $commissionPayment->id }})"
                    class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                حذف
            </button>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            @if($commissionPayment->status === 'pending')
            <form action="{{ route('admin.commission-payments.update', $commissionPayment) }}" method="POST" class="w-full sm:w-auto">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="processing">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    شروع پردازش
                </button>
            </form>
            @endif

            @if($commissionPayment->status === 'processing')
            <form action="{{ route('admin.commission-payments.update', $commissionPayment) }}" method="POST" class="w-full sm:w-auto">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="paid">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    تایید پرداخت
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

<script>
function deleteCommissionPayment(id) {
    if (confirm('آیا از حذف این پرداخت کمیسیون اطمینان دارید؟')) {
        fetch(`/admin/commission-payments/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.commission-payments.index") }}';
            } else {
                alert('خطا در حذف پرداخت کمیسیون');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در حذف پرداخت کمیسیون');
        });
    }
}
</script>
@endsection
