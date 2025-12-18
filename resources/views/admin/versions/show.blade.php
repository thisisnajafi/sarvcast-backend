@extends('admin.layouts.app')

@section('title', 'مشاهده نسخه')
@section('page-title', 'مشاهده نسخه')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-medium text-gray-900">{{ $version->title }}</h1>
                    <p class="mt-1 text-sm text-gray-600">نسخه {{ $version->version }}</p>
                </div>
                <div class="flex space-x-3 space-x-reverse">
                    <a href="{{ route('admin.versions.edit', $version) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        ویرایش
                    </a>
                    <a href="{{ route('admin.versions.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        بازگشت
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Version Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">اطلاعات نسخه</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">نسخه:</span>
                            <span class="text-sm font-medium">{{ $version->version }}</span>
                        </div>
                        @if($version->build_number)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">شماره بیلد:</span>
                            <span class="text-sm font-medium">{{ $version->build_number }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">پلتفرم:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $version->platform_label }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">نوع به‌روزرسانی:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $version->update_type == 'forced' ? 'bg-red-100 text-red-800' : ($version->update_type == 'optional' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $version->update_type_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">وضعیت و تاریخ‌ها</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">وضعیت:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $version->status_badge_class }}">
                                {{ $version->status_label }}
                            </span>
                        </div>
                        @if($version->release_date)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تاریخ انتشار:</span>
                            <span class="text-sm font-medium">{{ $version->formatted_release_date }}</span>
                        </div>
                        @endif
                        @if($version->force_update_date)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تاریخ اجباری:</span>
                            <span class="text-sm font-medium">{{ $version->force_update_date }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">اولویت:</span>
                            <span class="text-sm font-medium">{{ $version->priority }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($version->description)
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">توضیحات</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-700">{{ $version->description }}</p>
                </div>
            </div>
            @endif

            <!-- Changelog -->
            @if($version->changelog)
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">تغییرات</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $version->changelog }}</div>
                </div>
            </div>
            @endif

            <!-- Update Notes -->
            @if($version->update_notes)
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">یادداشت‌های به‌روزرسانی</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $version->update_notes }}</div>
                </div>
            </div>
            @endif

            <!-- Technical Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($version->download_url)
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">لینک دانلود</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <a href="{{ $version->download_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm break-all">{{ $version->download_url }}</a>
                    </div>
                </div>
                @endif

                @if($version->minimum_os_version)
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">حداقل نسخه سیستم عامل</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-700">{{ $version->minimum_os_version }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Compatibility -->
            @if($version->compatibility && count($version->compatibility) > 0)
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">سازگاری</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex flex-wrap gap-2">
                        @foreach($version->compatibility as $platform)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ ucfirst($platform) }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if($version->metadata && count($version->metadata) > 0)
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">اطلاعات اضافی</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <pre class="text-sm text-gray-700">{{ json_encode($version->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            <!-- Timestamps -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">اطلاعات زمانی</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">ایجاد شده:</span>
                        <span class="text-sm font-medium">{{ $version->created_at->format('Y/m/d H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">آخرین به‌روزرسانی:</span>
                        <span class="text-sm font-medium">{{ $version->updated_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
