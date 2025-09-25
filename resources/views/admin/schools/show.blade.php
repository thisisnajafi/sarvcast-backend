@extends('admin.layouts.app')

@section('title', 'مشاهده مشارکت مدرسه')
@section('page-title', 'مشاهده مشارکت مدرسه')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $school->school_name }}</h1>
            <p class="text-gray-600">{{ $school->school_city }}, {{ $school->school_state }} - {{ $school->school_country }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.schools.edit', $school) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.schools.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت تأیید</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($school->is_verified)
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
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">دانش‌آموزان</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($school->student_count) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">معلمان</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $school->teacher_count }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">نرخ تخفیف</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $school->discount_rate }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- School Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات پایه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام مدرسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->school_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع مدرسه</dt>
                            @php
                                $schoolTypeLabels = [
                                    'public_school' => 'دولتی',
                                    'private_school' => 'خصوصی',
                                    'international_school' => 'بین‌المللی',
                                    'charter_school' => 'چارتر',
                                    'homeschool_coop' => 'تعاونی خانه‌آموزی',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <dd class="mt-1 text-sm text-gray-900">{{ $schoolTypeLabels[$school->school_type] ?? ucfirst($school->school_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">آدرس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->school_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">موقعیت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->school_city }}, {{ $school->school_state }}, {{ $school->school_country }}</dd>
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
                        @if($school->school_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تلفن مدرسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->school_phone }}</dd>
                        </div>
                        @endif
                        @if($school->school_email)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل مدرسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->school_email }}</dd>
                        </div>
                        @endif
                        @if($school->school_website)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وب‌سایت مدرسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ $school->school_website }}" target="_blank" class="text-green-600 hover:text-green-800">{{ $school->school_website }}</a>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Principal Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات مدیر</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام مدیر</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->principal_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل مدیر</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->principal_email }}</dd>
                        </div>
                        @if($school->principal_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تلفن مدیر</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->principal_phone }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Educational Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات آموزشی</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">سطوح تحصیلی</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->grade_levels }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع برنامه درسی</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->curriculum_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تعداد دانش‌آموزان</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($school->student_count) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تعداد معلمان</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $school->teacher_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Verification Documents -->
            @if($school->verification_documents && count($school->verification_documents) > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اسناد تأیید</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($school->verification_documents as $index => $document)
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
                                <a href="{{ Storage::url($document) }}" target="_blank" class="text-green-600 hover:text-green-800 text-sm">مشاهده</a>
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
                    <h2 class="text-lg font-medium text-gray-900">وضعیت مشارکت</h2>
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
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$school->status] }}">
                                {{ $statusLabels[$school->status] }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تأیید شده</dt>
                        <dd class="mt-1">
                            @if($school->is_verified)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    تأیید شده
                                </span>
                                @if($school->verified_at)
                                    <p class="text-xs text-gray-500 mt-1">{{ $school->verified_at->format('Y/m/d H:i') }}</p>
                                @endif
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    تأیید نشده
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">نوع مشارکت</dt>
                        @php
                            $partnershipLabels = [
                                'full_access' => 'دسترسی کامل',
                                'limited_access' => 'دسترسی محدود',
                                'trial_access' => 'دسترسی آزمایشی'
                            ];
                        @endphp
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $partnershipLabels[$school->partnership_type] ?? ucfirst($school->partnership_type) }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">نرخ تخفیف</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $school->discount_rate }}%</dd>
                    </div>

                    @if($school->expires_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ انقضا</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $school->expires_at->format('Y/m/d') }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ ایجاد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $school->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $school->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if(!$school->is_verified)
                        <form method="POST" action="{{ route('admin.schools.verify', $school) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید مشارکت
                            </button>
                        </form>
                    @endif

                    @if($school->status === 'active')
                        <form method="POST" action="{{ route('admin.schools.suspend', $school) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                تعلیق مشارکت
                            </button>
                        </form>
                    @elseif($school->status === 'suspended')
                        <form method="POST" action="{{ route('admin.schools.activate', $school) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                فعال‌سازی مشارکت
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" class="w-full" onsubmit="return confirm('آیا از حذف این مشارکت مدرسه اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف مشارکت
                        </button>
                    </form>
                </div>
            </div>

            <!-- Student Licenses -->
            @if($school->studentLicenses && $school->studentLicenses->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مجوزهای دانش‌آموز</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($school->studentLicenses as $license)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $license->student_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $license->created_at->format('Y/m/d') }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $license->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $license->is_active ? 'فعال' : 'غیرفعال' }}
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
