@extends('admin.layouts.app')

@section('title', 'ارسال اعلان جدید')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ارسال اعلان جدید</h1>
        <a href="{{ route('admin.notifications.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
            بازگشت به لیست
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.notifications.store') }}">
            @csrf
            
            <!-- Notification Type -->
            <div class="mb-6">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع اعلان</label>
                <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('type') border-red-500 @enderror" required>
                    <option value="">انتخاب کنید</option>
                    <option value="info" {{ old('type') == 'info' ? 'selected' : '' }}>اطلاعاتی</option>
                    <option value="success" {{ old('type') == 'success' ? 'selected' : '' }}>موفقیت</option>
                    <option value="warning" {{ old('type') == 'warning' ? 'selected' : '' }}>هشدار</option>
                    <option value="error" {{ old('type') == 'error' ? 'selected' : '' }}>خطا</option>
                </select>
                @error('type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان اعلان</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror"
                       placeholder="عنوان اعلان را وارد کنید" required>
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Message -->
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">متن اعلان</label>
                <textarea name="message" id="message" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('message') border-red-500 @enderror"
                          placeholder="متن اعلان را وارد کنید" required>{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Users -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">کاربران هدف</label>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="radio" name="target_type" id="all_users" value="all" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300" 
                               {{ old('target_type', 'all') == 'all' ? 'checked' : '' }}>
                        <label for="all_users" class="mr-2 text-sm text-gray-700">همه کاربران</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="radio" name="target_type" id="specific_users" value="specific" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('target_type') == 'specific' ? 'checked' : '' }}>
                        <label for="specific_users" class="mr-2 text-sm text-gray-700">کاربران خاص</label>
                    </div>
                    
                    <div id="user_selection" class="mr-6 {{ old('target_type') == 'specific' ? '' : 'hidden' }}">
                        <select name="user_ids[]" id="user_ids" multiple 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        {{ in_array($user->id, old('user_ids', [])) ? 'selected' : '' }}>
                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->phone_number }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-sm text-gray-500 mt-1">برای انتخاب چند کاربر، کلید Ctrl را نگه دارید</p>
                    </div>
                </div>
            </div>

            <!-- Channels -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">کانال‌های ارسال</label>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="channels[]" id="in_app" value="in_app" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ in_array('in_app', old('channels', ['in_app'])) ? 'checked' : '' }}>
                        <label for="in_app" class="mr-2 text-sm text-gray-700">اعلان درون‌برنامه‌ای</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="channels[]" id="push" value="push" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ in_array('push', old('channels', [])) ? 'checked' : '' }}>
                        <label for="push" id="push" class="mr-2 text-sm text-gray-700">اعلان Push</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="channels[]" id="email" value="email" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ in_array('email', old('channels', [])) ? 'checked' : '' }}>
                        <label for="email" class="mr-2 text-sm text-gray-700">ایمیل</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="channels[]" id="sms" value="sms" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}>
                        <label for="sms" class="mr-2 text-sm text-gray-700">پیامک</label>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">زمان ارسال</label>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="radio" name="schedule_type" id="now" value="now" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('schedule_type', 'now') == 'now' ? 'checked' : '' }}>
                        <label for="now" class="mr-2 text-sm text-gray-700">ارسال فوری</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="radio" name="schedule_type" id="scheduled" value="scheduled" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                               {{ old('schedule_type') == 'scheduled' ? 'checked' : '' }}>
                        <label for="scheduled" class="mr-2 text-sm text-gray-700">ارسال زمان‌بندی شده</label>
                    </div>
                    
                    <div id="schedule_datetime" class="mr-6 {{ old('schedule_type') == 'scheduled' ? '' : 'hidden' }}">
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Test Notification -->
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">ارسال تست</h3>
                <p class="text-sm text-yellow-700 mb-3">قبل از ارسال به همه کاربران، می‌توانید یک اعلان تست ارسال کنید</p>
                <button type="button" id="send_test" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition duration-200">
                    ارسال اعلان تست
                </button>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4 space-x-reverse">
                <a href="{{ route('admin.notifications.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    انصراف
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    ارسال اعلان
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Target type change
    const targetTypeRadios = document.querySelectorAll('input[name="target_type"]');
    const userSelection = document.getElementById('user_selection');
    
    targetTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'specific') {
                userSelection.classList.remove('hidden');
            } else {
                userSelection.classList.add('hidden');
            }
        });
    });
    
    // Schedule type change
    const scheduleTypeRadios = document.querySelectorAll('input[name="schedule_type"]');
    const scheduleDatetime = document.getElementById('schedule_datetime');
    
    scheduleTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'scheduled') {
                scheduleDatetime.classList.remove('hidden');
            } else {
                scheduleDatetime.classList.add('hidden');
            }
        });
    });
    
    // Send test notification
    document.getElementById('send_test').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('message', document.getElementById('message').value);
        formData.append('type', document.getElementById('type').value);
        formData.append('test', '1');
        
        fetch('{{ route("admin.notifications.send-test") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('اعلان تست با موفقیت ارسال شد');
            } else {
                alert('خطا در ارسال اعلان تست: ' + data.message);
            }
        })
        .catch(error => {
            alert('خطا در ارسال اعلان تست');
        });
    });
});
</script>
@endsection
