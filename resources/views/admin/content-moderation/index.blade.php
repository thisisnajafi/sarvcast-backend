@extends('admin.layouts.app')

@section('title', 'مدیریت نظارت بر محتوا')
@section('page-title', 'نظارت بر محتوا')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل گزارش‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">در انتظار</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending'] }}</p>
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
                    <p class="text-sm font-medium text-gray-600">تأیید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['approved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">رد شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['rejected'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Severity Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">شدت بالا</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['high_severity'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">شدت متوسط</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['medium_severity'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">شدت کم</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['low_severity'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت نظارت بر محتوا</h1>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.content-moderation.statistics') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                آمار و گزارش‌ها
            </a>
            <a href="{{ route('admin.content-moderation.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                افزودن گزارش جدید
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در دلیل، یادداشت، کاربر..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>رد شده</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع محتوا</label>
                <select name="content_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">همه انواع</option>
                    <option value="story" {{ request('content_type') == 'story' ? 'selected' : '' }}>داستان</option>
                    <option value="episode" {{ request('content_type') == 'episode' ? 'selected' : '' }}>اپیزود</option>
                    <option value="comment" {{ request('content_type') == 'comment' ? 'selected' : '' }}>نظر</option>
                    <option value="review" {{ request('content_type') == 'review' ? 'selected' : '' }}>بررسی</option>
                    <option value="user_profile" {{ request('content_type') == 'user_profile' ? 'selected' : '' }}>پروفایل کاربر</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">شدت</label>
                <select name="severity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">همه سطوح</option>
                    <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>کم</option>
                    <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>متوسط</option>
                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>زیاد</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ناظر</label>
                <select name="moderator_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">همه ناظران</option>
                    @foreach($moderators as $moderator)
                        <option value="{{ $moderator->id }}" {{ request('moderator_id') == $moderator->id ? 'selected' : '' }}>{{ $moderator->first_name }} {{ $moderator->last_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ از</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ تا</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>
            
            <div class="md:col-span-6 flex items-end space-x-4 space-x-reverse">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
                <a href="{{ route('admin.content-moderation.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition-colors">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="bulk-form" method="POST" action="{{ route('admin.content-moderation.bulk-action') }}">
            @csrf
            <div class="flex items-center space-x-4 space-x-reverse">
                <button type="button" onclick="selectAll()" class="text-orange-600 hover:text-orange-800 text-sm">انتخاب همه</button>
                <button type="button" onclick="deselectAll()" class="text-gray-600 hover:text-gray-800 text-sm">لغو انتخاب</button>
                <select name="action" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="approve">تأیید</option>
                    <option value="reject">رد</option>
                    <option value="delete">حذف</option>
                </select>
                <button type="submit" onclick="return confirm('آیا از انجام این عملیات اطمینان دارید؟')" class="bg-orange-600 text-white px-4 py-1 rounded-lg hover:bg-orange-700 transition-colors">
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Content Moderation Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleAll()">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع محتوا</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دلیل</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شدت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ناظر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($moderations as $moderation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="moderation_ids[]" value="{{ $moderation->id }}" class="moderation-checkbox">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($moderation->user)
                                <div class="text-sm text-gray-900">{{ $moderation->user->first_name }} {{ $moderation->user->last_name }}</div>
                                <div class="text-sm text-gray-500">{{ $moderation->user->email }}</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $typeLabels = [
                                    'story' => 'داستان',
                                    'episode' => 'اپیزود',
                                    'comment' => 'نظر',
                                    'review' => 'بررسی',
                                    'user_profile' => 'پروفایل کاربر',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                {{ $typeLabels[$moderation->content_type] ?? ucfirst($moderation->content_type) }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">ID: {{ $moderation->content_id }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">{{ $moderation->reason }}</div>
                            @if($moderation->notes)
                                <div class="text-xs text-gray-500 max-w-xs truncate">{{ $moderation->notes }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $severityColors = [
                                    'low' => 'bg-green-100 text-green-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'high' => 'bg-red-100 text-red-800'
                                ];
                                $severityLabels = [
                                    'low' => 'کم',
                                    'medium' => 'متوسط',
                                    'high' => 'زیاد'
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $severityColors[$moderation->severity] }}">
                                {{ $severityLabels[$moderation->severity] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                $statusLabels = [
                                    'pending' => 'در انتظار',
                                    'approved' => 'تأیید شده',
                                    'rejected' => 'رد شده'
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$moderation->status] }}">
                                {{ $statusLabels[$moderation->status] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($moderation->moderator)
                                {{ $moderation->moderator->first_name }} {{ $moderation->moderator->last_name }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $moderation->created_at->format('Y/m/d H:i') }}</div>
                            @if($moderation->moderated_at)
                                <div class="text-xs text-gray-400">{{ $moderation->moderated_at->format('Y/m/d H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.content-moderation.show', $moderation) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                <a href="{{ route('admin.content-moderation.edit', $moderation) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                @if($moderation->status === 'pending')
                                    <form method="POST" action="{{ route('admin.content-moderation.approve', $moderation) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">تأیید</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.content-moderation.reject', $moderation) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">رد</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.content-moderation.destroy', $moderation) }}" class="inline" onsubmit="return confirm('آیا از حذف این آیتم اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">هیچ آیتم نظارت بر محتوایی یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $moderations->links() }}
        </div>
    </div>
</div>

<script src="{{ asset('js/admin/content-moderation-manager.js') }}"></script>
@endsection
