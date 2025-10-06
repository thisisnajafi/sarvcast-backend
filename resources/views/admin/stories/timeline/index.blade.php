@extends('admin.layouts.app')

@section('title', 'مدیریت تایم‌لاین داستان')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تایم‌لاین داستان</h1>
            <p class="text-gray-600 mt-1">{{ $story->title }}</p>
            <div class="mt-2 flex space-x-2 space-x-reverse">
                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                    {{ $story->episodes->count() }} اپیزود
                </span>
                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                    {{ $timelines->count() }} تایم‌لاین
                </span>
            </div>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.stories.timeline.create', $story) }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                افزودن تایم‌لاین
            </a>
            <a href="{{ route('admin.stories.show', $story) }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت به داستان
            </a>
        </div>
    </div>

    <!-- Timeline Overview -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-primary">{{ $timelines->count() }}</div>
                <div class="text-gray-600">تعداد تایم‌لاین</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ gmdate('i:s', $story->episodes->sum('duration')) }}</div>
                <div class="text-gray-600">مدت کل داستان</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $timelines->where('is_key_frame', true)->count() }}</div>
                <div class="text-gray-600">فریم‌های کلیدی</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ $timelines->groupBy('transition_type')->count() }}</div>
                <div class="text-gray-600">انواع انتقال</div>
            </div>
        </div>
    </div>

    <!-- Timeline Entries -->
    @if($timelines->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">لیست تایم‌لاین‌ها</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ترتیب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">انتقال</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کلیدی</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($timelines as $timeline)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="relative">
                                        <img src="{{ $timeline->getImageUrlFromPath($timeline->image_url) }}" alt="Timeline Image" 
                                             class="w-16 h-12 rounded-lg object-cover border border-gray-200">
                                        @if($timeline->is_key_frame)
                                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                            #{{ $timeline->image_order }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-green-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="font-medium">{{ gmdate('i:s', $timeline->start_time) }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-red-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="font-medium">{{ gmdate('i:s', $timeline->end_time) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            مدت: {{ $timeline->end_time - $timeline->start_time }} ثانیه
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs">
                                        @if($timeline->scene_description)
                                            {{ Str::limit($timeline->scene_description, 60) }}
                                        @else
                                            <span class="text-gray-400 italic">بدون توضیحات</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $transitionColors = [
                                            'fade' => 'bg-blue-100 text-blue-800',
                                            'cut' => 'bg-red-100 text-red-800',
                                            'dissolve' => 'bg-purple-100 text-purple-800',
                                            'slide' => 'bg-green-100 text-green-800'
                                        ];
                                        $transitionLabels = [
                                            'fade' => 'محو شدن',
                                            'cut' => 'برش',
                                            'dissolve' => 'حل شدن',
                                            'slide' => 'لغزش'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full {{ $transitionColors[$timeline->transition_type] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $transitionLabels[$timeline->transition_type] ?? $timeline->transition_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($timeline->is_key_frame)
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 flex items-center">
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                            کلیدی
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">عادی</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2 space-x-reverse">
                                        <a href="{{ route('admin.stories.timeline.edit', [$story, $timeline]) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            ویرایش
                                        </a>
                                        <form method="POST" action="{{ route('admin.stories.timeline.destroy', [$story, $timeline]) }}" 
                                              class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این تایم‌لاین را حذف کنید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 flex items-center">
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white p-12 rounded-lg shadow-sm text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">هنوز تایم‌لاینی ایجاد نشده</h3>
            <p class="text-gray-600 mb-6">برای شروع، اولین تایم‌لاین را ایجاد کنید.</p>
            <a href="{{ route('admin.stories.timeline.create', $story) }}" 
               class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition-colors">
                ایجاد اولین تایم‌لاین
            </a>
        </div>
    @endif
</div>
@endsection
