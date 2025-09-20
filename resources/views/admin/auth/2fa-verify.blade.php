@extends('admin.layouts.app')

@section('title', 'تایید دو مرحله‌ای')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary/10">
                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                تایید دو مرحله‌ای
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                کد تایید به شماره {{ $user->phone_number }} ارسال شد
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('admin.2fa.verify') }}" method="POST">
            @csrf
            
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    کد تایید ۴ رقمی
                </label>
                <input id="code" name="code" type="text" required maxlength="4" 
                       class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm text-center text-lg tracking-widest @error('code') border-red-500 @enderror"
                       placeholder="0000" autocomplete="off">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-200">
                    تایید کد
                </button>
                
                <button type="button" onclick="sendCode()" class="group relative w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-200">
                    ارسال مجدد
                </button>
            </div>

            @if(app()->environment('local', 'development'))
                <div class="text-center">
                    <button type="button" onclick="skip2FA()" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        رد کردن (فقط در محیط توسعه)
                    </button>
                </div>
            @endif
        </form>

        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                کد تایید تا ۵ دقیقه معتبر است
            </p>
        </div>
    </div>
</div>

<script>
function sendCode() {
    // Create a form to send the code request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.2fa.send-code") }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}

function skip2FA() {
    if (confirm('آیا از رد کردن تایید دو مرحله‌ای اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.2fa.skip") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-focus on code input
document.getElementById('code').focus();

// Auto-submit when 4 digits are entered
document.getElementById('code').addEventListener('input', function(e) {
    const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
    e.target.value = value;
    
    if (value.length === 4) {
        // Small delay to show the complete code
        setTimeout(() => {
            e.target.form.submit();
        }, 500);
    }
});

// Prevent form submission if code is not 4 digits
document.querySelector('form').addEventListener('submit', function(e) {
    const code = document.getElementById('code').value;
    if (code.length !== 4) {
        e.preventDefault();
        alert('لطفاً کد ۴ رقمی را وارد کنید');
    }
});
</script>
@endsection
