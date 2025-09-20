class QuizStatistics {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadEpisodePerformance();
        this.loadRecentActivity();
        this.loadAchievements();
        this.bindEvents();
    }

    bindEvents() {
        // You can add event listeners here for interactive elements
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/quiz/statistics', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load statistics');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateStatistics(data.data);
                this.createCharts(data.data);
            } else {
                this.showError('خطا در بارگذاری آمار');
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showError('خطا در بارگذاری آمار');
        }
    }

    async loadEpisodePerformance() {
        try {
            const response = await fetch('/api/quiz/episode-performance', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load episode performance');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateEpisodePerformance(data.data);
            }
        } catch (error) {
            console.error('Error loading episode performance:', error);
        }
    }

    async loadRecentActivity() {
        try {
            const response = await fetch('/api/quiz/recent-activity', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load recent activity');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateRecentActivity(data.data);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }

    async loadAchievements() {
        try {
            const response = await fetch('/api/quiz/achievements', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load achievements');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateAchievements(data.data);
            }
        } catch (error) {
            console.error('Error loading achievements:', error);
        }
    }

    updateStatistics(stats) {
        document.getElementById('total-quizzes').textContent = stats.total_quizzes || 0;
        document.getElementById('total-correct').textContent = stats.total_correct_answers || 0;
        document.getElementById('total-coins').textContent = stats.total_coins_earned || 0;
        document.getElementById('average-score').textContent = `${stats.average_score || 0}%`;
    }

    createCharts(stats) {
        this.createPerformanceChart(stats.performance_data);
        this.createScoreDistributionChart(stats.score_distribution);
    }

    createPerformanceChart(performanceData) {
        const ctx = document.getElementById('performance-chart');
        if (!ctx) return;

        const labels = performanceData?.map(item => item.date) || [];
        const scores = performanceData?.map(item => item.score) || [];

        this.charts.performance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'نمره',
                    data: scores,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    createScoreDistributionChart(distributionData) {
        const ctx = document.getElementById('score-distribution-chart');
        if (!ctx) return;

        const labels = ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'];
        const data = distributionData || [0, 0, 0, 0, 0];

        this.charts.distribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(16, 185, 129)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    updateEpisodePerformance(episodes) {
        const container = document.getElementById('episode-performance');
        
        if (episodes.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ داده‌ای یافت نشد</div>';
            return;
        }

        container.innerHTML = '';
        
        episodes.forEach(episode => {
            const episodeElement = this.createEpisodePerformanceElement(episode);
            container.appendChild(episodeElement);
        });
    }

    createEpisodePerformanceElement(episode) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-gray-50 rounded-lg';
        
        const scorePercentage = (episode.correct_answers / episode.total_questions) * 100;
        const progressPercentage = Math.min(scorePercentage, 100);
        
        div.innerHTML = `
            <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">${episode.title}</h4>
                <div class="mt-2">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>${episode.correct_answers}/${episode.total_questions} پاسخ صحیح</span>
                        <span>${scorePercentage.toFixed(1)}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                    </div>
                </div>
            </div>
            <div class="ml-4 text-right">
                <div class="text-sm font-medium text-yellow-600">${episode.coins_earned || 0}</div>
                <div class="text-xs text-gray-500">سکه</div>
            </div>
        `;
        
        return div;
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');
        
        if (activities.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ فعالیتی یافت نشد</div>';
            return;
        }

        container.innerHTML = '';
        
        activities.forEach(activity => {
            const activityElement = this.createActivityElement(activity);
            container.appendChild(activityElement);
        });
    }

    createActivityElement(activity) {
        const div = document.createElement('div');
        div.className = 'flex items-center p-3 bg-gray-50 rounded-lg';
        
        const iconClass = this.getActivityIconClass(activity.type);
        const scoreClass = this.getScoreClass(activity.score);
        
        div.innerHTML = `
            <div class="w-10 h-10 rounded-full flex items-center justify-center ${iconClass}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h4 class="text-sm font-medium text-gray-900">${activity.title}</h4>
                <p class="text-xs text-gray-500">${this.formatDate(activity.created_at)}</p>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium ${scoreClass}">${activity.score}%</div>
                <div class="text-xs text-gray-500">${activity.coins_earned || 0} سکه</div>
            </div>
        `;
        
        return div;
    }

    updateAchievements(achievements) {
        const container = document.getElementById('achievements');
        
        if (achievements.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ دستاوردی یافت نشد</div>';
            return;
        }

        container.innerHTML = '';
        
        achievements.forEach(achievement => {
            const achievementElement = this.createAchievementElement(achievement);
            container.appendChild(achievementElement);
        });
    }

    createAchievementElement(achievement) {
        const div = document.createElement('div');
        div.className = `achievement-card ${achievement.unlocked ? 'unlocked' : 'locked'}`;
        
        const iconClass = achievement.unlocked ? 'unlocked' : 'locked';
        const progressPercentage = achievement.progress ? (achievement.progress / achievement.requirement) * 100 : 0;
        
        div.innerHTML = `
            <div class="achievement-icon ${iconClass}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 mb-2">${achievement.title}</h3>
            <p class="text-xs text-gray-600 mb-3">${achievement.description}</p>
            ${achievement.unlocked ? 
                '<div class="text-xs text-green-600 font-medium">کسب شده</div>' :
                `<div class="text-xs text-gray-500 mb-2">${achievement.progress || 0}/${achievement.requirement}</div>
                 <div class="progress-bar">
                     <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                 </div>`
            }
        `;
        
        return div;
    }

    getActivityIconClass(type) {
        const classes = {
            'quiz_completed': 'bg-green-100 text-green-600',
            'quiz_started': 'bg-blue-100 text-blue-600',
            'achievement_unlocked': 'bg-yellow-100 text-yellow-600'
        };
        return classes[type] || 'bg-gray-100 text-gray-600';
    }

    getScoreClass(score) {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-blue-600';
        if (score >= 40) return 'text-yellow-600';
        return 'text-red-600';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fa-IR');
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new QuizStatistics();
});
