@extends('admin.layouts.app')

@section('title', 'مدیریت اپیزودها')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت اپیزودها</h1>
        <a href="{{ route('admin.episodes.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
            <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            افزودن اپیزود جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان یا توضیحات..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">داستان</label>
                <select name="story_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه داستان‌ها</option>
                    @foreach(\App\Models\Story::published()->get() as $story)
                        <option value="{{ $story->id }}" {{ request('story_id') == $story->id ? 'selected' : '' }}>
                            {{ $story->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>آرشیو شده</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    فیلتر
                </button>
            </div>
        </form>
    </div>

    <!-- Episodes Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto relative" style="scrollbar-width: thin;">
            <!-- Scroll indicator hint for mobile -->
            <div class="md:hidden absolute top-0 right-0 bg-gradient-to-l from-gray-100 to-transparent w-8 h-full pointer-events-none z-10 flex items-center justify-end pr-2">
                <svg class="w-4 h-4 text-gray-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">داستان</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شماره اپیزود</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مدت زمان</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد پخش</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                                @if($episode->cover_image_url)
                                    <img src="{{ $episode->cover_image_url }}" alt="{{ $episode->title }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $episode->title }}</div>
                                @if($episode->description)
                                    <div class="text-sm text-gray-500 mt-1">{{ Str::limit($episode->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $episode->story->title }}</div>
                                <div class="text-sm text-gray-500">{{ $episode->story->category->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $episode->episode_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ gmdate('i:s', $episode->duration) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($episode->play_count) }}
                            </td>
                            <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                                @if($episode->status == 'published')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        منتشر شده
                                    </span>
                                @elseif($episode->status == 'draft')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        پیش‌نویس
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        آرشیو شده
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 whitespace-nowrap">
                                @if($episode->is_premium)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        پولی
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        رایگان
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.episodes.show', $episode) }}" class="text-primary hover:text-primary/80">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.episodes.edit', $episode) }}" class="text-warning hover:text-warning/80">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.episodes.destroy', $episode) }}" class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این اپیزود را حذف کنید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error hover:text-error/80">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                </svg>
                                <p class="text-lg font-medium">هیچ اپیزودی یافت نشد</p>
                                <p class="text-sm">برای شروع، یک اپیزود جدید ایجاد کنید.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($episodes->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $episodes->links() }}
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/admin/episode-manager.js') }}"></script>
@endsection
