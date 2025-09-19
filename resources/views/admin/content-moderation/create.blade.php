@extends('admin.layouts.app')

@section('title', 'افزودن گزارش نظارت بر محتوا جدید')
@section('page-title', 'افزودن گزارش جدید')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">افزودن گزارش نظارت بر محتوا جدید</h1>
            <p class="mt-1 text-sm text-gray-600">اطلاعات گزارش نظارت بر محتوا جدید را وارد کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.content-moderation.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">کاربر گزارش‌دهنده *</label>
                <select name="user_id" id="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('user_id') border-red-500 @enderror">
                    <option value="">انتخاب کاربر</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content Type and ID -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="content_type" class="block text-sm font-medium text-gray-700 mb-2">نوع محتوا *</label>
                    <select name="content_type" id="content_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('content_type') border-red-500 @enderror">
                        <option value="">انتخاب نوع محتوا</option>
                        <option value="story" {{ old('content_type') == 'story' ? 'selected' : '' }}>داستان</option>
                        <option value="episode" {{ old('content_type') == 'episode' ? 'selected' : '' }}>اپیزود</option>
                        <option value="comment" {{ old('content_type') == 'comment' ? 'selected' : '' }}>نظر</option>
                        <option value="review" {{ old('content_type') == 'review' ? 'selected' : '' }}>بررسی</option>
                        <option value="user_profile" {{ old('content_type') == 'user_profile' ? 'selected' : '' }}>پروفایل کاربر</option>
                    </select>
                    @error('content_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="content_id" class="block text-sm font-medium text-gray-700 mb-2">شناسه محتوا *</label>
                    <input type="number" name="content_id" id="content_id" value="{{ old('content_id') }}" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('content_id') border-red-500 @enderror" placeholder="شناسه محتوای گزارش شده...">
                    @error('content_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Story and Episode Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="story_id" class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                    <select name="story_id" id="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('story_id') border-red-500 @enderror">
                        <option value="">انتخاب داستان (اختیاری)</option>
                        @foreach($stories as $story)
                            <option value="{{ $story->id }}" {{ old('story_id') == $story->id ? 'selected' : '' }}>
                                {{ $story->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('story_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="episode_id" class="block text-sm font-medium text-gray-700 mb-2">اپیزود</label>
                    <select name="episode_id" id="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('episode_id') border-red-500 @enderror">
                        <option value="">انتخاب اپیزود (اختیاری)</option>
                        @foreach($episodes as $episode)
                            <option value="{{ $episode->id }}" {{ old('episode_id') == $episode->id ? 'selected' : '' }}>
                                {{ $episode->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('episode_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Reason and Severity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">دلیل گزارش *</label>
                    <input type="text" name="reason" id="reason" value="{{ old('reason') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('reason') border-red-500 @enderror" placeholder="دلیل گزارش را وارد کنید...">
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-700 mb-2">شدت مشکل *</label>
                    <select name="severity" id="severity" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('severity') border-red-500 @enderror">
                        <option value="">انتخاب شدت</option>
                        <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>کم</option>
                        <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>متوسط</option>
                        <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>زیاد</option>
                    </select>
                    @error('severity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">وضعیت *</label>
                <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="">انتخاب وضعیت</option>
                    <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>رد شده</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">یادداشت‌ها</label>
                <textarea name="notes" id="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('notes') border-red-500 @enderror" placeholder="یادداشت‌های اضافی در مورد این گزارش...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Evidence Files -->
            <div>
                <label for="evidence_files" class="block text-sm font-medium text-gray-700 mb-2">فایل‌های مدرک</label>
                <input type="file" name="evidence_files[]" id="evidence_files" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('evidence_files') border-red-500 @enderror">
                @error('evidence_files')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">فرمت‌های مجاز: JPG, PNG, GIF, PDF, DOC, DOCX (حداکثر 10MB هر فایل، حداکثر 5 فایل)</p>
            </div>

            <!-- File Preview -->
            <div id="file-preview" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">پیش‌نمایش فایل‌ها</label>
                <div id="preview-container" class="grid grid-cols-2 md:grid-cols-3 gap-4"></div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                <a href="{{ route('admin.content-moderation.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    انصراف
                </a>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                    ایجاد گزارش
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File preview functionality
document.getElementById('evidence_files').addEventListener('change', function(e) {
    const files = e.target.files;
    const previewContainer = document.getElementById('preview-container');
    const filePreview = document.getElementById('file-preview');
    
    // Clear previous previews
    previewContainer.innerHTML = '';
    
    if (files.length > 0) {
        filePreview.classList.remove('hidden');
        
        Array.from(files).forEach((file, index) => {
            const fileDiv = document.createElement('div');
            fileDiv.className = 'border border-gray-200 rounded-lg p-3';
            
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'w-full h-24 object-cover rounded';
                img.onload = function() {
                    URL.revokeObjectURL(this.src);
                };
                fileDiv.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'w-full h-24 bg-gray-100 rounded flex items-center justify-center';
                icon.innerHTML = `
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                `;
                fileDiv.appendChild(icon);
            }
            
            const fileName = document.createElement('p');
            fileName.className = 'text-xs text-gray-600 mt-2 truncate';
            fileName.textContent = file.name;
            fileDiv.appendChild(fileName);
            
            previewContainer.appendChild(fileDiv);
        });
    } else {
        filePreview.classList.add('hidden');
    }
});

// Validate file size and count
document.getElementById('evidence_files').addEventListener('change', function(e) {
    const files = e.target.files;
    const maxFiles = 5;
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (files.length > maxFiles) {
        alert(`حداکثر ${maxFiles} فایل مجاز است.`);
        this.value = '';
        return;
    }
    
    for (let file of files) {
        if (file.size > maxSize) {
            alert(`فایل "${file.name}" بیش از 10MB است.`);
            this.value = '';
            return;
        }
    }
});
</script>
@endsection
