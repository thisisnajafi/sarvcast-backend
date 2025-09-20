@extends('admin.layouts.app')

@section('title', 'مشاهده نظر')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مشاهده نظر</h1>
            <p class="text-gray-600 mt-1">جزئیات کامل نظر کاربر</p>
        </div>
        <div class="flex items-center space-x-3 space-x-reverse">
            <a href="{{ route('admin.comments.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Comment -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center ml-4">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $comment->user->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $comment->user->phone_number }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        @if($comment->is_pinned)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                                سنجاق شده
                            </span>
                        @endif
                        @if($comment->parent_id)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                پاسخ
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">محتوا</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-900 whitespace-pre-wrap">{{ $comment->content }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
                        <span>{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                        @if($comment->likes_count > 0)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 text-red-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $comment->likes_count }} پسند
                            </span>
                        @endif
                        @if($comment->replies_count > 0)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 text-blue-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                {{ $comment->replies_count }} پاسخ
                            </span>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-2 space-x-reverse">
                        @if(!$comment->is_approved)
                            <form method="POST" action="{{ route('admin.comments.approve', $comment) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center" onclick="return confirm('آیا مطمئن هستید؟')">
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    تایید
                                </button>
                            </form>
                        @endif
                        
                        @if($comment->is_approved)
                            <form method="POST" action="{{ route('admin.comments.reject', $comment) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center" onclick="return confirm('آیا مطمئن هستید؟')">
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    رد
                                </button>
                            </form>
                        @endif
                        
                        @if($comment->is_pinned)
                            <form method="POST" action="{{ route('admin.comments.unpin', $comment) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                    </svg>
                                    حذف سنجاق
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.comments.pin', $comment) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                    </svg>
                                    سنجاق
                                </button>
                            </form>
                        @endif
                        
                        <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این نظر را حذف کنید؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Replies -->
            @if($comment->replies->count() > 0)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">پاسخ‌ها ({{ $comment->replies_count }})</h3>
                    <div class="space-y-4">
                        @foreach($comment->replies as $reply)
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mr-6">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center ml-3">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900">{{ $reply->user->name }}</h4>
                                            <p class="text-xs text-gray-500">{{ $reply->user->phone_number }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        @if($reply->is_pinned)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                سنجاق شده
                                            </span>
                                        @endif
                                        @if($reply->is_approved)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                تایید شده
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                در انتظار
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ $reply->content }}</p>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                    <div class="flex items-center space-x-3 space-x-reverse text-xs text-gray-500">
                                        <span>{{ $reply->created_at->format('Y/m/d H:i') }}</span>
                                        @if($reply->likes_count > 0)
                                            <span class="flex items-center">
                                                <svg class="w-3 h-3 text-red-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ $reply->likes_count }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center space-x-1 space-x-reverse">
                                        @if(!$reply->is_approved)
                                            <form method="POST" action="{{ route('admin.comments.approve', $reply) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800" title="تایید" onclick="return confirm('آیا مطمئن هستید؟')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($reply->is_approved)
                                            <form method="POST" action="{{ route('admin.comments.reject', $reply) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="رد" onclick="return confirm('آیا مطمئن هستید؟')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form method="POST" action="{{ route('admin.comments.destroy', $reply) }}" class="inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این پاسخ را حذف کنید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="حذف">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Comment Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">وضعیت نظر</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">وضعیت تایید:</span>
                        @if($comment->is_approved)
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                تایید شده
                            </span>
                        @elseif($comment->is_visible)
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                در انتظار
                            </span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                رد شده
                            </span>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">قابلیت مشاهده:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $comment->is_visible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $comment->is_visible ? 'قابل مشاهده' : 'غیرقابل مشاهده' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">سنجاق شده:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $comment->is_pinned ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $comment->is_pinned ? 'بله' : 'خیر' }}
                        </span>
                    </div>
                    
                    @if($comment->approved_at)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">تاریخ تایید:</span>
                            <span class="text-sm text-gray-900">{{ $comment->approved_at->format('Y/m/d H:i') }}</span>
                        </div>
                    @endif
                    
                    @if($comment->approver)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">تایید کننده:</span>
                            <span class="text-sm text-gray-900">{{ $comment->approver->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Story Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات داستان</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">عنوان:</span>
                        <p class="text-sm text-gray-900 font-medium">{{ $comment->story->title }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">دسته‌بندی:</span>
                        <p class="text-sm text-gray-900">{{ $comment->story->category->name ?? 'تعین نشده' }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">وضعیت:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $comment->story->status == 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $comment->story->status == 'published' ? 'منتشر شده' : 'پیش‌نویس' }}
                        </span>
                    </div>
                    
                    <div class="pt-3">
                        <a href="{{ route('admin.stories.show', $comment->story) }}" class="text-primary hover:text-blue-600 text-sm font-medium">
                            مشاهده داستان →
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات کاربر</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">نام:</span>
                        <p class="text-sm text-gray-900 font-medium">{{ $comment->user->name }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">شماره تلفن:</span>
                        <p class="text-sm text-gray-900">{{ $comment->user->phone_number }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">نقش:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $comment->user->role == 'parent' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ $comment->user->role == 'parent' ? 'والد' : 'کودک' }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">تاریخ عضویت:</span>
                        <p class="text-sm text-gray-900">{{ $comment->user->created_at->format('Y/m/d') }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">تعداد نظرات:</span>
                        <p class="text-sm text-gray-900">{{ $comment->user->storyComments()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
