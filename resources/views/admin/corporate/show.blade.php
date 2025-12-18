@extends('admin.layouts.app')

@section('title', 'مشاهده حمایت شرکتی')
@section('page-title', 'مشاهده حمایت شرکتی')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $corporate->company_name }}</h1>
            <p class="text-gray-600">{{ $corporate->company_city }}, {{ $corporate->company_state }} - {{ $corporate->company_country }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.corporate.edit', $corporate) }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.corporate.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت تأیید</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($corporate->is_verified)
                            <span class="text-green-600">تأیید شده</span>
                        @else
                            <span class="text-yellow-600">تأیید نشده</span>
                        @endif
                    </p>
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
                    <p class="text-sm font-medium text-gray-600">مبلغ حمایت</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($corporate->sponsorship_amount) }} تومان</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">مدت حمایت</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $corporate->sponsorship_duration }} {{ $corporate->sponsorship_duration_unit }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">اندازه شرکت</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $corporate->company_size }} نفر</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Company Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات پایه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام شرکت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع شرکت</dt>
                            @php
                                $companyTypeLabels = [
                                    'startup' => 'استارتاپ',
                                    'small_business' => 'کسب‌وکار کوچک',
                                    'medium_business' => 'کسب‌وکار متوسط',
                                    'large_corporation' => 'شرکت بزرگ',
                                    'non_profit' => 'غیرانتفاعی',
                                    'government' => 'دولتی',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <dd class="mt-1 text-sm text-gray-900">{{ $companyTypeLabels[$corporate->company_type] ?? ucfirst($corporate->company_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">اندازه شرکت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_size }} نفر</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">صنعت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->industry }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Company Address -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">آدرس شرکت</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">آدرس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">موقعیت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_city }}, {{ $corporate->company_state }}, {{ $corporate->company_country }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات تماس</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($corporate->company_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تلفن شرکت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_phone }}</dd>
                        </div>
                        @endif
                        @if($corporate->company_email)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل شرکت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->company_email }}</dd>
                        </div>
                        @endif
                        @if($corporate->company_website)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وب‌سایت شرکت</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ $corporate->company_website }}" target="_blank" class="text-orange-600 hover:text-orange-800">{{ $corporate->company_website }}</a>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Contact Person Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات شخص تماس</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام شخص تماس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->contact_person_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">سمت شخص تماس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->contact_person_title }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل شخص تماس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->contact_person_email }}</dd>
                        </div>
                        @if($corporate->contact_person_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تلفن شخص تماس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->contact_person_phone }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Sponsorship Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات حمایت</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع حمایت</dt>
                            @php
                                $sponsorshipTypeLabels = [
                                    'financial' => 'مالی',
                                    'product' => 'محصول',
                                    'service' => 'خدمات',
                                    'media' => 'رسانه',
                                    'event' => 'رویداد',
                                    'educational' => 'آموزشی',
                                    'research' => 'پژوهشی',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <dd class="mt-1 text-sm text-gray-900">{{ $sponsorshipTypeLabels[$corporate->sponsorship_type] ?? ucfirst($corporate->sponsorship_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مبلغ حمایت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($corporate->sponsorship_amount) }} تومان</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مدت حمایت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->sponsorship_duration }} {{ $corporate->sponsorship_duration_unit }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Benefits and Target Audience -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مزایا و مخاطب هدف</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مزایای ارائه شده</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->benefits_offered }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مخاطب هدف</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->target_audience }}</dd>
                        </div>
                        @if($corporate->marketing_materials)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مواد بازاریابی</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $corporate->marketing_materials }}</dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Verification Documents -->
            @if($corporate->verification_documents && count($corporate->verification_documents) > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اسناد تأیید</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($corporate->verification_documents as $index => $document)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">سند {{ $index + 1 }}</p>
                                        <p class="text-xs text-gray-500">{{ basename($document) }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($document) }}" target="_blank" class="text-orange-600 hover:text-orange-800 text-sm">مشاهده</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">وضعیت حمایت</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'active' => 'bg-green-100 text-green-800',
                                'suspended' => 'bg-red-100 text-red-800',
                                'expired' => 'bg-gray-100 text-gray-800'
                            ];
                            $statusLabels = [
                                'pending' => 'در انتظار',
                                'active' => 'فعال',
                                'suspended' => 'معلق',
                                'expired' => 'منقضی'
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$corporate->status] }}">
                                {{ $statusLabels[$corporate->status] }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تأیید شده</dt>
                        <dd class="mt-1">
                            @if($corporate->is_verified)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    تأیید شده
                                </span>
                                @if($corporate->verified_at)
                                    <p class="text-xs text-gray-500 mt-1">{{ $corporate->verified_at->format('Y/m/d H:i') }}</p>
                                @endif
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    تأیید نشده
                                </span>
                            @endif
                        </dd>
                    </div>

                    @if($corporate->expires_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ انقضا</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corporate->expires_at->format('Y/m/d') }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ ایجاد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corporate->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corporate->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if(!$corporate->is_verified)
                        <form method="POST" action="{{ route('admin.corporate.verify', $corporate) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید حمایت
                            </button>
                        </form>
                    @endif

                    @if($corporate->status === 'active')
                        <form method="POST" action="{{ route('admin.corporate.suspend', $corporate) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                تعلیق حمایت
                            </button>
                        </form>
                    @elseif($corporate->status === 'suspended')
                        <form method="POST" action="{{ route('admin.corporate.activate', $corporate) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                فعال‌سازی حمایت
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.corporate.destroy', $corporate) }}" class="w-full" onsubmit="return confirm('آیا از حذف این حمایت شرکتی اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف حمایت
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sponsorship Benefits -->
            @if($corporate->sponsorshipBenefits && $corporate->sponsorshipBenefits->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مزایای حمایت</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($corporate->sponsorshipBenefits as $benefit)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $benefit->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $benefit->description }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $benefit->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $benefit->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
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
