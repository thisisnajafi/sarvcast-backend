@extends('admin.layouts.app')

@section('title', 'مشاهده پلن اشتراک: ' . $plan->name)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">مشاهده پلن اشتراک: {{ $plan->name }}</h1>
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('admin.plans.edit', $plan) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                ویرایش
            </a>
            <a href="{{ route('admin.plans.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت
            </a>
        </div>
    </div>

    <!-- Plan Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Plan Details -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden {{ $plan->is_featured ? 'ring-2 ring-primary' : '' }}">
                <!-- Plan Header -->
                <div class="p-6 {{ $plan->is_featured ? 'bg-primary text-white' : 'bg-gray-50 dark:bg-gray-700' }}">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-xl font-semibold {{ $plan->is_featured ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                {{ $plan->name }}
                            </h2>
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
                        <div class="text-4xl font-bold {{ $plan->is_featured ? 'text-white' : 'text-primary' }} mb-2">
                            {{ number_format($plan->price) }} {{ $plan->currency }}
                        </div>
                        @if($plan->discount_percentage > 0)
                            <div class="text-xl {{ $plan->is_featured ? 'text-white' : 'text-green-600' }} mb-2">
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
                    <div class="space-y-6">
                        <!-- Basic Info -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">اطلاعات اصلی</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">شناسه یکتا</label>
                                    <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $plan->slug }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">مدت زمان</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $plan->duration_text }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">ترتیب نمایش</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $plan->sort_order }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">تاریخ ایجاد</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $plan->created_at->format('Y/m/d H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($plan->description)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">توضیحات</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $plan->description }}</p>
                            </div>
                        @endif

                        <!-- Features -->
                        @if($plan->features && count($plan->features) > 0)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">ویژگی‌ها</h3>
                                <ul class="space-y-2">
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                            <svg class="w-4 h-4 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="space-y-6">
            <!-- Subscription Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">آمار اشتراک‌ها</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">کل اشتراک‌ها</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $plan->subscriptions->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">اشتراک‌های فعال</span>
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $plan->subscriptions->where('status', 'active')->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">اشتراک‌های منقضی</span>
                        <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ $plan->subscriptions->where('status', 'expired')->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">اشتراک‌های لغو شده</span>
                        <span class="text-lg font-semibold text-gray-600 dark:text-gray-400">{{ $plan->subscriptions->where('status', 'cancelled')->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">آمار درآمد</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">کل درآمد</span>
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($plan->subscriptions->sum('price')) }} {{ $plan->currency }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">میانگین درآمد</span>
                        <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                            {{ $plan->subscriptions->count() > 0 ? number_format($plan->subscriptions->avg('price')) : 0 }} {{ $plan->currency }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">عملیات</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-center block">
                        ویرایش پلن
                    </a>
                    <form action="{{ route('admin.plans.toggle-status', $plan) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full {{ $plan->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg transition duration-200">
                            {{ $plan->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="w-full" onsubmit="return confirm('آیا از حذف این پلن اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200">
                            حذف پلن
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Subscriptions -->
    @if($plan->subscriptions->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">اشتراک‌های اخیر</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">وضعیت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تاریخ شروع</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تاریخ پایان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">قیمت</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($plan->subscriptions->take(10) as $subscription)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $subscription->user->first_name }} {{ $subscription->user->last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($subscription->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            فعال
                                        </span>
                                    @elseif($subscription->status === 'expired')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            منقضی
                                        </span>
                                    @elseif($subscription->status === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                            لغو شده
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ $subscription->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $subscription->start_date->format('Y/m/d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $subscription->end_date->format('Y/m/d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($subscription->price) }} {{ $subscription->currency }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
