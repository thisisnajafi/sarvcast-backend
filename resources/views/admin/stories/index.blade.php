@extends('admin.layouts.app')

@section('title', 'مدیریت داستان‌ها')
@section('page-title', 'داستان‌ها')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">داستان‌ها</h3>
            <a href="{{ route('admin.stories.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                افزودن داستان جدید
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="px-6 py-4 border-b border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه دسته‌بندی‌ها</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>رد شده</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition duration-200">
                    فیلتر
                </button>
            </div>
        </form>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="rounded border-gray-300" onchange="selectAll(this)">
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
                @forelse($stories as $story)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="rounded border-gray-300">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="{{ $story->image_url ?: '/images/placeholder-story.jpg' }}" alt="{{ $story->title }}" class="w-12 h-12 rounded-lg object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $story->title }}</div>
                        <div class="text-sm text-gray-500">{{ $story->subtitle }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $story->category->name ?? 'بدون دسته‌بندی' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($story->status === 'published') bg-green-100 text-green-800
                            @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($story->status === 'approved') bg-blue-100 text-blue-800
                            @elseif($story->status === 'rejected') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @switch($story->status)
                                @case('published')
                                    منتشر شده
                                    @break
                                @case('pending')
                                    در انتظار
                                    @break
                                @case('approved')
                                    تأیید شده
                                    @break
                                @case('rejected')
                                    رد شده
                                    @break
                                @default
                                    پیش‌نویس
                            @endswitch
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <span class="text-yellow-500">★</span>
                            <span class="text-sm text-gray-600 mr-1">{{ number_format($story->rating, 1) }}</span>
                            <span class="text-xs text-gray-500">({{ $story->ratings()->count() }})</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.stories.edit', $story) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                            <a href="{{ route('admin.stories.show', $story) }}" class="text-green-600 hover:text-green-900">مشاهده</a>
                            <form method="POST" action="{{ route('admin.stories.destroy', $story) }}" class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این داستان را حذف کنید؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        هیچ داستانی یافت نشد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($stories->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                نمایش {{ $stories->firstItem() }} تا {{ $stories->lastItem() }} از {{ $stories->total() }} نتیجه
            </div>
            <div class="flex space-x-2">
                @if($stories->previousPageUrl())
                    <a href="{{ $stories->previousPageUrl() }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">قبلی</a>
                @endif
                
                @foreach($stories->getUrlRange(1, $stories->lastPage()) as $page => $url)
                    @if($page == $stories->currentPage())
                        <span class="px-3 py-2 text-sm bg-primary text-white rounded-lg">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">{{ $page }}</a>
                    @endif
                @endforeach
                
                @if($stories->nextPageUrl())
                    <a href="{{ $stories->nextPageUrl() }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">بعدی</a>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
