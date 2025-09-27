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
                            <p class="mt-1 text-sm text-gray-900">{{ $story->formatted_duration }} <span class="text-xs text-gray-500">(دقیقه:ثانیه)</span></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تعداد اپیزودها</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->total_episodes_count }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">اپیزودهای رایگان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $story->free_episodes_count }}</p>
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

            <!-- Additional People -->
            @if($story->people->count() > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">افراد اضافی</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($story->people as $person)
                            <div class="flex items-center space-x-3 space-x-reverse p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    @if($person->image_url)
                                        <img src="{{ $person->image_url }}" alt="{{ $person->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $person->name }}</p>
                                    @if($person->pivot->role)
                                        <p class="text-xs text-gray-500">{{ ucfirst($person->pivot->role) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Episodes -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">اپیزودها</h3>
                        <p class="text-sm text-gray-500 mt-1">مدیریت اپیزودهای این داستان</p>
                    </div>
                    <a href="{{ route('admin.episodes.create', ['story_id' => $story->id]) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center space-x-2 space-x-reverse">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>افزودن اپیزود</span>
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
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">راوی</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">آمار</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($story->episodes->sortBy('episode_number') as $episode)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $episode->title }}</div>
                                            @if($episode->description)
                                                <div class="text-xs text-gray-500 mt-1 line-clamp-2">{{ Str::limit($episode->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $episode->episode_number }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $episode->duration }} دقیقه
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $episode->is_premium ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                {{ $episode->is_premium ? 'پولی' : 'رایگان' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($episode->status === 'published') bg-green-100 text-green-800
                                                @elseif($episode->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($episode->status === 'draft') bg-gray-100 text-gray-800
                                                @elseif($episode->status === 'approved') bg-blue-100 text-blue-800
                                                @else bg-red-100 text-red-800 @endif">
                                                @if($episode->status === 'published') منتشر شده
                                                @elseif($episode->status === 'pending') در انتظار
                                                @elseif($episode->status === 'draft') پیش‌نویس
                                                @elseif($episode->status === 'approved') تأیید شده
                                                @elseif($episode->status === 'rejected') رد شده
                                                @else {{ $episode->status }} @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($episode->narrator)
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center ml-2">
                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                    <span>{{ $episode->narrator->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">تعین نشده</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="space-y-1">
                                                <div class="flex items-center">
                                                    <svg class="w-3 h-3 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ number_format($episode->play_count) }} پخش
                                                </div>
                                                @if($episode->rating > 0)
                                                    <div class="flex items-center">
                                                        <svg class="w-3 h-3 text-yellow-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                        {{ number_format($episode->rating, 1) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2 space-x-reverse">
                                                <a href="{{ route('admin.episodes.show', $episode) }}" class="text-primary hover:text-blue-600 flex items-center" title="مشاهده">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.episodes.edit', $episode) }}" class="text-green-600 hover:text-green-800 flex items-center" title="ویرایش">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                                
                                                @if($episode->status === 'published')
                                                    <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="draft">
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-800 flex items-center" title="بازگشت به پیش‌نویس" onclick="return confirm('آیا مطمئن هستید؟')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @elseif($episode->status === 'draft')
                                                    <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="published">
                                                        <input type="hidden" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                                                        <button type="submit" class="text-green-600 hover:text-green-800 flex items-center" title="انتشار" onclick="return confirm('آیا مطمئن هستید؟')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <form method="POST" action="{{ route('admin.episodes.destroy', $episode) }}" class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این اپیزود را حذف کنید؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 flex items-center" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
               <!-- Episode Summary -->
               <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                   <div class="bg-blue-50 p-4 rounded-lg">
                       <div class="text-sm font-medium text-blue-600">کل اپیزودها</div>
                       <div class="text-2xl font-bold text-blue-900">{{ $story->total_episodes_count }}</div>
                   </div>
                   <div class="bg-green-50 p-4 rounded-lg">
                       <div class="text-sm font-medium text-green-600">منتشر شده</div>
                       <div class="text-2xl font-bold text-green-900">{{ $story->published_episodes_count }}</div>
                   </div>
                   <div class="bg-amber-50 p-4 rounded-lg">
                       <div class="text-sm font-medium text-amber-600">پولی</div>
                       <div class="text-2xl font-bold text-amber-900">{{ $story->premium_episodes_count }}</div>
                   </div>
                   <div class="bg-emerald-50 p-4 rounded-lg">
                       <div class="text-sm font-medium text-emerald-600">رایگان</div>
                       <div class="text-2xl font-bold text-emerald-900">{{ $story->free_episodes_count }}</div>
                   </div>
               </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">هیچ اپیزودی وجود ندارد</h3>
                        <p class="mt-1 text-sm text-gray-500">شروع کنید با افزودن اولین اپیزود به این داستان.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.episodes.create', ['story_id' => $story->id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                افزودن اپیزود
                            </a>
                        </div>
                    </div>
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
                        <span class="text-sm text-gray-900">{{ $story->total_episodes_count }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">اپیزودهای منتشر شده</span>
                        <span class="text-sm text-gray-900">{{ $story->published_episodes_count }}</span>
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

