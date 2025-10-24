# SarvCast Admin Dashboard UI Components

## Dashboard Layout Structure

### Main Layout
```html
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SarvCast Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4A90E2',
                        secondary: '#FF6B6B',
                        accent: '#FFD93D',
                        success: '#6BCF7F',
                        warning: '#FFA726',
                        error: '#EF5350',
                        info: '#26C6DA'
                    },
                    fontFamily: {
                        'iran': ['IRANSans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-iran">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-primary">سروکست</h1>
                <p class="text-sm text-gray-500 mt-1">پنل مدیریت</p>
            </div>
            
            <nav class="mt-6">
                <a href="/admin/dashboard" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    داشبورد
                </a>
                
                <a href="/admin/stories" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    داستان‌ها
                </a>
                
                <a href="/admin/episodes" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    اپیزودها
                </a>
                
                <a href="/admin/categories" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    دسته‌بندی‌ها
                </a>
                
                <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    کاربران
                </a>
                
                <a href="/admin/subscriptions" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    اشتراک‌ها
                </a>
                
                <a href="/admin/analytics" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    آمار و گزارشات
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">داشبورد</h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="جستجو..." class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <button class="p-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h6v-2H4v2zM4 11h6V9H4v2zM4 7h6V5H4v2z"></path>
                            </svg>
                        </button>
                        <div class="flex items-center">
                            <img src="/admin/avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full">
                            <span class="mr-2 text-sm font-medium text-gray-700">مدیر سیستم</span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 p-6">
                <!-- Content goes here -->
            </main>
        </div>
    </div>
</body>
</html>
```

## Dashboard Components

### Statistics Cards
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">کل کاربران</p>
                <p class="text-2xl font-bold text-gray-900">12,345</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">+12% از ماه گذشته</span>
        </div>
    </div>
    
    <!-- Total Stories Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">کل داستان‌ها</p>
                <p class="text-2xl font-bold text-gray-900">1,234</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">+8% از ماه گذشته</span>
        </div>
    </div>
    
    <!-- Active Subscriptions Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">اشتراک‌های فعال</p>
                <p class="text-2xl font-bold text-gray-900">8,765</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">+15% از ماه گذشته</span>
        </div>
    </div>
    
    <!-- Revenue Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
            <div class="mr-4">
                <p class="text-sm font-medium text-gray-600">درآمد ماهانه</p>
                <p class="text-2xl font-bold text-gray-900">12,345,000 تومان</p>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-green-600">+22% از ماه گذشته</span>
        </div>
    </div>
</div>
```

### Data Table Component
```html
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">داستان‌ها</h3>
            <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                افزودن داستان جدید
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه دسته‌بندی‌ها</option>
                    <option value="1">ماجراجویی</option>
                    <option value="2">آموزشی</option>
                    <option value="3">فانتزی</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="published">منتشر شده</option>
                    <option value="pending">در انتظار</option>
                    <option value="draft">پیش‌نویس</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" placeholder="جستجو در عنوان..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="flex items-end">
                <button class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition duration-200">
                    فیلتر
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="rounded border-gray-300">
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">دسته‌بندی</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">امتیاز</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="rounded border-gray-300">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="/storage/stories/story1.jpg" alt="Story" class="w-12 h-12 rounded-lg object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">ماجراجویی در جنگل جادویی</div>
                        <div class="text-sm text-gray-500">داستان پسر کوچکی که در جنگل جادویی گم می‌شود</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ماجراجویی
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            منتشر شده
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <span class="text-yellow-500">★</span>
                            <span class="text-sm text-gray-600 mr-1">4.5</span>
                            <span class="text-xs text-gray-500">(123)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="/admin/stories/1/edit" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                            <a href="/admin/stories/1" class="text-green-600 hover:text-green-900">مشاهده</a>
                            <button class="text-red-600 hover:text-red-900">حذف</button>
                        </div>
                    </td>
                </tr>
                <!-- More rows... -->
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                نمایش 1 تا 20 از 200 نتیجه
            </div>
            <div class="flex space-x-2">
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">قبلی</button>
                <button class="px-3 py-2 text-sm bg-primary text-white rounded-lg">1</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">2</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">3</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">بعدی</button>
            </div>
        </div>
    </div>
</div>
```

### Story Form Component
```html
<form class="bg-white shadow rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Title -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">عنوان داستان</label>
            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="عنوان داستان را وارد کنید">
        </div>
        
        <!-- Subtitle -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">زیرعنوان</label>
            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="زیرعنوان داستان را وارد کنید">
        </div>
        
        <!-- Description -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
            <textarea rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="توضیحات داستان را وارد کنید"></textarea>
        </div>
        
        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">انتخاب دسته‌بندی</option>
                <option value="1">ماجراجویی</option>
                <option value="2">آموزشی</option>
                <option value="3">فانتزی</option>
            </select>
        </div>
        
        <!-- Age Group -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">گروه سنی</label>
            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">انتخاب گروه سنی</option>
                <option value="3-5">3-5 سال</option>
                <option value="6-8">6-8 سال</option>
                <option value="9-12">9-12 سال</option>
            </select>
        </div>
        
        <!-- Image Upload -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">تصویر داستان</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600">تصویر را اینجا بکشید یا کلیک کنید</p>
                <input type="file" class="hidden" accept="image/*">
            </div>
        </div>
        
        <!-- Premium Status -->
        <div>
            <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                <span class="mr-2 text-sm text-gray-700">داستان ویژه</span>
            </label>
        </div>
        
        <!-- Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="draft">پیش‌نویس</option>
                <option value="pending">در انتظار بررسی</option>
                <option value="published">منتشر شده</option>
            </select>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="mt-6 flex justify-end space-x-3">
        <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
            انصراف
        </button>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
            ذخیره داستان
        </button>
    </div>
</form>
```

### Modal Component
```html
<!-- Modal Overlay -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="modal-overlay">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center pb-3">
            <h3 class="text-lg font-medium text-gray-900">تأیید حذف</h3>
            <button class="text-gray-400 hover:text-gray-600" onclick="closeModal()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="py-4">
            <p class="text-sm text-gray-600">آیا مطمئن هستید که می‌خواهید این داستان را حذف کنید؟ این عمل قابل بازگشت نیست.</p>
        </div>
        
        <!-- Modal Footer -->
        <div class="flex justify-end space-x-3">
            <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50" onclick="closeModal()">
                انصراف
            </button>
            <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                حذف
            </button>
        </div>
    </div>
</div>
```

### Alert Component
```html
<!-- Success Alert -->
<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="mr-3">
            <h3 class="text-sm font-medium text-green-800">موفقیت</h3>
            <div class="mt-2 text-sm text-green-700">
                داستان با موفقیت ذخیره شد.
            </div>
        </div>
    </div>
</div>

<!-- Error Alert -->
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="mr-3">
            <h3 class="text-sm font-medium text-red-800">خطا</h3>
            <div class="mt-2 text-sm text-red-700">
                خطایی در ذخیره داستان رخ داد. لطفاً دوباره تلاش کنید.
            </div>
        </div>
    </div>
</div>
```

### Loading Component
```html
<!-- Loading Spinner -->
<div class="flex justify-center items-center p-8">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    <span class="mr-2 text-gray-600">در حال بارگذاری...</span>
</div>

<!-- Skeleton Loading -->
<div class="animate-pulse">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
        <div class="space-y-3">
            <div class="h-4 bg-gray-200 rounded"></div>
            <div class="h-4 bg-gray-200 rounded w-5/6"></div>
            <div class="h-4 bg-gray-200 rounded w-4/6"></div>
        </div>
    </div>
</div>
```

## Responsive Design

### Mobile Navigation
```html
<!-- Mobile Menu Button -->
<button class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Mobile Menu -->
<div class="md:hidden">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
        <a href="/admin/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">داشبورد</a>
        <a href="/admin/stories" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">داستان‌ها</a>
        <a href="/admin/episodes" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">اپیزودها</a>
    </div>
</div>
```

## JavaScript Functionality

### Modal Functions
```javascript
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.add('hidden');
    }
});
```

### Form Validation
```javascript
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}
```

### Data Table Functions
```javascript
function selectAll(checkbox) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function deleteSelected() {
    const selected = document.querySelectorAll('input[type="checkbox"]:checked');
    if (selected.length > 0) {
        openModal('delete-modal');
    }
}
```

This comprehensive UI documentation provides all the necessary components and structure for building a complete admin dashboard for the SarvCast project using Tailwind CSS with RTL support and Persian language.
