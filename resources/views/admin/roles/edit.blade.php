@extends('admin.layouts.app')

@section('title', 'ویرایش نقش')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">ویرایش نقش: {{ $role->display_name }}</h1>
        <a href="{{ route('admin.roles.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
            <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            بازگشت
        </a>
    </div>

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Role Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اطلاعات نقش</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نام نقش (انگلیسی)</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror"
                               placeholder="مثال: content_manager" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نام نمایشی</label>
                        <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $role->display_name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('display_name') border-red-500 @enderror"
                               placeholder="مثال: مدیر محتوا" required>
                        @error('display_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">توضیحات</label>
                    <textarea name="description" id="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('description') border-red-500 @enderror"
                              placeholder="توضیحات نقش را وارد کنید">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Permissions Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">مجوزهای فعلی</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($role->permissions as $permission)
                    <div class="flex items-center p-2 bg-green-50 dark:bg-green-900 rounded-lg">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-green-800 dark:text-green-200">{{ $permission->display_name }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">مجوزهای دسترسی</h2>
                
                <div class="space-y-6">
                    @foreach($permissions as $group => $groupPermissions)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-md font-medium text-gray-900 dark:text-white">
                                {{ ucfirst(str_replace('_', ' ', $group)) }}
                            </h3>
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all-{{ $group }}" 
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                       onchange="toggleGroupPermissions('{{ $group }}', this.checked)">
                                <label for="select-all-{{ $group }}" class="mr-2 text-sm text-gray-700 dark:text-gray-300">
                                    انتخاب همه
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($groupPermissions as $permission)
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                       id="permission-{{ $permission->id }}"
                                       class="permission-checkbox h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded group-{{ $group }}"
                                       {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <label for="permission-{{ $permission->id }}" class="mr-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $permission->display_name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @error('permissions')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 space-x-reverse">
                <a href="{{ route('admin.roles.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200">
                    انصراف
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition duration-200">
                    به‌روزرسانی نقش
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGroupPermissions(group, checked) {
    const checkboxes = document.querySelectorAll(`.group-${group}`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
}

// Update group checkbox when individual permissions change
document.addEventListener('DOMContentLoaded', function() {
    @foreach($permissions as $group => $groupPermissions)
    const groupCheckboxes{{ $group }} = document.querySelectorAll('.group-{{ $group }}');
    const selectAll{{ $group }} = document.getElementById('select-all-{{ $group }}');
    
    // Set initial state
    const checkedCount = document.querySelectorAll('.group-{{ $group }}:checked').length;
    const totalCount = groupCheckboxes{{ $group }}.length;
    
    if (checkedCount === totalCount) {
        selectAll{{ $group }}.checked = true;
    } else if (checkedCount > 0) {
        selectAll{{ $group }}.indeterminate = true;
    }
    
    groupCheckboxes{{ $group }}.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.group-{{ $group }}:checked').length;
            const totalCount = groupCheckboxes{{ $group }}.length;
            
            if (checkedCount === 0) {
                selectAll{{ $group }}.checked = false;
                selectAll{{ $group }}.indeterminate = false;
            } else if (checkedCount === totalCount) {
                selectAll{{ $group }}.checked = true;
                selectAll{{ $group }}.indeterminate = false;
            } else {
                selectAll{{ $group }}.checked = false;
                selectAll{{ $group }}.indeterminate = true;
            }
        });
    });
    @endforeach
});
</script>
@endsection
