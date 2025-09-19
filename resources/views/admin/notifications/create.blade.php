@extends('admin.layouts.app')

@section('title', 'ایجاد اعلان جدید')
@section('page-title', 'ایجاد اعلان جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">ایجاد اعلان جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات اعلان جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.notifications.store') }}" class="p-6 space-y-6">
            @csrf

            <!-- Title and Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان اعلان *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="عنوان اعلان...">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع اعلان *</label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="">انتخاب نوع</option>
                        <option value="system" {{ old('type') == 'system' ? 'selected' : '' }}>سیستمی</option>
                        <option value="user" {{ old('type') == 'user' ? 'selected' : '' }}>کاربری</option>
                        <option value="marketing" {{ old('type') == 'marketing' ? 'selected' : '' }}>بازاریابی</option>
                        <option value="alert" {{ old('type') == 'alert' ? 'selected' : '' }}>هشدار</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Priority and Recipient Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">اولویت *</label>
                    <select name="priority" id="priority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('priority') border-red-500 @enderror">
                        <option value="">انتخاب اولویت</option>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>کم</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>متوسط</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>بالا</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>فوری</option>
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="recipient_type" class="block text-sm font-medium text-gray-700 mb-2">نوع گیرنده *</label>
                    <select name="recipient_type" id="recipient_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('recipient_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع گیرنده</option>
                        <option value="all" {{ old('recipient_type') == 'all' ? 'selected' : '' }}>همه کاربران</option>
                        <option value="user" {{ old('recipient_type') == 'user' ? 'selected' : '' }}>کاربر خاص</option>
                        <option value="specific" {{ old('recipient_type') == 'specific' ? 'selected' : '' }}>کاربر مشخص</option>
                    </select>
                    @error('recipient_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Recipient Selection -->
            <div id="recipient-selection" class="hidden">
                <label for="recipient_id" class="block text-sm font-medium text-gray-700 mb-2">انتخاب کاربر</label>
                <select name="recipient_id" id="recipient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('recipient_id') border-red-500 @enderror">
                    <option value="">انتخاب کاربر</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('recipient_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('recipient_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Message -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">پیام اعلان *</label>
                <textarea name="message" id="message" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('message') border-red-500 @enderror" placeholder="متن پیام اعلان...">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Delivery Channels -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">کانال‌های ارسال</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="send_push" id="send_push" value="1" {{ old('send_push') ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="send_push" class="mr-2 text-sm font-medium text-gray-700">اعلان درون‌برنامه‌ای</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="send_email" id="send_email" value="1" {{ old('send_email') ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="send_email" class="mr-2 text-sm font-medium text-gray-700">ایمیل</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="send_sms" id="send_sms" value="1" {{ old('send_sms') ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="send_sms" class="mr-2 text-sm font-medium text-gray-700">پیامک</label>
                    </div>
                </div>
            </div>

            <!-- Scheduling -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">زمان ارسال برنامه‌ریزی شده</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('scheduled_at') border-red-500 @enderror">
                    @error('scheduled_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">خالی بگذارید برای ارسال فوری</p>
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">تاریخ انقضا</label>
                    <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">اختیاری - تاریخ انقضای اعلان</p>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">پیش‌نمایش اعلان</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>عنوان:</strong> <span id="preview-title">-</span></p>
                    <p><strong>نوع:</strong> <span id="preview-type">-</span></p>
                    <p><strong>اولویت:</strong> <span id="preview-priority">-</span></p>
                    <p><strong>گیرنده:</strong> <span id="preview-recipient">-</span></p>
                    <p><strong>کانال‌ها:</strong> <span id="preview-channels">-</span></p>
                    <p><strong>زمان ارسال:</strong> <span id="preview-schedule">-</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.notifications.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                    ایجاد اعلان
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Show/hide recipient selection based on recipient type
document.getElementById('recipient_type').addEventListener('change', function() {
    const recipientSelection = document.getElementById('recipient-selection');
    const recipientId = document.getElementById('recipient_id');
    
    if (this.value === 'specific') {
        recipientSelection.classList.remove('hidden');
        recipientId.required = true;
    } else {
        recipientSelection.classList.add('hidden');
        recipientId.required = false;
        recipientId.value = '';
    }
    
    updatePreview();
});

// Update preview when form changes
function updatePreview() {
    const title = document.getElementById('title').value;
    const typeSelect = document.getElementById('type');
    const type = typeSelect.options[typeSelect.selectedIndex].text;
    const prioritySelect = document.getElementById('priority');
    const priority = prioritySelect.options[prioritySelect.selectedIndex].text;
    const recipientTypeSelect = document.getElementById('recipient_type');
    const recipientType = recipientTypeSelect.options[recipientTypeSelect.selectedIndex].text;
    const recipientId = document.getElementById('recipient_id');
    const recipientName = recipientId.options[recipientId.selectedIndex].text;
    const scheduledAt = document.getElementById('scheduled_at').value;
    
    // Get selected channels
    const channels = [];
    if (document.getElementById('send_push').checked) channels.push('اعلان درون‌برنامه‌ای');
    if (document.getElementById('send_email').checked) channels.push('ایمیل');
    if (document.getElementById('send_sms').checked) channels.push('پیامک');
    
    document.getElementById('preview-title').textContent = title || '-';
    document.getElementById('preview-type').textContent = type || '-';
    document.getElementById('preview-priority').textContent = priority || '-';
    
    if (recipientType === 'کاربر مشخص') {
        document.getElementById('preview-recipient').textContent = recipientName || '-';
    } else {
        document.getElementById('preview-recipient').textContent = recipientType || '-';
    }
    
    document.getElementById('preview-channels').textContent = channels.length > 0 ? channels.join(', ') : 'هیچ کانالی انتخاب نشده';
    document.getElementById('preview-schedule').textContent = scheduledAt ? new Date(scheduledAt).toLocaleString('fa-IR') : 'فوری';
}

// Add event listeners for preview updates
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('type').addEventListener('change', updatePreview);
document.getElementById('priority').addEventListener('change', updatePreview);
document.getElementById('recipient_type').addEventListener('change', updatePreview);
document.getElementById('recipient_id').addEventListener('change', updatePreview);
document.getElementById('send_push').addEventListener('change', updatePreview);
document.getElementById('send_email').addEventListener('change', updatePreview);
document.getElementById('send_sms').addEventListener('change', updatePreview);
document.getElementById('scheduled_at').addEventListener('change', updatePreview);

// Initialize preview and recipient selection
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    
    // Trigger recipient type change to show/hide recipient selection
    document.getElementById('recipient_type').dispatchEvent(new Event('change'));
});
</script>
@endsection