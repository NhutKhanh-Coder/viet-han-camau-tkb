/* ==========================================================================
   QUIZ ENGINE JAVASCRIPT - TRUONG CAO DANG CA MAU
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {
    const timerElement = document.getElementById('quiz-timer');
    const quizForm = document.getElementById('quiz-form');
    const timeSpentInput = document.getElementById('time_spent_seconds');

    let totalSeconds = 0;
    let timerInterval = null;

    // Start timer count up & count down if limit set
    if (timerElement) {
        timerInterval = setInterval(function () {
            totalSeconds++;
            if (timeSpentInput) {
                timeSpentInput.value = totalSeconds;
            }

            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;

            const formattedTime = 
                (minutes < 10 ? '0' : '') + minutes + ':' + 
                (seconds < 10 ? '0' : '') + seconds;

            timerElement.textContent = formattedTime;
        }, 1000);
    }

    // Question navigator updates & input tracking
    const qNavButtons = document.querySelectorAll('.q-nav-btn');

    function updateNavStatus() {
        const questionCards = document.querySelectorAll('.question-card');
        
        questionCards.forEach((card, index) => {
            const qId = card.getAttribute('data-q-id');
            const navBtn = document.querySelector(`.q-nav-btn[data-target="${qId}"]`);
            if (!navBtn) return;

            // Check if any input inside card has value or is checked
            const checkedInputs = card.querySelectorAll('input:checked');
            const textInputs = card.querySelectorAll('input[type="text"]');
            let isAnswered = checkedInputs.length > 0;

            textInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    isAnswered = true;
                }
            });

            if (isAnswered) {
                navBtn.classList.add('answered');
            } else {
                navBtn.classList.remove('answered');
            }
        });
    }

    // Attach change listeners to all quiz inputs
    const allQuizInputs = document.querySelectorAll('#quiz-form input');
    allQuizInputs.forEach(input => {
        input.addEventListener('change', updateNavStatus);
        input.addEventListener('keyup', updateNavStatus);
    });

    // Nav button click handler (scroll smoothly to question)
    qNavButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetCard = document.getElementById(`q-card-${targetId}`);
            if (targetCard) {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Highlight target card temporarily
                document.querySelectorAll('.question-card').forEach(c => c.classList.remove('active-q'));
                targetCard.classList.add('active-q');
            }
        });
    });

    // Form submit confirmation
    if (quizForm) {
        quizForm.addEventListener('submit', function (e) {
            const answeredCount = document.querySelectorAll('.q-nav-btn.answered').length;
            const totalCount = qNavButtons.length;

            if (answeredCount < totalCount) {
                const confirmSubmit = confirm(`Bạn mới trả lời ${answeredCount}/${totalCount} câu hỏi.\nBạn có chắc chắn muốn nộp bài ngay bây giờ không?`);
                if (!confirmSubmit) {
                    e.preventDefault();
                    return false;
                }
            }
            
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        });
    }
});
