{{-- Voice Actor Timeline Partial --}}
<div class="voice-actor-timeline-container">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">تایم‌لاین صداپیشگان</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">نمایش زمانی صداپیشگان در قسمت</p>
        </div>
        <div class="p-6">
            <div id="voice-actor-timeline" class="relative">
                @if(isset($voiceActors) && $voiceActors->count() > 0)
                    @php
                        $maxDuration = $voiceActors->max('end_time');
                        $scale = 100 / $maxDuration; // Percentage scale
                    @endphp
                    <div class="space-y-4">
                        @foreach($voiceActors->sortBy('start_time') as $voiceActor)
                            <div class="flex items-center">
                                <div class="w-32 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $voiceActor->person->name }}
                                </div>
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-8 relative">
                                    <div class="absolute top-0 right-0 h-full bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                         style="width: {{ ($voiceActor->end_time - $voiceActor->start_time) * $scale }}%; transform: translateX({{ $voiceActor->start_time * $scale }}%);">
                                        {{ $voiceActor->character_name ?: $voiceActor->role }}
                                    </div>
                                </div>
                                <div class="w-20 text-xs text-gray-500 dark:text-gray-400 text-right">
                                    {{ gmdate('i:s', $voiceActor->start_time) }} - {{ gmdate('i:s', $voiceActor->end_time) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <p>هیچ صداپیشه‌ای تعریف نشده است</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
