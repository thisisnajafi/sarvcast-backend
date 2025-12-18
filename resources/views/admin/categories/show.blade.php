@extends('admin.layouts.app')

@section('title', 'مشاهده دسته‌بندی: ' . $category->name)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $category->name }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.categories.edit', $category) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                ویرایش
            </a>
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Category Image -->
            @if($category->image_url)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصویر دسته‌بندی</h3>
                    <img src="{{ $category->image_url }}" alt="Category Image" class="w-full h-64 object-cover rounded-lg border">
                </div>
            @endif

            <!-- Category Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">جزئیات دسته‌بندی</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نام</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $category->name }}</p>
                    </div>
                    
                    @if($category->slug)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">نامک (Slug)</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $category->slug }}</p>
                        </div>
                    @endif
                    
                    @if($category->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $category->description }}</p>
                        </div>
                    @endif
                    
                    @if($category->color)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">رنگ</label>
                            <div class="flex items-center space-x-2 space-x-reverse mt-1">
                                <div class="w-8 h-8 rounded-full border" style="background-color: {{ $category->color }}"></div>
                                <span class="text-sm text-gray-900">{{ $category->color }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stories in this Category -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">داستان‌های این دسته‌بندی</h3>
                    <span class="text-sm text-gray-500">{{ $category->stories->count() }} داستان</span>
                </div>
                
                @if($category->stories->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">گروه سنی</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تعداد اپیزودها</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($category->stories->take(10) as $story)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $story->title }}</div>
                                            @if($story->subtitle)
                                                <div class="text-sm text-gray-500">{{ $story->subtitle }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $story->age_group }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($story->status === 'published') bg-green-100 text-green-800
                                                @elseif($story->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($story->status === 'draft') bg-gray-100 text-gray-800
                                                @elseif($story->status === 'approved') bg-blue-100 text-blue-800
                                                @else bg-red-100 text-red-800 @endif">
                                                @if($story->status === 'published') منتشر شده
                                                @elseif($story->status === 'pending') در انتظار
                                                @elseif($story->status === 'draft') پیش‌نویس
                                                @elseif($story->status === 'approved') تأیید شده
                                                @else {{ $story->status }} @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $story->episodes->count() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2 space-x-reverse">
                                                <a href="{{ route('admin.stories.show', $story) }}" class="text-primary hover:text-blue-600">مشاهده</a>
                                                <a href="{{ route('admin.stories.edit', $story) }}" class="text-green-600 hover:text-green-800">ویرایش</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($category->stories->count() > 10)
                        <div class="mt-4 text-center">
                            <a href="{{ route('admin.stories.index', ['category' => $category->id]) }}" class="text-primary hover:text-blue-600">
                                مشاهده همه داستان‌ها ({{ $category->stories->count() }})
                            </a>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500 text-center py-4">هیچ داستانی در این دسته‌بندی وجود ندارد</p>
                @endif
            </div>

            <!-- SEO Information -->
            @if($category->meta_title || $category->meta_description)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات SEO</h3>
                    <div class="space-y-4">
                        @if($category->meta_title)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">عنوان متا</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $category->meta_title }}</p>
                            </div>
                        @endif
                        
                        @if($category->meta_description)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">توضیحات متا</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $category->meta_description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
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
                            {{ $category->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->status === 'active' ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">ترتیب نمایش</span>
                        <span class="text-sm text-gray-900">{{ $category->order ?? 0 }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تاریخ ایجاد</span>
                        <span class="text-sm text-gray-900">{{ $category->created_at->format('Y/m/d H:i') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">آخرین به‌روزرسانی</span>
                        <span class="text-sm text-gray-900">{{ $category->updated_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">آمار</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تعداد داستان‌ها</span>
                        <span class="text-sm text-gray-900">{{ $category->stories->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">داستان‌های منتشر شده</span>
                        <span class="text-sm text-gray-900">{{ $category->stories->where('status', 'published')->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">داستان‌های پیش‌نویس</span>
                        <span class="text-sm text-gray-900">{{ $category->stories->where('status', 'draft')->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">کل اپیزودها</span>
                        <span class="text-sm text-gray-900">{{ $category->stories->sum(function($story) { return $story->episodes->count(); }) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">عملیات</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 text-center block">
                        ویرایش دسته‌بندی
                    </a>
                    
                    <a href="{{ route('admin.stories.create', ['category_id' => $category->id]) }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-center block">
                        افزودن داستان جدید
                    </a>
                    
                    <a href="{{ route('admin.stories.index', ['category' => $category->id]) }}" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 text-center block">
                        مشاهده همه داستان‌ها
                    </a>
                    
                    @if($category->status === 'active')
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="inactive">
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition duration-200">
                                غیرفعال کردن
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                فعال کردن
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

