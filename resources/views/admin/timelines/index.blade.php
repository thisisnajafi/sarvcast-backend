@extends('admin.layouts.app')

@section('title', 'مدیریت تایم‌لاین‌ها')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مدیریت تایم‌لاین‌ها</h1>
            <p class="text-gray-600 mt-1">مدیریت تمام تایم‌لاین‌های تصویری در سراسر سیستم</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <a href="{{ route('admin.timelines.statistics') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                آمار و گزارشات
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <form method="GET" action="{{ route('admin.timelines.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">اپیزود</label>
                <select name="episode_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه اپیزودها</option>
                    @foreach($episodes as $episode)
                        <option value="{{ $episode->id }}" {{ request('episode_id') == $episode->id ? 'selected' : '' }}>
                            {{ $episode->title }} - {{ $episode->story->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع انتقال</label>
                <select name="transition_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه انواع</option>
                    @foreach($transitionTypes as $type)
                        <option value="{{ $type }}" {{ request('transition_type') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">فریم کلیدی</label>
                <select name="is_key_frame" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه</option>
                    <option value="1" {{ request('is_key_frame') == '1' ? 'selected' : '' }}>فقط کلیدی</option>
                    <option value="0" {{ request('is_key_frame') == '0' ? 'selected' : '' }}>فقط عادی</option>
                </select>
            </div>

            <div class="md:col-span-4 flex justify-end space-x-3 space-x-reverse">
                <a href="{{ route('admin.timelines.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    پاک کردن فیلترها
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    اعمال فیلتر
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    @if($timelines->count() > 0)
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form method="POST" action="{{ route('admin.timelines.bulk-action') }}" id="bulk-form">
                @csrf
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <label for="select-all" class="mr-2 text-sm text-gray-700">انتخاب همه</label>
                    </div>
                    
                    <select name="action" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        <option value="">انتخاب عملیات</option>
                        <option value="delete">حذف</option>
                        <option value="change_transition">تغییر نوع انتقال</option>
                        <option value="change_key_frame">تغییر وضعیت کلیدی</option>
                    </select>

                    <select name="transition_type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" style="display: none;">
                        <option value="fade">محو شدن</option>
                        <option value="cut">برش</option>
                        <option value="dissolve">حل شدن</option>
                        <option value="slide">لغزش</option>
                    </select>

                    <select name="is_key_frame" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" style="display: none;">
                        <option value="1">کلیدی</option>
                        <option value="0">عادی</option>
                    </select>

                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                            onclick="return confirm('آیا مطمئن هستید؟')">
                        اجرای عملیات
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Timeline List -->
    @if($timelines->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    لیست تایم‌لاین‌ها 
                    <span class="text-sm text-gray-500">({{ $timelines->total() }} مورد)</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اپیزود</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">توضیحات</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">انتقال</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کلیدی</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($timelines as $timeline)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="timeline_ids[]" value="{{ $timeline->id }}" 
                                           class="timeline-checkbox rounded border-gray-300 text-primary focus:ring-primary">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="relative">
                                        <img src="{{ $timeline->image_url }}" alt="Timeline Image" 
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium">{{ $timeline->episode->title }}</div>
                                        <div class="text-gray-500">{{ $timeline->episode->story->title }}</div>
                                        <div class="text-xs text-gray-400">#{{ $timeline->image_order }}</div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $timeline->created_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2 space-x-reverse">
                                        <a href="{{ route('admin.episodes.timeline.edit', [$timeline->episode, $timeline]) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            ویرایش
                                        </a>
                                        <a href="{{ route('admin.episodes.timeline.index', $timeline->episode) }}" 
                                           class="text-blue-600 hover:text-blue-900 flex items-center">
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            مشاهده
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $timelines->appends(request()->query())->links() }}
            </div>
        </div>
    @else
        <div class="bg-white p-12 rounded-lg shadow-sm text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">هیچ تایم‌لاینی یافت نشد</h3>
            <p class="text-gray-600 mb-6">با فیلترهای انتخابی هیچ تایم‌لاینی وجود ندارد.</p>
            <a href="{{ route('admin.timelines.index') }}" 
               class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition-colors">
                مشاهده همه تایم‌لاین‌ها
            </a>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const timelineCheckboxes = document.querySelectorAll('.timeline-checkbox');
    const actionSelect = document.querySelector('select[name="action"]');
    const transitionSelect = document.querySelector('select[name="transition_type"]');
    const keyFrameSelect = document.querySelector('select[name="is_key_frame"]');

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        timelineCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Show/hide additional selects based on action
    actionSelect.addEventListener('change', function() {
        transitionSelect.style.display = this.value === 'change_transition' ? 'block' : 'none';
        keyFrameSelect.style.display = this.value === 'change_key_frame' ? 'block' : 'none';
    });
});
</script>
@endsection
