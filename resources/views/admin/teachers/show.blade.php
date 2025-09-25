@extends('admin.layouts.app')

@section('title', 'مشاهده معلم')
@section('page-title', 'مشاهده معلم')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $teacher->user->first_name }} {{ $teacher->user->last_name }}</h1>
            <p class="text-gray-600">{{ $teacher->institution_name }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.teachers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت تأیید</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($teacher->is_verified)
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">دانش‌آموزان فعلی</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $teacher->student_count }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">حداکثر مجوز</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $teacher->max_student_licenses }}</p>
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
                    <p class="text-lg font-semibold text-gray-900">{{ $teacher->discount_rate }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Teacher Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات پایه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام کامل</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->user->first_name }} {{ $teacher->user->last_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ایمیل</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شماره تلفن</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->user->phone_number ?? 'ثبت نشده' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تاریخ عضویت</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->user->created_at->format('Y/m/d') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Institution Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات مؤسسه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام مؤسسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->institution_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع مؤسسه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $teacher->institution_type)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">رشته تدریس</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->teaching_subject }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">سال‌های تجربه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->years_of_experience }} سال</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Certification Information -->
            @if($teacher->certification_number || $teacher->certification_authority || $teacher->certification_date)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات گواهینامه</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($teacher->certification_number)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شماره گواهینامه</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->certification_number }}</dd>
                        </div>
                        @endif
                        @if($teacher->certification_authority)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">مرجع صدور</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->certification_authority }}</dd>
                        </div>
                        @endif
                        @if($teacher->certification_date)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">تاریخ صدور</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $teacher->certification_date->format('Y/m/d') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            @endif

            <!-- Verification Documents -->
            @if($teacher->verification_documents && count($teacher->verification_documents) > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اسناد تأیید</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($teacher->verification_documents as $index => $document)
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
                                <a href="{{ Storage::url($document) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">مشاهده</a>
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
                    <h2 class="text-lg font-medium text-gray-900">وضعیت حساب</h2>
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
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$teacher->status] }}">
                                {{ $statusLabels[$teacher->status] }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تأیید شده</dt>
                        <dd class="mt-1">
                            @if($teacher->is_verified)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    تأیید شده
                                </span>
                                @if($teacher->verified_at)
                                    <p class="text-xs text-gray-500 mt-1">{{ $teacher->verified_at->format('Y/m/d H:i') }}</p>
                                @endif
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    تأیید نشده
                                </span>
                            @endif
                        </dd>
                    </div>

                    @if($teacher->expires_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ انقضا</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $teacher->expires_at->format('Y/m/d') }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">تاریخ ایجاد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $teacher->created_at->format('Y/m/d H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">آخرین به‌روزرسانی</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $teacher->updated_at->format('Y/m/d H:i') }}</dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if(!$teacher->is_verified)
                        <form method="POST" action="{{ route('admin.teachers.verify', $teacher) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید حساب
                            </button>
                        </form>
                    @endif

                    @if($teacher->status === 'active')
                        <form method="POST" action="{{ route('admin.teachers.suspend', $teacher) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                تعلیق حساب
                            </button>
                        </form>
                    @elseif($teacher->status === 'suspended')
                        <form method="POST" action="{{ route('admin.teachers.activate', $teacher) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                فعال‌سازی حساب
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="w-full" onsubmit="return confirm('آیا از حذف این معلم اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف حساب
                        </button>
                    </form>
                </div>
            </div>

            <!-- Student Licenses -->
            @if($teacher->studentLicenses && $teacher->studentLicenses->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مجوزهای دانش‌آموز</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($teacher->studentLicenses as $license)
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
