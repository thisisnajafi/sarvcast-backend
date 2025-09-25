@extends('admin.layouts.app')

@section('title', 'مشاهده گزارش نظارت بر محتوا')
@section('page-title', 'مشاهده گزارش')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده گزارش نظارت بر محتوا</h1>
            <p class="text-gray-600">گزارش #{{ $contentModeration->id }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.content-moderation.edit', $contentModeration) }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                ویرایش
            </a>
            <a href="{{ route('admin.content-moderation.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
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
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    @php
                        $statusColors = [
                            'pending' => 'text-yellow-600',
                            'approved' => 'text-green-600',
                            'rejected' => 'text-red-600'
                        ];
                        $statusLabels = [
                            'pending' => 'در انتظار',
                            'approved' => 'تأیید شده',
                            'rejected' => 'رد شده'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $statusColors[$contentModeration->status] }}">{{ $statusLabels[$contentModeration->status] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">نوع محتوا</p>
                    @php
                        $typeLabels = [
                            'story' => 'داستان',
                            'episode' => 'اپیزود',
                            'comment' => 'نظر',
                            'review' => 'بررسی',
                            'user_profile' => 'پروفایل کاربر',
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $typeLabels[$contentModeration->content_type] ?? ucfirst($contentModeration->content_type) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">شدت مشکل</p>
                    @php
                        $severityLabels = [
                            'low' => 'کم',
                            'medium' => 'متوسط',
                            'high' => 'زیاد'
                        ];
                        $severityColors = [
                            'low' => 'text-green-600',
                            'medium' => 'text-yellow-600',
                            'high' => 'text-red-600'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $severityColors[$contentModeration->severity] }}">{{ $severityLabels[$contentModeration->severity] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">شناسه محتوا</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $contentModeration->content_id }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Report Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Report Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات گزارش</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">دلیل گزارش</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contentModeration->reason }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع محتوا</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                    {{ $typeLabels[$contentModeration->content_type] ?? ucfirst($contentModeration->content_type) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شناسه محتوا</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contentModeration->content_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">شدت مشکل</dt>
                            <dd class="mt-1">
                                @php
                                    $severityColors = [
                                        'low' => 'bg-green-100 text-green-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'high' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $severityColors[$contentModeration->severity] }}">
                                    {{ $severityLabels[$contentModeration->severity] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$contentModeration->status] }}">
                                    {{ $statusLabels[$contentModeration->status] }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Reporter Information -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">اطلاعات گزارش‌دهنده</h2>
                </div>
                <div class="p-6">
                    @if($contentModeration->user)
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-medium text-lg">
                                        {{ substr($contentModeration->user->first_name, 0, 1) }}{{ substr($contentModeration->user->last_name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">{{ $contentModeration->user->first_name }} {{ $contentModeration->user->last_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $contentModeration->user->email }}</p>
                                <p class="text-sm text-gray-500">تاریخ عضویت: {{ $contentModeration->user->created_at->format('Y/m/d') }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">کاربر یافت نشد.</p>
                    @endif
                </div>
            </div>

            <!-- Content Information -->
            @if($contentModeration->story || $contentModeration->episode)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">مربوط به محتوا</h2>
                </div>
                <div class="p-6">
                    @if($contentModeration->story)
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-1">داستان</h3>
                            <p class="text-gray-900">{{ $contentModeration->story->title }}</p>
                        </div>
                    @endif

                    @if($contentModeration->episode)
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-1">اپیزود</h3>
                            <p class="text-gray-900">{{ $contentModeration->episode->title }}</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($contentModeration->notes)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">یادداشت‌ها</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $contentModeration->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Evidence Files -->
            @if($contentModeration->evidence_files)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">فایل‌های مدرک</h2>
                </div>
                <div class="p-6">
                    @php
                        $evidenceFiles = json_decode($contentModeration->evidence_files, true);
                    @endphp
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($evidenceFiles as $file)
                        <div class="border border-gray-200 rounded-lg p-3">
                            @if(pathinfo($file, PATHINFO_EXTENSION) === 'pdf' || in_array(pathinfo($file, PATHINFO_EXTENSION), ['doc', 'docx']))
                                <div class="w-full h-32 bg-gray-100 rounded flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            @else
                                <img src="{{ Storage::url($file) }}" alt="Evidence" class="w-full h-32 object-cover rounded">
                            @endif
                            <p class="text-xs text-gray-600 mt-2 truncate">{{ basename($file) }}</p>
                            <a href="{{ Storage::url($file) }}" target="_blank" class="text-xs text-orange-600 hover:text-orange-800 mt-1 block">مشاهده فایل</a>
                        </div>
                        @endforeach
                    </div>
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
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">ایجاد گزارش</p>
                                <p class="text-xs text-gray-500">{{ $contentModeration->created_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>

                        @if($contentModeration->moderated_at)
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 {{ $contentModeration->status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 {{ $contentModeration->status === 'approved' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">نظارت انجام شد</p>
                                <p class="text-xs text-gray-500">{{ $contentModeration->moderated_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">عملیات سریع</h2>
                </div>
                <div class="p-6 space-y-3">
                    @if($contentModeration->status === 'pending')
                        <form method="POST" action="{{ route('admin.content-moderation.approve', $contentModeration) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                تأیید گزارش
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.content-moderation.reject', $contentModeration) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                رد گزارش
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.content-moderation.destroy', $contentModeration) }}" class="w-full" onsubmit="return confirm('آیا از حذف این گزارش اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف گزارش
                        </button>
                    </form>
                </div>
            </div>

            <!-- Moderator Information -->
            @if($contentModeration->moderator)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">ناظر</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="text-purple-600 font-medium text-sm">
                                    {{ substr($contentModeration->moderator->first_name, 0, 1) }}{{ substr($contentModeration->moderator->last_name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">{{ $contentModeration->moderator->first_name }} {{ $contentModeration->moderator->last_name }}</h3>
                            <p class="text-xs text-gray-500">{{ $contentModeration->moderator->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Related Reports -->
            @if($relatedModerations && $relatedModerations->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">گزارش‌های مرتبط</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($relatedModerations as $relatedModeration)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $relatedModeration->reason }}</p>
                                    <p class="text-xs text-gray-500">{{ $relatedModeration->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $relatedModeration->status === 'approved' ? 'bg-green-100 text-green-800' : ($relatedModeration->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $statusLabels[$relatedModeration->status] }}
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
