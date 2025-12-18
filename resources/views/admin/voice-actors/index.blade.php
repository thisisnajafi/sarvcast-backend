@extends('admin.layouts.app')

@section('title', 'مدیریت صداپیشگان')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت صداپیشگان</h1>
        <a href="{{ route('admin.voice-actors.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
            افزودن صداپیشه جدید
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل صداپیشگان</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">تأیید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['verified'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">تأیید نشده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['unverified'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل اپیزودها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_episodes'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="جستجو در نام یا بیوگرافی صداپیشه..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت تأیید</label>
                <select name="verified" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>تأیید نشده</option>
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت فعال</label>
                <select name="active" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="1" {{ request('active') == '1' ? 'selected' : '' }}>فعال</option>
                    <option value="0" {{ request('active') == '0' ? 'selected' : '' }}>غیرفعال</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    فیلتر
                </button>
                <a href="{{ route('admin.voice-actors.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition duration-200 mr-2">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form id="bulkActionForm" method="POST" action="{{ route('admin.voice-actors.bulk-action') }}">
            @csrf
            <div class="flex items-center gap-4">
                <div class="flex items-center">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="selectAll" class="mr-2 text-sm text-gray-700">انتخاب همه</label>
                </div>
                
                <select name="action" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="verify">تأیید کردن</option>
                    <option value="unverify">لغو تأیید</option>
                    <option value="activate">فعال کردن</option>
                    <option value="deactivate">غیرفعال کردن</option>
                    <option value="delete">حذف</option>
                </select>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200" disabled>
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Voice Actors Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع صدا</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تخصص‌ها</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">آمار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($voiceActors as $voiceActor)
                        <tr class="hover:bg-gray-50" data-voice-actor-id="{{ $voiceActor->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="voice-actor-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="{{ $voiceActor->id }}">
                                @if($voiceActor->image_url)
                                    <img src="{{ $voiceActor->image_url }}" alt="{{ $voiceActor->name }}" class="w-12 h-12 object-cover rounded-lg mt-2">
                                @else
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mt-2">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $voiceActor->name }}</div>
                                @if($voiceActor->bio)
                                    <div class="text-sm text-gray-500 max-w-xs truncate">{{ Str::limit($voiceActor->bio, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $voiceActor->voice_type ?? 'ندارد' }}</div>
                                @if($voiceActor->voice_range)
                                    <div class="text-sm text-gray-500">{{ $voiceActor->voice_range }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($voiceActor->specialties)
                                        @foreach(array_slice($voiceActor->specialties, 0, 3) as $specialty)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ $specialty }}
                                            </span>
                                        @endforeach
                                        @if(count($voiceActor->specialties) > 3)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                +{{ count($voiceActor->specialties) - 3 }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-sm text-gray-500">ندارد</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full status-badge
                                        {{ $voiceActor->is_verified ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $voiceActor->is_verified ? 'تأیید شده' : 'تأیید نشده' }}
                                    </span>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $voiceActor->is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $voiceActor->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $voiceActor->total_episodes ?? 0 }} اپیزود</div>
                                @if($voiceActor->experience_years)
                                    <div>{{ $voiceActor->experience_years }} سال تجربه</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @jalali($voiceActor->created_at, 'Y/m/d')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.voice-actors.show', $voiceActor) }}" 
                                       class="text-primary hover:text-blue-600" data-action="view" data-voice-actor-id="{{ $voiceActor->id }}">مشاهده</a>
                                    <a href="{{ route('admin.voice-actors.edit', $voiceActor) }}" 
                                       class="text-green-600 hover:text-green-800" data-action="edit" data-voice-actor-id="{{ $voiceActor->id }}">ویرایش</a>
                                    <button class="text-yellow-600 hover:text-yellow-800" 
                                            data-action="verify" data-voice-actor-id="{{ $voiceActor->id }}">
                                        {{ $voiceActor->is_verified ? 'لغو تأیید' : 'تأیید' }}
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-800" 
                                            data-action="duplicate" data-voice-actor-id="{{ $voiceActor->id }}">کپی</button>
                                    <form method="POST" action="{{ route('admin.voice-actors.destroy', $voiceActor) }}" class="inline" 
                                          onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این صداپیشه را حذف کنید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" data-action="delete" data-voice-actor-id="{{ $voiceActor->id }}">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                هیچ صداپیشه‌ای یافت نشد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($voiceActors->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $voiceActors->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/admin/voice-actor-manager.js') }}"></script>
@endsection
