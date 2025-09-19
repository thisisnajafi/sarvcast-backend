@extends('admin.layouts.app')

@section('title', 'ویرایش صداپیشه - ' . $episode->title)

@section('content')
<div class="p-6" data-episode-id="{{ $episode->id }}" data-episode-duration="{{ $episode->duration }}">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                ویرایش صداپیشه
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    {{ $voiceActor->person->name }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    مدت زمان: {{ gmdate('i:s', $episode->duration) }}
                </span>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.episodes.voice-actors.index', $episode) }}" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <!-- Current Voice Actor Info -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">اطلاعات فعلی صداپیشه</h2>
            <div class="flex items-center space-x-6">
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                    <img src="{{ $voiceActor->person->image_url ?: '/images/default-avatar.png' }}" 
                         alt="{{ $voiceActor->person->name }}" 
                         class="w-20 h-20 rounded-full object-cover"
                         onerror="this.src='/images/default-avatar.png'">
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $voiceActor->person->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">{{ $voiceActor->person->bio ?: 'بدون توضیحات' }}</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $voiceActor->role === 'narrator' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($voiceActor->role === 'character' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200') }}">
                            {{ $voiceActor->role }}
                        </span>
                        @if($voiceActor->character_name)
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                شخصیت: {{ $voiceActor->character_name }}
                            </span>
                        @endif
                        @if($voiceActor->is_primary)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                صداپیشه اصلی
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-left">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">زمان</div>
                    <div class="font-mono text-lg text-gray-900 dark:text-white">
                        {{ gmdate('i:s', $voiceActor->start_time) }} - {{ gmdate('i:s', $voiceActor->end_time) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        مدت: {{ $voiceActor->end_time - $voiceActor->start_time }} ثانیه
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ویرایش اطلاعات صداپیشه</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">اطلاعات صداپیشه را ویرایش کنید</p>
        </div>
        
        <form action="{{ route('admin.episodes.voice-actors.update', [$episode, $voiceActor]) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
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
                                        data-image="{{ $person->image_url }}"
                                        {{ $person->id == $voiceActor->person_id ? 'selected' : '' }}>
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
                <div id="person-info" class="md:col-span-2">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center space-x-4">
                            <img id="person-image" src="{{ $voiceActor->person->image_url ?: '/images/default-avatar.png' }}" alt="" class="w-16 h-16 rounded-full object-cover">
                            <div>
                                <h3 id="person-name" class="text-lg font-medium text-gray-900 dark:text-white">{{ $voiceActor->person->name }}</h3>
                                <p id="person-bio" class="text-sm text-gray-600 dark:text-gray-400">{{ $voiceActor->person->bio ?: 'بدون توضیحات' }}</p>
                                <div id="person-roles" class="flex flex-wrap gap-2 mt-2">
                                    @if($voiceActor->person->roles)
                                        @foreach($voiceActor->person->roles as $role)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $role }}</span>
                                        @endforeach
                                    @endif
                                </div>
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
                        <option value="narrator" {{ $voiceActor->role === 'narrator' ? 'selected' : '' }}>راوی</option>
                        <option value="character" {{ $voiceActor->role === 'character' ? 'selected' : '' }}>شخصیت</option>
                        <option value="voice_over" {{ $voiceActor->role === 'voice_over' ? 'selected' : '' }}>صداگذاری</option>
                        <option value="background" {{ $voiceActor->role === 'background' ? 'selected' : '' }}>پس‌زمینه</option>
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
                           value="{{ old('character_name', $voiceActor->character_name) }}"
                           placeholder="نام شخصیت (اختیاری)"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    @error('character_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Range -->
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        زمان شروع (ثانیه) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="start_time" name="start_time" min="0" max="{{ $episode->duration }}" required
                           value="{{ old('start_time', $voiceActor->start_time) }}"
                           placeholder="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    @error('start_time')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        زمان پایان (ثانیه) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="end_time" name="end_time" min="1" max="{{ $episode->duration }}" required
                           value="{{ old('end_time', $voiceActor->end_time) }}"
                           placeholder="{{ $episode->duration }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    @error('end_time')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Range Display -->
                <div class="md:col-span-2">
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">پیش‌نمایش زمان</h4>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-blue-700 dark:text-blue-300">شروع:</span>
                                <span id="start-time-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ gmdate('i:s', $voiceActor->start_time) }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-blue-700 dark:text-blue-300">پایان:</span>
                                <span id="end-time-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ gmdate('i:s', $voiceActor->end_time) }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-blue-700 dark:text-blue-300">مدت:</span>
                                <span id="duration-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ $voiceActor->end_time - $voiceActor->start_time }} ثانیه</span>
                            </div>
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
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">{{ old('voice_description', $voiceActor->voice_description) }}</textarea>
                    @error('voice_description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Primary Voice Actor -->
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_primary" name="is_primary" value="1"
                               {{ old('is_primary', $voiceActor->is_primary) ? 'checked' : '' }}
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
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>

    <!-- Time Validation Info -->
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="mr-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">نکات مهم</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>زمان شروع باید کمتر از زمان پایان باشد</li>
                        <li>زمان پایان نمی‌تواند بیشتر از مدت زمان کل قسمت باشد</li>
                        <li>زمان‌های صداپیشگان نباید با یکدیگر تداخل داشته باشند</li>
                        <li>فقط یک صداپیشه می‌تواند به عنوان اصلی تعیین شود</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const episodeDuration = {{ $episode->duration }};
    const personSelect = document.getElementById('person_id');
    const personInfo = document.getElementById('person-info');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const startTimeDisplay = document.getElementById('start-time-display');
    const endTimeDisplay = document.getElementById('end-time-display');
    const durationDisplay = document.getElementById('duration-display');

    // Person selection change
    personSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            showPersonInfo(selectedOption);
        }
    });

    // Time inputs change
    startTimeInput.addEventListener('input', updateTimeDisplay);
    endTimeInput.addEventListener('input', updateTimeDisplay);

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validateTimeRange()) {
            e.preventDefault();
            alert('زمان شروع باید کمتر از زمان پایان باشد');
        }
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
    }

    function updateTimeDisplay() {
        const startTime = parseInt(startTimeInput.value) || 0;
        const endTime = parseInt(endTimeInput.value) || 0;
        
        startTimeDisplay.textContent = formatTime(startTime);
        endTimeDisplay.textContent = formatTime(endTime);
        
        const duration = Math.max(0, endTime - startTime);
        durationDisplay.textContent = `${duration} ثانیه`;
        
        // Validate time range
        validateTimeRange();
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    function validateTimeRange() {
        const startTime = parseInt(startTimeInput.value) || 0;
        const endTime = parseInt(endTimeInput.value) || 0;
        
        if (startTime >= endTime) {
            startTimeInput.classList.add('border-red-500');
            endTimeInput.classList.add('border-red-500');
            return false;
        }
        
        if (endTime > episodeDuration) {
            endTimeInput.classList.add('border-red-500');
            return false;
        }
        
        startTimeInput.classList.remove('border-red-500');
        endTimeInput.classList.remove('border-red-500');
        return true;
    }

    // Auto-suggest character name based on role
    document.getElementById('role').addEventListener('change', function() {
        const characterNameInput = document.getElementById('character_name');
        const role = this.value;
        
        if (role === 'character' && !characterNameInput.value) {
            characterNameInput.placeholder = 'نام شخصیت (مثال: شاهزاده، جنگجو، جادوگر)';
        } else if (role === 'narrator') {
            characterNameInput.placeholder = 'نام شخصیت (اختیاری)';
        }
    });

    // Initialize time display
    updateTimeDisplay();
});
</script>
@endpush
