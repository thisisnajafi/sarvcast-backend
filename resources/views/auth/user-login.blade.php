<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به حساب کاربری - سروکست</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl shadow-2xl shadow-sky-500/20 p-8">
            <div class="flex items-center justify-center mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center shadow-lg">
                    <span class="text-2xl font-black text-white">S</span>
                </div>
            </div>
            <h1 class="text-center text-lg font-semibold text-slate-50 mb-2">
                ورود به حساب کاربری
            </h1>
            <p class="text-center text-sm text-slate-400 mb-6">
                برای خرید و مدیریت اشتراک سروکست، ابتدا با شماره موبایل خود وارد شوید.
            </p>

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-xs text-red-100">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('user.login.post') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="step" value="{{ $step ?? 'phone' }}">

                {{-- Phone number --}}
                <div>
                    <label for="phone_number" class="block text-xs font-medium text-slate-300 mb-1">
                        شماره موبایل
                    </label>
                    <input
                        id="phone_number"
                        name="phone_number"
                        type="tel"
                        dir="ltr"
                        value="{{ old('phone_number', $phone) }}"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500"
                        placeholder="مثلاً 09123456789"
                        required
                    >
                </div>

                {{-- Verification code (second step) --}}
                @if(($step ?? 'phone') === 'verify')
                    <div>
                        <label for="verification_code" class="block text-xs font-medium text-slate-300 mb-1">
                            کد تأیید ارسال‌شده
                        </label>
                        <input
                            id="verification_code"
                            name="verification_code"
                            type="tel"
                            dir="ltr"
                            maxlength="6"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500 tracking-[0.3em] text-center"
                            placeholder="••••••"
                            required
                        >
                        <p class="mt-2 text-[11px] text-slate-500">
                            کد ۶ رقمی ارسال‌شده به شماره {{ $phone }} را وارد کنید.
                        </p>
                    </div>
                @endif

                <div class="pt-2 space-y-2">
                    @if(($step ?? 'phone') === 'phone')
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-l from-sky-500 to-indigo-500 text-white text-sm font-semibold py-2.5 shadow-lg shadow-sky-500/30"
                        >
                            دریافت کد تأیید
                        </button>
                    @else
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-l from-emerald-500 to-teal-500 text-white text-sm font-semibold py-2.5 shadow-lg shadow-emerald-500/30"
                        >
                            ورود و ادامه خرید
                        </button>
                        <button
                            type="submit"
                            name="step"
                            value="phone"
                            class="w-full inline-flex items-center justify-center rounded-xl border border-slate-700 text-slate-200 text-xs font-medium py-2 mt-1"
                        >
                            تغییر شماره موبایل
                        </button>
                    @endif
                </div>
            </form>

            <p class="mt-6 text-[11px] text-slate-500 text-center border-t border-slate-800 pt-3">
                اگر از داخل اپ دکمه <span class="font-semibold text-sky-300">«خرید از سایت»</span> را زده‌اید،
                پس از ورود، به‌صورت خودکار به صفحه خرید اشتراک هدایت می‌شوید.
            </p>
        </div>
    </div>
</body>
</html>


