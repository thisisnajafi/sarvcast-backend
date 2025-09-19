/**
 * Voice Actor Management JavaScript Component
 * Handles voice actor timeline, form validation, and interactions
 */

class VoiceActorManager {
    constructor(options = {}) {
        this.episodeId = options.episodeId;
        this.episodeDuration = options.episodeDuration || 3600;
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Person selection change
        const personSelect = document.getElementById('person_id');
        if (personSelect) {
            personSelect.addEventListener('change', (e) => this.handlePersonSelection(e));
        }

        // Time inputs change
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        
        if (startTimeInput) {
            startTimeInput.addEventListener('input', () => this.updateTimeDisplay());
        }
        
        if (endTimeInput) {
            endTimeInput.addEventListener('input', () => this.updateTimeDisplay());
        }

        // Role change
        const roleSelect = document.getElementById('role');
        if (roleSelect) {
            roleSelect.addEventListener('change', (e) => this.handleRoleChange(e));
        }

        // Form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => this.validateForm(e));
        }

        // Bulk actions
        this.bindBulkActions();
    }

    initializeComponents() {
        this.updateTimeDisplay();
        this.loadVoiceActors();
        this.loadTimeline();
    }

    handlePersonSelection(event) {
        const selectedOption = event.target.options[event.target.selectedIndex];
        if (selectedOption.value) {
            this.showPersonInfo(selectedOption);
        } else {
            this.hidePersonInfo();
        }
    }

    showPersonInfo(option) {
        const personName = option.textContent.trim();
        const personBio = option.dataset.bio || 'بدون توضیحات';
        const personImage = option.dataset.image || '/images/default-avatar.png';
        const personRoles = JSON.parse(option.dataset.roles || '[]');

        const personInfoElement = document.getElementById('person-info');
        if (personInfoElement) {
            document.getElementById('person-name').textContent = personName;
            document.getElementById('person-bio').textContent = personBio;
            document.getElementById('person-image').src = personImage;
            
            const rolesContainer = document.getElementById('person-roles');
            if (rolesContainer) {
                rolesContainer.innerHTML = personRoles.map(role => 
                    `<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">${role}</span>`
                ).join('');
            }

            personInfoElement.classList.remove('hidden');
        }
    }

    hidePersonInfo() {
        const personInfoElement = document.getElementById('person-info');
        if (personInfoElement) {
            personInfoElement.classList.add('hidden');
        }
    }

    updateTimeDisplay() {
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        
        if (!startTimeInput || !endTimeInput) return;

        const startTime = parseInt(startTimeInput.value) || 0;
        const endTime = parseInt(endTimeInput.value) || 0;
        
        const startTimeDisplay = document.getElementById('start-time-display');
        const endTimeDisplay = document.getElementById('end-time-display');
        const durationDisplay = document.getElementById('duration-display');
        
        if (startTimeDisplay) startTimeDisplay.textContent = this.formatTime(startTime);
        if (endTimeDisplay) endTimeDisplay.textContent = this.formatTime(endTime);
        
        const duration = Math.max(0, endTime - startTime);
        if (durationDisplay) durationDisplay.textContent = `${duration} ثانیه`;
        
        this.validateTimeRange();
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    validateTimeRange() {
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        
        if (!startTimeInput || !endTimeInput) return true;

        const startTime = parseInt(startTimeInput.value) || 0;
        const endTime = parseInt(endTimeInput.value) || 0;
        
        let isValid = true;
        
        if (startTime >= endTime) {
            startTimeInput.classList.add('border-red-500');
            endTimeInput.classList.add('border-red-500');
            isValid = false;
        } else {
            startTimeInput.classList.remove('border-red-500');
            endTimeInput.classList.remove('border-red-500');
        }
        
        if (endTime > this.episodeDuration) {
            endTimeInput.classList.add('border-red-500');
            isValid = false;
        } else {
            endTimeInput.classList.remove('border-red-500');
        }
        
        return isValid;
    }

    handleRoleChange(event) {
        const characterNameInput = document.getElementById('character_name');
        const role = event.target.value;
        
        if (characterNameInput) {
            if (role === 'character' && !characterNameInput.value) {
                characterNameInput.placeholder = 'نام شخصیت (مثال: شاهزاده، جنگجو، جادوگر)';
            } else if (role === 'narrator') {
                characterNameInput.placeholder = 'نام شخصیت (اختیاری)';
                if (!characterNameInput.value) {
                    characterNameInput.value = '';
                }
            }
        }
    }

    validateForm(event) {
        if (!this.validateTimeRange()) {
            event.preventDefault();
            this.showAlert('زمان شروع باید کمتر از زمان پایان باشد', 'error');
            return false;
        }
        
        return true;
    }

    bindBulkActions() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => this.handleSelectAll(e));
        }

        // Bulk action button
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        if (bulkActionBtn) {
            bulkActionBtn.addEventListener('click', () => this.showBulkActionModal());
        }

        // Bulk action modal events
        const cancelBulkAction = document.getElementById('cancel-bulk-action');
        if (cancelBulkAction) {
            cancelBulkAction.addEventListener('click', () => this.hideBulkActionModal());
        }

        const setPrimaryBtn = document.getElementById('set-primary-btn');
        if (setPrimaryBtn) {
            setPrimaryBtn.addEventListener('click', () => this.setPrimaryVoiceActor());
        }

        const deleteSelectedBtn = document.getElementById('delete-selected-btn');
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', () => this.deleteSelectedVoiceActors());
        }
    }

    handleSelectAll(event) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-voice-actor-id]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
        this.updateBulkActionButton();
    }

    updateBulkActionButton() {
        const selectedIds = this.getSelectedVoiceActorIds();
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        if (bulkActionBtn) {
            bulkActionBtn.disabled = selectedIds.length === 0;
        }
    }

    getSelectedVoiceActorIds() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-voice-actor-id]:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.dataset.voiceActorId);
    }

    showBulkActionModal() {
        const modal = document.getElementById('bulk-action-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    hideBulkActionModal() {
        const modal = document.getElementById('bulk-action-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    setPrimaryVoiceActor() {
        const selectedIds = this.getSelectedVoiceActorIds();
        if (selectedIds.length > 0) {
            this.performBulkAction('update_primary', selectedIds.slice(0, 1));
        }
    }

    deleteSelectedVoiceActors() {
        const selectedIds = this.getSelectedVoiceActorIds();
        if (selectedIds.length > 0) {
            if (confirm(`آیا از حذف ${selectedIds.length} صداپیشه اطمینان دارید؟`)) {
                this.performBulkAction('delete', selectedIds);
            }
        }
    }

    async performBulkAction(action, voiceActorIds) {
        try {
            const response = await fetch(`/admin/api/episodes/${this.episodeId}/voice-actors/bulk-action`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    action: action,
                    voice_actor_ids: voiceActorIds
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.loadVoiceActors();
                this.hideBulkActionModal();
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Error performing bulk action:', error);
            this.showAlert('خطا در انجام عملیات', 'error');
        }
    }

    async loadVoiceActors() {
        try {
            const response = await fetch(`/admin/api/episodes/${this.episodeId}/voice-actors/data`);
            const data = await response.json();
            
            if (data.success) {
                this.renderVoiceActorsTable(data.data.voice_actors);
                this.updateStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading voice actors:', error);
        }
    }

    async loadTimeline() {
        try {
            const response = await fetch(`/admin/api/episodes/${this.episodeId}/voice-actors/statistics`);
            const data = await response.json();
            
            if (data.success) {
                this.renderTimeline(data.data.voice_actor_timeline);
            }
        } catch (error) {
            console.error('Error loading timeline:', error);
        }
    }

    renderVoiceActorsTable(voiceActors) {
        const tbody = document.getElementById('voice-actors-table-body');
        if (!tbody) return;
        
        if (voiceActors.length === 0) {
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

        tbody.innerHTML = voiceActors.map(voiceActor => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" 
                           data-voice-actor-id="${voiceActor.id}" 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                           onchange="voiceActorManager.updateBulkActionButton()">
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
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${this.getRoleBadgeClass(voiceActor.role)}">
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
                        <a href="/admin/episodes/${this.episodeId}/voice-actors/${voiceActor.id}/edit" 
                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                            ویرایش
                        </a>
                        <button onclick="voiceActorManager.deleteVoiceActor(${voiceActor.id})" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            حذف
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderTimeline(timelineData) {
        const timelineContainer = document.getElementById('voice-actor-timeline');
        if (!timelineContainer) return;
        
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

    updateStatistics(data) {
        const totalElement = document.getElementById('total-voice-actors');
        const primaryElement = document.getElementById('primary-voice-actor');
        
        if (totalElement) {
            totalElement.textContent = data.voice_actors.length;
        }
        
        if (primaryElement) {
            const primaryVoiceActor = data.voice_actors.find(va => va.is_primary);
            primaryElement.textContent = primaryVoiceActor ? primaryVoiceActor.person.name : 'تعیین نشده';
        }
    }

    getRoleBadgeClass(role) {
        const classes = {
            'narrator': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'character': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'voice_over': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'background': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
        };
        return classes[role] || classes['background'];
    }

    async deleteVoiceActor(voiceActorId) {
        if (confirm('آیا از حذف این صداپیشه اطمینان دارید؟')) {
            try {
                const response = await fetch(`/admin/episodes/${this.episodeId}/voice-actors/${voiceActorId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showAlert(data.message, 'success');
                    this.loadVoiceActors();
                } else {
                    this.showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting voice actor:', error);
                this.showAlert('خطا در حذف صداپیشه', 'error');
            }
        }
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        alert.textContent = message;
        
        document.body.appendChild(alert);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}

// Global instance
let voiceActorManager;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const episodeIdElement = document.querySelector('[data-episode-id]');
    const episodeDurationElement = document.querySelector('[data-episode-duration]');
    
    if (episodeIdElement) {
        voiceActorManager = new VoiceActorManager({
            episodeId: episodeIdElement.dataset.episodeId,
            episodeDuration: episodeDurationElement?.dataset.episodeDuration || 3600
        });
    }
});

// Export for global access
window.VoiceActorManager = VoiceActorManager;
window.voiceActorManager = voiceActorManager;
