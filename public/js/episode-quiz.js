/**
 * Episode Quiz Management JavaScript
 * Handles episode quiz functionality for users
 */

class EpisodeQuizManager {
    constructor() {
        this.currentQuestion = 0;
        this.score = 0;
        this.questions = [];
        this.userAnswers = [];
        this.timeLimit = null;
        this.timer = null;
        this.init();
    }

    init() {
        this.loadQuizData();
        this.setupEventListeners();
        this.startQuiz();
    }

    loadQuizData() {
        // Get quiz data from the page
        const quizDataElement = document.getElementById('quiz-data');
        if (quizDataElement) {
            try {
                const data = JSON.parse(quizDataElement.textContent);
                this.questions = data.questions || [];
                this.timeLimit = data.time_limit || null;
                this.episodeId = data.episode_id;
            } catch (error) {
                console.error('Error loading quiz data:', error);
                this.showError('خطا در بارگذاری اطلاعات کویز');
            }
        }
    }

    setupEventListeners() {
        // Answer selection
        document.addEventListener('change', (e) => {
            if (e.target.name === 'answer') {
                this.selectAnswer(e.target.value);
            }
        });

        // Next/Previous buttons
        const nextBtn = document.getElementById('next-question');
        const prevBtn = document.getElementById('prev-question');
        const submitBtn = document.getElementById('submit-quiz');

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextQuestion());
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.prevQuestion());
        }
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitQuiz());
        }

        // Timer display
        this.updateTimerDisplay();
    }

    startQuiz() {
        if (this.questions.length === 0) {
            this.showError('هیچ سوالی برای نمایش وجود ندارد');
            return;
        }

        this.showQuestion(0);
        
        if (this.timeLimit) {
            this.startTimer();
        }
    }

    showQuestion(index) {
        if (index < 0 || index >= this.questions.length) {
            return;
        }

        this.currentQuestion = index;
        const question = this.questions[index];
        
        // Update question display
        this.updateQuestionDisplay(question);
        this.updateProgress();
        this.updateNavigationButtons();
    }

    updateQuestionDisplay(question) {
        const questionElement = document.getElementById('question-text');
        const optionsContainer = document.getElementById('options-container');
        const questionNumber = document.getElementById('question-number');

        if (questionElement) {
            questionElement.textContent = question.question;
        }

        if (questionNumber) {
            questionNumber.textContent = `سوال ${this.currentQuestion + 1}`;
        }

        if (optionsContainer) {
            optionsContainer.innerHTML = '';
            
            question.options.forEach((option, index) => {
                const optionElement = this.createOptionElement(option, index);
                optionsContainer.appendChild(optionElement);
            });
        }

        // Restore previous answer if exists
        if (this.userAnswers[this.currentQuestion]) {
            const selectedOption = document.querySelector(`input[name="answer"][value="${this.userAnswers[this.currentQuestion]}"]`);
            if (selectedOption) {
                selectedOption.checked = true;
            }
        }
    }

    createOptionElement(option, index) {
        const div = document.createElement('div');
        div.className = 'option-item mb-3';

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'answer';
        input.value = index;
        input.id = `option-${index}`;
        input.className = 'mr-2';

        const label = document.createElement('label');
        label.htmlFor = `option-${index}`;
        label.textContent = option;
        label.className = 'cursor-pointer';

        div.appendChild(input);
        div.appendChild(label);

        return div;
    }

    selectAnswer(answerIndex) {
        this.userAnswers[this.currentQuestion] = parseInt(answerIndex);
        
        // Enable next button or submit button
        this.updateNavigationButtons();
    }

    nextQuestion() {
        if (this.currentQuestion < this.questions.length - 1) {
            this.showQuestion(this.currentQuestion + 1);
        }
    }

    prevQuestion() {
        if (this.currentQuestion > 0) {
            this.showQuestion(this.currentQuestion - 1);
        }
    }

    updateNavigationButtons() {
        const nextBtn = document.getElementById('next-question');
        const prevBtn = document.getElementById('prev-question');
        const submitBtn = document.getElementById('submit-quiz');

        if (prevBtn) {
            prevBtn.style.display = this.currentQuestion > 0 ? 'block' : 'none';
        }

        if (nextBtn) {
            nextBtn.style.display = this.currentQuestion < this.questions.length - 1 ? 'block' : 'none';
        }

        if (submitBtn) {
            submitBtn.style.display = this.currentQuestion === this.questions.length - 1 ? 'block' : 'none';
        }

        // Check if current question is answered
        const isAnswered = this.userAnswers[this.currentQuestion] !== undefined;
        if (nextBtn) nextBtn.disabled = !isAnswered;
        if (submitBtn) submitBtn.disabled = !this.isQuizComplete();
    }

    updateProgress() {
        const progressBar = document.getElementById('quiz-progress');
        const progressText = document.getElementById('progress-text');

        if (progressBar) {
            const progress = ((this.currentQuestion + 1) / this.questions.length) * 100;
            progressBar.style.width = `${progress}%`;
        }

        if (progressText) {
            progressText.textContent = `${this.currentQuestion + 1} از ${this.questions.length}`;
        }
    }

    isQuizComplete() {
        return this.userAnswers.length === this.questions.length && 
               this.userAnswers.every(answer => answer !== undefined);
    }

    startTimer() {
        if (!this.timeLimit) return;

        let timeLeft = this.timeLimit * 60; // Convert minutes to seconds
        
        this.timer = setInterval(() => {
            timeLeft--;
            this.updateTimerDisplay(timeLeft);
            
            if (timeLeft <= 0) {
                this.timeUp();
            }
        }, 1000);
    }

    updateTimerDisplay(timeLeft = null) {
        const timerElement = document.getElementById('quiz-timer');
        if (!timerElement) return;

        if (timeLeft === null) {
            timeLeft = this.timeLimit * 60;
        }

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Change color when time is running low
        if (timeLeft <= 60) {
            timerElement.classList.add('text-red-500');
        } else {
            timerElement.classList.remove('text-red-500');
        }
    }

    timeUp() {
        clearInterval(this.timer);
        this.showNotification('زمان کویز به پایان رسید!', 'warning');
        this.submitQuiz();
    }

    submitQuiz() {
        if (!this.isQuizComplete()) {
            this.showNotification('لطفاً به تمام سوالات پاسخ دهید', 'warning');
            return;
        }

        // Calculate score
        this.calculateScore();

        // Send results to server
        this.sendQuizResults();
    }

    calculateScore() {
        this.score = 0;
        this.questions.forEach((question, index) => {
            if (this.userAnswers[index] === question.correct_answer) {
                this.score++;
            }
        });
    }

    sendQuizResults() {
        const data = {
            episode_id: this.episodeId,
            answers: this.userAnswers,
            score: this.score,
            total_questions: this.questions.length,
            time_taken: this.getTimeTaken()
        };

        fetch('/api/quiz/submit', {
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
                this.showQuizResults(result.data);
            } else {
                this.showError(result.message || 'خطا در ارسال نتایج کویز');
            }
        })
        .catch(error => {
            console.error('Error submitting quiz:', error);
            this.showError('خطا در ارسال نتایج کویز');
        });
    }

    showQuizResults(data) {
        // Hide quiz form
        const quizForm = document.getElementById('quiz-form');
        if (quizForm) {
            quizForm.style.display = 'none';
        }

        // Show results
        const resultsContainer = document.getElementById('quiz-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
            
            // Update results content
            const scoreElement = document.getElementById('final-score');
            const percentageElement = document.getElementById('score-percentage');
            const coinsEarnedElement = document.getElementById('coins-earned');

            if (scoreElement) {
                scoreElement.textContent = `${this.score} از ${this.questions.length}`;
            }

            if (percentageElement) {
                const percentage = Math.round((this.score / this.questions.length) * 100);
                percentageElement.textContent = `${percentage}%`;
            }

            if (coinsEarnedElement && data.coins_earned) {
                coinsEarnedElement.textContent = data.coins_earned;
            }
        }

        // Clear timer
        if (this.timer) {
            clearInterval(this.timer);
        }
    }

    getTimeTaken() {
        // Calculate time taken (simplified)
        return this.timeLimit ? this.timeLimit * 60 : 0;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-sm max-w-sm transform transition-all duration-300 translate-x-full`;
        
        switch(type) {
            case 'success': notification.classList.add('bg-green-500'); break;
            case 'error': notification.classList.add('bg-red-500'); break;
            case 'warning': notification.classList.add('bg-yellow-500'); break;
            default: notification.classList.add('bg-blue-500');
        }

        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => { notification.classList.remove('translate-x-full'); }, 100);
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => { 
                if (notification.parentNode) { 
                    notification.parentNode.removeChild(notification); 
                } 
            }, 300);
        }, 3000);
    }

    showError(message) {
        this.showNotification(message, 'error');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new EpisodeQuizManager();
});
