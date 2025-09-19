@extends('admin.layouts.app')

@section('title', 'مدیریت کمپین‌های اینفلوئنسر')
@section('page-title', 'کمپین‌های اینفلوئنسر')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V3a1 1 0 011 1v16a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1h8zM9 8h6m-6 4h6m-6 4h4"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل کمپین‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
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
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">تأیید شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['verified'] }}</p>
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
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">در انتظار</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل فالوورها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_followers']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت کمپین‌های اینفلوئنسر</h1>
        <a href="{{ route('admin.influencers.create') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
            <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            افزودن کمپین جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در نام، ایمیل یا نام کاربری..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منقضی</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">پلتفرم</label>
                <select name="platform" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه پلتفرم‌ها</option>
                    <option value="instagram" {{ request('platform') == 'instagram' ? 'selected' : '' }}>اینستاگرام</option>
                    <option value="youtube" {{ request('platform') == 'youtube' ? 'selected' : '' }}>یوتیوب</option>
                    <option value="tiktok" {{ request('platform') == 'tiktok' ? 'selected' : '' }}>تیک‌تاک</option>
                    <option value="twitter" {{ request('platform') == 'twitter' ? 'selected' : '' }}>توییتر</option>
                    <option value="facebook" {{ request('platform') == 'facebook' ? 'selected' : '' }}>فیس‌بوک</option>
                    <option value="linkedin" {{ request('platform') == 'linkedin' ? 'selected' : '' }}>لینکدین</option>
                    <option value="other" {{ request('platform') == 'other' ? 'selected' : '' }}>سایر</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تأیید شده</label>
                <select name="is_verified" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="1" {{ request('is_verified') == '1' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="0" {{ request('is_verified') == '0' ? 'selected' : '' }}>تأیید نشده</option>
                </select>
            </div>
            
            <div class="md:col-span-4 flex items-end space-x-4 space-x-reverse">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
                <a href="{{ route('admin.influencers.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition-colors">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="bulk-form" method="POST" action="{{ route('admin.influencers.bulk-action') }}">
            @csrf
            <div class="flex items-center space-x-4 space-x-reverse">
                <button type="button" onclick="selectAll()" class="text-purple-600 hover:text-purple-800 text-sm">انتخاب همه</button>
                <button type="button" onclick="deselectAll()" class="text-gray-600 hover:text-gray-800 text-sm">لغو انتخاب</button>
                <select name="action" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="verify">تأیید</option>
                    <option value="suspend">تعلیق</option>
                    <option value="activate">فعال‌سازی</option>
                    <option value="delete">حذف</option>
                </select>
                <button type="submit" onclick="return confirm('آیا از انجام این عملیات اطمینان دارید؟')" class="bg-red-600 text-white px-4 py-1 rounded-lg hover:bg-red-700 transition-colors">
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Influencers Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleAll()">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اینفلوئنسر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کمپین</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">پلتفرم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">فالوورها</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">درگیری</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تأیید</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($influencers as $influencer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="influencer_ids[]" value="{{ $influencer->id }}" class="influencer-checkbox">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                        <span class="text-purple-600 font-medium">{{ substr($influencer->user->first_name ?? 'م', 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="mr-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $influencer->user->first_name }} {{ $influencer->user->last_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $influencer->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $influencer->campaign_name }}</div>
                            <div class="text-sm text-gray-500">{{ $influencer->platform_username }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $platformLabels = [
                                    'instagram' => 'اینستاگرام',
                                    'youtube' => 'یوتیوب',
                                    'tiktok' => 'تیک‌تاک',
                                    'twitter' => 'توییتر',
                                    'facebook' => 'فیس‌بوک',
                                    'linkedin' => 'لینکدین',
                                    'other' => 'سایر'
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                {{ $platformLabels[$influencer->platform] ?? ucfirst($influencer->platform) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($influencer->follower_count) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $influencer->engagement_rate }}%</td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$influencer->status] }}">
                                {{ $statusLabels[$influencer->status] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($influencer->is_verified)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    تأیید شده
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    تأیید نشده
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.influencers.show', $influencer) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                <a href="{{ route('admin.influencers.edit', $influencer) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                @if(!$influencer->is_verified)
                                    <form method="POST" action="{{ route('admin.influencers.verify', $influencer) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">تأیید</button>
                                    </form>
                                @endif
                                @if($influencer->status === 'active')
                                    <form method="POST" action="{{ route('admin.influencers.suspend', $influencer) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900">تعلیق</button>
                                    </form>
                                @elseif($influencer->status === 'suspended')
                                    <form method="POST" action="{{ route('admin.influencers.activate', $influencer) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">فعال</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.influencers.destroy', $influencer) }}" class="inline" onsubmit="return confirm('آیا از حذف این کمپین اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">هیچ کمپین اینفلوئنسری یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $influencers->links() }}
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('select-all').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all').checked = false;
}

function toggleAll() {
    const selectAll = document.getElementById('select-all');
    document.querySelectorAll('.influencer-checkbox').forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Update bulk form with selected influencer IDs
document.getElementById('bulk-form').addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.influencer-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        e.preventDefault();
        alert('لطفاً حداقل یک کمپین را انتخاب کنید.');
        return;
    }
    
    // Add hidden inputs for selected influencer IDs
    selectedCheckboxes.forEach(checkbox => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'influencer_ids[]';
        hiddenInput.value = checkbox.value;
        this.appendChild(hiddenInput);
    });
});
</script>
@endsection
