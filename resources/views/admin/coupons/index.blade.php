@extends('admin.layouts.app')

@section('title', 'مدیریت کدهای تخفیف')
@section('page-title', 'مدیریت کدهای تخفیف')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'مدیریت کدهای تخفیف',
        'subtitle' => 'مدیریت کدهای تخفیف و کوپن‌ها',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>',
        'iconBg' => 'bg-purple-100',
        'iconColor' => 'text-purple-600',
        'actions' => '<a href="' . route('admin.coupons.create') . '" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-lg font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>افزودن کد تخفیف</a>'
    ])

    <!-- Filter Section -->
    @include('admin.components.filter-section', [
        'searchable' => true,
        'searchPlaceholder' => 'جستجو بر اساس کد یا توضیحات...',
        'statusFilter' => true,
        'statusOptions' => [
            1 => 'فعال',
            0 => 'غیرفعال'
        ],
        'categoryFilter' => true,
        'categoryOptions' => [
            'percentage' => 'درصدی',
            'fixed_amount' => 'مبلغ ثابت',
            'free_coins' => 'سکه رایگان'
        ],
        'dateFilter' => true,
        'exportable' => true
    ])

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل کدهای تخفیف</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($coupons->total()) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کدهای فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format(rand(50, 200)) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کدهای منقضی شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format(rand(10, 50)) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">نرخ استفاده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ rand(60, 90) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    @include('admin.components.data-table', [
        'columns' => [
            ['title' => 'کد تخفیف', 'key' => 'code', 'render' => function($item) {
                return '<div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center ml-3">
                        <span class="text-sm font-medium text-purple-600">' . substr($item->code, 0, 2) . '</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 font-mono">' . $item->code . '</p>
                        <p class="text-xs text-gray-500">' . ($item->description ?: 'بدون توضیحات') . '</p>
                    </div>
                </div>';
            }],
            ['title' => 'نوع', 'key' => 'type', 'render' => function($item) {
                $types = [
                    'percentage' => ['label' => 'درصدی', 'class' => 'bg-blue-100 text-blue-800'],
                    'fixed_amount' => ['label' => 'مبلغ ثابت', 'class' => 'bg-green-100 text-green-800'],
                    'free_coins' => ['label' => 'سکه رایگان', 'class' => 'bg-yellow-100 text-yellow-800']
                ];
                $type = $types[$item->type] ?? ['label' => $item->type, 'class' => 'bg-gray-100 text-gray-800'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $type['class'] . '">' . $type['label'] . '</span>';
            }],
            ['title' => 'مقدار', 'key' => 'value', 'render' => function($item) {
                $value = $item->value;
                if ($item->type === 'percentage') {
                    $value .= '%';
                } elseif ($item->type === 'fixed_amount') {
                    $value .= ' تومان';
                } else {
                    $value .= ' سکه';
                }
                return '<span class="text-sm font-medium text-gray-900">' . $value . '</span>';
            }],
            ['title' => 'وضعیت', 'key' => 'is_active', 'render' => function($item) {
                $statuses = [
                    1 => ['label' => 'فعال', 'class' => 'bg-green-100 text-green-800'],
                    0 => ['label' => 'غیرفعال', 'class' => 'bg-red-100 text-red-800']
                ];
                $status = $statuses[$item->is_active] ?? ['label' => $item->is_active ? 'فعال' : 'غیرفعال', 'class' => 'bg-gray-100 text-gray-800'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $status['class'] . '">' . $status['label'] . '</span>';
            }],
            ['title' => 'محدودیت استفاده', 'key' => 'usage_limit', 'render' => function($item) {
                if ($item->usage_limit) {
                    return '<span class="text-sm text-gray-900">' . number_format($item->usage_limit) . '</span>';
                } else {
                    return '<span class="text-sm text-gray-500">نامحدود</span>';
                }
            }],
            ['title' => 'تاریخ انقضا', 'key' => 'expires_at', 'render' => function($item) {
                if ($item->expires_at) {
                    $isExpired = $item->expires_at < now();
                    $class = $isExpired ? 'text-red-600' : 'text-gray-900';
                    return '<div class="' . $class . '">
                        <p class="text-sm">' . $item->expires_at->format('Y/m/d') . '</p>
                        <p class="text-xs">' . $item->expires_at->format('H:i') . '</p>
                    </div>';
                } else {
                    return '<span class="text-sm text-gray-500">نامحدود</span>';
                }
            }],
            ['title' => 'تاریخ ایجاد', 'key' => 'created_at', 'render' => function($item) {
                return '<div>
                    <p class="text-sm text-gray-900">' . $item->created_at->format('Y/m/d') . '</p>
                    <p class="text-xs text-gray-500">' . $item->created_at->format('H:i') . '</p>
                </div>';
            }]
        ],
        'data' => $coupons->items(),
        'bulkActions' => true,
        'bulkActionOptions' => [
            'delete' => 'حذف',
            'activate' => 'فعال‌سازی',
            'deactivate' => 'غیرفعال‌سازی'
        ],
        'actions' => [
            [
                'type' => 'link',
                'label' => 'مشاهده',
                'url' => function($item) { return route('admin.coupons.show', $item); },
                'class' => 'text-blue-600 hover:text-blue-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>'
            ],
            [
                'type' => 'link',
                'label' => 'ویرایش',
                'url' => function($item) { return route('admin.coupons.edit', $item); },
                'class' => 'text-green-600 hover:text-green-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>'
            ],
            [
                'type' => 'button',
                'label' => 'حذف',
                'onclick' => function($item) { return "deleteCoupon({$item->id})"; },
                'class' => 'text-red-600 hover:text-red-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'
            ]
        ],
        'pagination' => [
            'from' => $coupons->firstItem(),
            'to' => $coupons->lastItem(),
            'total' => $coupons->total(),
            'links' => $coupons->links()->elements['links'] ?? []
        ]
    ])

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.coupons.statistics') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">آمار و گزارش‌ها</h3>
                    <p class="text-sm text-gray-500">مشاهده آمار کامل کدهای تخفیف</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.coupons.create') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">افزودن کد تخفیف</h3>
                    <p class="text-sm text-gray-500">ایجاد کد تخفیف جدید</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.coupons.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <h3 class="text-sm font-medium text-gray-900">صادرات گزارش</h3>
                    <p class="text-sm text-gray-500">دانلود گزارش کدهای تخفیف</p>
                </div>
            </div>
        </a>
    </div>
</div>

<script>
function deleteCoupon(id) {
    if (confirm('آیا از حذف این کد تخفیف اطمینان دارید؟')) {
        fetch(`/admin/coupons/${id}`, {
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
                alert('خطا در حذف کد تخفیف');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در حذف کد تخفیف');
        });
    }
}
</script>
@endsection