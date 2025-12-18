/**
 * Gamification Manager JavaScript
 * Handles gamification features in admin panel
 */

class GamificationManager extends AdminManager {
    constructor() {
        super();
        this.initGamification();
    }

    initGamification() {
        this.setupGamificationEventListeners();
        this.loadGamificationData();
    }

    setupGamificationEventListeners() {
        // Achievement management
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('create-achievement-btn')) {
                e.preventDefault();
                this.showCreateAchievementModal();
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-achievement-btn')) {
                e.preventDefault();
                this.editAchievement(e.target.dataset.achievementId);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-achievement-btn')) {
                e.preventDefault();
                this.deleteAchievement(e.target.dataset.achievementId);
            }
        });

        // Badge management
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('create-badge-btn')) {
                e.preventDefault();
                this.showCreateBadgeModal();
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-badge-btn')) {
                e.preventDefault();
                this.editBadge(e.target.dataset.badgeId);
            }
        });

        // Leaderboard management
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('refresh-leaderboard-btn')) {
                e.preventDefault();
                this.refreshLeaderboard();
            }
        });

        // Gamification settings
        const settingsForm = document.getElementById('gamification-settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveGamificationSettings();
            });
        }
    }

    loadGamificationData() {
        this.loadAchievements();
        this.loadBadges();
        this.loadLeaderboard();
        this.loadSettings();
    }

    loadAchievements() {
        fetch('/admin/gamification/achievements', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayAchievements(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading achievements:', error);
        });
    }

    loadBadges() {
        fetch('/admin/gamification/badges', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayBadges(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading badges:', error);
        });
    }

    loadLeaderboard() {
        fetch('/admin/gamification/leaderboard', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayLeaderboard(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading leaderboard:', error);
        });
    }

    loadSettings() {
        fetch('/admin/gamification/settings', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.populateSettingsForm(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading settings:', error);
        });
    }

    displayAchievements(achievements) {
        const container = document.getElementById('achievements-container');
        if (!container) return;

        if (achievements.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-trophy text-4xl mb-4"></i>
                    <p>هیچ دستاوردی تعریف نشده است</p>
                </div>
            `;
            return;
        }

        const html = achievements.map(achievement => this.createAchievementRow(achievement)).join('');
        container.innerHTML = html;
    }

    createAchievementRow(achievement) {
        const statusClass = achievement.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const statusText = achievement.is_active ? 'فعال' : 'غیرفعال';

        return `
            <div class="achievement-row bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="achievement-icon">
                            <i class="fas fa-trophy text-2xl text-yellow-500"></i>
                        </div>
                        <div class="achievement-info">
                            <h3 class="font-semibold text-gray-800">${achievement.name}</h3>
                            <p class="text-sm text-gray-600">${achievement.description}</p>
                            <div class="flex items-center space-x-4 space-x-reverse mt-2">
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-coins mr-1"></i>
                                    پاداش: ${achievement.reward_coins} سکه
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    ${achievement.unlocked_count} نفر
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="achievement-actions flex items-center space-x-2 space-x-reverse">
                        <span class="status-badge ${statusClass} px-2 py-1 rounded-full text-xs">
                            ${statusText}
                        </span>
                        <button class="edit-achievement-btn text-blue-600 hover:text-blue-800" 
                                data-achievement-id="${achievement.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-achievement-btn text-red-600 hover:text-red-800" 
                                data-achievement-id="${achievement.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    displayBadges(badges) {
        const container = document.getElementById('badges-container');
        if (!container) return;

        if (badges.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-medal text-4xl mb-4"></i>
                    <p>هیچ نشانی تعریف نشده است</p>
                </div>
            `;
            return;
        }

        const html = badges.map(badge => this.createBadgeRow(badge)).join('');
        container.innerHTML = html;
    }

    createBadgeRow(badge) {
        const statusClass = badge.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const statusText = badge.is_active ? 'فعال' : 'غیرفعال';

        return `
            <div class="badge-row bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="badge-icon">
                            <i class="fas fa-medal text-2xl text-blue-500"></i>
                        </div>
                        <div class="badge-info">
                            <h3 class="font-semibold text-gray-800">${badge.name}</h3>
                            <p class="text-sm text-gray-600">${badge.description}</p>
                            <div class="flex items-center space-x-4 space-x-reverse mt-2">
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    ${badge.earned_count} نفر
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-star mr-1"></i>
                                    سطح: ${badge.level}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="badge-actions flex items-center space-x-2 space-x-reverse">
                        <span class="status-badge ${statusClass} px-2 py-1 rounded-full text-xs">
                            ${statusText}
                        </span>
                        <button class="edit-badge-btn text-blue-600 hover:text-blue-800" 
                                data-badge-id="${badge.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    displayLeaderboard(leaderboard) {
        const container = document.getElementById('leaderboard-container');
        if (!container) return;

        if (leaderboard.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-trophy text-4xl mb-4"></i>
                    <p>هیچ داده‌ای برای نمایش وجود ندارد</p>
                </div>
            `;
            return;
        }

        const html = leaderboard.map((user, index) => this.createLeaderboardRow(user, index + 1)).join('');
        container.innerHTML = html;
    }

    createLeaderboardRow(user, position) {
        const positionClass = position <= 3 ? 'text-yellow-500' : 'text-gray-600';
        const positionIcon = position === 1 ? 'fa-crown' : position === 2 ? 'fa-medal' : position === 3 ? 'fa-award' : 'fa-hashtag';

        return `
            <div class="leaderboard-row bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="position">
                            <i class="fas ${positionIcon} ${positionClass} text-xl"></i>
                        </div>
                        <div class="user-info">
                            <h3 class="font-semibold text-gray-800">${user.name}</h3>
                            <p class="text-sm text-gray-600">${user.email}</p>
                        </div>
                    </div>
                    <div class="user-stats flex items-center space-x-6 space-x-reverse">
                        <div class="stat-item text-center">
                            <div class="stat-value font-bold text-blue-600">${user.total_coins}</div>
                            <div class="stat-label text-xs text-gray-500">سکه</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="stat-value font-bold text-green-600">${user.achievements_count}</div>
                            <div class="stat-label text-xs text-gray-500">دستاورد</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="stat-value font-bold text-purple-600">${user.badges_count}</div>
                            <div class="stat-label text-xs text-gray-500">نشان</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    showCreateAchievementModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">ایجاد دستاورد جدید</h3>
                    <button class="close-modal text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="create-achievement-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نام دستاورد:</label>
                        <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات:</label>
                        <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">پاداش سکه:</label>
                        <input type="number" name="reward_coins" class="w-full border border-gray-300 rounded-lg px-3 py-2" min="0" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع دستاورد:</label>
                        <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="listening_time">زمان گوش دادن</option>
                            <option value="episodes_completed">تکمیل اپیزود</option>
                            <option value="quiz_completed">تکمیل کویز</option>
                            <option value="referral">معرفی کاربر</option>
                            <option value="streak">استمرار</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">شرط دستیابی:</label>
                        <input type="number" name="condition_value" class="w-full border border-gray-300 rounded-lg px-3 py-2" min="1" required>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" class="mr-2" checked>
                        <label for="is_active" class="text-sm text-gray-700">فعال</label>
                    </div>
                </form>
                
                <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
                    <button class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        انصراف
                    </button>
                    <button id="save-achievement" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        ایجاد
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle form submission
        modal.querySelector('#save-achievement').addEventListener('click', () => {
            this.createAchievement(modal);
        });

        // Close modal functionality
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    createAchievement(modal) {
        const formData = new FormData(modal.querySelector('#create-achievement-form'));
        const data = Object.fromEntries(formData.entries());
        data.is_active = modal.querySelector('#is_active').checked;

        fetch('/admin/gamification/achievements', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showNotification('دستاورد با موفقیت ایجاد شد', 'success');
                document.body.removeChild(modal);
                this.loadAchievements();
            } else {
                this.showNotification(result.message || 'خطا در ایجاد دستاورد', 'error');
            }
        })
        .catch(error => {
            console.error('Error creating achievement:', error);
            this.showNotification('خطا در ایجاد دستاورد', 'error');
        });
    }

    editAchievement(achievementId) {
        // Implementation for editing achievement
        this.showNotification('ویرایش دستاورد در حال توسعه...', 'info');
    }

    deleteAchievement(achievementId) {
        if (confirm('آیا از حذف این دستاورد اطمینان دارید؟')) {
            fetch(`/admin/gamification/achievements/${achievementId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.showNotification('دستاورد با موفقیت حذف شد', 'success');
                    this.loadAchievements();
                } else {
                    this.showNotification(result.message || 'خطا در حذف دستاورد', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting achievement:', error);
                this.showNotification('خطا در حذف دستاورد', 'error');
            });
        }
    }

    showCreateBadgeModal() {
        // Implementation for creating badge
        this.showNotification('ایجاد نشان در حال توسعه...', 'info');
    }

    editBadge(badgeId) {
        // Implementation for editing badge
        this.showNotification('ویرایش نشان در حال توسعه...', 'info');
    }

    refreshLeaderboard() {
        this.showLoading();
        this.loadLeaderboard();
        this.hideLoading();
        this.showNotification('جدول امتیازات به‌روزرسانی شد', 'success');
    }

    saveGamificationSettings() {
        const formData = new FormData(document.getElementById('gamification-settings-form'));
        const data = Object.fromEntries(formData.entries());

        fetch('/admin/gamification/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showNotification('تنظیمات با موفقیت ذخیره شد', 'success');
            } else {
                this.showNotification(result.message || 'خطا در ذخیره تنظیمات', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving settings:', error);
            this.showNotification('خطا در ذخیره تنظیمات', 'error');
        });
    }

    populateSettingsForm(settings) {
        Object.keys(settings).forEach(key => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = settings[key];
                } else {
                    input.value = settings[key];
                }
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new GamificationManager();
});
