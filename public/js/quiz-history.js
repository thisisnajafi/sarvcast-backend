class QuizHistory {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.filters = {
            episodeId: '',
            startDate: '',
            endDate: ''
        };
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadQuizHistory();
        this.loadEpisodes();
        this.bindEvents();
    }

    bindEvents() {
        // Apply filters
        document.getElementById('apply-filters')?.addEventListener('click', () => {
            this.applyFilters();
        });

        // Pagination
        document.getElementById('prev-page')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadQuizHistory();
            }
        });

        document.getElementById('next-page')?.addEventListener('click', () => {
            this.currentPage++;
            this.loadQuizHistory();
        });

        // Filter inputs
        document.getElementById('episode-filter')?.addEventListener('change', (e) => {
            this.filters.episodeId = e.target.value;
        });

        document.getElementById('start-date-filter')?.addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
        });

        document.getElementById('end-date-filter')?.addEventListener('change', (e) => {
            this.filters.endDate = e.target.value;
        });

        // Close modal
        document.getElementById('close-detail-modal')?.addEventListener('click', () => {
            this.closeDetailModal();
        });

        // Close modal on outside click
        document.getElementById('quiz-detail-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'quiz-detail-modal') {
                this.closeDetailModal();
            }
        });
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
            } else {
                this.showError('خطا در بارگذاری آمار');
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showError('خطا در بارگذاری آمار');
        }
    }

    async loadQuizHistory() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading('quiz-history-table-body');

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...this.filters
            });

            const response = await fetch(`/api/quiz/history?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load quiz history');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateQuizHistoryTable(data.data.quizzes);
                this.updatePaginationInfo(data.data);
            } else {
                this.showError('خطا در بارگذاری تاریخچه آزمون‌ها');
            }
        } catch (error) {
            console.error('Error loading quiz history:', error);
            this.showError('خطا در بارگذاری تاریخچه آزمون‌ها');
        } finally {
            this.loading = false;
            this.hideLoading('quiz-history-table-body');
        }
    }

    async loadEpisodes() {
        try {
            const response = await fetch('/api/episodes', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load episodes');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateEpisodeFilter(data.data);
            }
        } catch (error) {
            console.error('Error loading episodes:', error);
        }
    }

    applyFilters() {
        this.currentPage = 1;
        this.loadQuizHistory();
    }

    updateStatistics(stats) {
        document.getElementById('total-quizzes').textContent = stats.total_quizzes || 0;
        document.getElementById('correct-answers').textContent = stats.total_correct_answers || 0;
        document.getElementById('earned-coins').textContent = stats.total_coins_earned || 0;
        document.getElementById('average-score').textContent = `${stats.average_score || 0}%`;
    }

    updateEpisodeFilter(episodes) {
        const select = document.getElementById('episode-filter');
        
        episodes.forEach(episode => {
            const option = document.createElement('option');
            option.value = episode.id;
            option.textContent = episode.title;
            select.appendChild(option);
        });
    }

    updateQuizHistoryTable(quizzes) {
        const tbody = document.getElementById('quiz-history-table-body');
        
        if (quizzes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        هیچ آزمونی یافت نشد
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        
        quizzes.forEach(quiz => {
            const row = this.createQuizHistoryRow(quiz);
            tbody.appendChild(row);
        });
    }

    updatePaginationInfo(data) {
        document.getElementById('showing-count').textContent = data.quizzes.length;
        document.getElementById('total-count').textContent = data.total || 0;
        
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        prevBtn.disabled = this.currentPage <= 1;
        nextBtn.disabled = data.quizzes.length < 20;
    }

    createQuizHistoryRow(quiz) {
        const row = document.createElement('tr');
        
        const scorePercentage = (quiz.correct_answers / quiz.total_questions) * 100;
        const scoreClass = this.getScoreClass(scorePercentage);
        const statusClass = this.getStatusClass(quiz.status);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${quiz.episode_title || `قسمت ${quiz.episode_id}`}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${this.formatDate(quiz.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium ${scoreClass}">${scorePercentage.toFixed(1)}%</div>
                <div class="text-xs text-gray-500">${quiz.correct_answers}/${quiz.total_questions}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-yellow-600">${quiz.coins_earned || 0}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                    ${this.getStatusText(quiz.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="quizHistory.showQuizDetail(${quiz.id})" class="text-blue-600 hover:text-blue-900">
                    مشاهده جزئیات
                </button>
            </td>
        `;
        
        return row;
    }

    async showQuizDetail(quizId) {
        try {
            const response = await fetch(`/api/quiz/history/${quizId}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load quiz detail');
            }

            const data = await response.json();
            
            if (data.success) {
                this.displayQuizDetail(data.data);
            } else {
                this.showError('خطا در بارگذاری جزئیات آزمون');
            }
        } catch (error) {
            console.error('Error loading quiz detail:', error);
            this.showError('خطا در بارگذاری جزئیات آزمون');
        }
    }

    displayQuizDetail(quiz) {
        const modal = document.getElementById('quiz-detail-modal');
        const content = document.getElementById('quiz-detail-content');
        
        const scorePercentage = (quiz.correct_answers / quiz.total_questions) * 100;
        
        content.innerHTML = `
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-2">${quiz.episode_title || `قسمت ${quiz.episode_id}`}</h4>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-600">${scorePercentage.toFixed(1)}%</div>
                        <div class="text-sm text-gray-600">نمره</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-yellow-600">${quiz.coins_earned || 0}</div>
                        <div class="text-sm text-gray-600">سکه کسب شده</div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <h5 class="text-md font-semibold text-gray-900">پاسخ‌های شما:</h5>
                ${quiz.answers ? quiz.answers.map((answer, index) => `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900">سوال ${index + 1}</span>
                            <span class="px-2 py-1 text-xs rounded-full ${answer.is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${answer.is_correct ? 'صحیح' : 'غلط'}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <p class="mb-1">پاسخ شما: <span class="font-medium">${answer.selected_answer}</span></p>
                            <p>پاسخ صحیح: <span class="font-medium text-green-600">${answer.correct_answer}</span></p>
                        </div>
                    </div>
                `).join('') : '<p class="text-gray-500">جزئیات پاسخ‌ها در دسترس نیست</p>'}
            </div>
        `;
        
        modal.classList.remove('hidden');
    }

    closeDetailModal() {
        document.getElementById('quiz-detail-modal').classList.add('hidden');
    }

    getScoreClass(score) {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-blue-600';
        if (score >= 40) return 'text-yellow-600';
        return 'text-red-600';
    }

    getStatusText(status) {
        const statuses = {
            'completed': 'تکمیل شده',
            'in_progress': 'در حال انجام',
            'abandoned': 'رها شده'
        };
        return statuses[status] || status;
    }

    getStatusClass(status) {
        const classes = {
            'completed': 'bg-green-100 text-green-800',
            'in_progress': 'bg-yellow-100 text-yellow-800',
            'abandoned': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fa-IR');
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </td>
                </tr>
            `;
        }
    }

    hideLoading(elementId) {
        // Loading will be replaced by actual content
    }

    showError(message) {
        // You can implement a toast notification system here
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.quizHistory = new QuizHistory();
});
