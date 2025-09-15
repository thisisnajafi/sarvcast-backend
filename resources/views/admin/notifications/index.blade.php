@extends('admin.layouts.app')

@section('title', 'مدیریت اعلان‌ها')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت اعلان‌ها</h1>
        <a href="{{ route('admin.notifications.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
            ارسال اعلان جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="جستجو در عنوان یا متن اعلان..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع اعلان</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه انواع</option>
                    <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>اطلاعاتی</option>
                    <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>موفقیت</option>
                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>هشدار</option>
                    <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>خطا</option>
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت خواندن</label>
                <select name="read" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه</option>
                    <option value="0" {{ request('read') === '0' ? 'selected' : '' }}>خوانده نشده</option>
                    <option value="1" {{ request('read') === '1' ? 'selected' : '' }}>خوانده شده</option>
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">کاربر</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه کاربران</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->first_name }} {{ $user->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    فیلتر
                </button>
                <a href="{{ route('admin.notifications.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition duration-200 mr-2">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">متن</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($notifications as $notification)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($notification->user->first_name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                                        </div>
                                        <div class="text-sm text-gray-500">{{ $notification->user->phone_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($notification->type === 'info') bg-blue-100 text-blue-800
                                    @elseif($notification->type === 'success') bg-green-100 text-green-800
                                    @elseif($notification->type === 'warning') bg-yellow-100 text-yellow-800
                                    @elseif($notification->type === 'error') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    @if($notification->type === 'info') اطلاعاتی
                                    @elseif($notification->type === 'success') موفقیت
                                    @elseif($notification->type === 'warning') هشدار
                                    @elseif($notification->type === 'error') خطا
                                    @else {{ $notification->type }} @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $notification->title }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate">{{ $notification->message }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($notification->read_at)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        خوانده شده
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        خوانده نشده
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $notification->created_at->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.notifications.show', $notification) }}" 
                                       class="text-primary hover:text-blue-600">مشاهده</a>
                                    
                                    @if(!$notification->read_at)
                                        <form method="POST" action="{{ route('admin.notifications.mark-read', $notification) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800">علامت‌گذاری خوانده شده</button>
                                        </form>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('admin.notifications.destroy', $notification) }}" class="inline" 
                                          onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این اعلان را حذف کنید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                هیچ اعلانی یافت نشد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
