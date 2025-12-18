{{-- Standardized Loading Spinner Component --}}
<div id="{{ $id ?? 'loading-spinner' }}" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-75 {{ $show ?? false ? '' : 'hidden' }}">
    <div class="flex flex-col items-center">
        {{-- Spinner --}}
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        
        {{-- Loading Text --}}
        @if(isset($text))
        <p class="mt-4 text-sm font-medium text-gray-700">{{ $text }}</p>
        @endif
        
        {{-- Progress Bar (if applicable) --}}
        @if(isset($progress))
        <div class="mt-4 w-64 bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ $progress }}% تکمیل شده</p>
        @endif
    </div>
</div>

<script>
function showLoading(spinnerId = 'loading-spinner', text = 'در حال بارگذاری...') {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.classList.remove('hidden');
        const textElement = spinner.querySelector('p');
        if (textElement) {
            textElement.textContent = text;
        }
    }
}

function hideLoading(spinnerId = 'loading-spinner') {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.classList.add('hidden');
    }
}

function updateProgress(spinnerId = 'loading-spinner', progress) {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        const progressBar = spinner.querySelector('.bg-blue-600');
        const progressText = spinner.querySelector('.text-xs');
        
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        
        if (progressText) {
            progressText.textContent = progress + '% تکمیل شده';
        }
    }
}
</script>
