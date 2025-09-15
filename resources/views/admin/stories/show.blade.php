@extends('admin.layouts.app')

@section('title', 'مشاهده داستان: ' . $story->title)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $story->title }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.stories.edit', $story) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                ویرایش
            </a>
            <a href="{{ route('admin.stories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Story Images -->
            @if($story->cover_image_url || $story->image_url)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصاویر داستان</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($story->cover_image_url)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تصویر جلد</label>
                                <img src="{{ $story->cover_image_url }}" alt="Cover Image" class="w-full h-64 object-cover rounded-lg border">
                            </div>
                        @endif
                        
                        @if($story->image_url)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تصویر داستان</label>
                                <img src="{{ $story->image_url }}" alt="Story Image" class="w-full h-64 object-cover rounded-lg border">
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Story Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">جزئیات داستان</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">عنوان</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $story->title }}</p>
                    </div>
                    
                    @if($story->subtitle)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">زیرعنوان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->subtitle }}</p>
                        </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">توضیحات</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $story->description }}</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">دسته‌بندی</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->category->name ?? 'بدون دسته‌بندی' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">گروه سنی</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->age_group }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">مدت زمان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->duration }} دقیقه</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تعداد اپیزودها</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->total_episodes }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">اپیزودهای رایگان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->free_episodes }}</p>
                        </div>
                    </div>
                    
                    @if($story->tags)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">برچسب‌ها</label>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach(is_array($story->tags) ? $story->tags : explode(', ', $story->tags) as $tag)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ trim($tag) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- People -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">تیم تولید</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($story->director)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">کارگردان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->director->name }}</p>
                        </div>
                    @endif
                    
                    @if($story->writer)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">نویسنده</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->writer->name }}</p>
                        </div>
                    @endif
                    
                    @if($story->author)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">مؤلف</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->author->name }}</p>
                        </div>
                    @endif
                    
                    @if($story->narrator)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">راوی</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->narrator->name }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Episodes -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">اپیزودها</h3>
                    <a href="{{ route('admin.episodes.create', ['story_id' => $story->id]) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                        افزودن اپیزود
                    </a>
                </div>
                
                @if($story->episodes->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شماره</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مدت زمان</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($story->episodes as $episode)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $episode->title }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $episode->episode_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $episode->duration }} دقیقه
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($episode->status === 'published') bg-green-100 text-green-800
                                                @elseif($episode->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($episode->status === 'draft') bg-gray-100 text-gray-800
                                                @else bg-red-100 text-red-800 @endif">
                                                @if($episode->status === 'published') منتشر شده
                                                @elseif($episode->status === 'pending') در انتظار
                                                @elseif($episode->status === 'draft') پیش‌نویس
                                                @else {{ $episode->status }} @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2 space-x-reverse">
                                                <a href="{{ route('admin.episodes.show', $episode) }}" class="text-primary hover:text-blue-600">مشاهده</a>
                                                <a href="{{ route('admin.episodes.edit', $episode) }}" class="text-green-600 hover:text-green-800">ویرایش</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">هیچ اپیزودی برای این داستان وجود ندارد</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">وضعیت</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">وضعیت فعلی</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @if($story->status === 'published') bg-green-100 text-green-800
                            @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($story->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($story->status === 'approved') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800 @endif">
                            @if($story->status === 'published') منتشر شده
                            @elseif($story->status === 'pending') در انتظار بررسی
                            @elseif($story->status === 'draft') پیش‌نویس
                            @elseif($story->status === 'approved') تأیید شده
                            @elseif($story->status === 'rejected') رد شده
                            @else {{ $story->status }} @endif
                        </span>
                    </div>
                    
                    @if($story->published_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تاریخ انتشار</span>
                            <span class="text-sm text-gray-900">{{ $story->published_at->format('Y/m/d H:i') }}</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تاریخ ایجاد</span>
                        <span class="text-sm text-gray-900">{{ $story->created_at->format('Y/m/d H:i') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">آخرین به‌روزرسانی</span>
                        <span class="text-sm text-gray-900">{{ $story->updated_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Options Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">گزینه‌ها</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">داستان پولی</span>
                        <span class="text-sm {{ $story->is_premium ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $story->is_premium ? 'بله' : 'خیر' }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">کاملاً رایگان</span>
                        <span class="text-sm {{ $story->is_completely_free ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $story->is_completely_free ? 'بله' : 'خیر' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">آمار</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تعداد پخش</span>
                        <span class="text-sm text-gray-900">{{ number_format($story->play_count) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">امتیاز</span>
                        <span class="text-sm text-gray-900">{{ $story->rating ?? 'بدون امتیاز' }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تعداد اپیزودها</span>
                        <span class="text-sm text-gray-900">{{ $story->episodes->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">اپیزودهای منتشر شده</span>
                        <span class="text-sm text-gray-900">{{ $story->episodes->where('status', 'published')->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">عملیات</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.stories.edit', $story) }}" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 text-center block">
                        ویرایش داستان
                    </a>
                    
                    <a href="{{ route('admin.episodes.create', ['story_id' => $story->id]) }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-center block">
                        افزودن اپیزود
                    </a>
                    
                    @if($story->status === 'published')
                        <form method="POST" action="{{ route('admin.stories.update', $story) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="draft">
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition duration-200">
                                بازگشت به پیش‌نویس
                            </button>
                        </form>
                    @elseif($story->status === 'draft')
                        <form method="POST" action="{{ route('admin.stories.update', $story) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="published">
                            <input type="hidden" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                انتشار داستان
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

