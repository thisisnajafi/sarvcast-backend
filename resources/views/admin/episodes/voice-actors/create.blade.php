@extends('admin.layouts.app')

@section('title', 'افزودن صداپیشه - ' . $episode->title)

@section('content')
<div class="p-6" data-episode-id="{{ $episode->id }}" data-episode-duration="{{ $episode->duration }}">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                افزودن صداپیشه جدید
            </h1>
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    {{ $episode->title }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    مدت زمان: {{ gmdate('i:s', $episode->duration) }}
                </span>
            </div>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.episodes.voice-actors.index', $episode) }}" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <!-- Episode Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">اطلاعات قسمت</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">عنوان قسمت</label>
                    <p class="text-gray-900 dark:text-white">{{ $episode->title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">مدت زمان</label>
                    <p class="text-gray-900 dark:text-white">{{ gmdate('i:s', $episode->duration) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">صداپیشگان فعلی</label>
                    <p class="text-gray-900 dark:text-white">{{ $episode->voice_actor_count ?? 0 }} نفر</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">اطلاعات صداپیشه</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">لطفاً اطلاعات صداپیشه را تکمیل کنید</p>
        </div>
        
        <form action="{{ route('admin.episodes.voice-actors.store', $episode) }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Person Selection -->
                <div class="md:col-span-2">
                    <label for="person_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        انتخاب صداپیشه <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select id="person_id" name="person_id" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">انتخاب صداپیشه...</option>
                            @foreach($people as $person)
                                <option value="{{ $person->id }}" 
                                        data-roles="{{ json_encode($person->roles ?? []) }}"
                                        data-bio="{{ $person->bio }}"
                                        data-image="{{ $person->image_url }}">
                                    {{ $person->name }}
                                    @if($person->is_verified)
                                        <span class="text-green-500">✓</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute left-3 top-2.5">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    @error('person_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Person Info Display -->
                <div id="person-info" class="md:col-span-2 hidden">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <img id="person-image" src="" alt="" class="w-16 h-16 rounded-full object-cover">
                            <div>
                                <h3 id="person-name" class="text-lg font-medium text-gray-900 dark:text-white"></h3>
                                <p id="person-bio" class="text-sm text-gray-600 dark:text-gray-400"></p>
                                <div id="person-roles" class="flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        نقش صداپیشه <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">انتخاب نقش...</option>
                        <option value="narrator">راوی</option>
                        <option value="character">شخصیت</option>
                        <option value="voice_over">صداگذاری</option>
                        <option value="background">پس‌زمینه</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Character Name -->
                <div>
                    <label for="character_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        نام شخصیت
                    </label>
                    <input type="text" id="character_name" name="character_name" 
                           placeholder="نام شخصیت (اختیاری)"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    @error('character_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Range (Hidden - automatically set to full episode duration) -->
                <input type="hidden" id="start_time" name="start_time" value="0">
                <input type="hidden" id="end_time" name="end_time" value="{{ $episode->duration }}">
                
                <!-- Note about time range -->
                <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-medium mb-1">توجه:</p>
                            <p>صداپیشه به طور خودکار برای کل مدت زمان اپیزود ({{ gmdate('i:s', $episode->duration) }}) تنظیم می‌شود.</p>
                        </div>
                    </div>
                </div>

                <!-- Voice Description -->
                <div class="md:col-span-2">
                    <label for="voice_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        توضیحات صدا
                    </label>
                    <textarea id="voice_description" name="voice_description" rows="3"
                              placeholder="توضیحات مربوط به ویژگی‌های صدا (اختیاری)"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                    @error('voice_description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Primary Voice Actor -->
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_primary" name="is_primary" value="1"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is_primary" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            تعیین به عنوان صداپیشه اصلی
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        صداپیشه اصلی برای نمایش در اطلاعات کلی قسمت استفاده می‌شود
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.episodes.voice-actors.index', $episode) }}" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    انصراف
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    افزودن صداپیشه
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const episodeDuration = {{ $episode->duration }};
    const personSelect = document.getElementById('person_id');
    const personInfo = document.getElementById('person-info');

    // Person selection change
    personSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            showPersonInfo(selectedOption);
        } else {
            hidePersonInfo();
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        // No time validation needed since times are automatic
    });

    function showPersonInfo(option) {
        const personName = option.textContent.trim();
        const personBio = option.dataset.bio || 'بدون توضیحات';
        const personImage = option.dataset.image || '/images/default-avatar.png';
        const personRoles = JSON.parse(option.dataset.roles || '[]');

        document.getElementById('person-name').textContent = personName;
        document.getElementById('person-bio').textContent = personBio;
        document.getElementById('person-image').src = personImage;
        
        const rolesContainer = document.getElementById('person-roles');
        rolesContainer.innerHTML = personRoles.map(role => 
            `<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">${role}</span>`
        ).join('');

        personInfo.classList.remove('hidden');
    }

    function hidePersonInfo() {
        personInfo.classList.add('hidden');
    }

    // Auto-suggest character name based on role
    document.getElementById('role').addEventListener('change', function() {
        const characterNameInput = document.getElementById('character_name');
        const role = this.value;
        
        if (role === 'character' && !characterNameInput.value) {
            characterNameInput.placeholder = 'نام شخصیت (مثال: شاهزاده، جنگجو، جادوگر)';
        } else if (role === 'narrator') {
            characterNameInput.placeholder = 'نام شخصیت (اختیاری)';
            characterNameInput.value = '';
        }
    });
});
</script>
@endpush
