{{-- Voice Actor Form Partial --}}
<div class="voice-actor-form-container">
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
                    @if(isset($people))
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" 
                                    data-roles="{{ json_encode($person->roles ?? []) }}"
                                    data-bio="{{ $person->bio }}"
                                    data-image="{{ $person->image_url }}"
                                    {{ (isset($voiceActor) && $person->id == $voiceActor->person_id) ? 'selected' : '' }}>
                                {{ $person->name }}
                                @if($person->is_verified)
                                    <span class="text-green-500">✓</span>
                                @endif
                            </option>
                        @endforeach
                    @endif
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
        <div id="person-info" class="md:col-span-2 {{ isset($voiceActor) ? '' : 'hidden' }}">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <img id="person-image" 
                         src="{{ isset($voiceActor) ? ($voiceActor->person->image_url ?: '/images/default-avatar.png') : '' }}" 
                         alt="" 
                         class="w-16 h-16 rounded-full object-cover">
                    <div>
                        <h3 id="person-name" class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ isset($voiceActor) ? $voiceActor->person->name : '' }}
                        </h3>
                        <p id="person-bio" class="text-sm text-gray-600 dark:text-gray-400">
                            {{ isset($voiceActor) ? ($voiceActor->person->bio ?: 'بدون توضیحات') : '' }}
                        </p>
                        <div id="person-roles" class="flex flex-wrap gap-2 mt-2">
                            @if(isset($voiceActor) && $voiceActor->person->roles)
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
                <option value="narrator" {{ (isset($voiceActor) && $voiceActor->role === 'narrator') ? 'selected' : '' }}>راوی</option>
                <option value="character" {{ (isset($voiceActor) && $voiceActor->role === 'character') ? 'selected' : '' }}>شخصیت</option>
                <option value="voice_over" {{ (isset($voiceActor) && $voiceActor->role === 'voice_over') ? 'selected' : '' }}>صداگذاری</option>
                <option value="background" {{ (isset($voiceActor) && $voiceActor->role === 'background') ? 'selected' : '' }}>پس‌زمینه</option>
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
                   value="{{ old('character_name', isset($voiceActor) ? $voiceActor->character_name : '') }}"
                   placeholder="نام شخصیت (اختیاری)"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            @error('character_name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Time Range (Hidden - automatically set to full episode duration) -->
        <input type="hidden" id="start_time" name="start_time" value="0">
        <input type="hidden" id="end_time" name="end_time" value="{{ isset($episode) ? $episode->duration : 0 }}">
        
        <!-- Note about time range -->
        <div class="col-span-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">توجه:</p>
                    <p>صداپیشه به طور خودکار برای کل مدت زمان اپیزود ({{ isset($episode) ? gmdate('i:s', $episode->duration) : '00:00' }}) تنظیم می‌شود.</p>
                </div>
            </div>
        </div>

        <!-- Time Range Display -->
        <div class="md:col-span-2">
            <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">پیش‌نمایش زمان</h4>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm text-blue-700 dark:text-blue-300">شروع:</span>
                        <span id="start-time-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">
                            {{ isset($voiceActor) ? gmdate('i:s', $voiceActor->start_time) : '00:00' }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm text-blue-700 dark:text-blue-300">پایان:</span>
                        <span id="end-time-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">
                            {{ isset($voiceActor) ? gmdate('i:s', $voiceActor->end_time) : '00:00' }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-sm text-blue-700 dark:text-blue-300">مدت:</span>
                        <span id="duration-display" class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">
                            {{ isset($voiceActor) ? ($voiceActor->end_time - $voiceActor->start_time) . ' ثانیه' : '0 ثانیه' }}
                        </span>
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
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">{{ old('voice_description', isset($voiceActor) ? $voiceActor->voice_description : '') }}</textarea>
            @error('voice_description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Primary Voice Actor -->
        <div class="md:col-span-2">
            <div class="flex items-center">
                <input type="checkbox" id="is_primary" name="is_primary" value="1"
                       {{ old('is_primary', isset($voiceActor) ? $voiceActor->is_primary : false) ? 'checked' : '' }}
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
</div>
