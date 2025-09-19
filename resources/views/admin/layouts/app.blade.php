<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'سروکست') - پنل مدیریت</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
                        'iran': ['IranSansWeb', 'IRANSans', 'Tahoma', 'sans-serif'],
                        'sans': ['IranSansWeb', 'IRANSans', 'Tahoma', 'ui-sans-serif', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/voice-actor-management.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'IranSansWeb', 'IRANSans', 'Tahoma', sans-serif;
        }
    </style>
    <script src="{{ asset('js/voice-actor-management.js') }}"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-iran transition-colors duration-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white dark:bg-gray-800 shadow-xl border-l border-gray-200 dark:border-gray-700 fixed right-0 h-full overflow-y-auto transition-colors duration-300">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">سروکست</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">پنل مدیریت</p>
                    </div>
                </div>
            </div>
            
            <nav class="mt-6 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900 dark:hover:to-blue-800 hover:text-blue-700 dark:hover:text-blue-300 transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 text-blue-700 dark:text-blue-300 border-l-2 border-blue-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                    </div>
                    <span class="font-medium dark:text-gray-300">داشبورد</span>
                </a>
                
                <a href="{{ route('admin.stories.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-green-100 hover:text-green-700 transition-all duration-200 {{ request()->routeIs('admin.stories.*') ? 'bg-gradient-to-r from-green-50 to-green-100 text-green-700 border-l-2 border-green-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.stories.*') ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <span class="font-medium">داستان‌ها</span>
                </a>
                
                <a href="{{ route('admin.episodes.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-purple-100 hover:text-purple-700 transition-all duration-200 {{ request()->routeIs('admin.episodes.*') ? 'bg-gradient-to-r from-purple-50 to-purple-100 text-purple-700 border-l-2 border-purple-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.episodes.*') ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                    </div>
                    <span class="font-medium">اپیزودها</span>
                </a>
                
                <!-- Voice Actor Management Submenu -->
                @if(request()->routeIs('admin.episodes.*'))
                <div class="mr-8 space-y-1">
                    <a href="{{ route('admin.episodes.index') }}" class="flex items-center px-6 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors duration-200 {{ request()->routeIs('admin.episodes.index') ? 'text-purple-700 dark:text-purple-300 font-medium' : '' }}">
                        <svg class="w-4 h-4 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                        لیست اپیزودها
                    </a>
                    <a href="{{ route('admin.episodes.create') }}" class="flex items-center px-6 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors duration-200 {{ request()->routeIs('admin.episodes.create') ? 'text-purple-700 dark:text-purple-300 font-medium' : '' }}">
                        <svg class="w-4 h-4 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        افزودن اپیزود
                    </a>
                    @if(request()->routeIs('admin.episodes.show') || request()->routeIs('admin.episodes.edit') || request()->routeIs('admin.episodes.voice-actors.*'))
                    <a href="{{ route('admin.episodes.show', request()->route('episode')) }}" class="flex items-center px-6 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors duration-200 {{ request()->routeIs('admin.episodes.show') ? 'text-purple-700 dark:text-purple-300 font-medium' : '' }}">
                        <svg class="w-4 h-4 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        مشاهده اپیزود
                    </a>
                    <a href="{{ route('admin.episodes.voice-actors.index', request()->route('episode')) }}" class="flex items-center px-6 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors duration-200 {{ request()->routeIs('admin.episodes.voice-actors.*') ? 'text-purple-700 dark:text-purple-300 font-medium' : '' }}">
                        <svg class="w-4 h-4 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        مدیریت صداپیشگان
                    </a>
                    @endif
                </div>
                @endif
                
                <a href="{{ route('admin.categories.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-100 hover:text-orange-700 transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-gradient-to-r from-orange-50 to-orange-100 text-orange-700 border-l-2 border-orange-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.categories.*') ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <span class="font-medium">دسته‌بندی‌ها</span>
                </a>
                
                <a href="{{ route('admin.people.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-pink-100 hover:text-pink-700 transition-all duration-200 {{ request()->routeIs('admin.people.*') ? 'bg-gradient-to-r from-pink-50 to-pink-100 text-pink-700 border-l-2 border-pink-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.people.*') ? 'bg-pink-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">افراد</span>
                </a>
                
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-indigo-100 hover:text-indigo-700 transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-indigo-50 to-indigo-100 text-indigo-700 border-l-2 border-indigo-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">کاربران</span>
                </a>
                
                <a href="#" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    اشتراک‌ها
                </a>
                
                <a href="{{ route('admin.files.upload') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.files.*') ? 'bg-gray-100 text-primary' : '' }}">
                    <svg class="w-5 h-5 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    آپلود فایل
                </a>
                
                <a href="{{ route('admin.analytics') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.analytics*') ? 'bg-gray-100 text-primary' : '' }}">
                    <svg class="w-5 h-5 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    آمار و تحلیل‌ها
                </a>
                
                <a href="{{ route('admin.profile.index') }}" class="flex items-center px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-purple-50 hover:to-purple-100 dark:hover:from-purple-900 dark:hover:to-purple-800 hover:text-purple-700 dark:hover:text-purple-300 transition-all duration-200 {{ request()->routeIs('admin.profile.*') ? 'bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 text-purple-700 dark:text-purple-300 border-l-2 border-purple-500' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center ml-4 {{ request()->routeIs('admin.profile.*') ? 'bg-purple-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <span class="font-medium dark:text-gray-300">پروفایل</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <div class="px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">@yield('page-title', 'داشبورد')</h2>
                        <div class="hidden md:flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ \App\Helpers\JalaliHelper::now('Y/m/d') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative hidden md:block">
                            <input type="text" placeholder="جستجو..." class="w-64 px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        
                        <!-- Dark Mode Toggle -->
                        <button id="dark-mode-toggle" class="p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg id="sun-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <svg id="moon-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </button>
                        
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h6v-2H4v2zM4 11h6V9H4v2zM4 7h6V5H4v2z"></path>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                        </button>
                        
                        <!-- User Profile -->
                        <div class="flex items-center space-x-3 pr-4 border-l border-gray-200 dark:border-gray-700">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">م</span>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">مدیر سیستم</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">admin@sarvcast.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 p-6 mr-64">
                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-green-800">موفقیت</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    {{ session('success') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-red-800">خطا</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    {{ session('error') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex justify-center items-center h-full">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            <span class="mr-2 text-gray-600">در حال بارگذاری...</span>
        </div>
    </div>

    <script>
        // Modal functions
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

        // Form validation
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

        // Data table functions
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

        // Show loading spinner
        function showLoading() {
            document.getElementById('loading-spinner').classList.remove('hidden');
        }

        // Hide loading spinner
        function hideLoading() {
            document.getElementById('loading-spinner').classList.add('hidden');
        }

        // Dark mode functionality
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');
        const body = document.body;

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        if (currentTheme === 'dark') {
            body.classList.add('dark');
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        }

        darkModeToggle.addEventListener('click', function() {
            if (body.classList.contains('dark')) {
                body.classList.remove('dark');
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.add('dark');
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Add smooth transitions
        document.documentElement.style.transition = 'background-color 0.3s ease, color 0.3s ease';
    </script>
</body>
</html>
