@extends('admin.layouts.app')

@section('title', 'مشاهده اپیزود: ' . $episode->title)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $episode->title }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.episodes.voice-actors.index', $episode) }}" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-200 flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                مدیریت صداپیشگان
            </a>
            <a href="{{ route('admin.episodes.edit', $episode) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                ویرایش
            </a>
            <a href="{{ route('admin.episodes.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                بازگشت به لیست
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Episode Image -->
            @if($episode->image_url)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تصویر اپیزود</h3>
                    <img src="{{ $episode->image_url }}" alt="Episode Image" class="w-full h-64 object-cover rounded-lg border">
                </div>
            @endif

            <!-- Episode Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">جزئیات اپیزود</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">عنوان</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $episode->title }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">داستان</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $episode->story->title }}</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">شماره اپیزود</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $episode->episode_number }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ترتیب</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $episode->order }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">مدت زمان</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $episode->duration }} دقیقه</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">حجم فایل</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $episode->file_size ? $episode->file_size . ' مگابایت' : 'نامشخص' }}</p>
                        </div>
                    </div>
                    
                    @if($episode->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $episode->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audio Player -->
            @if($episode->audio_url)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">پخش فایل صوتی</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <audio controls class="w-full">
                            <source src="{{ $episode->audio_url }}" type="audio/mpeg">
                            مرورگر شما از پخش فایل صوتی پشتیبانی نمی‌کند.
                        </audio>
                        <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                            <span>فایل: {{ basename($episode->audio_url) }}</span>
                            <span>{{ $episode->duration }} دقیقه</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Voice Actors -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">صداپیشگان</h3>
                    <a href="{{ route('admin.episodes.voice-actors.index', $episode) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        مدیریت صداپیشگان →
                    </a>
                </div>
                @if($episode->voiceActors->count() > 0)
                    <div class="space-y-3">
                        @foreach($episode->voiceActors as $voiceActor)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <img src="{{ $voiceActor->person->image_url ?: '/images/default-avatar.png' }}" 
                                             alt="{{ $voiceActor->person->name }}" 
                                             class="w-10 h-10 rounded-full object-cover"
                                             onerror="this.src='/images/default-avatar.png'">
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $voiceActor->person->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $voiceActor->role }}</div>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs text-gray-500">{{ gmdate('i:s', $voiceActor->start_time) }} - {{ gmdate('i:s', $voiceActor->end_time) }}</div>
                                    @if($voiceActor->is_primary)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">اصلی</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 mb-4">هیچ صداپیشه‌ای برای این اپیزود تعریف نشده است</p>
                        <a href="{{ route('admin.episodes.voice-actors.create', $episode) }}" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors duration-200">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            افزودن صداپیشه
                        </a>
                    </div>
                @endif
            </div>

            <!-- Play History -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">تاریخچه پخش</h3>
                @if($episode->playHistories->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">کاربر</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمان پخش</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مدت گوش دادن</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($episode->playHistories->take(10) as $playHistory)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $playHistory->user->first_name }} {{ $playHistory->user->last_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $playHistory->user->phone_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $playHistory->created_at->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $playHistory->listened_duration ? round($playHistory->listened_duration / 60, 1) . ' دقیقه' : 'نامشخص' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($playHistory->completed) bg-green-100 text-green-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ $playHistory->completed ? 'تکمیل شده' : 'در حال پخش' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">هیچ تاریخچه پخشی برای این اپیزود وجود ندارد</p>
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
                            @if($episode->status === 'published') bg-green-100 text-green-800
                            @elseif($episode->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($episode->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($episode->status === 'approved') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800 @endif">
                            @if($episode->status === 'published') منتشر شده
                            @elseif($episode->status === 'pending') در انتظار بررسی
                            @elseif($episode->status === 'draft') پیش‌نویس
                            @elseif($episode->status === 'approved') تأیید شده
                            @elseif($episode->status === 'rejected') رد شده
                            @else {{ $episode->status }} @endif
                        </span>
                    </div>
                    
                    @if($episode->published_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تاریخ انتشار</span>
                            <span class="text-sm text-gray-900">{{ $episode->published_at->format('Y/m/d H:i') }}</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تاریخ ایجاد</span>
                        <span class="text-sm text-gray-900">{{ $episode->created_at->format('Y/m/d H:i') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">آخرین به‌روزرسانی</span>
                        <span class="text-sm text-gray-900">{{ $episode->updated_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Options Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">گزینه‌ها</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">اپیزود پولی</span>
                        <span class="text-sm {{ $episode->is_premium ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $episode->is_premium ? 'بله' : 'خیر' }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">رایگان</span>
                        <span class="text-sm {{ $episode->is_free ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $episode->is_free ? 'بله' : 'خیر' }}
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
                        <span class="text-sm text-gray-900">{{ number_format($episode->play_count) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">تکمیل شده</span>
                        <span class="text-sm text-gray-900">{{ $episode->playHistories->where('completed', true)->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">در حال پخش</span>
                        <span class="text-sm text-gray-900">{{ $episode->playHistories->where('completed', false)->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">میانگین مدت گوش دادن</span>
                        <span class="text-sm text-gray-900">
                            @php
                                $avgDuration = $episode->playHistories->avg('listened_duration');
                            @endphp
                            {{ $avgDuration ? round($avgDuration / 60, 1) . ' دقیقه' : 'نامشخص' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">عملیات</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.episodes.edit', $episode) }}" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 text-center block">
                        ویرایش اپیزود
                    </a>
                    
                    <a href="{{ route('admin.stories.show', $episode->story) }}" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 text-center block">
                        مشاهده داستان
                    </a>
                    
                    @if($episode->status === 'published')
                        <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="draft">
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition duration-200">
                                بازگشت به پیش‌نویس
                            </button>
                        </form>
                    @elseif($episode->status === 'draft')
                        <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="w-full">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="published">
                            <input type="hidden" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                انتشار اپیزود
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

