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
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400" id="status-message">
                برای ادامه، ابتدا کد تایید را ارسال کنید
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

        <!-- Send Code Button (Initially Visible) -->
        <div id="send-code-section" class="mt-8">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    کد تایید به شماره {{ $user->phone_number }} ارسال خواهد شد
                </p>
                <button type="button" onclick="sendCode()" id="send-code-btn" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-200">
                    <span id="send-code-text">ارسال کد تایید</span>
                    <svg id="send-code-spinner" class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Verification Form (Initially Hidden) -->
        <form id="verification-form" class="mt-8 space-y-6 hidden" action="{{ route('admin.2fa.verify') }}" method="POST">
            @csrf
            
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    کد تایید ۶ رقمی
                </label>
                <input id="code" name="code" type="text" required maxlength="6" 
                       class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm text-center text-lg tracking-widest @error('code') border-red-500 @enderror"
                       placeholder="000000" autocomplete="off">
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
let codeSent = false;

function sendCode() {
    if (codeSent) {
        // Resend code
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
        return;
    }

    // First time sending code
    const sendBtn = document.getElementById('send-code-btn');
    const sendText = document.getElementById('send-code-text');
    const spinner = document.getElementById('send-code-spinner');
    const statusMessage = document.getElementById('status-message');
    
    // Show loading state
    sendBtn.disabled = true;
    sendText.textContent = 'در حال ارسال...';
    spinner.classList.remove('hidden');
    
    // Create form to send the code request
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

// Check if code was already sent (from session)
@if(session('success'))
    codeSent = true;
    document.getElementById('send-code-section').classList.add('hidden');
    document.getElementById('verification-form').classList.remove('hidden');
    document.getElementById('status-message').textContent = 'کد تایید به شماره {{ $user->phone_number }} ارسال شد';
    document.getElementById('code').focus();
@endif

// Auto-focus on code input when form is shown
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    if (codeInput && !codeInput.closest('form').classList.contains('hidden')) {
        codeInput.focus();
    }
});

// Auto-submit when 6 digits are entered
document.getElementById('code').addEventListener('input', function(e) {
    const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
    e.target.value = value;
    
    if (value.length === 6) {
        // Small delay to show the complete code
        setTimeout(() => {
            e.target.form.submit();
        }, 500);
    }
});

// Prevent form submission if code is not 6 digits
document.getElementById('verification-form').addEventListener('submit', function(e) {
    const code = document.getElementById('code').value;
    if (code.length !== 6) {
        e.preventDefault();
        alert('لطفاً کد ۶ رقمی را وارد کنید');
    }
});
</script>
@endsection
