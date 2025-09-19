@extends('admin.layouts.app')

@section('title', 'مدیریت سیستم معرفی')
@section('page-title', 'سیستم معرفی')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل معرفی‌ها</p>
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
                <div class="mr-4">
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
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">تکمیل شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل پاداش‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_rewards']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت سیستم معرفی</h1>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.referrals.statistics') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                آمار و گزارش‌ها
            </a>
            <a href="{{ route('admin.referrals.create') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                افزودن معرفی جدید
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در کد معرفی، نام معرف..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منقضی شده</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع معرفی</label>
                <select name="referral_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه انواع</option>
                    <option value="user_registration" {{ request('referral_type') == 'user_registration' ? 'selected' : '' }}>ثبت‌نام کاربر</option>
                    <option value="subscription_purchase" {{ request('referral_type') == 'subscription_purchase' ? 'selected' : '' }}>خرید اشتراک</option>
                    <option value="content_engagement" {{ request('referral_type') == 'content_engagement' ? 'selected' : '' }}>تعامل با محتوا</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت پاداش</label>
                <select name="reward_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" {{ request('reward_status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="paid" {{ request('reward_status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                    <option value="cancelled" {{ request('reward_status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ از</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ تا</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div class="md:col-span-5 flex items-end space-x-4 space-x-reverse">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
                <a href="{{ route('admin.referrals.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition-colors">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="bulk-form" method="POST" action="{{ route('admin.referrals.bulk-action') }}">
            @csrf
            <div class="flex items-center space-x-4 space-x-reverse">
                <button type="button" onclick="selectAll()" class="text-purple-600 hover:text-purple-800 text-sm">انتخاب همه</button>
                <button type="button" onclick="deselectAll()" class="text-gray-600 hover:text-gray-800 text-sm">لغو انتخاب</button>
                <select name="action" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="approve">تأیید</option>
                    <option value="reject">رد</option>
                    <option value="pay_rewards">پرداخت پاداش</option>
                    <option value="delete">حذف</option>
                </select>
                <button type="submit" onclick="return confirm('آیا از انجام این عملیات اطمینان دارید؟')" class="bg-purple-600 text-white px-4 py-1 rounded-lg hover:bg-purple-700 transition-colors">
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleAll()">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کد معرفی</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">معرف</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ایمیل معرفی شده</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">پاداش</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($referrals as $referral)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="referral_ids[]" value="{{ $referral->id }}" class="referral-checkbox">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $referral->referral_code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($referral->referrer)
                                <div class="text-sm text-gray-900">{{ $referral->referrer->first_name }} {{ $referral->referrer->last_name }}</div>
                                <div class="text-sm text-gray-500">{{ $referral->referrer->email }}</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $referral->referred_email }}</div>
                            @if($referral->referred)
                                <div class="text-sm text-gray-500">{{ $referral->referred->first_name }} {{ $referral->referred->last_name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $typeLabels = [
                                    'user_registration' => 'ثبت‌نام کاربر',
                                    'subscription_purchase' => 'خرید اشتراک',
                                    'content_engagement' => 'تعامل با محتوا',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                {{ $typeLabels[$referral->referral_type] ?? ucfirst($referral->referral_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($referral->reward_amount) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-1">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'expired' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'در انتظار',
                                        'completed' => 'تکمیل شده',
                                        'expired' => 'منقضی شده',
                                        'cancelled' => 'لغو شده'
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$referral->status] }}">
                                    {{ $statusLabels[$referral->status] }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    @php
                                        $rewardStatusColors = [
                                            'pending' => 'text-yellow-600',
                                            'paid' => 'text-green-600',
                                            'cancelled' => 'text-gray-600'
                                        ];
                                        $rewardStatusLabels = [
                                            'pending' => 'پاداش: در انتظار',
                                            'paid' => 'پاداش: پرداخت شده',
                                            'cancelled' => 'پاداش: لغو شده'
                                        ];
                                    @endphp
                                    <span class="{{ $rewardStatusColors[$referral->reward_status] }}">
                                        {{ $rewardStatusLabels[$referral->reward_status] }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $referral->created_at->format('Y/m/d H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.referrals.show', $referral) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                <a href="{{ route('admin.referrals.edit', $referral) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                @if($referral->status === 'pending')
                                    <form method="POST" action="{{ route('admin.referrals.approve', $referral) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">تأیید</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.referrals.reject', $referral) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">رد</button>
                                    </form>
                                @endif
                                @if($referral->reward_status === 'pending' && $referral->status === 'completed')
                                    <form method="POST" action="{{ route('admin.referrals.pay-reward', $referral) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-purple-600 hover:text-purple-900">پرداخت پاداش</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.referrals.destroy', $referral) }}" class="inline" onsubmit="return confirm('آیا از حذف این معرفی اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">هیچ معرفی یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $referrals->links() }}
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('select-all').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all').checked = false;
}

function toggleAll() {
    const selectAll = document.getElementById('select-all');
    document.querySelectorAll('.referral-checkbox').forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Update bulk form with selected referral IDs
document.getElementById('bulk-form').addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.referral-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        e.preventDefault();
        alert('لطفاً حداقل یک معرفی را انتخاب کنید.');
        return;
    }
    
    // Add hidden inputs for selected referral IDs
    selectedCheckboxes.forEach(checkbox => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'referral_ids[]';
        hiddenInput.value = checkbox.value;
        this.appendChild(hiddenInput);
    });
});
</script>
@endsection
