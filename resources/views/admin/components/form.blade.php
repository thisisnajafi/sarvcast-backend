{{-- Standardized Form Component --}}
<form method="{{ $method ?? 'POST' }}" action="{{ $action }}" enctype="{{ $enctype ?? 'multipart/form-data' }}" class="space-y-6">
    @csrf
    @if(isset($method) && $method !== 'POST')
        @method($method)
    @endif

    {{-- Form Header --}}
    @if(isset($title))
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-lg font-medium text-gray-900 mb-2">{{ $title }}</h2>
        @if(isset($subtitle))
        <p class="text-sm text-gray-600">{{ $subtitle }}</p>
        @endif
    </div>
    @endif

    {{-- Form Fields --}}
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($fields as $field)
                @if($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'password' || $field['type'] === 'number')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $field['label'] }}
                        @if(isset($field['required']) && $field['required'])
                        <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <input type="{{ $field['type'] }}" 
                           id="{{ $field['name'] }}" 
                           name="{{ $field['name'] }}" 
                           value="{{ old($field['name'], $field['value'] ?? '') }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error($field['name']) border-red-500 @enderror"
                           placeholder="{{ $field['placeholder'] ?? '' }}"
                           {{ isset($field['required']) && $field['required'] ? 'required' : '' }}
                           {{ isset($field['disabled']) && $field['disabled'] ? 'disabled' : '' }}>
                    @error($field['name'])
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if(isset($field['help']))
                    <p class="mt-1 text-sm text-gray-500">{{ $field['help'] }}</p>
                    @endif
                </div>
                
                @elseif($field['type'] === 'textarea')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $field['label'] }}
                        @if(isset($field['required']) && $field['required'])
                        <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <textarea id="{{ $field['name'] }}" 
                              name="{{ $field['name'] }}" 
                              rows="{{ $field['rows'] ?? 3 }}"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error($field['name']) border-red-500 @enderror"
                              placeholder="{{ $field['placeholder'] ?? '' }}"
                              {{ isset($field['required']) && $field['required'] ? 'required' : '' }}
                              {{ isset($field['disabled']) && $field['disabled'] ? 'disabled' : '' }}>{{ old($field['name'], $field['value'] ?? '') }}</textarea>
                    @error($field['name'])
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if(isset($field['help']))
                    <p class="mt-1 text-sm text-gray-500">{{ $field['help'] }}</p>
                    @endif
                </div>
                
                @elseif($field['type'] === 'select')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $field['label'] }}
                        @if(isset($field['required']) && $field['required'])
                        <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <select id="{{ $field['name'] }}" 
                            name="{{ $field['name'] }}"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error($field['name']) border-red-500 @enderror"
                            {{ isset($field['required']) && $field['required'] ? 'required' : '' }}
                            {{ isset($field['disabled']) && $field['disabled'] ? 'disabled' : '' }}>
                        @if(isset($field['placeholder']))
                        <option value="">{{ $field['placeholder'] }}</option>
                        @endif
                        @foreach($field['options'] as $value => $label)
                        <option value="{{ $value }}" {{ old($field['name'], $field['value'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error($field['name'])
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if(isset($field['help']))
                    <p class="mt-1 text-sm text-gray-500">{{ $field['help'] }}</p>
                    @endif
                </div>
                
                @elseif($field['type'] === 'checkbox')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="{{ $field['name'] }}" 
                               name="{{ $field['name'] }}" 
                               value="1"
                               {{ old($field['name'], $field['value'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="{{ $field['name'] }}" class="mr-2 text-sm font-medium text-gray-700">
                            {{ $field['label'] }}
                        </label>
                    </div>
                    @error($field['name'])
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if(isset($field['help']))
                    <p class="mt-1 text-sm text-gray-500">{{ $field['help'] }}</p>
                    @endif
                </div>
                
                @elseif($field['type'] === 'file')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $field['label'] }}
                        @if(isset($field['required']) && $field['required'])
                        <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors duration-200">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="{{ $field['name'] }}" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>آپلود فایل</span>
                                    <input type="file" 
                                           id="{{ $field['name'] }}" 
                                           name="{{ $field['name'] }}" 
                                           class="sr-only"
                                           {{ isset($field['required']) && $field['required'] ? 'required' : '' }}
                                           {{ isset($field['accept']) ? 'accept="' . $field['accept'] . '"' : '' }}>
                                </label>
                                <p class="pr-1">یا کشیدن و رها کردن</p>
                            </div>
                            <p class="text-xs text-gray-500">{{ $field['help'] ?? 'PNG, JPG, GIF تا 10MB' }}</p>
                        </div>
                    </div>
                    @error($field['name'])
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                @elseif($field['type'] === 'custom')
                <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                    {!! $field['content'] !!}
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center justify-end space-x-3 space-x-reverse">
            @if(isset($cancelUrl))
            <a href="{{ $cancelUrl }}" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                انصراف
            </a>
            @endif
            
            <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                {{ $submitText ?? 'ذخیره' }}
            </button>
        </div>
    </div>
</form>
