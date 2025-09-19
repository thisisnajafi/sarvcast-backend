@extends('admin.layouts.app')

@section('title', 'مدیریت پلن‌های اشتراک')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">مدیریت پلن‌های اشتراک</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.plans.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                افزودن پلن جدید
            </a>
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت به اشتراک‌ها
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($plans as $plan)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden {{ $plan->is_featured ? 'ring-2 ring-primary' : '' }}">
            <!-- Plan Header -->
            <div class="p-6 {{ $plan->is_featured ? 'bg-primary text-white' : 'bg-gray-50 dark:bg-gray-700' }}">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold {{ $plan->is_featured ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                            {{ $plan->name }}
                        </h3>
                        @if($plan->is_featured)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white text-primary mt-2">
                                پلن ویژه
                            </span>
                        @endif
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        @if($plan->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                فعال
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                غیرفعال
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Price -->
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $plan->is_featured ? 'text-white' : 'text-primary' }} mb-2">
                        {{ number_format($plan->price) }} {{ $plan->currency }}
                    </div>
                    @if($plan->discount_percentage > 0)
                        <div class="text-lg {{ $plan->is_featured ? 'text-white' : 'text-green-600' }} mb-2">
                            {{ number_format($plan->final_price) }} {{ $plan->currency }}
                        </div>
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $plan->discount_percentage }}% تخفیف
                        </div>
                    @endif
                </div>
            </div>

            <!-- Plan Details -->
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Duration -->
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        مدت زمان: {{ $plan->duration_text }}
                    </div>

                    <!-- Description -->
                    @if($plan->description)
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $plan->description }}
                        </div>
                    @endif

                    <!-- Features -->
                    @if($plan->features && count($plan->features) > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">ویژگی‌ها:</h4>
                            <ul class="space-y-1">
                                @foreach($plan->features as $feature)
                                    <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                        <svg class="w-3 h-3 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Statistics -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">کل اشتراک‌ها</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $plan->subscriptions->count() }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">اشتراک‌های فعال</div>
                                <div class="font-semibold text-green-600 dark:text-green-400">{{ $plan->subscriptions->where('status', 'active')->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-2 space-x-reverse">
                        <a href="{{ route('admin.plans.show', $plan) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            مشاهده
                        </a>
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">
                            ویرایش
                        </a>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <form action="{{ route('admin.plans.toggle-status', $plan) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium {{ $plan->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $plan->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="inline" onsubmit="return confirm('آیا از حذف این پلن اطمینان دارید؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">هیچ پلنی یافت نشد</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">شروع کنید با ایجاد اولین پلن اشتراک.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.plans.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        افزودن پلن جدید
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
