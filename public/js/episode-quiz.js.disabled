class EpisodeQuiz {
    constructor() {
        this.episodeId = this.getEpisodeIdFromUrl();
        this.questions = [];
        this.currentQuestionIndex = 0;
        this.answers = {};
        this.isSubmitted = false;
        this.init();
    }

    init() {
        this.loadQuizData();
        this.bindEvents();
    }

    bindEvents() {
        // Submit quiz
        document.getElementById('submit-quiz')?.addEventListener('click', () => {
            this.submitQuiz();
        });

        // Reset quiz
        document.getElementById('reset-quiz')?.addEventListener('click', () => {
            this.resetQuiz();
        });

        // Close modals
        document.getElementById('close-result-modal')?.addEventListener('click', () => {
            this.closeResultModal();
        });

        document.getElementById('close-explanation-modal')?.addEventListener('click', () => {
            this.closeExplanationModal();
        });

        document.getElementById('view-explanations')?.addEventListener('click', () => {
            this.showExplanations();
        });

        // Close modals on outside click
        document.getElementById('quiz-result-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'quiz-result-modal') {
                this.closeResultModal();
            }
        });

        document.getElementById('explanation-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'explanation-modal') {
                this.closeExplanationModal();
            }
        });
    }

    getEpisodeIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('episode_id') || this.getEpisodeIdFromPath();
    }

    getEpisodeIdFromPath() {
        const path = window.location.pathname;
        const matches = path.match(/\/quiz\/(\d+)/);
        return matches ? matches[1] : null;
    }

    async loadQuizData() {
        if (!this.episodeId) {
            this.showError('شناسه قسمت یافت نشد');
            return;
        }

        try {
            const response = await fetch(`/api/quiz/episodes/${this.episodeId}/questions`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load quiz data');
            }

            const data = await response.json();
            
            if (data.success) {
                this.questions = data.data;
                this.updateEpisodeInfo();
                this.renderQuestions();
                this.updateProgress();
            } else {
                this.showError('خطا در بارگذاری سوالات آزمون');
            }
        } catch (error) {
            console.error('Error loading quiz data:', error);
            this.showError('خطا در بارگذاری سوالات آزمون');
        }
    }

    updateEpisodeInfo() {
        // Update episode title and description
        const episodeTitle = document.getElementById('episode-title');
        const episodeDescription = document.getElementById('episode-description');
        
        if (episodeTitle) {
            episodeTitle.textContent = `آزمون قسمت ${this.episodeId}`;
        }
        
        if (episodeDescription) {
            episodeDescription.textContent = `${this.questions.length} سوال برای پاسخ`;
        }

        // Calculate total coins
        const totalCoins = this.questions.reduce((sum, question) => sum + (question.coins_reward || 5), 0);
        document.getElementById('total-coins').textContent = totalCoins;
    }

    renderQuestions() {
        const container = document.getElementById('quiz-container');
        
        if (this.questions.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">هیچ سوالی برای این قسمت وجود ندارد</div>';
            return;
        }

        container.innerHTML = '';
        
        this.questions.forEach((question, index) => {
            const questionElement = this.createQuestionElement(question, index);
            container.appendChild(questionElement);
        });
    }

    createQuestionElement(question, index) {
        const div = document.createElement('div');
        div.className = 'question-card';
        div.id = `question-${question.id}`;
        
        div.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">سوال ${index + 1}</h3>
                <div class="text-sm text-gray-500">
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                        ${question.coins_reward || 5} سکه
                    </span>
                </div>
            </div>
            <p class="text-gray-700 mb-6">${question.question}</p>
            <div class="space-y-3">
                <button class="option-button" data-question-id="${question.id}" data-option="a">
                    <span class="font-medium">الف)</span> ${question.option_a}
                </button>
                <button class="option-button" data-question-id="${question.id}" data-option="b">
                    <span class="font-medium">ب)</span> ${question.option_b}
                </button>
                <button class="option-button" data-question-id="${question.id}" data-option="c">
                    <span class="font-medium">ج)</span> ${question.option_c}
                </button>
                <button class="option-button" data-question-id="${question.id}" data-option="d">
                    <span class="font-medium">د)</span> ${question.option_d}
                </button>
            </div>
        `;

        // Bind option click events
        div.querySelectorAll('.option-button').forEach(button => {
            button.addEventListener('click', (e) => {
                this.selectOption(question.id, e.target.dataset.option);
            });
        });

        return div;
    }

    selectOption(questionId, option) {
        if (this.isSubmitted) return;

        this.answers[questionId] = option;
        
        // Update UI
        const questionElement = document.getElementById(`question-${questionId}`);
        const optionButtons = questionElement.querySelectorAll('.option-button');
        
        optionButtons.forEach(button => {
            button.classList.remove('selected');
            if (button.dataset.option === option) {
                button.classList.add('selected');
            }
        });

        // Mark question as answered
        questionElement.classList.add('answered');
        
        this.updateProgress();
        this.updateSubmitButton();
    }

    updateProgress() {
        const totalQuestions = this.questions.length;
        const answeredQuestions = Object.keys(this.answers).length;
        const progressPercentage = (answeredQuestions / totalQuestions) * 100;
        
        document.getElementById('progress-bar').style.width = `${progressPercentage}%`;
        document.getElementById('progress-text').textContent = `${answeredQuestions} از ${totalQuestions}`;
        document.getElementById('answered-count').textContent = answeredQuestions;
    }

    updateSubmitButton() {
        const submitButton = document.getElementById('submit-quiz');
        const totalQuestions = this.questions.length;
        const answeredQuestions = Object.keys(this.answers).length;
        
        submitButton.disabled = answeredQuestions < totalQuestions;
    }

    async submitQuiz() {
        if (this.isSubmitted) return;

        const totalQuestions = this.questions.length;
        const answeredQuestions = Object.keys(this.answers).length;
        
        if (answeredQuestions < totalQuestions) {
            this.showError('لطفاً به همه سوالات پاسخ دهید');
            return;
        }

        this.isSubmitted = true;
        this.showLoading();

        try {
            const response = await fetch('/api/quiz/submit-answer', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    episode_id: this.episodeId,
                    answers: this.answers
                })
            });

            if (!response.ok) {
                throw new Error('Failed to submit quiz');
            }

            const data = await response.json();
            
            if (data.success) {
                this.showQuizResult(data.data);
            } else {
                this.showError(data.message || 'خطا در ارسال پاسخ‌ها');
            }
        } catch (error) {
            console.error('Error submitting quiz:', error);
            this.showError('خطا در ارسال پاسخ‌ها');
        } finally {
            this.hideLoading();
        }
    }

    showQuizResult(result) {
        const modal = document.getElementById('quiz-result-modal');
        const icon = document.getElementById('result-icon');
        const title = document.getElementById('result-title');
        const message = document.getElementById('result-message');
        const correctAnswers = document.getElementById('correct-answers');
        const earnedCoins = document.getElementById('earned-coins');

        // Set result data
        correctAnswers.textContent = result.correct_answers || 0;
        earnedCoins.textContent = result.coins_earned || 0;

        // Set icon and colors based on performance
        const scorePercentage = (result.correct_answers / this.questions.length) * 100;
        
        if (scorePercentage >= 80) {
            icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100 text-green-600';
            icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            title.textContent = 'عالی!';
            message.textContent = 'نمره شما عالی است!';
        } else if (scorePercentage >= 60) {
            icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100 text-blue-600';
            icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            title.textContent = 'خوب!';
            message.textContent = 'نمره شما خوب است!';
        } else {
            icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-yellow-100 text-yellow-600';
            icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>';
            title.textContent = 'نیاز به تلاش بیشتر';
            message.textContent = 'لطفاً بیشتر مطالعه کنید!';
        }

        // Show correct/incorrect answers
        this.showAnswerResults(result.question_results);

        modal.classList.remove('hidden');
    }

    showAnswerResults(questionResults) {
        this.questions.forEach((question, index) => {
            const questionElement = document.getElementById(`question-${question.id}`);
            const optionButtons = questionElement.querySelectorAll('.option-button');
            const userAnswer = this.answers[question.id];
            const correctAnswer = question.correct_answer;
            const isCorrect = userAnswer === correctAnswer;

            // Mark question as correct or incorrect
            questionElement.classList.remove('answered');
            questionElement.classList.add(isCorrect ? 'correct' : 'incorrect');

            // Update option buttons
            optionButtons.forEach(button => {
                button.classList.remove('selected', 'correct', 'incorrect');
                button.disabled = true;
                button.classList.add('disabled');

                if (button.dataset.option === correctAnswer) {
                    button.classList.add('correct');
                } else if (button.dataset.option === userAnswer && !isCorrect) {
                    button.classList.add('incorrect');
                }
            });
        });
    }

    showExplanations() {
        const modal = document.getElementById('explanation-modal');
        const container = document.getElementById('explanations-container');
        
        container.innerHTML = '';
        
        this.questions.forEach((question, index) => {
            const explanationElement = this.createExplanationElement(question, index);
            container.appendChild(explanationElement);
        });

        this.closeResultModal();
        modal.classList.remove('hidden');
    }

    createExplanationElement(question, index) {
        const div = document.createElement('div');
        div.className = 'border border-gray-200 rounded-lg p-4';
        
        const userAnswer = this.answers[question.id];
        const isCorrect = userAnswer === question.correct_answer;
        
        div.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-lg font-semibold text-gray-900">سوال ${index + 1}</h4>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${isCorrect ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${isCorrect ? 'صحیح' : 'غلط'}
                </span>
            </div>
            <p class="text-gray-700 mb-3">${question.question}</p>
            <div class="mb-3">
                <p class="text-sm text-gray-600 mb-1">پاسخ شما: <span class="font-medium">${this.getOptionText(question, userAnswer)}</span></p>
                <p class="text-sm text-gray-600 mb-1">پاسخ صحیح: <span class="font-medium text-green-600">${this.getOptionText(question, question.correct_answer)}</span></p>
            </div>
            ${question.explanation ? `<div class="bg-gray-50 rounded-lg p-3"><p class="text-sm text-gray-700">${question.explanation}</p></div>` : ''}
        `;
        
        return div;
    }

    getOptionText(question, option) {
        const options = {
            'a': question.option_a,
            'b': question.option_b,
            'c': question.option_c,
            'd': question.option_d
        };
        return options[option] || '';
    }

    resetQuiz() {
        if (confirm('آیا مطمئن هستید که می‌خواهید آزمون را از نو شروع کنید؟')) {
            this.answers = {};
            this.isSubmitted = false;
            this.renderQuestions();
            this.updateProgress();
            this.updateSubmitButton();
        }
    }

    closeResultModal() {
        document.getElementById('quiz-result-modal').classList.add('hidden');
    }

    closeExplanationModal() {
        document.getElementById('explanation-modal').classList.add('hidden');
    }

    showLoading() {
        // You can implement a loading overlay here
        document.body.style.cursor = 'wait';
    }

    hideLoading() {
        document.body.style.cursor = 'default';
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
    new EpisodeQuiz();
});
