<?php
if (!isset($_GET['model_id'])) {
    header("Location: ?view=courses");
    exit();
}
$model_id = intval($_GET['model_id']);
$user_id = $_SESSION['user']['user_id'] ?? 'N/A';
?>

<style>
/* ========== Main Layout & Structure ========== */
.quiz-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.quiz-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 2.5rem;
    align-items: flex-start;
}

.quiz-main-content {
    min-width: 0;
}

.quiz-navigation-panel {
    position: sticky;
    top: 100px;
}

/* ========== Cards & Containers ========== */
.controls-card {
    background: var(--color-surface-1);
    border-radius: var(--border-radius-xl);
    padding: 1.75rem;
    border: 1px solid var(--color-glass-border);
    margin-bottom: 1.75rem;
    box-shadow: var(--shadow-lg);
}

.controls-card h3 {
    text-align: center;
    margin-bottom: 1.75rem;
    font-size: 1.4rem;
    color: var(--color-text-primary);
    font-weight: 600;
}

.question-card {
    margin-bottom: 2rem;
}

.question-block {
    background: var(--color-surface-1);
    border-radius: var(--border-radius-xl);
    border: 1px solid var(--color-glass-border);
    box-shadow: var(--shadow-lg);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

/* ========== Question Content ========== */
.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 2rem;
    background-color: var(--color-surface-2);
    border-bottom: 1px solid var(--color-glass-border);
}

.question-header h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.question-image-wrapper {
    position: relative;
    width: 100%;
    background-color: var(--color-surface-2);
    padding: 2rem;
    text-align: center;
}

#photo-id-overlay {
    background-color: rgba(0, 0, 0, 0.1);
    color: var(--color-text-muted);
    padding: 0.3rem 0.8rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    margin-left: 1rem;
}

#fullscreen-btn {
    background-color: var(--color-surface-3);
    color: var(--color-text-primary);
    border: 1px solid var(--color-glass-border);
    border-radius: var(--border-radius-md);
    width: 44px;
    height: 44px;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
    margin-left: 1rem;
}

#fullscreen-btn:hover {
    background-color: var(--color-surface-2);
}

.question-image {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    cursor: default;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.image-zoom-overlay {
    display: none;
}

.question-text-container {
    padding: 2.5rem;
}

.question-text {
    font-size: 1.5rem;
    line-height: 1.8;
    color: var(--color-text-primary);
    margin-bottom: 0;
}

/* ========== Options Styling ========== */
.options-container {
    padding: 2.5rem;
}

.in-question-controls {
    padding: 1.5rem 2.5rem;
}

.options-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

.option-btn {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem 2rem;
    text-align: right;
    font-size: 1.3rem;
    border-radius: var(--border-radius-lg);
    border: 2px solid var(--color-surface-3);
    transition: all 0.25s ease;
    background-color: var(--color-surface-1);
}

.option-btn:hover {
    border-color: var(--color-accent-hover);
    background-color: var(--color-surface-2);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.option-btn.selected {
    border-color: var(--color-accent);
    background-color: var(--color-accent-container);
    color: var(--color-accent);
    box-shadow: 0 5px 20px -5px var(--color-accent);
}

.option-btn.correct {
    border-color: var(--color-success);
    background-color: var(--color-success-container);
    color: var(--color-success);
}

.option-letter {
    background-color: var(--color-surface-3);
    border-radius: 10px;
    width: 50px;
    height: 50px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-weight: 700;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.option-btn.selected .option-letter {
    background-color: var(--color-accent);
    color: var(--color-accent-contrast);
}

.option-btn.correct .option-letter {
    background-color: var(--color-success);
    color: var(--color-success-contrast);
}

.option-value {
    flex-grow: 1;
    font-weight: 500;
}

.option-image-in-button {
    max-width: 100%;
    max-height: 200px;
    border-radius: var(--border-radius-md);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* ========== Navigation & Controls ========== */
.control-buttons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: center;
}

.in-question-controls .btn {
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
}

.in-question-controls #finish-quiz-btn {
    white-space: nowrap;
}

.control-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--color-surface-2);
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius-md);
    margin-bottom: 1.25rem;
}

.control-row span {
    font-weight: 600;
    font-size: 1.1rem;
}

#finish-quiz-btn {
    width: 100%;
    padding: 1.25rem;
    font-size: 1.2rem;
    font-weight: 600;
    border-radius: var(--border-radius-lg);
}

#finish-quiz-btn:disabled {
    background-color: var(--color-surface-2);
    color: var(--color-text-muted);
    cursor: not-allowed;
    border: 1px solid var(--color-glass-border);
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-text-muted);
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: var(--color-accent);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* ========== Question Palette ========== */
.question-palette {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.palette-btn {
    aspect-ratio: 1 / 1;
    border-radius: 50%;
    border: 2px solid var(--color-glass-border);
    background-color: var(--color-surface-2);
    color: var(--color-text-primary);
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.palette-btn:hover {
    background-color: var(--color-surface-3);
    border-color: var(--color-accent-hover);
    transform: scale(1.05);
}

.palette-btn.answered {
    background-color: var(--color-success-container);
    border-color: var(--color-success);
    color: var(--color-success);
}

.palette-btn.timed-out {
    background-color: var(--color-error-container);
    border-color: var(--color-error);
    color: var(--color-error);
    cursor: not-allowed;
}

.palette-btn.current {
    background-color: var(--color-accent);
    border-color: var(--color-accent-hover);
    color: var(--color-accent-contrast);
    transform: scale(1.15);
    box-shadow: 0 0 0 3px var(--color-accent-container);
}

/* ========== Timers ========== */
.quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--color-surface-1);
    border-radius: var(--border-radius-xl);
    box-shadow: var(--shadow-md);
}

.quiz-title {
    font-size: 1.8rem;
    margin: 0;
    color: var(--color-text-primary);
}

.quiz-timer-box {
    background-color: var(--color-surface-2);
    padding: 1rem 2rem;
    border-radius: var(--border-radius-lg);
    text-align: center;
    min-width: 150px;
}

.quiz-timer-box .timer-label {
    font-size: 1rem;
    color: var(--color-text-muted);
    display: block;
    margin-bottom: 0.5rem;
}

.quiz-timer-box .quiz-timer {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-accent);
    line-height: 1;
}

.question-timer-box {
    padding: 0.75rem 1.5rem;
    background-color: var(--color-surface-1);
}

.question-timer-box .timer-label {
    font-size: 0.9rem;
}

.question-timer-box .quiz-timer {
    font-size: 1.8rem;
}

/* ========== Calculator Modal ========== */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
}

.modal-overlay.visible {
    opacity: 1;
    visibility: visible;
}

.calculator-container {
    position: relative;
    width: 350px;
    background-color: var(--color-surface-1);
    border-radius: var(--border-radius-xl);
    box-shadow: var(--shadow-2xl);
    border: 1px solid var(--color-glass-border);
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s;
}

.modal-overlay.visible .calculator-container {
    transform: scale(1);
}

.calculator-header {
    background-color: var(--color-surface-2);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: move;
    border-bottom: 1px solid var(--color-glass-border);
}

.calculator-header span {
    font-weight: 600;
    font-size: 1.2rem;
}

#close-calculator-btn {
    background: none;
    border: none;
    color: var(--color-text-primary);
    font-size: 1.8rem;
    cursor: pointer;
    padding: 0 0.5rem;
    line-height: 1;
}

.calculator-display {
    background-color: var(--color-surface-2);
    color: var(--color-text-primary);
    font-size: 3rem;
    text-align: right;
    padding: 1.5rem;
    width: 100%;
    border: none;
    box-sizing: border-box;
    font-family: monospace;
}

.calculator-keys {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background-color: var(--color-glass-border);
}

.calculator-keys button {
    background-color: var(--color-surface-1);
    border: none;
    padding: 1.5rem 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--color-text-primary);
    cursor: pointer;
    transition: background-color 0.2s;
}

.calculator-keys button:hover {
    background-color: var(--color-surface-3);
}

.calculator-keys .operator {
    background-color: var(--color-surface-3);
    color: var(--color-accent);
    font-size: 1.4rem;
}

.calculator-keys .equal-sign {
    grid-column: span 2;
    background-color: var(--color-accent);
    color: var(--color-accent-contrast);
    font-size: 1.5rem;
}

.calculator-keys .equal-sign:hover {
    background-color: var(--color-accent-hover);
}

/* ========== Responsive Design ========== */
@media (max-width: 1200px) {
    .quiz-layout {
        grid-template-columns: 1fr;
    }
    
    .quiz-navigation-panel {
        position: static;
        order: 99; /* Move navigation to the bottom */
        margin-bottom: 2rem;
    }
    
    .question-image {
        max-height: 60vh;
    }

    /* Reorder question blocks for tablet and mobile */
    #question-card-container {
        display: flex !important;
        flex-direction: column;
    }
    #question-controls-block { order: 1; } /* Controls on top */
    #question-content-block { order: 2; } /* Question content */
    #question-options-block { order: 3; } /* Options */
}

@media (max-width: 768px) {
    /* Ordering rules moved to 1200px media query to apply to tablets */

    .quiz-container {
        padding: 1rem;
    }
    
    .question-text-container, .options-container {
        padding: 1.75rem;
    }
    
    .question-text {
        font-size: 1.3rem;
        margin-bottom: 2rem;
    }
    
    .option-btn {
        padding: 1.25rem;
        font-size: 1.2rem;
    }
    
    .quiz-timer-box .quiz-timer {
        font-size: 2rem;
    }
    
    .question-header h2 {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .quiz-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .control-buttons-grid {
        grid-template-columns: 1fr;
    }
    
    .question-text {
        font-size: 1.2rem;
    }
    
    .option-btn {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .question-palette {
        grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
        gap: 0.75rem;
    }
    
    .calculator-container {
        width: 100%;
        max-width: 350px;
        margin: 0 1rem;
    }
}
</style>

<div id="quiz-container" class="quiz-container">
    <div id="quiz-loader" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
        <div class="loading-spinner" style="width: 60px; height: 60px; border: 6px solid var(--color-surface-2); border-top-color: var(--color-accent); border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <p style="margin-top: 1.5rem; font-size: 1.2rem;">جاري تحضير الاختبار...</p>
    </div>

    <div id="quiz-content" style="display: none;">
        <div class="quiz-header">
            <div style="flex: 1;">
                <h1 id="quiz-title" class="quiz-title">عنوان الاختبار</h1>
            </div>
            <div id="total-timer-container" class="quiz-timer-box" style="display: none;">
                <span class="timer-label">الوقت الكلي</span>
                <div id="total-timer" class="quiz-timer">00:00</div>
            </div>
        </div>

        <div class="quiz-layout">
            
            <main id="quiz-main-content" class="quiz-main-content">
                <div id="question-card-container"></div>

            </main>

            <aside class="quiz-navigation-panel">
                <div class="controls-card">
                    <h3>التنقل السريع</h3>
                    <div id="question-palette" class="question-palette"></div>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Calculator Modal -->
<div id="calculator-modal" class="modal-overlay">
    <div id="calculator" class="calculator-container">
        <div class="calculator-header" id="calculator-header">
            <span>آلة حاسبة</span>
            <button id="close-calculator-btn">&times;</button>
        </div>
        <input type="text" class="calculator-display" id="calculator-display" value="0" disabled>
        <div class="calculator-keys">
            <button value="all-clear" class="operator">AC</button>
            <button value="toggle-sign" class="operator">+/-</button>
            <button value="%" class="operator">%</button>
            <button value="/" class="operator">&divide;</button>
            <button value="7">7</button> <button value="8">8</button> <button value="9">9</button>
            <button value="*" class="operator">&times;</button>
            <button value="4">4</button> <button value="5">5</button> <button value="6">6</button>
            <button value="-" class="operator">&ndash;</button>
            <button value="1">1</button> <button value="2">2</button> <button value="3">3</button>
            <button value="+" class="operator">+</button>
            <button value="0">0</button> <button value=".">.</button>
            <button value="=" class="equal-sign operator">=</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modelId = <?= $model_id ?>;
    const userId = '<?= $user_id ?>';
    const loader = document.getElementById('quiz-loader');
    const quizContent = document.getElementById('quiz-content');
    const quizTitle = document.getElementById('quiz-title');
    const totalTimerEl = document.getElementById('total-timer');
    const questionCardContainer = document.getElementById('question-card-container');
    const paletteContainer = document.getElementById('question-palette');
    const showAnswerToggle = document.getElementById('show-answer-toggle');
    const calculatorModal = document.getElementById('calculator-modal');
    const calculator = document.getElementById('calculator');
    const calculatorDisplay = document.getElementById('calculator-display');
    const calculatorKeys = document.querySelector('.calculator-keys');
    const closeCalculatorBtn = document.getElementById('close-calculator-btn');

    let quizState = {};
    let totalTimerInterval, questionTimerInterval;
    let currentQuestionRemainingSeconds = 0;

    // Improved time formatting with hours support
    const formatTime = (seconds) => {
        if (isNaN(seconds)) return "00:00";
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hrs > 0) {
            return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const apiCall = async (action, data = {}) => {
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...data }),
            });
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            return { status: 'error', message: 'Network error occurred' };
        }
    };

    const renderPalette = () => {
        paletteContainer.innerHTML = '';
        quizState.questions.forEach((q, i) => {
            const btn = document.createElement('button');
            btn.className = 'palette-btn';
            btn.textContent = i + 1;
            btn.dataset.questionIndex = i;
            
            const status = quizState.question_statuses[i];
            if (status === 'answered') {
                btn.classList.add('answered');
            } else if (status === 'timed_out') {
                btn.classList.add('timed-out');
            }

            if (i === quizState.current_question_index) {
                btn.classList.add('current');
            }
            
            btn.addEventListener('click', () => {
                if (i !== quizState.current_question_index) {
                    handleNavigation(i);
                }
            });
            
            paletteContainer.appendChild(btn);
        });
    };

    const buildQuestion = (index) => {
        const question = quizState.questions[index];
        
        // Build options HTML
        let optionsHtml = '';
        if (question.options && Array.isArray(question.options)) {
            question.options.forEach((opt, i) => {
                const optionContent = opt.type === 'image' 
                    ? `<img src="${opt.value}" alt="Option ${i+1}" class="option-image-in-button">` 
                    : opt.value;
                
                optionsHtml += `
                    <button class="option-btn" data-question-index="${index}" data-answer-index="${i}">
                        <span class="option-letter">${String.fromCharCode(65 + i)}</span>
                        <span class="option-value">${optionContent}</span>
                    </button>
                `;
            });
        }

        // Build question HTML
        const questionImageHtml = question.question_image ? `
            <div class="question-image-wrapper">
                <img src="${question.question_image}" class="question-image" alt="Question Image">
            </div>
        ` : '';

        const fullscreenBtnHtml = question.question_image ? `
            <div style="text-align: center; margin-left: 1rem;">
                <button id="fullscreen-btn" title="عرض ملء الشاشة">⛶</button>
                <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px;">كبر الصورة من هنا</div>
            </div>
        ` : '';
        const photoIdHtml = question.question_image ? `<div id="photo-id-overlay"></div>` : '';
        
        const questionTextHtml = question.question_text ? `
            <div class="question-text-container">
                <div class="question-text">${question.question_text}</div>
            </div>
        ` : '';
        
        questionCardContainer.innerHTML = `
            <div id="question-content-block" class="question-block">
                <div class="question-header">
                    <h2>السؤال ${index + 1} من ${quizState.questions.length}</h2>
                    <div style="display: flex; align-items: center;">
                        ${photoIdHtml}
                        ${fullscreenBtnHtml}
                        <div id="question-timer-container" class="quiz-timer-box question-timer-box" style="display: none;">
                            <span class="timer-label">وقت السؤال</span>
                            <div id="question-timer" class="quiz-timer">00:00</div>
                        </div>
                    </div>
                </div>
                
                ${questionImageHtml}
                ${questionTextHtml}
            </div>

            <div id="question-options-block" class="question-block">
                <div class="options-container">
                    <div class="options-grid">${optionsHtml}</div>
                </div>
            </div>

            <div id="question-controls-block" class="question-block">
                <div class="in-question-controls">
                    <div class="control-buttons-grid">
                        <button id="prev-question-btn" class="btn btn-secondary">السؤال السابق</button>
                        <button id="next-question-btn" class="btn btn-secondary">السؤال التالي</button>
                        <button id="finish-quiz-btn" class="btn btn-primary" disabled>إنهاء الاختبار</button>
                    </div>
                </div>
            </div>
        `;
        
        // Populate Photo ID and initialize fullscreen
        const photoIdOverlay = questionCardContainer.querySelector('#photo-id-overlay');
        if (photoIdOverlay) {
            photoIdOverlay.textContent = `ID: ${userId}`;
        }

        const fullscreenBtn = questionCardContainer.querySelector('#fullscreen-btn');
        const imageWrapper = questionCardContainer.querySelector('.question-image-wrapper');
        if (fullscreenBtn && imageWrapper) {
            fullscreenBtn.addEventListener('click', () => {
                if (!document.fullscreenElement) {
                    imageWrapper.requestFullscreen().catch(err => {
                        alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
                    });
                } else {
                    document.exitFullscreen();
                }
            });
        }
        
        // Update UI state
        updateAnswerSelection(index);
        startQuestionTimer(question);
        showAnswerToggle.checked = false;

        // Disable options if timed out
        if (quizState.question_statuses[index] === 'timed_out') {
            questionCardContainer.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = true;
            });
        }
    };

    const updateAnswerSelection = (index) => {
        const userAnswer = quizState.answers[index];
        const optionButtons = questionCardContainer.querySelectorAll('.option-btn');
        
        optionButtons.forEach(btn => {
            btn.classList.remove('selected', 'correct');
            const btnAnswerIndex = parseInt(btn.dataset.answerIndex);
            
            if (userAnswer === btnAnswerIndex) {
                btn.classList.add('selected');
            }
        });
        
        updateNavButtons();
        renderPalette();
    };

    const updateNavButtons = () => {
        const prevBtn = questionCardContainer.querySelector('#prev-question-btn');
        const nextBtn = questionCardContainer.querySelector('#next-question-btn');
        const finishBtn = questionCardContainer.querySelector('#finish-quiz-btn');

        if (prevBtn) {
            prevBtn.disabled = quizState.current_question_index === 0;
        }
        if (nextBtn) {
            nextBtn.disabled = quizState.current_question_index === quizState.questions.length - 1;
        }
        
        const unansweredCount = quizState.answers.filter(a => a === null).length;
        if(finishBtn) {
            finishBtn.disabled = unansweredCount > 0;
            
            if (unansweredCount > 0) {
                finishBtn.textContent = `أجب على ${unansweredCount} سؤال${unansweredCount > 1 ? 'ات' : ''} لإنهاء الاختبار`;
            } else {
                finishBtn.textContent = 'إنهاء الاختبار';
            }
        }
    };

    const handleNavigation = async (newIndex) => {
        if (newIndex < 0 || newIndex >= quizState.questions.length) return;

        const lastQuestionIndex = quizState.current_question_index;
        
        const response = await apiCall('navigate_question', { 
            new_index: newIndex,
            last_question_index: lastQuestionIndex,
            remaining_time: currentQuestionRemainingSeconds
        });
        
        if (response.status === 'success') {
            // The server will update the session, we need to get the fresh state
            const stateResponse = await apiCall('get_quiz_state');
            if (stateResponse.status === 'success') {
                quizState = stateResponse.data;
                buildQuestion(newIndex);
            } else {
                alert('Error fetching updated quiz state.');
            }
        } else {
            alert('Error navigating to the next question.');
        }
    };

    // Event delegation for option buttons and navigation
    questionCardContainer.addEventListener('click', async (e) => {
        const optionBtn = e.target.closest('.option-btn');
        const prevBtn = e.target.closest('#prev-question-btn');
        const nextBtn = e.target.closest('#next-question-btn');
        const finishBtn = e.target.closest('#finish-quiz-btn');

        if (optionBtn && !optionBtn.disabled) {
            const qIndex = parseInt(optionBtn.dataset.questionIndex);
            const aIndex = parseInt(optionBtn.dataset.answerIndex);
            
            const response = await apiCall('answer_question', { 
                question_index: qIndex, 
                answer_index: aIndex 
            });
            
            if (response.status === 'success') {
                quizState.answers[qIndex] = aIndex;
                quizState.question_statuses[qIndex] = 'answered';
                updateAnswerSelection(qIndex);
            }
        } else if (prevBtn) {
            if (quizState.current_question_index > 0) {
                handleNavigation(quizState.current_question_index - 1);
            }
        } else if (nextBtn) {
            if (quizState.current_question_index < quizState.questions.length - 1) {
                handleNavigation(quizState.current_question_index + 1);
            }
        } else if (finishBtn && !finishBtn.disabled) {
            clearInterval(totalTimerInterval);
            clearInterval(questionTimerInterval);

            const response = await apiCall('finish_quiz');

            if (response.status === 'success') {
                window.location.href = `?view=quiz_result&session_id=${response.data.session_id}`;
            } else {
                alert(response.message || 'حدث خطأ أثناء إنهاء الاختبار. قد تكون جلستك قد انتهت.');
                if (response.message.includes('No active quiz session')) {
                    window.location.href = '?view=login';
                }
            }
        }
    });


    // Timer functions
    const startTotalTimer = () => {
        const timerType = quizState.model_details.timer_type;
        const totalTimerContainer = document.getElementById('total-timer-container');
        
        if (timerType === 'total_time' || timerType === 'both') {
            totalTimerContainer.style.display = 'block';
            let totalSeconds = quizState.model_details.total_time_seconds;
            
            // Adjust for time already spent
            const serverStartTime = quizState.start_time;
            const clientLoadTime = Math.floor(Date.now() / 1000);
            const elapsed = clientLoadTime - serverStartTime;
            totalSeconds = Math.max(0, totalSeconds - elapsed);

            totalTimerInterval = setInterval(() => {
                totalTimerEl.textContent = formatTime(totalSeconds);
                
                if (totalSeconds <= 0) {
                    clearInterval(totalTimerInterval);
                    finishBtn.click();
                }
                
                totalSeconds--;
            }, 1000);
        }
    };

    const startQuestionTimer = (question) => {
        clearInterval(questionTimerInterval);
        const timerType = quizState.model_details.timer_type;
        const questionTimerContainer = document.getElementById('question-timer-container');
        const questionTimerEl = document.getElementById('question-timer');
        
        if (question && (timerType === 'per_question' || timerType === 'both')) {
            const qIndex = quizState.current_question_index;
            if (question.time_limit_seconds > 0) {
                questionTimerContainer.style.display = 'block';
                questionTimerEl.parentElement.style.backgroundColor = 'var(--color-surface-1)';
                
                // Use the remaining time from the state
                let questionSeconds = quizState.question_remaining_times[qIndex];
                currentQuestionRemainingSeconds = questionSeconds;

                if (questionSeconds <= 0) {
                    // If time is already up when loading, handle it immediately
                    questionTimerEl.textContent = "انتهى الوقت!";
                    questionTimerEl.parentElement.style.backgroundColor = 'var(--color-error-container)';
                    handleTimeUp(true); // Pass a flag to indicate it's pre-timed-out
                    return;
                }
                
                questionTimerInterval = setInterval(() => {
                    questionTimerEl.textContent = formatTime(questionSeconds);
                    currentQuestionRemainingSeconds = questionSeconds;
                    
                    if (questionSeconds <= 0) {
                        clearInterval(questionTimerInterval);
                        handleTimeUp();
                    }
                    
                    questionSeconds--;
                }, 1000);
            } else {
                questionTimerContainer.style.display = 'none';
            }
        } else {
            questionTimerContainer.style.display = 'none';
        }
    };

    const handleTimeUp = async (isPreTimedOut = false) => {
        const qIndex = quizState.current_question_index;
        
        if (!isPreTimedOut) {
            // Mark as timed out on the server only if it just happened
            await apiCall('time_up_question', { question_index: qIndex });
        }

        // Update local state
        quizState.question_statuses[qIndex] = 'timed_out';
        quizState.answers[qIndex] = -1; // Mark answer as incorrect due to time out
        quizState.question_remaining_times[qIndex] = 0;
        currentQuestionRemainingSeconds = 0;

        // Update UI
        const questionTimerEl = document.getElementById('question-timer');
        if (questionTimerEl) {
            questionTimerEl.textContent = "انتهى الوقت!";
            questionTimerEl.parentElement.style.backgroundColor = 'var(--color-error-container)';
        }

        const question = quizState.questions[qIndex];
        const optionButtons = questionCardContainer.querySelectorAll('.option-btn');
        
        optionButtons.forEach(btn => {
            btn.disabled = true;
            // Show correct answer after time is up
            if (parseInt(btn.dataset.answerIndex) === question.correct) {
                btn.classList.add('correct');
            }
        });
        
        renderPalette(); // Update palette to show timed-out status
        updateNavButtons(); // Update finish button state

        // Auto-navigate after 3 seconds, unless it was already timed out when loaded
        if (!isPreTimedOut) {
            setTimeout(() => {
                if (quizState.current_question_index < quizState.questions.length - 1) {
                    nextBtn.click();
                } else {
                    // If it's the last question, attempt to finish the quiz
                    if (!finishBtn.disabled) {
                        finishBtn.click();
                    }
                }
            }, 3000);
        }
    };

    // Initialize quiz
    const startQuiz = async () => {
        const startResponse = await apiCall('ensure_quiz_session', { model_id: modelId });
        if (startResponse.status !== 'success') {
            loader.innerHTML = `
                <div style="text-align: center; max-width: 500px;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                    <h3 style="color: var(--color-error); margin-bottom: 1rem;">فشل بدء الاختبار</h3>
                    <p style="margin-bottom: 1.5rem;">${startResponse.message || 'حدث خطأ غير معروف'}</p>
                    <a href="?view=courses" class="btn btn-primary">العودة إلى الدورات</a>
                </div>
            `;
            return;
        }

        const stateResponse = await apiCall('get_quiz_state');
        if (stateResponse.status === 'success') {
            quizState = stateResponse.data;

            if (!quizState.questions || quizState.questions.length === 0) {
                quizContent.innerHTML = `
                    <div style="text-align: center; padding: 3rem;">
                        <h3 style="margin-bottom: 1rem;">لا توجد أسئلة</h3>
                        <p style="margin-bottom: 1.5rem;">هذا الاختبار لا يحتوي على أي أسئلة في الوقت الحالي.</p>
                        <a href="?view=courses" class="btn btn-primary">العودة إلى الدورات</a>
                    </div>
                `;
                loader.style.display = 'none';
                quizContent.style.display = 'block';
                return;
            }

            loader.style.display = 'none';
            quizContent.style.display = 'block';
            quizTitle.textContent = quizState.model_details.title;
            
            startTotalTimer();
            buildQuestion(quizState.current_question_index);
        } else {
            loader.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                    <h3 style="color: var(--color-error); margin-bottom: 1rem;">فشل تحميل الاختبار</h3>
                    <p style="margin-bottom: 1.5rem;">${stateResponse.message || 'حدث خطأ أثناء تحميل بيانات الاختبار'}</p>
                    <a href="?view=courses" class="btn btn-primary">العودة إلى الدورات</a>
                </div>
            `;
        }
    };

    // Calculator Logic
    const calculatorState = { 
        displayValue: '0', 
        firstOperand: null, 
        waitingForSecondOperand: false, 
        operator: null 
    };

    function inputDigit(digit) {
        const { displayValue, waitingForSecondOperand } = calculatorState;
        
        if (waitingForSecondOperand) {
            calculatorState.displayValue = digit;
            calculatorState.waitingForSecondOperand = false;
        } else {
            calculatorState.displayValue = displayValue === '0' ? digit : displayValue + digit;
        }
    }

    function inputDecimal(dot) {
        if (calculatorState.waitingForSecondOperand) return;
        
        if (!calculatorState.displayValue.includes(dot)) {
            calculatorState.displayValue += dot;
        }
    }

    function handleOperator(nextOperator) {
        const { firstOperand, displayValue, operator } = calculatorState;
        const inputValue = parseFloat(displayValue);
        
        if (operator && calculatorState.waitingForSecondOperand) {
            calculatorState.operator = nextOperator;
            return;
        }
        
        if (firstOperand == null && !isNaN(inputValue)) {
            calculatorState.firstOperand = inputValue;
        } else if (operator) {
            const result = performCalculation[operator](firstOperand, inputValue);
            calculatorState.displayValue = `${parseFloat(result.toFixed(7))}`;
            calculatorState.firstOperand = result;
        }
        
        calculatorState.waitingForSecondOperand = true;
        calculatorState.operator = nextOperator;
    }

    const performCalculation = {
        '/': (first, second) => first / second,
        '*': (first, second) => first * second,
        '+': (first, second) => first + second,
        '-': (first, second) => first - second,
        '=': (first, second) => second,
    };

    function resetCalculator() {
        calculatorState.displayValue = '0';
        calculatorState.firstOperand = null;
        calculatorState.waitingForSecondOperand = false;
        calculatorState.operator = null;
    }

    function updateDisplay() {
        calculatorDisplay.value = calculatorState.displayValue;
    }

    // Calculator event listeners
    calculatorKeys.addEventListener('click', (event) => {
        const { target } = event;
        if (!target.matches('button')) return;
        
        if (target.classList.contains('operator')) {
            handleOperator(target.value);
            updateDisplay();
            return;
        }
        
        if (target.value === '.') {
            inputDecimal(target.value);
            updateDisplay();
            return;
        }
        
        if (target.value === 'all-clear') {
            resetCalculator();
            updateDisplay();
            return;
        }
        
        if (target.value === 'toggle-sign') {
            calculatorState.displayValue = (parseFloat(calculatorState.displayValue) * -1).toString();
            updateDisplay();
            return;
        }
        
        if (target.value === '%') {
            calculatorState.displayValue = (parseFloat(calculatorState.displayValue) / 100).toString();
            updateDisplay();
            return;
        }
        
        inputDigit(target.value);
        updateDisplay();
    });
    
    // Calculator modal controls
    closeCalculatorBtn.addEventListener('click', () => {
        calculatorModal.classList.remove('visible');
    });
    
    calculatorModal.addEventListener('click', (e) => {
        if (e.target === calculatorModal) {
            calculatorModal.classList.remove('visible');
        }
    });

    // Draggable calculator
    let isDragging = false;
    let offset = { x: 0, y: 0 };
    const calculatorHeader = document.getElementById('calculator-header');
    
    calculatorHeader.addEventListener('mousedown', (e) => {
        isDragging = true;
        offset = {
            x: calculator.offsetLeft - e.clientX,
            y: calculator.offsetTop - e.clientY
        };
        calculatorHeader.style.cursor = 'grabbing';
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        
        calculator.style.left = `${e.clientX + offset.x}px`;
        calculator.style.top = `${e.clientY + offset.y}px`;
    });
    
    document.addEventListener('mouseup', () => {
        isDragging = false;
        calculatorHeader.style.cursor = 'move';
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevBtn.click();
        } else if (e.key === 'ArrowRight') {
            nextBtn.click();
        } else if (e.key >= '1' && e.key <= '9' && e.key <= quizState.questions.length) {
            handleNavigation(parseInt(e.key) - 1);
        } else if (e.key.toLowerCase() >= 'a' && e.key.toLowerCase() <= 'd') {
            const optionIndex = e.key.toLowerCase().charCodeAt(0) - 97;
            const optionBtn = document.querySelector(`.option-btn[data-answer-index="${optionIndex}"]`);
            if (optionBtn) optionBtn.click();
        }
    });

    // Start the quiz
    startQuiz();
});
</script>
