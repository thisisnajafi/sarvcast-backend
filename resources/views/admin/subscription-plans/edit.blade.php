@extends('admin.layouts.app')

@section('title', 'ویرایش پلن اشتراک')
@section('page-title', 'ویرایش پلن اشتراک')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    @include('admin.components.page-header', [
        'title' => 'ویرایش پلن اشتراک: ' . $subscriptionPlan->name,
        'subtitle' => 'ویرایش اطلاعات پلن اشتراک و تنظیمات قیمت برای هر پلتفرم',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
        'iconBg' => 'bg-emerald-100',
        'iconColor' => 'text-emerald-600',
    ])

    <form method="POST" action="{{ route('admin.subscription-plans.update', $subscriptionPlan) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات پایه</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام پلن <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $subscriptionPlan->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="مثال: پلن یک ماهه">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">شناسه یکتا (Slug)</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $subscriptionPlan->slug) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('slug') border-red-500 @enderror"
                           placeholder="مثال: 1month">
                    @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="توضیحات پلن اشتراک...">{{ old('description', $subscriptionPlan->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-2">مدت زمان (روز) <span class="text-red-500">*</span></label>
                    <input type="number" name="duration_days" id="duration_days" value="{{ old('duration_days', $subscriptionPlan->duration_days) }}" required min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('duration_days') border-red-500 @enderror"
                           placeholder="30">
                    @error('duration_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">ارز</label>
                    <select name="currency" id="currency"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('currency') border-red-500 @enderror">
                        <option value="IRT" {{ old('currency', $subscriptionPlan->currency) === 'IRT' ? 'selected' : '' }}>تومان (IRT)</option>
                        <option value="IRR" {{ old('currency', $subscriptionPlan->currency) === 'IRR' ? 'selected' : '' }}>ریال (IRR)</option>
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">درصد تخفیف</label>
                    <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $subscriptionPlan->discount_percentage) }}" min="0" max="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('discount_percentage') border-red-500 @enderror"
                           placeholder="0">
                    @error('discount_percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">ترتیب نمایش</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $subscriptionPlan->sort_order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('sort_order') border-red-500 @enderror"
                           placeholder="0">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Pricing by Flavor -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">قیمت‌گذاری بر اساس پلتفرم</h3>
            
            <!-- Website (Default) -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h4 class="text-md font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                    وب‌سایت (پیش‌فرض)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">قیمت (تومان) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" id="price" value="{{ old('price', $subscriptionPlan->price) }}" required min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('price') border-red-500 @enderror"
                               placeholder="50000">
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">شناسه محصول: {{ $subscriptionPlan->slug }}</p>
                    </div>
                </div>
            </div>

            <!-- Myket -->
            <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <h4 class="text-md font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    مایکت
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="myket_price" class="block text-sm font-medium text-gray-700 mb-2">قیمت (تومان)</label>
                        <input type="number" name="myket_price" id="myket_price" value="{{ old('myket_price', $subscriptionPlan->myket_price) }}" min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('myket_price') border-red-500 @enderror"
                               placeholder="قیمت مایکت (اختیاری)">
                        @error('myket_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">اگر خالی باشد، از قیمت وب‌سایت استفاده می‌شود</p>
                    </div>
                    <div>
                        <label for="myket_product_id" class="block text-sm font-medium text-gray-700 mb-2">شناسه محصول مایکت</label>
                        <input type="text" name="myket_product_id" id="myket_product_id" value="{{ old('myket_product_id', $subscriptionPlan->myket_product_id) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('myket_product_id') border-red-500 @enderror"
                               placeholder="شناسه محصول در مایکت">
                        @error('myket_product_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- CafeBazaar -->
            <div class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
                <h4 class="text-md font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    کافه‌بازار
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cafebazaar_price" class="block text-sm font-medium text-gray-700 mb-2">قیمت (تومان)</label>
                        <input type="number" name="cafebazaar_price" id="cafebazaar_price" value="{{ old('cafebazaar_price', $subscriptionPlan->cafebazaar_price) }}" min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('cafebazaar_price') border-red-500 @enderror"
                               placeholder="قیمت کافه‌بازار (اختیاری)">
                        @error('cafebazaar_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">اگر خالی باشد، از قیمت وب‌سایت استفاده می‌شود</p>
                    </div>
                    <div>
                        <label for="cafebazaar_product_id" class="block text-sm font-medium text-gray-700 mb-2">شناسه محصول کافه‌بازار</label>
                        <input type="text" name="cafebazaar_product_id" id="cafebazaar_product_id" value="{{ old('cafebazaar_product_id', $subscriptionPlan->cafebazaar_product_id) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('cafebazaar_product_id') border-red-500 @enderror"
                               placeholder="شناسه محصول در کافه‌بازار">
                        @error('cafebazaar_product_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">ویژگی‌های پلن</h3>
            <div id="features-container">
                @php
                    $features = old('features', $subscriptionPlan->features ?? []);
                    if (empty($features)) {
                        $features = [''];
                    }
                @endphp
                @foreach($features as $index => $feature)
                <div class="flex items-center gap-2 mb-2">
                    <input type="text" name="features[]" value="{{ is_string($feature) ? $feature : '' }}"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="ویژگی پلن...">
                    @if($index > 0 || count($features) > 1)
                    <button type="button" onclick="removeFeature(this)" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
                        حذف
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
            <button type="button" onclick="addFeature()" class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                + افزودن ویژگی
            </button>
        </div>

        <!-- Status -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">وضعیت</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $subscriptionPlan->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                    <label for="is_active" class="mr-2 text-sm font-medium text-gray-700">پلن فعال است</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $subscriptionPlan->is_featured) ? 'checked' : '' }}
                           class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                    <label for="is_featured" class="mr-2 text-sm font-medium text-gray-700">پلن ویژه</label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.subscription-plans.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                انصراف
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                به‌روزرسانی پلن
            </button>
        </div>
    </form>
</div>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 mb-2';
    div.innerHTML = `
        <input type="text" name="features[]" 
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
               placeholder="ویژگی پلن...">
        <button type="button" onclick="removeFeature(this)" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
            حذف
        </button>
    `;
    container.appendChild(div);
}

function removeFeature(button) {
    button.parentElement.remove();
}
</script>
@endsection

