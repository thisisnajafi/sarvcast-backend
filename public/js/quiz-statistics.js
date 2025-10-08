/**
 * Quiz Statistics Management JavaScript
 * Handles quiz statistics for users
 */

class QuizStatisticsManager {
    constructor() {
        this.statistics = {};
        this.charts = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadStatistics();
    }

    setupEventListeners() {
        // Date range filter
        const dateRangeForm = document.getElementById('date-range-form');
        if (dateRangeForm) {
            dateRangeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyDateFilter();
            });
        }

        // Reset filter
        const resetBtn = document.getElementById('reset-filter');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetFilter());
        }

        // Export statistics
        const exportBtn = document.getElementById('export-statistics');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportStatistics());
        }

        // Quiz type filter
        document.addEventListener('change', (e) => {
            if (e.target.name === 'quiz_type') {
                this.filterByQuizType(e.target.value);
            }
        });
    }

    loadStatistics() {
        this.showLoading();

        const params = new URLSearchParams(window.location.search);
        
        fetch(`/api/quiz/statistics?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.statistics = data.data;
                this.displayStatistics();
                this.createCharts();
            } else {
                this.showError(data.message || 'خطا در بارگذاری آمار کویزها');
            }
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
            this.showError('خطا در بارگذاری آمار کویزها');
        })
        .finally(() => {
            this.hideLoading();
        });
    }

    displayStatistics() {
        this.updateOverviewCards();
        this.updateQuizHistory();
        this.updatePerformanceMetrics();
    }

    updateOverviewCards() {
        const cards = {
            'total-quizzes': this.statistics.total_quizzes || 0,
            'correct-answers': this.statistics.correct_answers || 0,
            'average-score': this.statistics.average_score || 0,
            'total-coins-earned': this.statistics.total_coins_earned || 0
        };

        Object.keys(cards).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = cards[id].toLocaleString('fa-IR');
            }
        });
    }

    updateQuizHistory() {
        const container = document.getElementById('quiz-history-container');
        if (!container) return;

        const quizzes = this.statistics.recent_quizzes || [];
        
        if (quizzes.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-question-circle text-4xl mb-4"></i>
                    <p>هیچ کویزی انجام نداده‌اید</p>
                </div>
            `;
            return;
        }

        const html = quizzes.map(quiz => this.createQuizHistoryItem(quiz)).join('');
        container.innerHTML = html;
    }

    createQuizHistoryItem(quiz) {
        const date = new Date(quiz.completed_at).toLocaleDateString('fa-IR');
        const scorePercentage = Math.round((quiz.score / quiz.total_questions) * 100);
        const scoreClass = scorePercentage >= 80 ? 'text-green-600' : 
                          scorePercentage >= 60 ? 'text-yellow-600' : 'text-red-600';

        return `
            <div class="quiz-history-item bg-white rounded-lg shadow-sm border p-4 mb-3">
                <div class="flex items-center justify-between">
                    <div class="quiz-info">
                        <h3 class="font-semibold text-gray-800">${quiz.episode_title}</h3>
                        <p class="text-sm text-gray-600">${date}</p>
                        <p class="text-xs text-gray-500">${quiz.total_questions} سوال</p>
                    </div>
                    <div class="quiz-score text-right">
                        <div class="score-percentage ${scoreClass} font-bold text-lg">
                            ${scorePercentage}%
                        </div>
                        <div class="score-details text-sm text-gray-600">
                            ${quiz.score} از ${quiz.total_questions}
                        </div>
                        ${quiz.coins_earned ? `
                            <div class="coins-earned text-xs text-green-600 mt-1">
                                <i class="fas fa-coins mr-1"></i>
                                ${quiz.coins_earned} سکه
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    updatePerformanceMetrics() {
        const metrics = this.statistics.performance_metrics || {};
        
        // Update accuracy by category
        this.updateAccuracyChart(metrics.accuracy_by_category || {});
        
        // Update score trends
        this.updateScoreTrends(metrics.score_trends || []);
        
        // Update time analysis
        this.updateTimeAnalysis(metrics.time_analysis || {});
    }

    createCharts() {
        this.createScoreDistributionChart();
        this.createAccuracyTrendChart();
        this.createCategoryPerformanceChart();
    }

    createScoreDistributionChart() {
        const ctx = document.getElementById('score-distribution-chart');
        if (!ctx) return;

        const distribution = this.statistics.score_distribution || {};
        const labels = Object.keys(distribution);
        const data = Object.values(distribution);

        this.charts.scoreDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#ef4444', // Red for low scores
                        '#f97316', // Orange for medium-low
                        '#eab308', // Yellow for medium
                        '#22c55e', // Green for high scores
                        '#3b82f6'  // Blue for excellent
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'توزیع نمرات'
                    }
                }
            }
        });
    }

    createAccuracyTrendChart() {
        const ctx = document.getElementById('accuracy-trend-chart');
        if (!ctx) return;

        const trends = this.statistics.accuracy_trends || [];
        const labels = trends.map(trend => new Date(trend.date).toLocaleDateString('fa-IR'));
        const accuracyData = trends.map(trend => trend.accuracy);

        this.charts.accuracyTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'دقت پاسخ‌ها',
                    data: accuracyData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'روند دقت پاسخ‌ها'
                    }
                }
            }
        });
    }

    createCategoryPerformanceChart() {
        const ctx = document.getElementById('category-performance-chart');
        if (!ctx) return;

        const performance = this.statistics.category_performance || {};
        const labels = Object.keys(performance);
        const scores = Object.values(performance);

        this.charts.categoryPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'میانگین نمره',
                    data: scores,
                    backgroundColor: '#22c55e',
                    borderColor: '#16a34a',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'عملکرد بر اساس دسته‌بندی'
                    }
                }
            }
        });
    }

    updateAccuracyChart(accuracyData) {
        const container = document.getElementById('accuracy-by-category');
        if (!container) return;

        const html = Object.keys(accuracyData).map(category => {
            const accuracy = accuracyData[category];
            const accuracyClass = accuracy >= 80 ? 'text-green-600' : 
                                 accuracy >= 60 ? 'text-yellow-600' : 'text-red-600';

            return `
                <div class="accuracy-item flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                    <span class="text-gray-700">${category}</span>
                    <span class="font-semibold ${accuracyClass}">${accuracy}%</span>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    updateScoreTrends(trends) {
        const container = document.getElementById('score-trends');
        if (!container) return;

        if (trends.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-4">داده‌ای برای نمایش وجود ندارد</p>';
            return;
        }

        const html = trends.map(trend => {
            const date = new Date(trend.date).toLocaleDateString('fa-IR');
            const scoreClass = trend.score >= 80 ? 'text-green-600' : 
                              trend.score >= 60 ? 'text-yellow-600' : 'text-red-600';

            return `
                <div class="trend-item flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                    <span class="text-gray-700">${date}</span>
                    <span class="font-semibold ${scoreClass}">${trend.score}%</span>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    updateTimeAnalysis(timeData) {
        const elements = {
            'average-time-per-question': timeData.average_time_per_question || 0,
            'total-quiz-time': timeData.total_quiz_time || 0,
            'fastest-quiz': timeData.fastest_quiz || 0,
            'slowest-quiz': timeData.slowest_quiz || 0
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.formatTime(elements[id]);
            }
        });
    }

    applyDateFilter() {
        const formData = new FormData(document.getElementById('date-range-form'));
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');

        const url = new URL(window.location);
        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        if (dateTo) url.searchParams.set('date_to', dateTo);
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    resetFilter() {
        const url = new URL(window.location);
        url.searchParams.delete('date_from');
        url.searchParams.delete('date_to');
        url.searchParams.delete('quiz_type');
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    filterByQuizType(quizType) {
        const url = new URL(window.location);
        if (quizType) {
            url.searchParams.set('quiz_type', quizType);
        } else {
            url.searchParams.delete('quiz_type');
        }
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    exportStatistics() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'true');
        
        window.open(`/api/quiz/statistics/export?${params}`, '_blank');
    }

    formatTime(seconds) {
        if (seconds < 60) {
            return `${seconds} ثانیه`;
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes} دقیقه و ${remainingSeconds} ثانیه`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours} ساعت و ${minutes} دقیقه`;
        }
    }

    showLoading() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.classList.remove('hidden');
        }
    }

    hideLoading() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.classList.add('hidden');
        }
    }

    showError(message) {
        const container = document.getElementById('statistics-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new QuizStatisticsManager();
});
