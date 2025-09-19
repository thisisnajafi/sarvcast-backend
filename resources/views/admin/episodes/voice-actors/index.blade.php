@extends('admin.layouts.app')

@section('title', 'مدیریت صداپیشگان - ' . $episode->title)

@section('content')
<div class="p-6" data-episode-id="{{ $episode->id }}" data-episode-duration="{{ $episode->duration }}">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                مدیریت صداپیشگان
            </h1>
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    {{ $episode->title }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ gmdate('i:s', $episode->duration) }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    {{ $episode->voice_actor_count ?? 0 }} صداپیشه
                </span>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.episodes.show', $episode) }}" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                بازگشت به قسمت
            </a>
            <a href="{{ route('admin.episodes.voice-actors.create', $episode) }}" 
               class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                افزودن صداپیشه
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">کل صداپیشگان</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-voice-actors">{{ $episode->voice_actor_count ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">صداپیشه اصلی</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="primary-voice-actor">
                        @if($episode->primaryVoiceActor)
                            {{ $episode->primaryVoiceActor->person->name }}
                        @else
                            تعیین نشده
                        @endif
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">مدت زمان کل</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ gmdate('i:s', $episode->duration) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">وضعیت</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        @if($episode->has_multiple_voice_actors)
                            چندگانه
                        @else
                            تک
                        @endif
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Voice Actors Timeline -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">تایم‌لاین صداپیشگان</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">نمایش زمانی صداپیشگان در قسمت</p>
        </div>
        <div class="p-6">
            <div id="voice-actor-timeline" class="relative">
                <!-- Timeline will be loaded here via JavaScript -->
                <div class="flex items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>در حال بارگذاری تایم‌لاین...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Voice Actors List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">لیست صداپیشگان</h2>
                <div class="flex items-center space-x-3">
                    <button id="bulk-action-btn" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        عملیات گروهی
                    </button>
                    <button id="refresh-btn" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">صداپیشه</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نقش</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">شخصیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">زمان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مدت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody id="voice-actors-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Voice actors will be loaded here via JavaScript -->
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex items-center justify-center">
                                <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                در حال بارگذاری صداپیشگان...
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div id="bulk-action-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">عملیات گروهی</h3>
                <div class="space-y-4">
                    <button id="set-primary-btn" 
                            class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200">
                        تعیین صداپیشه اصلی
                    </button>
                    <button id="delete-selected-btn" 
                            class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200">
                        حذف انتخاب شده‌ها
                    </button>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancel-bulk-action" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200">
                        انصراف
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأیید حذف</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">آیا از حذف این صداپیشه اطمینان دارید؟</p>
                <div class="flex justify-end space-x-3">
                    <button id="cancel-delete" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200">
                        انصراف
                    </button>
                    <button id="confirm-delete" 
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200">
                        حذف
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const episodeId = {{ $episode->id }};
    let selectedVoiceActors = [];
    let voiceActorsData = [];

    // Load voice actors data
    loadVoiceActors();
    loadTimeline();

    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        loadVoiceActors();
        loadTimeline();
    });

    // Select all checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-voice-actor-id]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionButton();
    });

    // Bulk action button
    document.getElementById('bulk-action-btn').addEventListener('click', function() {
        document.getElementById('bulk-action-modal').classList.remove('hidden');
    });

    // Cancel bulk action
    document.getElementById('cancel-bulk-action').addEventListener('click', function() {
        document.getElementById('bulk-action-modal').classList.add('hidden');
    });

    // Set primary voice actor
    document.getElementById('set-primary-btn').addEventListener('click', function() {
        const selectedIds = getSelectedVoiceActorIds();
        if (selectedIds.length > 0) {
            setPrimaryVoiceActor(selectedIds[0]);
        }
    });

    // Delete selected voice actors
    document.getElementById('delete-selected-btn').addEventListener('click', function() {
        const selectedIds = getSelectedVoiceActorIds();
        if (selectedIds.length > 0) {
            deleteVoiceActors(selectedIds);
        }
    });

    function loadVoiceActors() {
        fetch(`/admin/api/episodes/${episodeId}/voice-actors/data`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    voiceActorsData = data.data.voice_actors;
                    renderVoiceActorsTable();
                    updateStatistics();
                }
            })
            .catch(error => {
                console.error('Error loading voice actors:', error);
            });
    }

    function loadTimeline() {
        fetch(`/admin/api/episodes/${episodeId}/voice-actors/statistics`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTimeline(data.data.voice_actor_timeline);
                }
            })
            .catch(error => {
                console.error('Error loading timeline:', error);
            });
    }

    function renderVoiceActorsTable() {
        const tbody = document.getElementById('voice-actors-table-body');
        
        if (voiceActorsData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex items-center justify-center">
                            <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            هیچ صداپیشه‌ای تعریف نشده است
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = voiceActorsData.map(voiceActor => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" 
                           data-voice-actor-id="${voiceActor.id}" 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                           onchange="updateBulkActionButton()">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center ml-3">
                            <img src="${voiceActor.person.image_url || '/images/default-avatar.png'}" 
                                 alt="${voiceActor.person.name}" 
                                 class="w-10 h-10 rounded-full object-cover"
                                 onerror="this.src='/images/default-avatar.png'">
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">${voiceActor.person.name}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">${voiceActor.person.bio || 'بدون توضیحات'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${getRoleBadgeClass(voiceActor.role)}">
                        ${voiceActor.role}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${voiceActor.character_name || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${voiceActor.start_time_formatted} - ${voiceActor.end_time_formatted}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${voiceActor.duration} ثانیه
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${voiceActor.is_primary ? 
                        '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">اصلی</span>' : 
                        '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">عادی</span>'
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-2">
                        <a href="/admin/episodes/${episodeId}/voice-actors/${voiceActor.id}/edit" 
                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                            ویرایش
                        </a>
                        <button onclick="deleteVoiceActor(${voiceActor.id})" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            حذف
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderTimeline(timelineData) {
        const timelineContainer = document.getElementById('voice-actor-timeline');
        
        if (!timelineData || timelineData.length === 0) {
            timelineContainer.innerHTML = `
                <div class="flex items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p>هیچ صداپیشه‌ای تعریف نشده است</p>
                    </div>
                </div>
            `;
            return;
        }

        const maxDuration = Math.max(...timelineData.map(item => item.end_time));
        const scale = 100 / maxDuration; // Percentage scale

        timelineContainer.innerHTML = `
            <div class="space-y-4">
                ${timelineData.map(item => `
                    <div class="flex items-center">
                        <div class="w-32 text-sm font-medium text-gray-700 dark:text-gray-300">
                            ${item.person_name}
                        </div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-8 relative">
                            <div class="absolute top-0 right-0 h-full bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                 style="width: ${(item.end_time - item.start_time) * scale}%; transform: translateX(${item.start_time * scale}%);">
                                ${item.character_name || item.role}
                            </div>
                        </div>
                        <div class="w-20 text-xs text-gray-500 dark:text-gray-400 text-left">
                            ${item.start_time_formatted} - ${item.end_time_formatted}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function updateStatistics() {
        document.getElementById('total-voice-actors').textContent = voiceActorsData.length;
        
        const primaryVoiceActor = voiceActorsData.find(va => va.is_primary);
        document.getElementById('primary-voice-actor').textContent = 
            primaryVoiceActor ? primaryVoiceActor.person.name : 'تعیین نشده';
    }

    function getRoleBadgeClass(role) {
        const classes = {
            'narrator': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'character': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'voice_over': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'background': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
        };
        return classes[role] || classes['background'];
    }

    function updateBulkActionButton() {
        const selectedIds = getSelectedVoiceActorIds();
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        bulkActionBtn.disabled = selectedIds.length === 0;
    }

    function getSelectedVoiceActorIds() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-voice-actor-id]:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.dataset.voiceActorId);
    }

    function setPrimaryVoiceActor(voiceActorId) {
        fetch(`/admin/api/episodes/${episodeId}/voice-actors/bulk-action`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: 'update_primary',
                voice_actor_ids: [voiceActorId]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadVoiceActors();
                document.getElementById('bulk-action-modal').classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error setting primary voice actor:', error);
        });
    }

    function deleteVoiceActors(voiceActorIds) {
        // Implementation for bulk delete
        console.log('Deleting voice actors:', voiceActorIds);
    }

    // Global functions for inline event handlers
    window.deleteVoiceActor = function(voiceActorId) {
        document.getElementById('delete-modal').classList.remove('hidden');
        document.getElementById('confirm-delete').onclick = function() {
            fetch(`/admin/episodes/${episodeId}/voice-actors/${voiceActorId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadVoiceActors();
                    document.getElementById('delete-modal').classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error deleting voice actor:', error);
            });
        };
    };

    // Cancel delete
    document.getElementById('cancel-delete').addEventListener('click', function() {
        document.getElementById('delete-modal').classList.add('hidden');
    });
});
</script>
@endpush
