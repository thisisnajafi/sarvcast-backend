{{-- Standardized Filter Section Component --}}
<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search Input --}}
            @if(isset($searchable))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="block w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="{{ $searchPlaceholder ?? 'جستجو...' }}">
                </div>
            </div>
            @endif

            {{-- Status Filter --}}
            @if(isset($statusFilter))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach($statusOptions ?? [] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Category Filter --}}
            @if(isset($categoryFilter))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                <select name="category" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه دسته‌ها</option>
                    @foreach($categoryOptions ?? [] as $value => $label)
                    <option value="{{ $value }}" {{ request('category') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Date Range Filter --}}
            @if(isset($dateFilter))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">بازه زمانی</label>
                <select name="date_range" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">همه زمان‌ها</option>
                    <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>امروز</option>
                    <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>هفته گذشته</option>
                    <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>ماه گذشته</option>
                    <option value="year" {{ request('date_range') == 'year' ? 'selected' : '' }}>سال گذشته</option>
                </select>
            </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    فیلتر
                </button>
                
                <a href="{{ request()->url() }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    پاک کردن
                </a>
            </div>

            @if(isset($exportable))
            <div class="flex items-center space-x-3 space-x-reverse">
                <button type="button" onclick="exportData()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    صادرات
                </button>
            </div>
            @endif
        </div>
    </form>
</div>
