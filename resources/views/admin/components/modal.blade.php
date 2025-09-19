{{-- Standardized Modal Component --}}
<div id="{{ $id }}" class="fixed inset-0 z-50 overflow-y-auto {{ $show ? '' : 'hidden' }}" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('{{ $id }}')"></div>

        {{-- This element is to trick the browser into centering the modal contents. --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal panel --}}
        <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {{ $size ?? 'sm:max-w-lg' }} sm:w-full">
            {{-- Modal header --}}
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    @if(isset($icon))
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $iconBg ?? 'bg-red-100' }} sm:mx-0 sm:h-10 sm:w-10">
                        {!! $icon !!}
                    </div>
                    @endif
                    <div class="mt-3 text-center sm:mt-0 {{ isset($icon) ? 'sm:mr-4' : '' }} sm:text-right">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ $title }}
                        </h3>
                        @if(isset($subtitle))
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Modal body --}}
            <div class="bg-white px-4 pb-4 sm:p-6 sm:pt-0">
                {!! $content !!}
            </div>

            {{-- Modal footer --}}
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                @if(isset($actions))
                    {!! $actions !!}
                @else
                <button type="button" onclick="closeModal('{{ $id }}')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    تایید
                </button>
                <button type="button" onclick="closeModal('{{ $id }}')" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    انصراف
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('[id$="-modal"]');
        modals.forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                closeModal(modal.id);
            }
        });
    }
});
</script>
