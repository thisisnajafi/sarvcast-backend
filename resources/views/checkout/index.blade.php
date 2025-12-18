<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>خرید اشتراک - سروکست</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-white min-h-screen flex flex-col">
<div class="flex-1 bg-gradient-to-b from-slate-900 via-slate-950 to-black">
    <div class="max-w-4xl mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center shadow-lg">
                    <span class="text-xl font-black">S</span>
                </div>
                <div>
                    <div class="text-sm text-slate-300">سروکست</div>
                    <div class="text-xs text-slate-500">داستان‌های صوتی برای بچه‌ها</div>
                </div>
            </div>
            <div class="text-xs text-slate-500">
                منبع پرداخت: <span class="font-semibold text-slate-200">{{ $source === 'app' ? 'اپلیکیشن' : 'وب‌سایت' }}</span>
            </div>
        </div>

        {{-- Main card --}}
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl shadow-2xl shadow-sky-500/10 overflow-hidden">
            <div class="border-b border-slate-800/80 px-5 py-4 flex items-center justify-between bg-gradient-to-l from-slate-900 to-slate-950">
                <div>
                    <h1 class="text-base font-semibold text-slate-50">انتخاب اشتراک</h1>
                    <p class="text-xs text-slate-400 mt-1">
                        یک پلن را انتخاب کنید. اعمال کد تخفیف و پرداخت در همین صفحه انجام می‌شود.
                    </p>
                </div>
            </div>

            <form class="grid md:grid-cols-[2fr,1.5fr] gap-6 p-5 md:p-6" method="POST" action="{{ route('checkout.store') }}">
                @csrf
                {{-- Plans --}}
                <div class="space-y-3">
                    @forelse($plans as $plan)
                        @php
                            $isSelected = $selectedPlan && $selectedPlan->id === $plan->id;
                        @endphp
                        <label
                            class="block relative rounded-2xl border {{ $isSelected ? 'border-sky-400/80 bg-slate-900/90' : 'border-slate-800 bg-slate-900/60' }} p-4 cursor-pointer transition hover:border-sky-500/70 hover:bg-slate-900/90"
                        >
                            <input
                                type="radio"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="hidden"
                                {{ $isSelected ? 'checked' : '' }}
                            >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h2 class="text-sm font-semibold text-slate-50">
                                            {{ $plan->name }}
                                        </h2>
                                        @if($plan->is_featured)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-500/10 text-[10px] font-semibold text-amber-300 border border-amber-500/40">
                                                ویژه
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400 mb-2">
                                        {{ $plan->description }}
                                    </p>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-800 text-slate-200">
                                            مدت: {{ $plan->duration_text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-left">
                                    @if($plan->discount_percentage > 0)
                                        <div class="text-[11px] text-slate-500 line-through">
                                            {{ $plan->formatted_price }}
                                        </div>
                                        <div class="text-sm font-bold text-emerald-300">
                                            {{ $plan->formatted_final_price }}
                                        </div>
                                    @else
                                        <div class="text-sm font-bold text-slate-50">
                                            {{ $plan->formatted_price }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($isSelected)
                                <div class="absolute left-4 top-4">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-sky-500 text-white text-[11px]">
                                        ✓
                                    </span>
                                </div>
                            @endif
                        </label>
                    @empty
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 text-xs text-slate-400">
                            هیچ پلن فعالی برای اشتراک یافت نشد. لطفاً با پشتیبانی تماس بگیرید.
                        </div>
                    @endforelse

                    <p class="text-[11px] text-slate-500 mt-1">
                        قیمت‌ها و مدت اشتراک از سرور بارگذاری می‌شوند. اعمال کد تخفیف و محاسبه مبلغ نهایی در همین صفحه و روی سرور انجام خواهد شد.
                    </p>
                </div>

                {{-- Sidebar: coupon + summary --}}
                <div class="space-y-4">
                    {{-- Coupon --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                        <h3 class="text-xs font-semibold text-slate-50 mb-2">
                            کد تخفیف
                        </h3>
                        <p class="text-[11px] text-slate-400 mb-3">
                            اگر کد تخفیف دارید، آن را وارد کنید تا مبلغ نهایی اشتراک برای شما محاسبه شود.
                        </p>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                dir="ltr"
                                name="coupon_code"
                                class="flex-1 rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500"
                                placeholder="مثلاً WELCOME"
                                value="{{ old('coupon_code', $appliedCouponCode ?? '') }}"
                            >
                            <button
                                type="submit"
                                name="action"
                                value="apply_coupon"
                                class="px-3 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white transition"
                            >
                                اعمال
                            </button>
                        </div>
                        @error('coupon_code')
                            <p class="mt-2 text-[11px] text-red-400">{{ $message }}</p>
                        @enderror
                        <input type="hidden" name="source" value="{{ $source }}">
                        @if($source === 'app')
                            <input type="hidden" name="return_scheme" value="sarvcast">
                        @endif
                        @if(!empty($episodeId))
                            <input type="hidden" name="episode_id" value="{{ $episodeId }}">
                        @endif
                    </div>

                    {{-- Summary + CTA --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-slate-50">
                            خلاصه پرداخت
                        </h3>

                        @if($selectedPlan)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">پلن انتخاب‌شده</span>
                                <span class="text-slate-100 font-medium">{{ $selectedPlan->name }}</span>
                            </div>

                            @php
                                $hasCalculatedPrice = isset($priceInfo);
                            @endphp

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">قیمت پایه</span>
                                <span class="text-slate-100 font-medium">
                                    @if($hasCalculatedPrice)
                                        {{ number_format($priceInfo['base_price']) }} تومان
                                    @else
                                        {{ $selectedPlan->formatted_price }}
                                    @endif
                                </span>
                            </div>

                            @if($hasCalculatedPrice && !empty($priceInfo['coupon_discount'] ?? null))
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">تخفیف کوپن</span>
                                    <span class="text-emerald-300 font-medium">
                                        {{ number_format($priceInfo['coupon_discount']) }} تومان-
                                    </span>
                                </div>
                            @endif
                        @else
                            <p class="text-[11px] text-slate-500">
                                برای ادامه، ابتدا یکی از پلن‌های موجود را انتخاب کنید.
                            </p>
                        @endif

                        <div class="border-t border-slate-800 my-2"></div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300 font-semibold">
                                مبلغ نهایی (تومان)
                            </span>
                            <span class="text-emerald-300 font-bold">
                                @if(isset($priceInfo))
                                    {{ number_format($priceInfo['final_price']) }} تومان
                                @else
                                    {{ $selectedPlan ? $selectedPlan->formatted_final_price : '—' }}
                                @endif
                            </span>
                        </div>

                        @if(isset($priceInfo) && !empty($priceInfo['conversion_note'] ?? null))
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $priceInfo['conversion_note'] }}
                                ({{ number_format($priceInfo['amount']) }} ریال در درگاه پرداخت)
                            </p>
                        @endif

                        <p class="text-[11px] text-slate-500">
                            با کلیک روی دکمه زیر، اشتراک برای شما ایجاد شده و به درگاه امن زرین‌پال هدایت می‌شوید.
                            در صورت شروع از اپلیکیشن، پس از پرداخت، به اپ برمی‌گردید.
                        </p>

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-l from-sky-500 to-indigo-500 text-white text-xs font-semibold py-2.5 shadow-lg shadow-sky-500/30"
                        >
                            ادامه و انتقال به درگاه پرداخت
                        </button>

                        @error('plan_id')
                            <p class="mt-2 text-[11px] text-red-400">{{ $message }}</p>
                        @enderror
                        @error('payment')
                            <p class="mt-2 text-[11px] text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="mt-6 text-[10px] text-slate-500 text-center">
            پرداخت‌ها توسط درگاه امن زرین‌پال انجام می‌شود. این صفحه صرفاً پیش‌نمایش جریان پرداخت وب است؛
            منطق اصلی کوپن و شروع پرداخت در کنترلرها و APIهای بک‌اند پیاده‌سازی خواهد شد.
        </div>
    </div>
</div>
</body>
</html>


