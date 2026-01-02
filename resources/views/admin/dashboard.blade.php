@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('page-title', 'داشبورد')

@section('content')
<!-- Global Search Bar -->
<div class="mb-4 sm:mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
        <div class="relative">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input 
                type="text" 
                id="globalSearch" 
                placeholder="جستجوی سریع: داستان‌ها، اپیزودها، کاربران..." 
                class="w-full pr-10 pl-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
                onkeyup="handleGlobalSearch(this.value)"
                onfocus="showSearchSuggestions()"
                onblur="setTimeout(() => hideSearchSuggestions(), 200)"
            >
            <div id="searchSuggestions" class="hidden absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                <div id="searchResults" class="p-2">
                    <!-- Search results will be populated here -->
                </div>
                <div id="searchHistory" class="border-t border-gray-200 dark:border-gray-700 p-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">تاریخچه جستجو</span>
                        <button onclick="clearSearchHistory()" class="text-xs text-red-600 dark:text-red-400 hover:underline">پاک کردن</button>
                    </div>
                    <div id="searchHistoryList" class="space-y-1">
                        <!-- Search history will be populated here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">جستجوی سریع:</span>
            <button onclick="quickSearch('stories')" class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">داستان‌ها</button>
            <button onclick="quickSearch('episodes')" class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">اپیزودها</button>
            <button onclick="quickSearch('users')" class="px-3 py-1 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">کاربران</button>
            <button onclick="quickSearch('payments')" class="px-3 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">پرداخت‌ها</button>
            <button onclick="quickSearch('comments')" class="px-3 py-1 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">نظرات</button>
        </div>
    </div>
</div>

<!-- Smart Filters Panel -->
<div id="widget-smartFilters" class="mb-4 sm:mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">فیلترهای هوشمند</h3>
            <button onclick="toggleFiltersPanel()" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                <svg id="filtersToggleIcon" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
    </div>
    <div id="filtersPanelContent" class="p-4 space-y-4">
        <!-- Quick Filter Buttons -->
        <div>
            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 block">فیلترهای سریع:</label>
            <div class="flex flex-wrap gap-2">
                <button onclick="applyQuickFilter('active_users')" class="px-3 py-1.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">کاربران فعال</button>
                <button onclick="applyQuickFilter('premium_stories')" class="px-3 py-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">داستان‌های پریمیوم</button>
                <button onclick="applyQuickFilter('pending_comments')" class="px-3 py-1.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-lg hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors">نظرات در انتظار</button>
                <button onclick="applyQuickFilter('recent_payments')" class="px-3 py-1.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">پرداخت‌های اخیر</button>
                <button onclick="applyQuickFilter('top_stories')" class="px-3 py-1.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">داستان‌های پربازدید</button>
                <button onclick="clearAllFilters()" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">پاک کردن همه</button>
            </div>
        </div>
        
        <!-- Filter Presets -->
        <div>
            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 block">پیش‌تنظیمات:</label>
            <div class="flex flex-wrap gap-2">
                <button onclick="loadFilterPreset('today')" class="px-3 py-1.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">امروز</button>
                <button onclick="loadFilterPreset('this_week')" class="px-3 py-1.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">این هفته</button>
                <button onclick="loadFilterPreset('this_month')" class="px-3 py-1.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">این ماه</button>
                <button onclick="saveCurrentFilters()" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                    ذخیره فیلترها
                </button>
            </div>
        </div>
        
        <!-- Multi-criteria Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">نوع محتوا:</label>
                <select id="filterContentType" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">همه</option>
                    <option value="stories">داستان‌ها</option>
                    <option value="episodes">اپیزودها</option>
                    <option value="users">کاربران</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">وضعیت:</label>
                <select id="filterStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">همه</option>
                    <option value="active">فعال</option>
                    <option value="inactive">غیرفعال</option>
                    <option value="pending">در انتظار</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">بازه زمانی:</label>
                <select id="filterTimeRange" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">همه</option>
                    <option value="today">امروز</option>
                    <option value="week">این هفته</option>
                    <option value="month">این ماه</option>
                    <option value="year">امسال</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">مرتب‌سازی:</label>
                <select id="filterSortBy" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <option value="newest">جدیدترین</option>
                    <option value="oldest">قدیمی‌ترین</option>
                    <option value="popular">محبوب‌ترین</option>
                    <option value="name">بر اساس نام</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="mb-4 sm:mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="dateRange" class="text-sm font-medium text-gray-700 dark:text-gray-300">بازه زمانی:</label>
                    <select id="dateRange" name="date_range" onchange="applyDateRange(this.value)" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                        <option value="today" {{ request('date_range', '30days') == 'today' ? 'selected' : '' }}>امروز</option>
                        <option value="7days" {{ request('date_range') == '7days' ? 'selected' : '' }}>۷ روز گذشته</option>
                        <option value="30days" {{ request('date_range', '30days') == '30days' ? 'selected' : '' }}>۳۰ روز گذشته</option>
                        <option value="3months" {{ request('date_range') == '3months' ? 'selected' : '' }}>۳ ماه گذشته</option>
                        <option value="1year" {{ request('date_range') == '1year' ? 'selected' : '' }}>یک سال گذشته</option>
                        <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>سفارشی</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" id="compareMode" onchange="toggleCompareMode(this.checked)" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <span>مقایسه با دوره قبل</span>
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="toggleCustomization()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm flex items-center gap-2" title="سفارشی‌سازی داشبورد">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    سفارشی‌سازی
                </button>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh(this.checked)" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <span>به‌روزرسانی خودکار</span>
                </label>
                <select id="refreshInterval" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-xs" onchange="updateRefreshInterval(this.value)">
                    <option value="30">۳۰ ثانیه</option>
                    <option value="60" selected>۱ دقیقه</option>
                    <option value="300">۵ دقیقه</option>
                </select>
                <button onclick="refreshDashboard()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    به‌روزرسانی
                </button>
                <a href="{{ route('admin.dashboard.export', request()->all()) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    خروجی CSV
                </a>
                <span id="lastUpdate" class="text-xs text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>
        
        <!-- Customization Panel -->
        <div id="customizationPanel" class="hidden mt-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">سفارشی‌سازی داشبورد</h3>
                <button onclick="toggleCustomization()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="statsCards" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">کارت‌های آمار</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="performanceMetrics" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">معیارهای عملکرد</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="charts" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">نمودارها</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="topStories" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">داستان‌های پربازدید</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="recentStories" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">داستان‌های اخیر</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="recentUsers" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">کاربران اخیر</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="recentPayments" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">پرداخت‌های اخیر</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="recentComments" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">نظرات اخیر</span>
                    </label>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="widget-toggle" data-widget="recentReports" checked onchange="toggleWidget(this)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">گزارش‌های اخیر</span>
                    </label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button onclick="resetCustomization()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                    بازنشانی به پیش‌فرض
                </button>
                <button onclick="saveCustomization()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm">
                    ذخیره تنظیمات
                </button>
            </div>
        </div>
        </div>
        <!-- Custom Date Range (hidden by default) -->
        <div id="customDateRange" class="mt-4 hidden flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">از تاریخ:</label>
            <input type="date" id="dateFrom" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">تا تاریخ:</label>
            <input type="date" id="dateTo" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
            <button onclick="applyCustomDateRange()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm">اعمال</button>
        </div>
        
        <!-- Comparison Period (hidden by default) -->
        <div id="comparisonPeriod" class="mt-4 hidden bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">مقایسه با:</label>
                <select id="comparePeriod" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <option value="previous">دوره قبل (همان مدت)</option>
                    <option value="lastYear">همان دوره سال قبل</option>
                    <option value="custom">دوره سفارشی</option>
                </select>
                <div id="customCompareDates" class="hidden flex items-center gap-2">
                    <input type="date" id="compareDateFrom" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                    <span class="text-sm text-gray-600 dark:text-gray-400">تا</span>
                    <input type="date" id="compareDateTo" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <button onclick="applyComparison()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">اعمال مقایسه</button>
            </div>
        </div>
    </div>
</div>
<!-- Alerts & Notifications Section -->
@if(isset($alerts) && count($alerts) > 0)
<div id="dashboardAlerts" class="mb-4 sm:mb-6 space-y-3">
    @foreach($alerts as $alert)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-l-4 
        @if($alert['type'] == 'danger') border-red-500 bg-red-50 dark:bg-red-900/20
        @elseif($alert['type'] == 'warning') border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20
        @elseif($alert['type'] == 'info') border-blue-500 bg-blue-50 dark:bg-blue-900/20
        @else border-gray-500 bg-gray-50 dark:bg-gray-900/20
        @endif
        p-4 flex items-start gap-3">
        <div class="flex-shrink-0">
            @if($alert['icon'] == 'exclamation-triangle')
            <svg class="w-6 h-6 
                @if($alert['type'] == 'danger') text-red-600 dark:text-red-400
                @elseif($alert['type'] == 'warning') text-yellow-600 dark:text-yellow-400
                @else text-gray-600 dark:text-gray-400
                @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            @elseif($alert['icon'] == 'user-minus')
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            @elseif($alert['icon'] == 'clock')
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            @elseif($alert['icon'] == 'chart-line')
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            @elseif($alert['icon'] == 'comment')
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            @elseif($alert['icon'] == 'users')
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            @elseif($alert['icon'] == 'exclamation-circle')
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ $alert['title'] }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $alert['message'] }}</p>
            @if(isset($alert['action']) && $alert['action'])
            <a href="{{ $alert['action'] }}" class="mt-2 inline-block text-sm font-medium 
                @if($alert['type'] == 'danger') text-red-700 dark:text-red-400 hover:text-red-900
                @elseif($alert['type'] == 'warning') text-yellow-700 dark:text-yellow-400 hover:text-yellow-900
                @elseif($alert['type'] == 'info') text-blue-700 dark:text-blue-400 hover:text-blue-900
                @else text-gray-700 dark:text-gray-400 hover:text-gray-900
                @endif">
                مشاهده جزئیات →
            </a>
            @endif
        </div>
        <button onclick="dismissAlert(this)" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="بستن">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    @endforeach
</div>
@endif

<!-- Quick Actions Panel -->
<div id="widget-quickActions" class="mb-4 sm:mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
    <a href="{{ route('admin.stories.create') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">داستان جدید</span>
    </a>
    
    <a href="{{ route('admin.episodes.create') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">اپیزود جدید</span>
    </a>
    
    <a href="{{ route('admin.users.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">مدیریت کاربران</span>
    </a>
    
    <a href="{{ route('admin.payments.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50 transition-colors">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">پرداخت‌ها</span>
    </a>
    
    <a href="{{ route('admin.comments.pending') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/50 transition-colors">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">نظرات</span>
    </a>
    
    <a href="{{ route('admin.categories.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col items-center justify-center text-center group">
        <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:bg-pink-200 dark:group-hover:bg-pink-900/50 transition-colors">
            <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
        </div>
        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">دسته‌بندی‌ها</span>
    </a>
</div>

<!-- Welcome Section -->
<div class="mb-6 sm:mb-8" id="widget-welcome">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold mb-1 sm:mb-2 truncate">
                    خوش آمدید به پنل مدیریت سروکست
                </h1>
                <p class="text-sm sm:text-base text-blue-100">
                    مدیریت کامل پلتفرم داستان‌های صوتی کودکان
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-4 sm:gap-6">
                    <div class="text-center min-w-[90px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_users']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">کل کاربران</div>
                    </div>
                    <div class="text-center min-w-[90px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_stories']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">کل داستان‌ها</div>
                    </div>
                    <div class="text-center min-w-[120px]">
                        <div class="text-xl sm:text-2xl font-bold">{{ number_format($stats['total_revenue']) }}</div>
                        <div class="text-xs sm:text-sm text-blue-100">درآمد کل (تومان)</div>
                    </div>
                </div>
            </div>
            <div class="hidden md:block flex-shrink-0">
                <div class="w-16 h-16 lg:w-20 lg:h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 lg:w-10 lg:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Statistics Overview -->
<div id="widget-statsCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Total Users Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">کل کاربران</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ number_format($stats['total_users']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium">{{ number_format($stats['active_users']) }} فعال</span>
                    <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">{{ number_format($stats['pending_users']) }} در انتظار</span>
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">{{ number_format($stats['blocked_users']) }} مسدود</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['user_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">امروز</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['new_users_today']) }} کاربر جدید</span>
            </div>
        </div>
    </div>

    <!-- Total Stories Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">کل داستان‌ها</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_stories']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['published_stories']) }} منتشر شده</span>
                    <span class="text-sm text-yellow-600 font-medium">{{ number_format($stats['pending_stories']) }} در انتظار</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">{{ number_format($stats['draft_stories']) }} پیش‌نویس</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-green-500 to-green-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['story_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">کل اپیزودها</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['total_episodes']) }}</span>
            </div>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">درآمد کل</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_revenue']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['total_payments']) }} پرداخت موفق</span>
                    <span class="text-sm text-yellow-600 font-medium">{{ number_format($stats['pending_payments']) }} در انتظار</span>
                    <span class="text-sm text-red-600 font-medium">{{ number_format($stats['failed_payments']) }} ناموفق</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">رشد این ماه</span>
                <span class="text-green-600 font-medium">+{{ $stats['revenue_growth_rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">امروز</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['revenue_today'] ?? 0) }} تومان</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">این هفته</span>
                <span class="text-green-600 font-medium">{{ number_format($stats['revenue_this_week'] ?? 0) }} تومان</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">این ماه</span>
                <span class="text-purple-600 font-medium">{{ number_format($stats['revenue_this_month'] ?? 0) }} تومان</span>
            </div>
        </div>
    </div>

    <!-- Engagement Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-4 sm:p-5 md:p-6 border border-gray-100 dark:border-gray-700 min-w-0 overflow-hidden">
        <div class="flex items-center justify-between min-w-0">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1 whitespace-normal leading-snug">تعامل کاربران</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_comments']) }}</p>
                <div class="flex items-center mt-2 space-x-4 space-x-reverse text-xs sm:text-sm flex-wrap">
                    <span class="text-sm text-blue-600 font-medium">{{ number_format($stats['total_ratings']) }} امتیاز</span>
                    <span class="text-sm text-purple-600 font-medium">{{ number_format($stats['total_favorites']) }} علاقه‌مندی</span>
                    <span class="text-sm text-green-600 font-medium">{{ number_format($stats['total_play_history']) }} پخش</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">میانگین امتیاز</span>
                <span class="text-yellow-600 font-medium">{{ number_format($stats['average_rating'], 1) }}/5</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-gray-500">اشتراک‌گذاری</span>
                <span class="text-blue-600 font-medium">{{ number_format($stats['total_content_shares']) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Statistics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Subscriptions -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">اشتراک‌ها</h3>
            <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-green-600">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">کل اشتراک‌ها</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">فعال</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['active_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">منقضی شده</span>
                <span class="font-semibold text-red-600">{{ number_format($stats['expired_subscriptions']) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">لغو شده</span>
                <span class="font-semibold text-gray-600 dark:text-gray-300">{{ number_format($stats['cancelled_subscriptions']) }}</span>
            </div>
        </div>
    </div>

    <!-- Play History Analytics -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">تاریخچه پخش داستان‌ها</h3>
            <div class="p-3 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">امروز</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['play_history_today'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">این هفته</span>
                <span class="font-semibold text-blue-600">{{ number_format($stats['play_history_this_week'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">این ماه</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['play_history_this_month'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-300">امسال</span>
                <span class="font-semibold text-purple-600">{{ number_format($stats['play_history_this_year'] ?? 0) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Plan Sales Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Plan Sales Today -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 sm:p-6 border border-blue-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-blue-800 mb-1 whitespace-normal leading-snug">فروش امروز</p>
                <p class="text-xl sm:text-2xl font-bold text-blue-900">{{ number_format($stats['plan_sales_today']) }}</p>
                <p class="text-xs text-blue-700 mt-1">اشتراک فروخته شده</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Plan Sales This Week -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 sm:p-6 border border-green-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-green-800 mb-1 whitespace-normal leading-snug">فروش این هفته</p>
                <p class="text-xl sm:text-2xl font-bold text-green-900">{{ number_format($stats['plan_sales_this_week']) }}</p>
                <p class="text-xs text-green-700 mt-1">اشتراک فروخته شده</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Plan Sales Growth -->
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 sm:p-6 border border-purple-200 min-w-0">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-purple-800 mb-1 whitespace-normal leading-snug">رشد فروش</p>
                <p class="text-xl sm:text-2xl font-bold text-purple-900">+{{ $stats['plan_sales_growth_rate'] }}%</p>
                <p class="text-xs text-purple-700 mt-1">نسبت به ماه قبل</p>
            </div>
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">عملیات سریع</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4">
            <a href="{{ route('admin.stories.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    داستان جدید
                </span>
            </a>
            <a href="{{ route('admin.episodes.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    اپیزود جدید
                </span>
            </a>
            <a href="{{ route('admin.categories.create') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    دسته‌بندی جدید
                </span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    مدیریت کاربران
                </span>
            </a>
            <a href="{{ route('admin.payments.index') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    پرداخت‌ها
                </span>
            </a>
            <a href="{{ route('admin.analytics') }}" class="flex flex-col items-center justify-center min-h-[4.5rem] p-3 sm:p-4 rounded-lg bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 transition-all duration-200 group">
                <div class="w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 text-center whitespace-normal leading-snug">
                    آمار و تحلیل
                </span>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity Feed -->
@if(isset($recentActivity) && $recentActivity->count() > 0)
<div id="widget-recentActivity" class="mb-6 sm:mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">فعالیت‌های اخیر</h3>
            <button onclick="toggleActivityFeed()" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                <svg id="activityToggleIcon" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
    </div>
    <div id="activityFeedContent" class="divide-y divide-gray-200 dark:divide-gray-700">
        @foreach($recentActivity as $activity)
        <a href="{{ $activity['url'] }}" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                        @if($activity['color'] == 'blue') bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400
                        @elseif($activity['color'] == 'green') bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400
                        @elseif($activity['color'] == 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400
                        @elseif($activity['color'] == 'purple') bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400
                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400
                        @endif">
                        @if($activity['icon'] == 'user-plus')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        @elseif($activity['icon'] == 'book')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        @elseif($activity['icon'] == 'dollar-sign')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @elseif($activity['icon'] == 'comment')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['title'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $activity['description'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ \Morilog\Jalali\Jalalian::fromCarbon($activity['time'])->ago() }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Content Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Recent Stories -->
    <div id="widget-recentStories" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین داستان‌ها</h3>
                <a href="{{ route('admin.stories.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentStories->count() > 0)
                <div class="space-y-4">
                    @foreach($recentStories as $story)
                    <div class="flex items-center space-x-4 space-x-reverse p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-xl object-cover shadow-sm flex-shrink-0" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
                            <p class="text-sm text-gray-500">{{ $story->category->name ?? 'بدون دسته‌بندی' }}</p>
                            <p class="text-xs text-gray-400 mt-1">@jalaliRelative($story->created_at)</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($story->status === 'published') bg-green-100 text-green-800
                                @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($story->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ داستانی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    <div id="widget-recentUsers" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین کاربران</h3>
                <a href="{{ route('admin.users.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentUsers->count() > 0)
                <div class="space-y-4">
                    @foreach($recentUsers as $user)
                    <div class="flex items-center space-x-4 space-x-reverse p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="relative">
                            <img src="{{ $user->profile_image_url ?: '/images/placeholder-user.jpg' }}" alt="{{ $user->first_name }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover shadow-sm flex-shrink-0" loading="lazy">
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white
                                @if($user->status === 'active') bg-green-500
                                @elseif($user->status === 'pending') bg-yellow-500
                                @else bg-red-500
                                @endif">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->first_name }} {{ $user->last_name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->phone_number }}</p>
                            <p class="text-xs text-gray-400 mt-1">@jalaliRelative($user->created_at)</p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($user->status === 'active') bg-green-100 text-green-800
                                @elseif($user->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ کاربری یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Payments -->
    <div id="widget-recentPayments" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-yellow-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین پرداخت‌ها</h3>
                <a href="{{ route('admin.payments.index') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentPayments->count() > 0)
                <div class="space-y-4">
                    @foreach($recentPayments as $payment)
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($payment->amount) }} تومان</p>
                                <p class="text-sm text-gray-500">{{ $payment->user->first_name ?? 'کاربر ناشناس' }}</p>
                                <p class="text-xs text-gray-400 mt-1">@jalaliRelative($payment->created_at)</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($payment->status === 'completed') bg-green-100 text-green-800
                                @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ پرداختی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Plan Sales Details Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
    <!-- Top Selling Plans -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">پرفروش‌ترین پلن‌ها</h3>
                <a href="{{ route('admin.subscription-plans.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topSellingPlans->count() > 0)
                <div class="space-y-4">
                    @foreach($topSellingPlans as $plan)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ number_format($plan->price) }} تومان</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $plan->subscriptions_count }}</div>
                                <div class="text-xs text-gray-500">فروش</div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $plan->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ پلنی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Plan Sales Analytics -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار فروش پلن‌ها</h3>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($planSalesData->count() > 0)
                <div class="space-y-4">
                    @foreach($planSalesData as $planSale)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    @if($planSale->type === '1month') یک ماهه
                                    @elseif($planSale->type === '3months') سه ماهه
                                    @elseif($planSale->type === '6months') شش ماهه
                                    @elseif($planSale->type === '1year') یک ساله
                                    @else {{ $planSale->type }}
                                    @endif
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ number_format($planSale->count) }} فروش</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($planSale->total_revenue) }}</div>
                            <div class="text-xs text-gray-500">تومان درآمد</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ فروشی یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>
    <!-- Top Categories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">محبوب‌ترین دسته‌بندی‌ها</h3>
                <a href="{{ route('admin.categories.index') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topCategories->count() > 0)
                <div class="space-y-4">
                    @foreach($topCategories as $category)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" style="background-color: {{ $category->color }}20">
                                <div class="w-6 h-6 rounded-full" style="background-color: {{ $category->color }}"></div>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->name }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ $category->stories_count }} داستان</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->stories_count }}</div>
                                <div class="text-xs text-gray-500">داستان</div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ دسته‌بندی‌ای یافت نشد</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Top Rated Stories -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-yellow-100 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">بهترین داستان‌ها</h3>
                <a href="{{ route('admin.stories.index') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">مشاهده همه</a>
            </div>
        </div>
        <div class="p-6">
            @if($topRatedStories->count() > 0)
                <div class="space-y-4">
                    @foreach($topRatedStories as $story)
                    <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-cover shadow-sm flex-shrink-0" loading="lazy">
                            <div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ Str::limit($story->title, 30) }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ $story->ratings_count }} امتیاز</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($story->ratings_avg_rating, 1) }}</div>
                                <div class="text-xs text-gray-500">امتیاز</div>
                            </div>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $story->ratings_avg_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">هیچ داستان امتیازدهی شده‌ای یافت نشد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Top Stories Listened Section -->
<div class="mb-6 sm:mb-8">
    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 sm:mb-6">پربازدیدترین داستان‌ها</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 md:gap-6">
        <!-- Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">امروز</h3>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1">{{ number_format($stats['play_history_today'] ?? 0) }} پخش</p>
            </div>
            <div class="p-4 sm:p-6">
                @if(isset($topStoriesListenedToday) && $topStoriesListenedToday->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStoriesListenedToday->take(5) as $story)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($story->play_histories_count) }} پخش</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">هیچ پخشی ثبت نشده</p>
                @endif
            </div>
        </div>

        <!-- This Week -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">این هفته</h3>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1">{{ number_format($stats['play_history_this_week'] ?? 0) }} پخش</p>
            </div>
            <div class="p-4 sm:p-6">
                @if(isset($topStoriesListenedThisWeek) && $topStoriesListenedThisWeek->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStoriesListenedThisWeek->take(5) as $story)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($story->play_histories_count) }} پخش</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">هیچ پخشی ثبت نشده</p>
                @endif
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">این ماه</h3>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1">{{ number_format($stats['play_history_this_month'] ?? 0) }} پخش</p>
            </div>
            <div class="p-4 sm:p-6">
                @if(isset($topStoriesListenedThisMonth) && $topStoriesListenedThisMonth->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStoriesListenedThisMonth->take(5) as $story)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($story->play_histories_count) }} پخش</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">هیچ پخشی ثبت نشده</p>
                @endif
            </div>
        </div>

        <!-- This Year -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">امسال</h3>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1">{{ number_format($stats['play_history_this_year'] ?? 0) }} پخش</p>
            </div>
            <div class="p-4 sm:p-6">
                @if(isset($topStoriesListenedThisYear) && $topStoriesListenedThisYear->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStoriesListenedThisYear->take(5) as $story)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">{{ $story->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($story->play_histories_count) }} پخش</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">هیچ پخشی ثبت نشده</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Analytics Chart Section -->
<div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار و تحلیل‌ها</h3>
    </div>
    <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 md:gap-6 mb-6 sm:mb-8">
            <!-- Conversion Rate -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-800 mb-1">نرخ تبدیل</p>
                        <p class="text-3xl font-bold text-green-900">{{ number_format(($stats['active_subscriptions'] / max($stats['total_users'], 1)) * 100, 1) }}%</p>
                        <p class="text-xs text-green-700 mt-1">اشتراک فعال / کل کاربران</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Session Time -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-800 mb-1">میانگین زمان پخش</p>
                        <p class="text-3xl font-bold text-blue-900">{{ number_format($stats['total_play_history'] / max($stats['total_users'], 1), 1) }}</p>
                        <p class="text-xs text-blue-700 mt-1">پخش به ازای هر کاربر</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- User Satisfaction -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-800 mb-1">رضایت کاربران</p>
                        <p class="text-3xl font-bold text-purple-900">{{ number_format($stats['average_rating'], 1) }}/5</p>
                        <p class="text-xs text-purple-700 mt-1">میانگین امتیاز</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Revenue per User -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-xl border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-yellow-800 mb-1">درآمد به ازای کاربر</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ number_format($stats['total_revenue'] / max($stats['total_users'], 1)) }}</p>
                        <p class="text-xs text-yellow-700 mt-1">تومان</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voice Actors Analytics Section -->
        @if(isset($voiceActorsAnalytics) && count($voiceActorsAnalytics) > 0)
        <div id="widget-voiceActorsAnalytics" class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار صداپیشه‌ها</h3>
                    <a href="{{ route('admin.voice-actors.index') }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 text-sm font-medium">مشاهده همه</a>
                </div>
            </div>
            <div class="p-6">
                <!-- Top Voice Actors Chart -->
                <div class="mb-6">
                    <canvas id="voiceActorsChart" height="300"></canvas>
                </div>
                
                <!-- Voice Actors Performance Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">صداپیشه</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">داستان‌های روایت شده</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">داستان‌های نوشته شده</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تعداد پخش</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">میانگین امتیاز</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نرخ تعامل</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(array_slice($voiceActorsAnalytics, 0, 10) as $actor)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($actor['profile_image'])
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $actor['profile_image'] }}" alt="{{ $actor['name'] }}">
                                        @else
                                        <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                            <span class="text-purple-600 dark:text-purple-400 font-medium">{{ substr($actor['name'], 0, 1) }}</span>
                                        </div>
                                        @endif
                                        <div class="mr-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $actor['name'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $actor['phone'] ?? 'بدون شماره' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ number_format($actor['total_stories_narrated']) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ number_format($actor['total_stories_authored']) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($actor['total_plays']) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($actor['average_rating'], 1) }}</div>
                                        @if($actor['average_rating'] >= 4)
                                        <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ number_format($actor['engagement_rate'], 1) }}</div>
                                        <div class="mr-2 w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ min(100, ($actor['engagement_rate'] / 10) * 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-sm text-purple-600 dark:text-purple-400 mb-1">کل صداپیشه‌ها</div>
                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ count($voiceActorsAnalytics) }}</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">کل داستان‌های روایت شده</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format(array_sum(array_column($voiceActorsAnalytics, 'total_stories_narrated'))) }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-sm text-green-600 dark:text-green-400 mb-1">میانگین امتیاز</div>
                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format(array_sum(array_column($voiceActorsAnalytics, 'average_rating')) / count($voiceActorsAnalytics), 1) }}</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 mb-1">کل پخش‌ها</div>
                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ number_format(array_sum(array_column($voiceActorsAnalytics, 'total_plays'))) }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Content Moderation Dashboard Section -->
        @if(isset($moderationAnalytics))
        <div id="widget-moderationDashboard" class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">داشبورد مدیریت محتوا</h3>
                    <a href="{{ route('admin.comments.pending') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 text-sm font-medium">مشاهده همه</a>
                </div>
            </div>
            <div class="p-6">
                <!-- Moderation Metrics Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                        <div class="text-sm text-red-600 dark:text-red-400 mb-1">نظرات در انتظار</div>
                        <div class="text-2xl font-bold text-red-900 dark:text-red-100">{{ number_format($moderationAnalytics['comments']['pending'] ?? 0) }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-sm text-green-600 dark:text-green-400 mb-1">نظرات تایید شده</div>
                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($moderationAnalytics['comments']['approved'] ?? 0) }}</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 mb-1">نظرات رد شده</div>
                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ number_format($moderationAnalytics['comments']['rejected'] ?? 0) }}</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">داستان‌های در انتظار</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($moderationAnalytics['stories']['pending'] ?? 0) }}</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-sm text-purple-600 dark:text-purple-400 mb-1">داستان‌های گزارش شده</div>
                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ number_format($moderationAnalytics['stories']['flagged'] ?? 0) }}</div>
                    </div>
                </div>
                
                <!-- Approval/Rejection Rates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">نرخ تایید/رد</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">نرخ تایید</span>
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($moderationAnalytics['comments']['approval_rate'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $moderationAnalytics['comments']['approval_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">نرخ رد</span>
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ number_format($moderationAnalytics['comments']['rejection_rate'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-red-600 h-2 rounded-full" style="width: {{ $moderationAnalytics['comments']['rejection_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Moderation Activity Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">فعالیت مدیریت محتوا (۳۰ روز گذشته)</h4>
                        <canvas id="moderationActivityChart" height="150"></canvas>
                    </div>
                </div>
                
                <!-- Moderation Queue -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">صف بررسی</h4>
                    @if(isset($moderationAnalytics['queue']) && $moderationAnalytics['queue']->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($moderationAnalytics['queue'] as $item)
                        <a href="{{ $item['url'] }}" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            @if($item['type'] == 'comment') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                            @else bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                            @endif">
                                            {{ $item['type'] == 'comment' ? 'نظر' : 'داستان' }}
                                        </span>
                                        @if(isset($item['story_title']))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">داستان: {{ $item['story_title'] }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $item['content'] }}</p>
                                    @if(isset($item['user_name']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">کاربر: {{ $item['user_name'] }}</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mr-3">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($item['created_at'])->ago() }}
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">هیچ موردی در صف بررسی وجود ندارد</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Device & Platform Analytics Section -->
        @if(isset($devicePlatformAnalytics))
        <div id="widget-devicePlatformAnalytics" class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آمار دستگاه و پلتفرم</h3>
                </div>
            </div>
            <div class="p-6">
                <!-- Platform Distribution Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    @php
                        $platforms = $devicePlatformAnalytics['platforms'] ?? [];
                        $totalPlatformUsers = array_sum($platforms);
                    @endphp
                    @foreach(['Android', 'iOS', 'Web', 'Unknown'] as $platform)
                        @php
                            $count = $platforms[$platform] ?? 0;
                            $percentage = $totalPlatformUsers > 0 ? round(($count / $totalPlatformUsers) * 100, 1) : 0;
                            $colors = [
                                'Android' => ['bg' => 'green', 'text' => 'green'],
                                'iOS' => ['bg' => 'blue', 'text' => 'blue'],
                                'Web' => ['bg' => 'purple', 'text' => 'purple'],
                                'Unknown' => ['bg' => 'gray', 'text' => 'gray']
                            ];
                            $color = $colors[$platform] ?? $colors['Unknown'];
                        @endphp
                        <div class="bg-{{ $color['bg'] }}-50 dark:bg-{{ $color['bg'] }}-900/20 rounded-lg p-4 border border-{{ $color['bg'] }}-200 dark:border-{{ $color['bg'] }}-800">
                            <div class="text-sm text-{{ $color['text'] }}-600 dark:text-{{ $color['text'] }}-400 mb-1">{{ $platform }}</div>
                            <div class="text-2xl font-bold text-{{ $color['text'] }}-900 dark:text-{{ $color['text'] }}-100">{{ number_format($count) }}</div>
                            <div class="text-xs text-{{ $color['text'] }}-500 dark:text-{{ $color['text'] }}-400 mt-1">{{ $percentage }}%</div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Platform Distribution Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">توزیع پلتفرم</h4>
                        <canvas id="platformDistributionChart" height="200"></canvas>
                    </div>
                    
                    <!-- Platform Growth Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">رشد پلتفرم (۳۰ روز گذشته)</h4>
                        <canvas id="platformGrowthChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Platform Engagement & Revenue -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Platform Engagement Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">تعامل پلتفرم</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">پلتفرم</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">کاربران</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">میانگین جلسات</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">میانگین زمان پخش</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($devicePlatformAnalytics['engagement'] ?? [] as $engagement)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $engagement['platform'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ number_format($engagement['user_count']) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ number_format($engagement['avg_sessions'], 1) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ number_format($engagement['avg_play_time'] / 60, 1) }} دقیقه</div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Platform Revenue Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">درآمد پلتفرم</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">پلتفرم</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">درآمد کل</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">کاربران پرداخت‌کننده</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ARPU</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($devicePlatformAnalytics['revenue'] ?? [] as $revenue)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $revenue['platform'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($revenue['total_revenue']) }} ریال</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ number_format($revenue['paying_users']) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ number_format($revenue['arpu']) }} ریال</div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Device Types -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">نوع دستگاه</h4>
                        <div class="space-y-2">
                            @foreach($devicePlatformAnalytics['device_types'] ?? [] as $type => $count)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $type ?? 'نامشخص' }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Top OS Versions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">نسخه‌های سیستم عامل</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach(array_slice($devicePlatformAnalytics['os_versions'] ?? [], 0, 5) as $os)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $os['os'] }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($os['count']) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Retention Rates -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">نرخ نگهداری (۷ روزه)</h4>
                        <div class="space-y-3">
                            @foreach($devicePlatformAnalytics['retention_rates'] ?? [] as $platform => $rate)
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $platform }}</span>
                                    <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ number_format($rate, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $rate }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                @if(isset($devicePlatformAnalytics['active_devices']) && $devicePlatformAnalytics['active_devices'] > 0)
                <div class="mt-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-indigo-600 dark:text-indigo-400 mb-1">دستگاه‌های فعال (۳۰ روز گذشته)</div>
                            <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ number_format($devicePlatformAnalytics['active_devices']) }}</div>
                        </div>
                        <svg class="w-12 h-12 text-indigo-300 dark:text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- System Health Section -->
        @if(isset($systemHealth))
        <div id="widget-systemHealth" class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">وضعیت سلامت سیستم</h3>
                <div class="flex items-center gap-2">
                    @if($systemHealth['overall'] == 'healthy')
                        <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm font-medium">سالم</span>
                    @elseif($systemHealth['overall'] == 'warning')
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-sm font-medium">هشدار</span>
                    @else
                        <span class="px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full text-sm font-medium">مشکل</span>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($systemHealth['checks'] as $checkName => $check)
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-shrink-0">
                        @if($check['icon'] == 'check-circle')
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @elseif($check['icon'] == 'exclamation-circle')
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @elseif($check['icon'] == 'x-circle')
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                            @if($checkName == 'database') پایگاه داده
                            @elseif($checkName == 'cache') سیستم کش
                            @elseif($checkName == 'storage') ذخیره‌سازی
                            @elseif($checkName == 'queue') سیستم صف
                            @elseif($checkName == 'failed_jobs') کارهای ناموفق
                            @elseif($checkName == 'disk_space') فضای دیسک
                            @elseif($checkName == 'memory') حافظه
                            @else {{ $checkName }}
                            @endif
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $check['message'] }}</p>
                        @if(isset($check['percent']))
                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div class="bg-{{ $check['color'] }}-600 h-2 rounded-full" style="width: {{ min($check['percent'], 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Performance Metrics Section -->
        <div id="widget-performanceMetrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 md:gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">نرخ تکمیل</h4>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['avg_completion_rate'] ?? 0, 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">میانگین تکمیل داستان‌ها</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">مدت زمان گوش دادن</h4>
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format(($stats['avg_listening_duration'] ?? 0) / 60, 0) }} دقیقه</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">میانگین مدت زمان پخش</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">کاربران فعال روزانه</h4>
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['dau'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">DAU</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">کاربران فعال هفتگی</h4>
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['wau'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">WAU</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">کاربران فعال ماهانه</h4>
                    <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['mau'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">MAU</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">نرخ حفظ کاربر</h4>
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['retention_rate'] ?? 0, 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">نرخ بازگشت کاربران</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">نرخ ریزش</h4>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['churn_rate'] ?? 0, 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">نرخ ترک کاربران</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">درآمد به ازای هر کاربر</h4>
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['arpu'] ?? 0) }} تومان</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ARPU</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">ارزش طول عمر کاربر</h4>
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['ltv'] ?? 0) }} تومان</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">LTV (تخمینی)</p>
            </div>
        </div>

        <!-- Interactive Charts Section -->
        <div id="widget-charts" class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 md:gap-6">
            <!-- Revenue Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">نمودار درآمد ماهانه</h4>
                <div class="relative" style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- User Growth Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">رشد کاربران روزانه</h4>
                <div class="relative" style="height: 300px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            <!-- Play History Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">تاریخچه پخش (۳۰ روز گذشته)</h4>
                <div class="relative" style="height: 300px;">
                    <canvas id="playHistoryChart"></canvas>
                </div>
            </div>

            <!-- Revenue Breakdown Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">توزیع درآمد بر اساس نوع پلن</h4>
                <div class="relative" style="height: 300px;">
                    <canvas id="revenueBreakdownChart"></canvas>
                </div>
            </div>

            <!-- Engagement Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 border border-gray-200 dark:border-gray-700 lg:col-span-2">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">تحلیل تعاملات (۳۰ روز گذشته)</h4>
                <div class="relative" style="height: 300px;">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reports Section -->
@if($recentReports->count() > 0)
<div id="widget-recentReports" class="mt-6 sm:mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-w-0">
    <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-red-100 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">آخرین گزارش‌ها</h3>
            <a href="{{ route('admin.reports.index') }}" class="text-red-600 hover:text-red-800 text-sm font-medium">مشاهده همه</a>
        </div>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($recentReports as $report)
            <div class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ Str::limit($report->title ?? 'گزارش بدون عنوان', 50) }}</p>
                        <p class="text-sm text-gray-500">{{ $report->user->first_name ?? 'کاربر ناشناس' }}</p>
                        <p class="text-xs text-gray-400 mt-1">@jalaliRelative($report->created_at)</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($report->status === 'resolved') bg-green-100 text-green-800
                        @elseif($report->status === 'pending') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($report->status) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Global chart instances for updating
let revenueChartInstance = null;
let userGrowthChartInstance = null;
let playHistoryChartInstance = null;
let revenueBreakdownChartInstance = null;
let engagementChartInstance = null;
let voiceActorsChartInstance = null;
let moderationActivityChartInstance = null;
let platformDistributionChartInstance = null;
let platformGrowthChartInstance = null;

// Auto-refresh variables
let autoRefreshInterval = null;
let refreshIntervalSeconds = 60; // Default 1 minute

// Auto-refresh functions
function toggleAutoRefresh(enabled) {
    if (enabled) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

function updateRefreshInterval(seconds) {
    refreshIntervalSeconds = parseInt(seconds);
    if (document.getElementById('autoRefresh').checked) {
        stopAutoRefresh();
        startAutoRefresh();
    }
}

function startAutoRefresh() {
    stopAutoRefresh(); // Clear any existing interval
    autoRefreshInterval = setInterval(function() {
        refreshDashboard();
    }, refreshIntervalSeconds * 1000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Dashboard Customization Functions
const STORAGE_KEY = 'dashboard_customization';

function toggleCustomization() {
    const panel = document.getElementById('customizationPanel');
    if (panel) {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            loadCustomization();
        }
    }
}

function loadCustomization() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const preferences = JSON.parse(saved);
            // Apply saved preferences to checkboxes
            Object.keys(preferences).forEach(widget => {
                const checkbox = document.querySelector(`.widget-toggle[data-widget="${widget}"]`);
                if (checkbox) {
                    checkbox.checked = preferences[widget];
                    toggleWidget(checkbox, false); // false = don't save yet
                }
            });
        } catch (e) {
            console.error('Error loading customization:', e);
        }
    } else {
        // Default: all widgets visible
        document.querySelectorAll('.widget-toggle').forEach(checkbox => {
            checkbox.checked = true;
        });
    }
}

function toggleWidget(checkbox, save = true) {
    const widgetId = checkbox.getAttribute('data-widget');
    const widgetElement = document.getElementById(`widget-${widgetId}`);
    
    if (widgetElement) {
        if (checkbox.checked) {
            widgetElement.style.display = '';
            widgetElement.classList.remove('hidden');
        } else {
            widgetElement.style.display = 'none';
        }
    }
    
    if (save) {
        saveCustomization();
    }
}

function saveCustomization() {
    const preferences = {};
    document.querySelectorAll('.widget-toggle').forEach(checkbox => {
        const widgetId = checkbox.getAttribute('data-widget');
        preferences[widgetId] = checkbox.checked;
    });
    
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        // Show success message
        const panel = document.getElementById('customizationPanel');
        if (panel) {
            const successMsg = document.createElement('div');
            successMsg.className = 'mt-2 p-2 bg-green-100 text-green-800 rounded text-sm';
            successMsg.textContent = 'تنظیمات با موفقیت ذخیره شد';
            panel.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        }
    } catch (e) {
        console.error('Error saving customization:', e);
        alert('خطا در ذخیره تنظیمات');
    }
}

function resetCustomization() {
    if (confirm('آیا مطمئن هستید که می‌خواهید تنظیمات را به حالت پیش‌فرض بازگردانید؟')) {
        localStorage.removeItem(STORAGE_KEY);
        // Reset all checkboxes to checked
        document.querySelectorAll('.widget-toggle').forEach(checkbox => {
            checkbox.checked = true;
            toggleWidget(checkbox, false);
        });
        saveCustomization();
    }
}

// Activity Feed Toggle
function toggleActivityFeed() {
    const content = document.getElementById('activityFeedContent');
    const icon = document.getElementById('activityToggleIcon');
    if (content && icon) {
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(180deg)';
        }
    }
}

// Global Search Functions
let searchTimeout;
let searchHistory = JSON.parse(localStorage.getItem('dashboard_search_history') || '[]');

function handleGlobalSearch(query) {
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        hideSearchSuggestions();
        return;
    }
    
    searchTimeout = setTimeout(() => {
        performGlobalSearch(query);
    }, 300);
}

function performGlobalSearch(query) {
    const resultsDiv = document.getElementById('searchResults');
    if (!resultsDiv) return;
    
    resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-500 dark:text-gray-400">در حال جستجو...</div>';
    showSearchSuggestions();
    
    // Simulate search - in production, this would be an AJAX call
    fetch(`/admin/search?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data, query);
        addToSearchHistory(query);
    })
    .catch(error => {
        resultsDiv.innerHTML = '<div class="p-4 text-center text-red-500">خطا در جستجو</div>';
    });
}

function displaySearchResults(data, query) {
    const resultsDiv = document.getElementById('searchResults');
    if (!resultsDiv) return;
    
    if (!data || (!data.stories && !data.episodes && !data.users)) {
        resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-500 dark:text-gray-400">نتیجه‌ای یافت نشد</div>';
        return;
    }
    
    let html = '<div class="p-2 space-y-2">';
    
    if (data.stories && data.stories.length > 0) {
        html += '<div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">داستان‌ها</div>';
        data.stories.slice(0, 3).forEach(story => {
            html += `<a href="/admin/stories/${story.id}" class="block p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <div class="font-medium text-sm text-gray-900 dark:text-white">${story.title}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">${story.category?.name || ''}</div>
            </a>`;
        });
    }
    
    if (data.users && data.users.length > 0) {
        html += '<div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 mt-3">کاربران</div>';
        data.users.slice(0, 3).forEach(user => {
            html += `<a href="/admin/users/${user.id}" class="block p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <div class="font-medium text-sm text-gray-900 dark:text-white">${user.first_name} ${user.last_name}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">${user.phone || user.email || ''}</div>
            </a>`;
        });
    }
    
    html += '</div>';
    resultsDiv.innerHTML = html;
}

function showSearchSuggestions() {
    const suggestions = document.getElementById('searchSuggestions');
    if (suggestions) {
        suggestions.classList.remove('hidden');
        loadSearchHistory();
    }
}

function hideSearchSuggestions() {
    const suggestions = document.getElementById('searchSuggestions');
    if (suggestions) {
        suggestions.classList.add('hidden');
    }
}

function addToSearchHistory(query) {
    if (!query || query.length < 2) return;
    
    searchHistory = searchHistory.filter(q => q !== query);
    searchHistory.unshift(query);
    searchHistory = searchHistory.slice(0, 5);
    localStorage.setItem('dashboard_search_history', JSON.stringify(searchHistory));
    loadSearchHistory();
}

function loadSearchHistory() {
    const historyDiv = document.getElementById('searchHistoryList');
    if (!historyDiv) return;
    
    if (searchHistory.length === 0) {
        historyDiv.innerHTML = '<div class="text-xs text-gray-400 text-center py-2">تاریخچه‌ای وجود ندارد</div>';
        return;
    }
    
    let html = '';
    searchHistory.forEach(query => {
        html += `<button onclick="document.getElementById('globalSearch').value='${query}'; performGlobalSearch('${query}')" class="w-full text-right px-2 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
            ${query}
        </button>`;
    });
    historyDiv.innerHTML = html;
}

function clearSearchHistory() {
    searchHistory = [];
    localStorage.removeItem('dashboard_search_history');
    loadSearchHistory();
}

function quickSearch(type) {
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    
    // Navigate to appropriate page
    const routes = {
        'stories': '/admin/stories',
        'episodes': '/admin/episodes',
        'users': '/admin/users',
        'payments': '/admin/payments',
        'comments': '/admin/comments/pending'
    };
    
    if (routes[type]) {
        window.location.href = routes[type];
    }
}

// Smart Filters Functions
function toggleFiltersPanel() {
    const content = document.getElementById('filtersPanelContent');
    const icon = document.getElementById('filtersToggleIcon');
    if (content && icon) {
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(180deg)';
        }
    }
}

function applyQuickFilter(filterType) {
    const filters = {
        'active_users': { status: 'active', type: 'users' },
        'premium_stories': { is_premium: true, type: 'stories' },
        'pending_comments': { status: 'pending', type: 'comments' },
        'recent_payments': { time_range: 'week', type: 'payments' },
        'top_stories': { sort: 'popular', type: 'stories' }
    };
    
    const filter = filters[filterType];
    if (!filter) return;
    
    // Apply filters to dashboard
    const params = new URLSearchParams();
    Object.keys(filter).forEach(key => {
        params.append(key, filter[key]);
    });
    
    window.location.href = `/admin/dashboard?${params.toString()}`;
}

function clearAllFilters() {
    window.location.href = '/admin/dashboard';
}

function loadFilterPreset(preset) {
    const presets = {
        'today': { date_range: 'today' },
        'this_week': { date_range: '7days' },
        'this_month': { date_range: '30days' }
    };
    
    const presetParams = presets[preset];
    if (!presetParams) return;
    
    const params = new URLSearchParams();
    Object.keys(presetParams).forEach(key => {
        params.append(key, presetParams[key]);
    });
    
    window.location.href = `/admin/dashboard?${params.toString()}`;
}

function saveCurrentFilters() {
    const filters = {
        contentType: document.getElementById('filterContentType')?.value || '',
        status: document.getElementById('filterStatus')?.value || '',
        timeRange: document.getElementById('filterTimeRange')?.value || '',
        sortBy: document.getElementById('filterSortBy')?.value || ''
    };
    
    const savedFilters = JSON.parse(localStorage.getItem('dashboard_saved_filters') || '[]');
    savedFilters.push({
        name: `فیلتر ${savedFilters.length + 1}`,
        filters: filters,
        date: new Date().toISOString()
    });
    
    localStorage.setItem('dashboard_saved_filters', JSON.stringify(savedFilters));
    alert('فیلترها ذخیره شدند');
}

// Alert dismissal
function dismissAlert(button) {
    const alert = button.closest('.bg-white, .bg-red-50, .bg-yellow-50, .bg-blue-50, .bg-gray-50');
    if (alert) {
        alert.style.transition = 'opacity 0.3s, transform 0.3s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            alert.remove();
            // Save dismissed alerts to localStorage
            const dismissedAlerts = JSON.parse(localStorage.getItem('dismissed_alerts') || '[]');
            const alertId = alert.getAttribute('data-alert-id') || Date.now().toString();
            if (!dismissedAlerts.includes(alertId)) {
                dismissedAlerts.push(alertId);
                localStorage.setItem('dismissed_alerts', JSON.stringify(dismissedAlerts));
            }
        }, 300);
    }
}

// Comparison Mode Functions
function toggleCompareMode(enabled) {
    const comparisonPanel = document.getElementById('comparisonPeriod');
    if (comparisonPanel) {
        if (enabled) {
            comparisonPanel.classList.remove('hidden');
        } else {
            comparisonPanel.classList.add('hidden');
            // Remove compare parameter from URL
            const url = new URL(window.location.href);
            url.searchParams.delete('compare');
            url.searchParams.delete('compare_period');
            url.searchParams.delete('compare_date_from');
            url.searchParams.delete('compare_date_to');
            window.location.href = url.toString();
        }
    }
}

function applyComparison() {
    const comparePeriod = document.getElementById('comparePeriod').value;
    const url = new URL(window.location.href);
    url.searchParams.set('compare', '1');
    url.searchParams.set('compare_period', comparePeriod);
    
    if (comparePeriod === 'custom') {
        const dateFrom = document.getElementById('compareDateFrom').value;
        const dateTo = document.getElementById('compareDateTo').value;
        if (dateFrom && dateTo) {
            url.searchParams.set('compare_date_from', dateFrom);
            url.searchParams.set('compare_date_to', dateTo);
        } else {
            alert('لطفا هر دو تاریخ را برای مقایسه سفارشی انتخاب کنید');
            return;
        }
    }
    
    window.location.href = url.toString();
}

// Show/hide custom compare dates
document.addEventListener('DOMContentLoaded', function() {
    const comparePeriodSelect = document.getElementById('comparePeriod');
    const customCompareDates = document.getElementById('customCompareDates');
    
    if (comparePeriodSelect && customCompareDates) {
        comparePeriodSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customCompareDates.classList.remove('hidden');
            } else {
                customCompareDates.classList.add('hidden');
            }
        });
    }
});

// Date Range Functions
function applyDateRange(range) {
    if (range === 'custom') {
        document.getElementById('customDateRange').classList.remove('hidden');
        return;
    }
    document.getElementById('customDateRange').classList.add('hidden');
    const url = new URL(window.location.href);
    url.searchParams.set('date_range', range);
    window.location.href = url.toString();
}

function applyCustomDateRange() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    if (!dateFrom || !dateTo) {
        alert('لطفا هر دو تاریخ را انتخاب کنید');
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set('date_range', 'custom');
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    window.location.href = url.toString();
}

function refreshDashboard() {
    window.location.reload();
}

// Update last update time
function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' });
    const lastUpdateEl = document.getElementById('lastUpdate');
    if (lastUpdateEl) {
        lastUpdateEl.textContent = 'آخرین به‌روزرسانی: ' + dateString + ' - ' + timeString;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateLastUpdateTime();
    
    // Load dashboard customization on page load
    loadCustomization();
    
    // Show custom date range if selected
    @if(request('date_range') == 'custom')
        document.getElementById('customDateRange').classList.remove('hidden');
        @if(request('date_from'))
            document.getElementById('dateFrom').value = '{{ request('date_from') }}';
        @endif
        @if(request('date_to'))
            document.getElementById('dateTo').value = '{{ request('date_to') }}';
        @endif
    @endif
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        stopAutoRefresh();
    });
    // Check if Chart is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    // Chart.js default configuration for RTL and dark mode
    Chart.defaults.font.family = 'IranSansWeb, IRANSans, Tahoma, sans-serif';
    Chart.defaults.layout.padding = {
        right: 10,
        left: 10,
        top: 10,
        bottom: 10
    };

    // Helper function to get dark mode state
    const isDarkMode = () => document.documentElement.classList.contains('dark');
    const getTextColor = () => isDarkMode() ? '#f3f4f6' : '#374151';
    const getGridColor = () => isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const getTickColor = () => isDarkMode() ? '#9ca3af' : '#6b7280';

    // Lazy load charts when they come into view
    const chartObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const chartId = entry.target.id;
                if (chartId === 'revenueChart' && !revenueChartInstance) {
                    initRevenueChart();
                } else if (chartId === 'userGrowthChart' && !userGrowthChartInstance) {
                    initUserGrowthChart();
                } else if (chartId === 'playHistoryChart' && !playHistoryChartInstance) {
                    initPlayHistoryChart();
                } else if (chartId === 'revenueBreakdownChart' && !revenueBreakdownChartInstance) {
                    initRevenueBreakdownChart();
                } else if (chartId === 'engagementChart' && !engagementChartInstance) {
                    initEngagementChart();
                }
            }
        });
    }, { rootMargin: '50px' });
    
    // Observe all chart canvases
    document.querySelectorAll('canvas[id$="Chart"]').forEach(canvas => {
        chartObserver.observe(canvas);
    });
    
    // Initialize revenue chart
    function initRevenueChart() {
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx && !revenueChartInstance) {
        const revenueData = @json($monthlyRevenue);
        const revenueLabels = revenueData.map(item => {
            const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            return monthNames[item.month - 1] + ' ' + item.year;
        });
        const revenueValues = revenueData.map(item => parseFloat(item.total || 0));

        revenueChartInstance = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'درآمد (تومان)',
                    data: revenueValues,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                            },
                            color: getTextColor()
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'درآمد: ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y) + ' تومان';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fa-IR').format(value);
                            },
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    },
                    x: {
                        ticks: {
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    }
                }
            }
        });
    }

    // User Growth Chart - Daily Registrations (Line Chart)
    const userGrowthCtx = document.getElementById('userGrowthChart');
    if (userGrowthCtx) {
        const userData = @json($dailyUserRegistrations);
        const userLabels = userData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
        });
        const userValues = userData.map(item => parseInt(item.count || 0));

        userGrowthChartInstance = new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: userLabels,
                datasets: [{
                    label: 'کاربران جدید',
                    data: userValues,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                            },
                            color: getTextColor()
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'کاربران جدید: ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y) + ' نفر';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return new Intl.NumberFormat('fa-IR').format(value);
                            },
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    },
                    x: {
                        ticks: {
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    }
                }
            }
        });
    }

    // Play History Chart - Daily Plays (Area Chart)
    const playHistoryCtx = document.getElementById('playHistoryChart');
    if (playHistoryCtx) {
        const playData = @json($dailyPlayHistory);
        const playLabels = playData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
        });
        const playValues = playData.map(item => parseInt(item.count || 0));

        playHistoryChartInstance = new Chart(playHistoryCtx, {
            type: 'line',
            data: {
                labels: playLabels,
                datasets: [{
                    label: 'تعداد پخش',
                    data: playValues,
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.2)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                            },
                            color: getTextColor()
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'پخش: ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y) + ' بار';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return new Intl.NumberFormat('fa-IR').format(value);
                            },
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    },
                    x: {
                        ticks: {
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    }
                }
            }
        });
    }

    // Revenue Breakdown Chart - By Plan Type (Doughnut Chart)
    const revenueBreakdownCtx = document.getElementById('revenueBreakdownChart');
    if (revenueBreakdownCtx) {
        const planSalesData = @json($planSalesData);
        const planLabels = planSalesData.map(item => {
            const typeMap = {
                '1month': 'یک ماهه',
                '3months': 'سه ماهه',
                '6months': 'شش ماهه',
                '1year': 'یک ساله'
            };
            return typeMap[item.type] || item.type;
        });
        const planValues = planSalesData.map(item => parseFloat(item.total_revenue || 0));
        const planColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'];

        revenueBreakdownChartInstance = new Chart(revenueBreakdownCtx, {
            type: 'doughnut',
            data: {
                labels: planLabels,
                datasets: [{
                    data: planValues,
                    backgroundColor: planColors.slice(0, planValues.length),
                    borderWidth: 2,
                    borderColor: isDarkMode() ? '#1f2937' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                            },
                            color: getTextColor(),
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = new Intl.NumberFormat('fa-IR').format(context.parsed);
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' تومان (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Engagement Chart - Comments, Ratings, Favorites (Bar Chart)
    const engagementCtx = document.getElementById('engagementChart');
    if (engagementCtx) {
        const engagementData = @json($dailyEngagement);
        const engagementLabels = engagementData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
        });
        const commentsData = engagementData.map(item => parseInt(item.comments || 0));
        const ratingsData = engagementData.map(item => parseInt(item.ratings || 0));
        const favoritesData = engagementData.map(item => parseInt(item.favorites || 0));

        engagementChartInstance = new Chart(engagementCtx, {
            type: 'bar',
            data: {
                labels: engagementLabels,
                datasets: [
                    {
                        label: 'نظرات',
                        data: commentsData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'امتیازها',
                        data: ratingsData,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1
                    },
                    {
                        label: 'علاقه‌مندی‌ها',
                        data: favoritesData,
                        backgroundColor: 'rgba(139, 92, 246, 0.7)',
                        borderColor: 'rgb(139, 92, 246)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                            },
                            color: getTextColor()
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return new Intl.NumberFormat('fa-IR').format(value);
                            },
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    },
                    x: {
                        ticks: {
                            color: getTickColor()
                        },
                        grid: {
                            color: getGridColor()
                        }
                    }
                }
            }
        });
    }

    // Voice Actors Performance Chart
    const voiceActorsCtx = document.getElementById('voiceActorsChart');
    if (voiceActorsCtx) {
        @if(isset($voiceActorsAnalytics) && count($voiceActorsAnalytics) > 0)
        const voiceActorsData = @json(array_slice($voiceActorsAnalytics, 0, 10));
        if (voiceActorsData && voiceActorsData.length > 0) {
            const actorNames = voiceActorsData.map(actor => actor.name || 'بدون نام');
            const totalPlays = voiceActorsData.map(actor => parseInt(actor.total_plays || 0));
            const totalStories = voiceActorsData.map(actor => parseInt(actor.total_stories_narrated || 0));

            voiceActorsChartInstance = new Chart(voiceActorsCtx, {
                type: 'bar',
                data: {
                    labels: actorNames,
                    datasets: [
                        {
                            label: 'تعداد پخش',
                            data: totalPlays,
                            backgroundColor: 'rgba(139, 92, 246, 0.6)',
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 2
                        },
                        {
                            label: 'داستان‌های روایت شده',
                            data: totalStories,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                                },
                                color: getTextColor()
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fa-IR').format(value);
                                },
                                color: getTickColor()
                            },
                            grid: {
                                color: getGridColor()
                            }
                        },
                        x: {
                            ticks: {
                                color: getTickColor(),
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: getGridColor()
                            }
                        }
                    }
                }
            });
        }
        @endif
    }

    // Moderation Activity Chart
    const moderationActivityCtx = document.getElementById('moderationActivityChart');
    if (moderationActivityCtx) {
        @if(isset($moderationAnalytics) && isset($moderationAnalytics['activity']))
        const moderationData = @json($moderationAnalytics['activity']);
        if (moderationData && moderationData.length > 0) {
            const activityLabels = moderationData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
            });
            const approvedData = moderationData.map(item => parseInt(item.approved || 0));
            const rejectedData = moderationData.map(item => parseInt(item.rejected || 0));
            const pendingData = moderationData.map(item => parseInt(item.pending || 0));

            moderationActivityChartInstance = new Chart(moderationActivityCtx, {
                type: 'line',
                data: {
                    labels: activityLabels,
                    datasets: [
                        {
                            label: 'تایید شده',
                            data: approvedData,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'رد شده',
                            data: rejectedData,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'در انتظار',
                            data: pendingData,
                            borderColor: 'rgb(251, 191, 36)',
                            backgroundColor: 'rgba(251, 191, 36, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                                },
                                color: getTextColor()
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return new Intl.NumberFormat('fa-IR').format(value);
                                },
                                color: getTickColor()
                            },
                            grid: {
                                color: getGridColor()
                            }
                        },
                        x: {
                            ticks: {
                                color: getTickColor(),
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: getGridColor()
                            }
                        }
                    }
                }
            });
        }
        @endif
    }

    // Platform Distribution Chart
    const platformDistributionCtx = document.getElementById('platformDistributionChart');
    if (platformDistributionCtx) {
        @if(isset($devicePlatformAnalytics['platforms']))
        const platformData = @json($devicePlatformAnalytics['platforms']);
        if (platformData && Object.keys(platformData).length > 0) {
            const platformLabels = Object.keys(platformData);
            const platformCounts = Object.values(platformData);
            const platformColors = {
                'Android': 'rgb(34, 197, 94)',
                'iOS': 'rgb(59, 130, 246)',
                'Web': 'rgb(139, 92, 246)',
                'Unknown': 'rgb(107, 114, 128)'
            };

            platformDistributionChartInstance = new Chart(platformDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: platformLabels,
                    datasets: [{
                        data: platformCounts,
                        backgroundColor: platformLabels.map(label => platformColors[label] || 'rgb(107, 114, 128)'),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                                },
                                color: getTextColor(),
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + new Intl.NumberFormat('fa-IR').format(value) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        @endif
    }

    // Platform Growth Chart
    const platformGrowthCtx = document.getElementById('platformGrowthChart');
    if (platformGrowthCtx) {
        @if(isset($devicePlatformAnalytics['growth']))
        const growthData = @json($devicePlatformAnalytics['growth']);
        if (growthData && growthData.length > 0) {
            const growthLabels = growthData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
            });
            const androidData = growthData.map(item => parseInt(item.android || 0));
            const iosData = growthData.map(item => parseInt(item.ios || 0));
            const webData = growthData.map(item => parseInt(item.web || 0));

            platformGrowthChartInstance = new Chart(platformGrowthCtx, {
                type: 'line',
                data: {
                    labels: growthLabels,
                    datasets: [
                        {
                            label: 'Android',
                            data: androidData,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'iOS',
                            data: iosData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Web',
                            data: webData,
                            borderColor: 'rgb(139, 92, 246)',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'IranSansWeb, IRANSans, Tahoma, sans-serif'
                                },
                                color: getTextColor()
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return new Intl.NumberFormat('fa-IR').format(value);
                                },
                                color: getTickColor()
                            },
                            grid: {
                                color: getGridColor()
                            }
                        },
                        x: {
                            ticks: {
                                color: getTickColor(),
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: getGridColor()
                            }
                        }
                    }
                }
            });
        }
        @endif
    }
});
</script>
@endpush
@endsection
