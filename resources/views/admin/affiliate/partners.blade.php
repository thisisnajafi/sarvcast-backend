@extends('admin.layouts.app')

@section('title', 'مدیریت شرکای همکاری')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">مدیریت شرکای همکاری</h1>
                <p class="text-gray-600">مدیریت شرکای همکاری و عملکرد آن‌ها</p>
            </div>
            <button id="add-partner-btn" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                افزودن شریک جدید
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع شریک</label>
                <select id="partner-type-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">همه انواع</option>
                    <option value="teacher">معلم/مربی</option>
                    <option value="influencer">اینفلوئنسر</option>
                    <option value="school">مدرسه</option>
                    <option value="corporate">شرکتی</option>
                    <option value="individual">فردی</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select id="status-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending">در انتظار</option>
                    <option value="active">فعال</option>
                    <option value="suspended">معلق</option>
                    <option value="rejected">رد شده</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                <input type="date" id="start-date-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex items-end">
                <button id="apply-filters" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    اعمال فیلتر
                </button>
            </div>
        </div>
    </div>

    <!-- Partners Table -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">لیست شرکای همکاری</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شریک</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کمیسیون</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">فروش</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ عضویت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody id="partners-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Partners will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    نمایش <span id="showing-count">0</span> از <span id="total-count">0</span> شریک
                </div>
                <div class="flex space-x-2">
                    <button id="prev-page" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        قبلی
                    </button>
                    <button id="next-page" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        بعدی
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Partner Modal -->
<div id="add-partner-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">افزودن شریک جدید</h3>
                <button id="close-add-partner-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="add-partner-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نام</label>
                        <input type="text" id="partner-name" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ایمیل</label>
                        <input type="email" id="partner-email" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">شماره تلفن</label>
                        <input type="tel" id="partner-phone" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع شریک</label>
                        <select id="partner-type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="">انتخاب کنید</option>
                            <option value="teacher">معلم/مربی</option>
                            <option value="influencer">اینفلوئنسر</option>
                            <option value="school">مدرسه</option>
                            <option value="corporate">شرکتی</option>
                            <option value="individual">فردی</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                        <textarea id="partner-description" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3"></textarea>
                    </div>
                </div>
                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        افزودن شریک
                    </button>
                    <button type="button" id="cancel-add-partner" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        لغو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Partner Detail Modal -->
<div id="partner-detail-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">جزئیات شریک</h3>
                <button id="close-partner-detail-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="partner-detail-content">
                <!-- Partner detail content will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/admin-affiliate-partners.js') }}"></script>
@endsection
