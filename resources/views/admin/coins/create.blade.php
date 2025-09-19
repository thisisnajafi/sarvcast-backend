@extends('admin.layouts.app')

@section('title', 'افزودن سکه')
@section('page-title', 'افزودن سکه')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'افزودن سکه',
        'subtitle' => 'افزودن سکه جدید به کاربر',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
        'iconBg' => 'bg-green-100',
        'iconColor' => 'text-green-600',
        'breadcrumbs' => [
            ['title' => 'مدیریت سکه‌ها', 'url' => route('admin.coins.index')],
            ['title' => 'افزودن سکه']
        ]
    ])

    <!-- Form -->
    @include('admin.components.form', [
        'method' => 'POST',
        'action' => route('admin.coins.store'),
        'title' => 'اطلاعات تراکنش سکه',
        'subtitle' => 'لطفاً اطلاعات تراکنش سکه را وارد کنید',
        'fields' => [
            [
                'type' => 'select',
                'name' => 'user_id',
                'label' => 'کاربر',
                'placeholder' => 'انتخاب کاربر',
                'required' => true,
                'options' => $users->pluck('name', 'id')->toArray(),
                'help' => 'کاربری که سکه به حساب او اضافه می‌شود'
            ],
            [
                'type' => 'number',
                'name' => 'amount',
                'label' => 'مبلغ سکه',
                'placeholder' => 'تعداد سکه',
                'required' => true,
                'help' => 'تعداد سکه‌هایی که به کاربر اضافه می‌شود'
            ],
            [
                'type' => 'select',
                'name' => 'type',
                'label' => 'نوع تراکنش',
                'placeholder' => 'انتخاب نوع',
                'required' => true,
                'options' => [
                    'earned' => 'کسب شده',
                    'purchased' => 'خریداری شده',
                    'gift' => 'هدیه',
                    'refund' => 'بازپرداخت',
                    'admin_adjustment' => 'تنظیم ادمین'
                ],
                'help' => 'نوع تراکنش سکه'
            ],
            [
                'type' => 'textarea',
                'name' => 'description',
                'label' => 'توضیحات',
                'placeholder' => 'توضیحات تراکنش...',
                'rows' => 3,
                'help' => 'توضیحات اختیاری در مورد تراکنش'
            ]
        ],
        'cancelUrl' => route('admin.coins.index'),
        'submitText' => 'افزودن سکه'
    ])
</div>
@endsection
