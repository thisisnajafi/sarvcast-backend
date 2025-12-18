@extends('admin.layouts.app')

@section('title', 'مشاهده معرفی')
@section('page-title', 'مشاهده معرفی')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده معرفی</h1>
            <p class="text-gray-600">کد معرفی: {{ $referral->referral_code }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.referrals.edit', $referral) }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.referrals.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    @php
                        $statusColors = [
                            'pending' => 'text-yellow-600',
                            'completed' => 'text-green-600',
                            'expired' => 'text-red-600',
                            'cancelled' => 'text-gray-600'
                        ];
                        $statusLabels = [
                            'pending' => 'در انتظار',
                            'completed' => 'تکمیل شده',
                            'expired' => 'منقضی شده',
                            'cancelled' => 'لغو شده'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $statusColors[$referral->status] }}">{{ $statusLabels[$referral->status] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">پاداش</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($referral->reward_amount) }} سکه</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نوع معرفی</p>
                    @php
                        $typeLabels = [
                            'user_registration' => 'ثبت‌نام کاربر',
                            'subscription_purchase' => 'خرید اشتراک',
                            'content_engagement' => 'تعامل با محتوا',
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $typeLabels[$referral->referral_type] ?? ucfirst($referral->referral_type) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت پاداش</p>
                    @php
                        $rewardStatusColors = [
                            'pending' => 'text-yellow-600',
                            'paid' => 'text-green-600',
                            'cancelled' => 'text-gray-600'
                        ];
                        $rewardStatusLabels = [
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                            'cancelled' => 'لغو شده'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $rewardStatusColors[$referral->reward_status] }}">{{ $rewardStatusLabels[$referral->reward_status] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Referral Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Referral Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات معرفی</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">کد معرفی</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $referral->referral_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع معرفی</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $typeLabels[$referral->referral_type] ?? ucfirst($referral->referral_type) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مبلغ پاداش</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($referral->reward_amount) }} سکه</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مدت اعتبار</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $referral->expiry_days }} روز</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $referral->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($referral->status === 'completed' ? 'bg-green-100 text-green-800' : ($referral->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ $statusLabels[$referral->status] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وضعیت پاداش</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $referral->reward_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($referral->reward_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $rewardStatusLabels[$referral->reward_status] }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Referrer Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات معرف</h2>
                </div>
                <div class="p-6">
                    @if($referral->referrer)
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-purple-600 font-medium text-lg">
                                        {{ substr($referral->referrer->first_name, 0, 1) }}{{ substr($referral->referrer->last_name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">{{ $referral->referrer->first_name }} {{ $referral->referrer->last_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $referral->referrer->email }}</p>
                                <p class="text-sm text-gray-500">تاریخ عضویت: {{ $referral->referrer->created_at->format('Y/m/d') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">سکه موجود</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($referral->referrer->coins) }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">معرف یافت نشد.</p>
                    @endif
                </div>
            </div>

            <!-- Referred User Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات معرفی شده</h2>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-500">ایمیل معرفی شده</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $referral->referred_email }}</dd>
                    </div>
                    
                    @if($referral->referred)
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <span class="text-green-600 font-medium text-lg">
                                        {{ substr($referral->referred->first_name, 0, 1) }}{{ substr($referral->referred->last_name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">{{ $referral->referred->first_name }} {{ $referral->referred->last_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $referral->referred->email }}</p>
                                <p class="text-sm text-gray-500">تاریخ عضویت: {{ $referral->referred->created_at->format('Y/m/d') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    ثبت‌نام کرده
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">این ایمیل هنوز ثبت‌نام نکرده است.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Description -->
            @if($referral->description)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">توضیحات</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $referral->description }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">زمان‌بندی</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">ایجاد معرفی</p>
                                <p class="text-xs text-gray-500">{{ $referral->created_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>

                        @if($referral->completed_at)
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">تکمیل معرفی</p>
                                <p class="text-xs text-gray-500">{{ $referral->completed_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($referral->paid_at)
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">پرداخت پاداش</p>
                                <p class="text-xs text-gray-500">{{ $referral->paid_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">تاریخ انقضا</p>
                                <p class="text-xs text-gray-500">{{ $referral->expires_at ? $referral->expires_at->format('Y/m/d H:i') : '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if($referral->status === 'pending')
                        <form method="POST" action="{{ route('admin.referrals.approve', $referral) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید معرفی
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.referrals.reject', $referral) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                رد معرفی
                            </button>
                        </form>
                    @endif

                    @if($referral->reward_status === 'pending' && $referral->status === 'completed')
                        <form method="POST" action="{{ route('admin.referrals.pay-reward', $referral) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                پرداخت پاداش
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.referrals.destroy', $referral) }}" class="w-full" onsubmit="return confirm('آیا از حذف این معرفی اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف معرفی
                        </button>
                    </form>
                </div>
            </div>

            <!-- Related Referrals -->
            @if($relatedReferrals->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">معرفی‌های مرتبط</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($relatedReferrals as $relatedReferral)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $relatedReferral->referral_code }}</p>
                                    <p class="text-xs text-gray-500">{{ $relatedReferral->referred_email }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($relatedReferral->reward_amount) }}</p>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $relatedReferral->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $statusLabels[$relatedReferral->status] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
