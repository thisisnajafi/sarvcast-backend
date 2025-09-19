@extends('admin.layouts.app')

@section('title', 'مدیریت حساب‌های معلمان')
@section('page-title', 'حساب‌های معلمان')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل معلمان</p>
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
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">کل دانش‌آموزان</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_students'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت حساب‌های معلمان</h1>
        <a href="{{ route('admin.teachers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            افزودن معلم جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در نام، ایمیل یا نام مؤسسه..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منقضی</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع مؤسسه</label>
                <select name="institution_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه انواع</option>
                    <option value="public_school" {{ request('institution_type') == 'public_school' ? 'selected' : '' }}>مدرسه دولتی</option>
                    <option value="private_school" {{ request('institution_type') == 'private_school' ? 'selected' : '' }}>مدرسه خصوصی</option>
                    <option value="university" {{ request('institution_type') == 'university' ? 'selected' : '' }}>دانشگاه</option>
                    <option value="college" {{ request('institution_type') == 'college' ? 'selected' : '' }}>کالج</option>
                    <option value="homeschool" {{ request('institution_type') == 'homeschool' ? 'selected' : '' }}>خانه‌آموزی</option>
                    <option value="other" {{ request('institution_type') == 'other' ? 'selected' : '' }}>سایر</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تأیید شده</label>
                <select name="is_verified" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="1" {{ request('is_verified') == '1' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="0" {{ request('is_verified') == '0' ? 'selected' : '' }}>تأیید نشده</option>
                </select>
            </div>
            
            <div class="md:col-span-4 flex items-end space-x-4 space-x-reverse">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
                <a href="{{ route('admin.teachers.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition-colors">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="bulk-form" method="POST" action="{{ route('admin.teachers.bulk-action') }}">
            @csrf
            <div class="flex items-center space-x-4 space-x-reverse">
                <button type="button" onclick="selectAll()" class="text-blue-600 hover:text-blue-800 text-sm">انتخاب همه</button>
                <button type="button" onclick="deselectAll()" class="text-gray-600 hover:text-gray-800 text-sm">لغو انتخاب</button>
                <select name="action" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

    <!-- Teachers Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleAll()">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">معلم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مؤسسه</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">رشته تدریس</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دانش‌آموزان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تأیید</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($teachers as $teacher)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" class="teacher-checkbox">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium">{{ substr($teacher->user->first_name ?? 'م', 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="mr-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $teacher->user->first_name }} {{ $teacher->user->last_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $teacher->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $teacher->institution_name }}</div>
                            <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $teacher->institution_type)) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $teacher->teaching_subject }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $teacher->student_count }}</td>
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
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$teacher->status] }}">
                                {{ $statusLabels[$teacher->status] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($teacher->is_verified)
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
                                <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                @if(!$teacher->is_verified)
                                    <form method="POST" action="{{ route('admin.teachers.verify', $teacher) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">تأیید</button>
                                    </form>
                                @endif
                                @if($teacher->status === 'active')
                                    <form method="POST" action="{{ route('admin.teachers.suspend', $teacher) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900">تعلیق</button>
                                    </form>
                                @elseif($teacher->status === 'suspended')
                                    <form method="POST" action="{{ route('admin.teachers.activate', $teacher) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">فعال</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="inline" onsubmit="return confirm('آیا از حذف این معلم اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">هیچ معلمی یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $teachers->links() }}
        </div>
    </div>
</div>

<script>
// Enhanced Teacher Management JavaScript Functionality
class TeacherManager {
    constructor() {
        this.selectedTeachers = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeUpdates();
        this.setupKeyboardShortcuts();
    }

    setupEventListeners() {
        // Bulk form submission
        document.getElementById('bulk-form').addEventListener('submit', (e) => this.handleBulkAction(e));
        
        // Search with debounce
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => this.handleSearch(e), 300));
        }

        // Individual teacher actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="verify"], [data-action="suspend"], [data-action="activate"], [data-action="delete"]')) {
                e.preventDefault();
                this.handleTeacherAction(e);
            }
        });
    }

    setupRealTimeUpdates() {
        // Auto-refresh statistics every 30 seconds
        setInterval(() => this.updateStatistics(), 30000);
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                this.selectAll();
            }
            // Escape to deselect all
            if (e.key === 'Escape') {
                this.deselectAll();
            }
        });
    }

    // Selection functions
    selectAll() {
        document.querySelectorAll('.teacher-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            this.selectedTeachers.add(checkbox.value);
        });
        document.getElementById('select-all').checked = true;
        this.updateBulkActionUI();
        this.showToast('همه معلمان انتخاب شدند', 'success');
    }

    deselectAll() {
        document.querySelectorAll('.teacher-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('select-all').checked = false;
        this.selectedTeachers.clear();
        this.updateBulkActionUI();
        this.showToast('انتخاب لغو شد', 'info');
    }

    toggleAll() {
        const selectAll = document.getElementById('select-all');
        document.querySelectorAll('.teacher-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                this.selectedTeachers.add(checkbox.value);
            } else {
                this.selectedTeachers.delete(checkbox.value);
            }
        });
        this.updateBulkActionUI();
    }

    // Bulk actions
    handleBulkAction(e) {
        const selectedCheckboxes = document.querySelectorAll('.teacher-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            this.showToast('لطفاً حداقل یک معلم را انتخاب کنید', 'warning');
            return;
        }
        
        const action = document.querySelector('select[name="action"]').value;
        if (!action) {
            e.preventDefault();
            this.showToast('لطفاً عملیات مورد نظر را انتخاب کنید', 'warning');
            return;
        }

        // Add hidden inputs for selected teacher IDs
        selectedCheckboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'teacher_ids[]';
            hiddenInput.value = checkbox.value;
            e.target.appendChild(hiddenInput);
        });

        this.showLoadingSpinner();
    }

    // Search functionality
    handleSearch(e) {
        const query = e.target.value;
        if (query.length >= 2 || query.length === 0) {
            this.performSearch(query);
        }
    }

    performSearch(query) {
        this.showLoadingSpinner();
        
        const formData = new FormData();
        formData.append('search', query);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        fetch('{{ route("admin.teachers.index") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateTable(data.teachers);
                this.updateStatistics(data.stats);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            this.showToast('خطا در جستجو', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Individual teacher actions
    handleTeacherAction(e) {
        const action = e.target.dataset.action;
        const teacherId = e.target.dataset.teacherId;
        
        if (action === 'delete') {
            this.confirmDelete(teacherId);
        } else if (action === 'verify') {
            this.confirmVerify(teacherId);
        } else if (action === 'suspend') {
            this.confirmSuspend(teacherId);
        } else if (action === 'activate') {
            this.confirmActivate(teacherId);
        }
    }

    // Status change handlers
    handleStatusChange(e) {
        e.preventDefault();
        const action = e.target.dataset.action;
        const teacherId = e.target.dataset.teacherId;
        
        this.showLoadingSpinner();
        
        fetch(`/admin/teachers/${teacherId}/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.updateTeacherStatus(teacherId, action);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در انجام عملیات', 'error');
            }
        })
        .catch(error => {
            console.error('Status change error:', error);
            this.showToast('خطا در انجام عملیات', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    // Update teacher status in UI
    updateTeacherStatus(teacherId, action) {
        const row = document.querySelector(`tr[data-teacher-id="${teacherId}"]`);
        if (!row) return;

        const statusCell = row.querySelector('.status-badge');
        const actionCell = row.querySelector('.teacher-actions');
        
        if (action === 'verify') {
            statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">تأیید شده</span>';
            // Update action buttons
            const verifyBtn = actionCell.querySelector('[data-action="verify"]');
            if (verifyBtn) verifyBtn.remove();
        } else if (action === 'suspend') {
            statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">معلق</span>';
        } else if (action === 'activate') {
            statusCell.innerHTML = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">فعال</span>';
        }
    }

    // Update bulk action UI
    updateBulkActionUI() {
        const selectedCount = this.selectedTeachers.size;
        const bulkActionBtn = document.querySelector('#bulk-form button[type="submit"]');
        
        if (selectedCount > 0) {
            bulkActionBtn.textContent = `اجرا (${selectedCount} مورد)`;
            bulkActionBtn.disabled = false;
        } else {
            bulkActionBtn.textContent = 'اجرا';
            bulkActionBtn.disabled = true;
        }
    }

    // Update statistics
    updateStatistics() {
        fetch('/admin/teachers/statistics', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateStatsCards(data.stats);
            }
        })
        .catch(error => {
            console.error('Statistics update error:', error);
        });
    }

    updateStatsCards(stats) {
        document.querySelector('.grid .bg-white:nth-child(1) .text-2xl').textContent = stats.total;
        document.querySelector('.grid .bg-white:nth-child(2) .text-2xl').textContent = stats.verified;
        document.querySelector('.grid .bg-white:nth-child(3) .text-2xl').textContent = stats.pending;
        document.querySelector('.grid .bg-white:nth-child(4) .text-2xl').textContent = stats.total_students;
    }

    // Confirmation dialogs
    confirmDelete(teacherId) {
        if (confirm('آیا از حذف این معلم اطمینان دارید؟')) {
            this.performDelete(teacherId);
        }
    }

    confirmVerify(teacherId) {
        if (confirm('آیا از تأیید این معلم اطمینان دارید؟')) {
            this.handleStatusChange({ target: { dataset: { action: 'verify', teacherId } }, preventDefault: () => {} });
        }
    }

    confirmSuspend(teacherId) {
        if (confirm('آیا از تعلیق این معلم اطمینان دارید؟')) {
            this.handleStatusChange({ target: { dataset: { action: 'suspend', teacherId } }, preventDefault: () => {} });
        }
    }

    confirmActivate(teacherId) {
        if (confirm('آیا از فعال‌سازی این معلم اطمینان دارید؟')) {
            this.handleStatusChange({ target: { dataset: { action: 'activate', teacherId } }, preventDefault: () => {} });
        }
    }

    performDelete(teacherId) {
        this.showLoadingSpinner();
        
        fetch(`/admin/teachers/${teacherId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.removeTeacherRow(teacherId);
                this.updateStatistics();
            } else {
                this.showToast(data.message || 'خطا در حذف', 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            this.showToast('خطا در حذف', 'error');
        })
        .finally(() => {
            this.hideLoadingSpinner();
        });
    }

    removeTeacherRow(teacherId) {
        const row = document.querySelector(`tr[data-teacher-id="${teacherId}"]`);
        if (row) {
            row.remove();
        }
    }

    // UI helpers
    showLoadingSpinner() {
        if (this.isLoading) return;
        this.isLoading = true;
        
        const spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        spinner.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                </div>
            </div>
        `;
        document.body.appendChild(spinner);
    }

    hideLoadingSpinner() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
        this.isLoading = false;
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.teacherManager = new TeacherManager();
});

// Legacy functions for backward compatibility
function selectAll() {
    const manager = window.teacherManager;
    if (manager) manager.selectAll();
}

function deselectAll() {
    const manager = window.teacherManager;
    if (manager) manager.deselectAll();
}

function toggleAll() {
    const manager = window.teacherManager;
    if (manager) manager.toggleAll();
}
</script>
@endsection
