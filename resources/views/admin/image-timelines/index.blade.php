@extends('admin.layouts.app')

@section('title', 'مدیریت تایم‌لاین تصاویر')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت تایم‌لاین تصاویر</h1>
        <a href="{{ route('admin.image-timelines.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
            افزودن تایم‌لاین تصویر جدید
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل تایم‌لاین‌ها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">فعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['active'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">غیرفعال</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['inactive'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">کل اپیزودها</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_episodes'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="جستجو در عنوان اپیزود یا داستان..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                <select name="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه داستان‌ها</option>
                    @foreach($stories as $story)
                        <option value="{{ $story->id }}" {{ request('story_id') == $story->id ? 'selected' : '' }}>
                            {{ $story->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">اپیزود</label>
                <select name="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه اپیزودها</option>
                    @foreach($episodes as $episode)
                        <option value="{{ $episode->id }}" {{ request('episode_id') == $episode->id ? 'selected' : '' }}>
                            {{ $episode->title }} ({{ $episode->story->title }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    فیلتر
                </button>
                <a href="{{ route('admin.image-timelines.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition duration-200 mr-2">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form id="bulkActionForm" method="POST" action="{{ route('admin.image-timelines.bulk-action') }}">
            @csrf
            <div class="flex items-center gap-4">
                <div class="flex items-center">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="selectAll" class="mr-2 text-sm text-gray-700">انتخاب همه</label>
                </div>
                
                <select name="action" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    <option value="activate">فعال کردن</option>
                    <option value="deactivate">غیرفعال کردن</option>
                    <option value="delete">حذف</option>
                </select>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200" disabled>
                    اجرا
                </button>
            </div>
        </form>
    </div>

    <!-- Image Timelines Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اپیزود</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ترتیب</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($imageTimelines as $imageTimeline)
                        <tr class="hover:bg-gray-50" data-image-timeline-id="{{ $imageTimeline->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="image-timeline-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="{{ $imageTimeline->id }}">
                                @if($imageTimeline->image_url)
                                    <img src="{{ $imageTimeline->image_url }}" alt="تصویر تایم‌لاین" class="w-16 h-12 object-cover rounded-lg mt-2">
                                @else
                                    <div class="w-16 h-12 bg-gray-200 rounded-lg flex items-center justify-center mt-2">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $imageTimeline->episode->title }}</div>
                                <div class="text-sm text-gray-500">{{ $imageTimeline->episode->story->title }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ gmdate('H:i:s', $imageTimeline->start_time) }}</div>
                                <div class="text-sm text-gray-500">تا {{ gmdate('H:i:s', $imageTimeline->end_time) }}</div>
                                <div class="text-xs text-gray-400">{{ $imageTimeline->end_time - $imageTimeline->start_time }} ثانیه</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $imageTimeline->image_order }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full status-badge
                                    {{ $imageTimeline->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $imageTimeline->status === 'active' ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @jalali($imageTimeline->created_at, 'Y/m/d')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.image-timelines.show', $imageTimeline) }}" 
                                       class="text-primary hover:text-blue-600" data-action="view" data-image-timeline-id="{{ $imageTimeline->id }}">مشاهده</a>
                                    <a href="{{ route('admin.image-timelines.edit', $imageTimeline) }}" 
                                       class="text-green-600 hover:text-green-800" data-action="edit" data-image-timeline-id="{{ $imageTimeline->id }}">ویرایش</a>
                                    <button class="text-yellow-600 hover:text-yellow-800" 
                                            data-action="toggle-status" data-image-timeline-id="{{ $imageTimeline->id }}">
                                        {{ $imageTimeline->status === 'active' ? 'غیرفعال' : 'فعال' }}
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-800" 
                                            data-action="duplicate" data-image-timeline-id="{{ $imageTimeline->id }}">کپی</button>
                                    <form method="POST" action="{{ route('admin.image-timelines.destroy', $imageTimeline) }}" class="inline" 
                                          onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این تایم‌لاین تصویر را حذف کنید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" data-action="delete" data-image-timeline-id="{{ $imageTimeline->id }}">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                هیچ تایم‌لاین تصویری یافت نشد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($imageTimelines->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $imageTimelines->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/admin/image-timeline-manager.js') }}"></script>
@endsection
