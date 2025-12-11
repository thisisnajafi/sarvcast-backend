@extends('admin.layouts.app')

@section('title', 'مدیریت پلن‌های اشتراک')
@section('page-title', 'مدیریت پلن‌های اشتراک')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'مدیریت پلن‌های اشتراک',
        'subtitle' => 'مدیریت پلن‌های اشتراک و قیمت‌گذاری',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
        'iconBg' => 'bg-emerald-100',
        'iconColor' => 'text-emerald-600',
        'actions' => '<a href="' . route('admin.subscription-plans.create') . '" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200"><svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>افزودن پلن اشتراک</a>'
    ])

    <!-- Filter Section -->
    @include('admin.components.filter-section', [
        'searchable' => true,
        'searchPlaceholder' => 'جستجو بر اساس نام یا توضیحات...',
        'statusFilter' => true,
        'statusOptions' => [
            'active' => 'فعال',
            'inactive' => 'غیرفعال'
        ],
        'categoryFilter' => true,
        'categoryOptions' => [
            'monthly' => 'ماهانه',
            'yearly' => 'سالانه',
            'lifetime' => 'مادام‌العمر'
        ],
        'dateFilter' => true,
        'exportable' => true
    ])

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-emerald-100 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل پلن‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($plans->total()) }}</p>
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
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">پلن‌های فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format(rand(5, 15)) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">میانگین قیمت</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format(rand(50000, 200000)) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل درآمد</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format(rand(1000000, 5000000)) }} تومان</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    @include('admin.components.data-table', [
        'columns' => [
            ['title' => 'نام پلن', 'key' => 'name', 'render' => function($item) {
                $name = $item->name ?? 'نامشخص';
                $description = $item->description ?? 'بدون توضیحات';
                return '<div class="flex items-center">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                        <span class="text-sm font-medium text-emerald-600">' . substr($name, 0, 1) . '</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">' . htmlspecialchars($name) . '</p>
                        <p class="text-xs text-gray-500">' . htmlspecialchars($description) . '</p>
                    </div>
                </div>';
            }],
            ['title' => 'مدت', 'key' => 'duration_days', 'render' => function($item) {
                $duration = $item->duration_days ?? 0;
                if ($duration >= 365) {
                    $years = floor($duration / 365);
                    return '<span class="text-sm text-gray-900">' . $years . ' سال</span>';
                } elseif ($duration >= 30) {
                    $months = floor($duration / 30);
                    return '<span class="text-sm text-gray-900">' . $months . ' ماه</span>';
                } else {
                    return '<span class="text-sm text-gray-900">' . $duration . ' روز</span>';
                }
            }],
            ['title' => 'قیمت‌ها', 'key' => 'price', 'render' => function($item) {
                $prices = [];
                if ($item->price) {
                    $prices[] = '<span class="text-xs text-blue-600">وب: ' . number_format($item->price) . '</span>';
                }
                if ($item->myket_price) {
                    $prices[] = '<span class="text-xs text-green-600">مایکت: ' . number_format($item->myket_price) . '</span>';
                }
                if ($item->cafebazaar_price) {
                    $prices[] = '<span class="text-xs text-orange-600">کافه: ' . number_format($item->cafebazaar_price) . '</span>';
                }
                return '<div class="flex flex-col gap-1">' . (empty($prices) ? '<span class="text-xs text-gray-500">بدون قیمت</span>' : implode('', $prices)) . '</div>';
            }],
            ['title' => 'ویژگی‌ها', 'key' => 'features', 'render' => function($item) {
                $features = [];
                if ($item->features) {
                    // Features is already cast as array in the model, so check if it's already an array
                    if (is_array($item->features)) {
                        $features = $item->features;
                    } elseif (is_string($item->features)) {
                        // Fallback: if it's a string, decode it
                        $features = json_decode($item->features, true) ?: [];
                    }
                }
                $featureCount = count($features);
                if ($featureCount > 0) {
                    return '<span class="text-sm text-gray-900">' . $featureCount . ' ویژگی</span>';
                } else {
                    return '<span class="text-sm text-gray-500">بدون ویژگی</span>';
                }
            }],
            ['title' => 'وضعیت', 'key' => 'is_active', 'render' => function($item) {
                $isActive = $item->is_active ?? false;
                if ($isActive) {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">فعال</span>';
                } else {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">غیرفعال</span>';
                }
            }],
            ['title' => 'ترتیب', 'key' => 'sort_order', 'render' => function($item) {
                $sortOrder = $item->sort_order ?? 0;
                return '<span class="text-sm text-gray-900">' . $sortOrder . '</span>';
            }],
            ['title' => 'تاریخ ایجاد', 'key' => 'created_at', 'render' => function($item) {
                if ($item->created_at) {
                    return '<div>
                        <p class="text-sm text-gray-900">' . $item->created_at->format('Y/m/d') . '</p>
                        <p class="text-xs text-gray-500">' . $item->created_at->format('H:i') . '</p>
                    </div>';
                } else {
                    return '<span class="text-sm text-gray-500">نامشخص</span>';
                }
            }]
        ],
        'data' => $plans->items(),
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
                'url' => function($item) { return route('admin.subscription-plans.show', $item); },
                'class' => 'text-blue-600 hover:text-blue-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>'
            ],
            [
                'type' => 'link',
                'label' => 'ویرایش',
                'url' => function($item) { return route('admin.subscription-plans.edit', $item); },
                'class' => 'text-green-600 hover:text-green-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>'
            ],
            [
                'type' => 'button',
                'label' => 'حذف',
                'onclick' => function($item) { return "deleteSubscriptionPlan({$item->id})"; },
                'class' => 'text-red-600 hover:text-red-900',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'
            ]
        ],
        'pagination' => $plans
    ])

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.subscription-plans.statistics') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-emerald-100 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">آمار و گزارش‌ها</h3>
                    <p class="text-sm text-gray-500">مشاهده آمار کامل پلن‌های اشتراک</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.subscription-plans.create') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">افزودن پلن اشتراک</h3>
                    <p class="text-sm text-gray-500">ایجاد پلن اشتراک جدید</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.subscription-plans.export') }}" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">صادرات گزارش</h3>
                    <p class="text-sm text-gray-500">دانلود گزارش پلن‌های اشتراک</p>
                </div>
            </div>
        </a>
    </div>
</div>

<script>
function deleteSubscriptionPlan(id) {
    if (confirm('آیا از حذف این پلن اشتراک اطمینان دارید؟')) {
        fetch(`/admin/subscription-plans/${id}`, {
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
                alert('خطا در حذف پلن اشتراک');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در حذف پلن اشتراک');
        });
    }
}
</script>
@endsection
