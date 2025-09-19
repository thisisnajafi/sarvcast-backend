@extends('admin.layouts.app')

@section('title', 'مدیریت هشدارهای سیستم')
@section('page-title', 'هشدارهای سیستم')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل هشدارها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['total'] }}</p>
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
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">بحرانی</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['critical'] }}</p>
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
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">هشدار</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['warning'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">اطلاعاتی</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['info'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">حل شده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['resolved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">حل نشده</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['unresolved'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت هشدارهای سیستم</h1>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.performance-monitoring.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                داشبورد
            </a>
            <a href="{{ route('admin.performance-monitoring.create-alert') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                ایجاد هشدار جدید
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان، پیام، منبع..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">شدت</label>
                <select name="severity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه سطوح</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>بحرانی</option>
                    <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>هشدار</option>
                    <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>اطلاعاتی</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>حل نشده</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>حل شده</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ از</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ تا</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div class="md:col-span-5 flex items-end space-x-4 space-x-reverse">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
                <a href="{{ route('admin.performance-monitoring.alerts') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition-colors">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="bulk-form" method="POST" action="{{ route('admin.performance-monitoring.bulk-action-alerts') }}">
            @csrf
            <div class="flex items-center space-x-4 space-x-reverse">
                <button type="button" onclick="selectAll()" class="text-indigo-600 hover:text-indigo-800 text-sm">انتخاب همه</button>
                <button type="button" onclick="deselectAll()" class="text-gray-600 hover:text-gray-800 text-sm">لغو انتخاب</button>
                <select name="action" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="resolve">حل کردن</option>
                    <option value="delete">حذف</option>
                </select>
                <button type="submit" onclick="return confirm('آیا از انجام این عملیات اطمینان دارید؟')" class="bg-indigo-600 text-white px-4 py-1 rounded-lg hover:bg-indigo-700 transition-colors">
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Alerts Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleAll()">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شدت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">منبع</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alerts as $alert)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="alert_ids[]" value="{{ $alert->id }}" class="alert-checkbox">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $alert->title }}</div>
                            <div class="text-sm text-gray-500 max-w-xs truncate">{{ $alert->message }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $severityColors = [
                                    'critical' => 'bg-red-100 text-red-800',
                                    'warning' => 'bg-yellow-100 text-yellow-800',
                                    'info' => 'bg-blue-100 text-blue-800',
                                ];
                                $severityLabels = [
                                    'critical' => 'بحرانی',
                                    'warning' => 'هشدار',
                                    'info' => 'اطلاعاتی',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $severityColors[$alert->severity] }}">
                                {{ $severityLabels[$alert->severity] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $alert->source }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'unresolved' => 'bg-red-100 text-red-800',
                                    'resolved' => 'bg-green-100 text-green-800',
                                ];
                                $statusLabels = [
                                    'unresolved' => 'حل نشده',
                                    'resolved' => 'حل شده',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$alert->status] }}">
                                {{ $statusLabels[$alert->status] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $alert->created_at->format('Y/m/d H:i') }}</div>
                            @if($alert->resolved_at)
                                <div class="text-xs text-gray-400">حل شده: {{ $alert->resolved_at->format('Y/m/d H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.performance-monitoring.show-alert', $alert) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                @if($alert->status === 'unresolved')
                                    <form method="POST" action="{{ route('admin.performance-monitoring.resolve-alert', $alert) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">حل کردن</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.performance-monitoring.destroy-alert', $alert) }}" class="inline" onsubmit="return confirm('آیا از حذف این هشدار اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">هیچ هشداری یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $alerts->links() }}
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.alert-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('select-all').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.alert-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all').checked = false;
}

function toggleAll() {
    const selectAll = document.getElementById('select-all');
    document.querySelectorAll('.alert-checkbox').forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Update bulk form with selected alert IDs
document.getElementById('bulk-form').addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.alert-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        e.preventDefault();
        alert('لطفاً حداقل یک هشدار را انتخاب کنید.');
        return;
    }
    
    // Add hidden inputs for selected alert IDs
    selectedCheckboxes.forEach(checkbox => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'alert_ids[]';
        hiddenInput.value = checkbox.value;
        this.appendChild(hiddenInput);
    });
});
</script>
@endsection
