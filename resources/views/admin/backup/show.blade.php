@extends('admin.layouts.app')

@section('title', 'مشاهده پشتیبان')
@section('page-title', 'مشاهده پشتیبان')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده پشتیبان</h1>
            <p class="text-gray-600">{{ $backup->name }}</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            @if($backup->status === 'completed')
                <a href="{{ route('admin.backup.download', $backup) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    دانلود
                </a>
                <form method="POST" action="{{ route('admin.backup.restore', $backup) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        بازیابی
                    </button>
                </form>
            @endif
            @if($backup->status === 'in_progress')
                <form method="POST" action="{{ route('admin.backup.cancel', $backup) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        لغو
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.backup.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                بازگشت
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-teal-100 rounded-lg">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">وضعیت</p>
                    @php
                        $statusColors = [
                            'pending' => 'text-yellow-600',
                            'in_progress' => 'text-blue-600',
                            'completed' => 'text-green-600',
                            'failed' => 'text-red-600',
                            'cancelled' => 'text-gray-600',
                            'restoring' => 'text-purple-600'
                        ];
                        $statusLabels = [
                            'pending' => 'در انتظار',
                            'in_progress' => 'در حال انجام',
                            'completed' => 'تکمیل شده',
                            'failed' => 'ناموفق',
                            'cancelled' => 'لغو شده',
                            'restoring' => 'در حال بازیابی'
                        ];
                    @endphp
                    <p class="text-lg font-semibold {{ $statusColors[$backup->status] }}">{{ $statusLabels[$backup->status] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">نوع</p>
                    @php
                        $typeLabels = [
                            'database' => 'پایگاه داده',
                            'files' => 'فایل‌ها',
                            'full' => 'کامل',
                        ];
                    @endphp
                    <p class="text-lg font-semibold text-gray-900">{{ $typeLabels[$backup->type] ?? ucfirst($backup->type) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">اندازه</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($backup->size)
                            {{ $this->formatBytes($backup->size) }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">مدت زمان</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($backup->completed_at)
                            {{ $backup->created_at->diffForHumans($backup->completed_at, true) }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Backup Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Backup Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">جزئیات پشتیبان</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نام پشتیبان</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $backup->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع</dt>
                            <dd class="mt-1">
                                @php
                                    $typeColors = [
                                        'database' => 'bg-purple-100 text-purple-800',
                                        'files' => 'bg-yellow-100 text-yellow-800',
                                        'full' => 'bg-indigo-100 text-indigo-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $typeColors[$backup->type] }}">
                                    {{ $typeLabels[$backup->type] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">اندازه فایل</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($backup->size)
                                    {{ $this->formatBytes($backup->size) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">وضعیت</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'restoring' => 'bg-purple-100 text-purple-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$backup->status] }}">
                                    {{ $statusLabels[$backup->status] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">فشرده‌سازی</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $backup->compression ? 'فعال' : 'غیرفعال' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">رمزگذاری</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $backup->encryption ? 'فعال' : 'غیرفعال' }}</dd>
                        </div>
                        @if($backup->schedule)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">برنامه‌ریزی</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @php
                                    $scheduleLabels = [
                                        'daily' => 'روزانه',
                                        'weekly' => 'هفتگی',
                                        'monthly' => 'ماهانه',
                                    ];
                                @endphp
                                {{ $scheduleLabels[$backup->schedule] ?? $backup->schedule }}
                            </dd>
                        </div>
                        @endif
                        @if($backup->is_automatic)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">نوع ایجاد</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    خودکار
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Description -->
            @if($backup->description)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">توضیحات</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 leading-relaxed">{{ $backup->description }}</p>
                </div>
            </div>
            @endif

            <!-- File Configuration -->
            @if($backup->include_files || $backup->exclude_files)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">تنظیمات فایل</h2>
                </div>
                <div class="p-6">
                    @if($backup->include_files)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">شامل فایل‌ها</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            @php
                                $includeFiles = json_decode($backup->include_files, true);
                            @endphp
                            @if($includeFiles)
                                @foreach($includeFiles as $file)
                                    <div class="text-sm text-gray-600">{{ $file }}</div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($backup->exclude_files)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">حذف فایل‌ها</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            @php
                                $excludeFiles = json_decode($backup->exclude_files, true);
                            @endphp
                            @if($excludeFiles)
                                @foreach($excludeFiles as $file)
                                    <div class="text-sm text-gray-600">{{ $file }}</div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Error Information -->
            @if($backup->status === 'failed' && $backup->error_message)
            <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-400">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-red-900">خطا</h2>
                </div>
                <div class="p-6">
                    <p class="text-red-800">{{ $backup->error_message }}</p>
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
                                <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">شروع پشتیبان‌گیری</p>
                                <p class="text-xs text-gray-500">{{ $backup->created_at->format('Y/m/d H:i:s') }}</p>
                            </div>
                        </div>

                        @if($backup->completed_at)
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 {{ $backup->status === 'completed' ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 {{ $backup->status === 'completed' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">تکمیل پشتیبان‌گیری</p>
                                <p class="text-xs text-gray-500">{{ $backup->completed_at->format('Y/m/d H:i:s') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($backup->restored_at)
                        <div class="flex items-end space-x-3 space-x-reverse">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">بازیابی انجام شد</p>
                                <p class="text-xs text-gray-500">{{ $backup->restored_at->format('Y/m/d H:i:s') }}</p>
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
                    @if($backup->status === 'completed')
                        <a href="{{ route('admin.backup.download', $backup) }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors block text-center">
                            دانلود پشتیبان
                        </a>

                        <form method="POST" action="{{ route('admin.backup.restore', $backup) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                بازیابی از پشتیبان
                            </button>
                        </form>
                    @endif

                    @if($backup->status === 'in_progress')
                        <form method="POST" action="{{ route('admin.backup.cancel', $backup) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                لغو پشتیبان‌گیری
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.backup.destroy', $backup) }}" class="w-full" onsubmit="return confirm('آیا از حذف این پشتیبان اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            حذف پشتیبان
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backup Logs -->
            @if($logs && count($logs) > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">لاگ‌ها</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($logs as $log)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-900">{{ $log['message'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ $log['time']->format('H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Related Backups -->
            @if($relatedBackups && $relatedBackups->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">پشتیبان‌های مرتبط</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($relatedBackups as $relatedBackup)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $relatedBackup->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $relatedBackup->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $relatedBackup->status === 'completed' ? 'bg-green-100 text-green-800' : ($relatedBackup->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $statusLabels[$relatedBackup->status] }}
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

@php
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
@endphp
