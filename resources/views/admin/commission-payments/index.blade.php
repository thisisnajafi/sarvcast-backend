@extends('admin.layouts.app')

@section('title', 'مدیریت پرداخت‌های کمیسیون')
@section('page-title', 'مدیریت پرداخت‌های کمیسیون')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'مدیریت پرداخت‌های کمیسیون',
        'subtitle' => 'مدیریت پرداخت‌های کمیسیون به شرکای وابسته',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
        'iconBg' => 'bg-indigo-100',
        'iconColor' => 'text-indigo-600',
        'actions' => '<a href="' . route('admin.commission-payments.create') . '" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>افزودن پرداخت کمیسیون</a>'
    ])

    <!-- Filter Section -->
    @include('admin.components.filter-section', [
        'searchable' => true,
        'searchPlaceholder' => 'جستجو بر اساس نام کاربر یا ایمیل...',
        'statusFilter' => true,
        'statusOptions' => [
            'active' => 'فعال',
            'inactive' => 'غیرفعال'
        ],
        'categoryFilter' => true,
        'categoryOptions' => [
            'pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'failed' => 'پرداخت ناموفق'
        ],
        'dateFilter' => true,
        'exportable' => true
    ])

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">کل پرداخت‌ها</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_payments']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">کل مبلغ</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_amount']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">پرداخت شده</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['paid_amount']) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">در انتظار</p>
                    <p class="text-lg sm:text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['pending_amount']) }} تومان</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    @include('admin.components.data-table', [
        'columns' => [
            ['title' => 'شریک', 'key' => 'affiliatePartner', 'render' => function($item) {
                return '<div class="flex items-center">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                        <span class="text-xs sm:text-sm font-medium text-indigo-600">' . substr($item->affiliatePartner->name ?? 'شریک', 0, 1) . '</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">' . ($item->affiliatePartner->name ?? 'شریک') . '</p>
                        <p class="text-xs text-gray-500 truncate hidden sm:block">' . ($item->affiliatePartner->email ?? '') . '</p>
                    </div>
                </div>';
            }],
            ['title' => 'مبلغ', 'key' => 'amount', 'render' => function($item) {
                return '<div class="text-left">
                    <span class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white block">' . number_format($item->amount) . '</span>
                    <span class="text-xs text-gray-500">' . $item->currency . '</span>
                </div>';
            }],
            ['title' => 'نوع پرداخت', 'key' => 'payment_type', 'render' => function($item) {
                $types = [
                    'commission' => ['label' => 'کمیسیون', 'class' => 'bg-blue-100 text-blue-800'],
                    'bonus' => ['label' => 'پاداش', 'class' => 'bg-green-100 text-green-800'],
                    'refund' => ['label' => 'بازپرداخت', 'class' => 'bg-yellow-100 text-yellow-800']
                ];
                $type = $types[$item->payment_type] ?? ['label' => $item->payment_type, 'class' => 'bg-gray-100 text-gray-800'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $type['class'] . '">' . $type['label'] . '</span>';
            }],
            ['title' => 'روش پرداخت', 'key' => 'payment_method', 'render' => function($item) {
                $methods = [
                    'bank_transfer' => ['label' => 'انتقال بانکی', 'class' => 'bg-blue-100 text-blue-800'],
                    'paypal' => ['label' => 'پی‌پال', 'class' => 'bg-purple-100 text-purple-800'],
                    'zarinpal' => ['label' => 'زرین‌پال', 'class' => 'bg-green-100 text-green-800'],
                    'crypto' => ['label' => 'ارز دیجیتال', 'class' => 'bg-yellow-100 text-yellow-800']
                ];
                $method = $methods[$item->payment_method] ?? ['label' => $item->payment_method, 'class' => 'bg-gray-100 text-gray-800'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $method['class'] . '">' . $method['label'] . '</span>';
            }],
            ['title' => 'وضعیت', 'key' => 'status', 'render' => function($item) {
                $statuses = [
                    'pending' => ['label' => 'در انتظار', 'class' => 'bg-yellow-100 text-yellow-800'],
                    'processing' => ['label' => 'در حال پردازش', 'class' => 'bg-blue-100 text-blue-800'],
                    'paid' => ['label' => 'پرداخت شده', 'class' => 'bg-green-100 text-green-800'],
                    'failed' => ['label' => 'ناموفق', 'class' => 'bg-red-100 text-red-800'],
                    'cancelled' => ['label' => 'لغو شده', 'class' => 'bg-gray-100 text-gray-800']
                ];
                $status = $statuses[$item->status] ?? ['label' => $item->status, 'class' => 'bg-gray-100 text-gray-800'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $status['class'] . '">' . $status['label'] . '</span>';
            }],
            ['title' => 'مرجع پرداخت', 'key' => 'payment_reference', 'render' => function($item) {
                return '<span class="text-sm text-gray-900 dark:text-white">' . ($item->payment_reference ?? '-') . '</span>';
            }],
            ['title' => 'تاریخ ایجاد', 'key' => 'created_at', 'render' => function($item) {
                return '<div class="text-left">
                    <p class="text-xs sm:text-sm text-gray-900 dark:text-white">' . $item->created_at->format('Y/m/d') . '</p>
                    <p class="text-xs text-gray-500 hidden sm:block">' . $item->created_at->format('H:i') . '</p>
                </div>';
            }]
        ],
        'data' => $commissionPayments->items(),
        'bulkActions' => true,
        'bulkActionOptions' => [
            'delete' => 'حذف',
            'mark_paid' => 'علامت‌گذاری پرداخت شده',
            'mark_pending' => 'علامت‌گذاری در انتظار',
            'mark_failed' => 'علامت‌گذاری ناموفق',
            'mark_processing' => 'علامت‌گذاری در حال پردازش'
        ],
        'actions' => [
            [
                'type' => 'link',
                'label' => 'مشاهده',
                'url' => function($item) { return route('admin.commission-payments.show', $item); },
                'class' => 'text-blue-600 hover:text-blue-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>'
            ],
            [
                'type' => 'link',
                'label' => 'ویرایش',
                'url' => function($item) { return route('admin.commission-payments.edit', $item); },
                'class' => 'text-green-600 hover:text-green-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>'
            ],
            [
                'type' => 'button',
                'label' => 'حذف',
                'onclick' => function($item) { return "deleteCommissionPayment({$item->id})"; },
                'class' => 'text-red-600 hover:text-red-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'
            ]
        ],
        'pagination' => [
            'from' => $commissionPayments->firstItem(),
            'to' => $commissionPayments->lastItem(),
            'total' => $commissionPayments->total(),
            'links' => $commissionPayments->links()->elements['links'] ?? []
        ]
    ])

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('admin.commission-payments.statistics') }}" class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">آمار و گزارش‌ها</h3>
                    <p class="text-xs sm:text-sm text-gray-500 truncate">مشاهده آمار کامل پرداخت‌های کمیسیون</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.commission-payments.create') }}" class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">افزودن پرداخت کمیسیون</h3>
                    <p class="text-xs sm:text-sm text-gray-500 truncate">ایجاد پرداخت کمیسیون جدید</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.commission-payments.export') }}" class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition-shadow sm:col-span-2 lg:col-span-1">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">صادرات گزارش</h3>
                    <p class="text-xs sm:text-sm text-gray-500 truncate">دانلود گزارش پرداخت‌های کمیسیون</p>
                </div>
            </div>
        </a>
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
                location.reload();
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
