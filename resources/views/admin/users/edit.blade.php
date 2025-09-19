@extends('admin.layouts.app')

@section('title', 'ویرایش کاربر - ' . $user->first_name . ' ' . $user->last_name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">ویرایش کاربر</h1>
        <p class="text-gray-600 mt-2">{{ $user->first_name }} {{ $user->last_name }}</p>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات شخصی</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">نام *</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('first_name') border-red-500 @enderror" placeholder="نام را وارد کنید">
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">نام خانوادگی *</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('last_name') border-red-500 @enderror" placeholder="نام خانوادگی را وارد کنید">
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل *</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('email') border-red-500 @enderror" placeholder="ایمیل را وارد کنید">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">شماره تلفن</label>
                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone_number') border-red-500 @enderror" placeholder="شماره تلفن را وارد کنید">
                    @error('phone_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات حساب کاربری</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور جدید</label>
                    <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password') border-red-500 @enderror" placeholder="رمز عبور جدید را وارد کنید">
                    <p class="mt-1 text-sm text-gray-500">در صورت خالی گذاشتن، رمز عبور تغییر نخواهد کرد</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تأیید رمز عبور جدید</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password_confirmation') border-red-500 @enderror" placeholder="رمز عبور جدید را مجدداً وارد کنید">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">نقش *</label>
                    <select name="role" id="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('role') border-red-500 @enderror">
                        <option value="">انتخاب نقش</option>
                        <option value="parent" {{ old('role', $user->role) == 'parent' ? 'selected' : '' }}>والد</option>
                        <option value="child" {{ old('role', $user->role) == 'child' ? 'selected' : '' }}>کودک</option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>مدیر</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                    <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="">انتخاب وضعیت</option>
                        <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                        <option value="suspended" {{ old('status', $user->status) == 'suspended' ? 'selected' : '' }}>معلق</option>
                        <option value="pending" {{ old('status', $user->status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات اضافی</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Parent Selection (for child users) -->
                <div id="parent-selection" style="display: {{ old('role', $user->role) == 'child' ? 'block' : 'none' }};">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">والد</label>
                    <select name="parent_id" id="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('parent_id') border-red-500 @enderror">
                        <option value="">انتخاب والد</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $user->parent_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->first_name }} {{ $parent->last_name }} ({{ $parent->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Language -->
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">زبان</label>
                    <select name="language" id="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('language') border-red-500 @enderror">
                        <option value="fa" {{ old('language', $user->language) == 'fa' ? 'selected' : '' }}>فارسی</option>
                        <option value="en" {{ old('language', $user->language) == 'en' ? 'selected' : '' }}>English</option>
                    </select>
                    @error('language')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">منطقه زمانی</label>
                    <select name="timezone" id="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('timezone') border-red-500 @enderror">
                        <option value="Asia/Tehran" {{ old('timezone', $user->timezone) == 'Asia/Tehran' ? 'selected' : '' }}>Asia/Tehran</option>
                        <option value="UTC" {{ old('timezone', $user->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Role and Permission Management -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">مدیریت نقش‌ها و مجوزها</h2>
            
            <!-- Current Roles -->
            <div class="mb-6">
                <h3 class="text-md font-medium text-gray-900 mb-3">نقش‌های فعلی</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                    <div class="flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                        <span>{{ $role->display_name }}</span>
                        @if($role->name !== 'super_admin' || !$user->isSuperAdmin())
                        <button type="button" onclick="removeRole({{ $user->id }}, {{ $role->id }})" 
                                class="mr-2 text-blue-600 hover:text-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        @endif
                    </div>
                    @empty
                    <p class="text-gray-500 text-sm">هیچ نقشی اختصاص داده نشده است</p>
                    @endforelse
                </div>
            </div>

            <!-- Assign New Role -->
            <div class="mb-6">
                <h3 class="text-md font-medium text-gray-900 mb-3">اختصاص نقش جدید</h3>
                <div class="flex gap-4">
                    <select id="new-role-select" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">انتخاب نقش</option>
                        @foreach($availableRoles as $role)
                            @if(!$user->hasRole($role->name))
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <button type="button" onclick="assignRole()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        اختصاص نقش
                    </button>
                </div>
            </div>

            <!-- Direct Permissions -->
            <div class="mb-6">
                <h3 class="text-md font-medium text-gray-900 mb-3">مجوزهای مستقیم</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($permissions as $group => $groupPermissions)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ ucfirst(str_replace('_', ' ', $group)) }}</h4>
                        <div class="space-y-2">
                            @foreach($groupPermissions as $permission)
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="permission-{{ $permission->id }}" 
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                       {{ $user->directPermissions()->where('permissions.id', $permission->id)->exists() ? 'checked' : '' }}
                                       onchange="togglePermission({{ $user->id }}, {{ $permission->id }}, this.checked)">
                                <label for="permission-{{ $permission->id }}" class="mr-2 text-sm text-gray-700">
                                    {{ $permission->display_name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 space-x-reverse">
            <a href="{{ route('admin.users.show', $user) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                انصراف
            </a>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                به‌روزرسانی کاربر
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    const parentSelection = document.getElementById('parent-selection');
    if (this.value === 'child') {
        parentSelection.style.display = 'block';
        document.getElementById('parent_id').required = true;
    } else {
        parentSelection.style.display = 'none';
        document.getElementById('parent_id').required = false;
    }
});

// Role Management Functions
function assignRole() {
    const roleId = document.getElementById('new-role-select').value;
    if (!roleId) {
        alert('لطفاً یک نقش انتخاب کنید');
        return;
    }

    if (confirm('آیا مطمئن هستید که می‌خواهید این نقش را به کاربر اختصاص دهید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.roles.assign") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = '{{ $user->id }}';
        
        const roleIdInput = document.createElement('input');
        roleIdInput.type = 'hidden';
        roleIdInput.name = 'role_id';
        roleIdInput.value = roleId;
        
        form.appendChild(csrfToken);
        form.appendChild(userIdInput);
        form.appendChild(roleIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function removeRole(userId, roleId) {
    if (confirm('آیا مطمئن هستید که می‌خواهید این نقش را از کاربر حذف کنید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/users/${userId}/roles/${roleId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function togglePermission(userId, permissionId, isChecked) {
    const action = isChecked ? 'grant' : 'revoke';
    const message = isChecked ? 'آیا مطمئن هستید که می‌خواهید این مجوز را به کاربر اعطا کنید؟' : 'آیا مطمئن هستید که می‌خواهید این مجوز را از کاربر حذف کنید؟';
    
    if (confirm(message)) {
        fetch(`/admin/users/${userId}/permissions/${permissionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification(data.message, 'success');
            } else {
                // Show error message
                showNotification(data.message || 'خطا در اعمال تغییرات', 'error');
                // Revert checkbox state
                document.getElementById(`permission-${permissionId}`).checked = !isChecked;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('خطا در اعمال تغییرات', 'error');
            // Revert checkbox state
            document.getElementById(`permission-${permissionId}`).checked = !isChecked;
        });
    } else {
        // Revert checkbox state if user cancels
        document.getElementById(`permission-${permissionId}`).checked = !isChecked;
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endsection
