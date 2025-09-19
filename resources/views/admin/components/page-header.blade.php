{{-- Standardized Page Header Component --}}
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="flex items-center">
                    @if(isset($icon))
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $iconBg ?? 'bg-gray-100' }} {{ $iconColor ?? 'text-gray-600' }} ml-4">
                        {!! $icon !!}
                    </div>
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
                        @if(isset($subtitle))
                        <p class="text-sm text-gray-600 mt-1">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-3 space-x-reverse">
                @if(isset($actions))
                    {!! $actions !!}
                @endif
                
                @if(isset($breadcrumbs))
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 space-x-reverse">
                        @foreach($breadcrumbs as $index => $breadcrumb)
                        <li class="flex items-center">
                            @if($index > 0)
                            <svg class="w-4 h-4 text-gray-400 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            @endif
                            @if(isset($breadcrumb['url']))
                            <a href="{{ $breadcrumb['url'] }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                                {{ $breadcrumb['title'] }}
                            </a>
                            @else
                            <span class="text-sm font-medium text-gray-900">{{ $breadcrumb['title'] }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </nav>
                @endif
            </div>
        </div>
    </div>
</div>
