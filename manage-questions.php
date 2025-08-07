<?php
session_start();

// This page can be accessed by a logged-in admin OR a logged-in teacher.
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_teacher = isset($_SESSION['teacher_id']);

if (!$is_admin && !$is_teacher) {
    // If neither is logged in, redirect to admin login as a default.
    header('Location: admin-login.php');
    exit;
}

// Check for model_id in the URL
if (!isset($_GET['model_id']) || !is_numeric($_GET['model_id'])) {
    header('Location: admin.php');
    exit();
}
$model_id = intval($_GET['model_id']);
$course_id_for_back_button = $_GET['course_id'] ?? 0;

require_once 'config.php';
require_once 'functions.php'; // We need the supabase functions

// Security Check: Verify that the user has permission to manage this model.
if ($is_teacher) {
    // If it's a teacher, they must own the quiz model.
    $result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&teacher_id=eq.' . $_SESSION['teacher_id'] . '&select=id', null, 'GET', true);
    if ($result['http_code'] !== 200 || empty($result['data'])) {
        // This teacher does not own this model, or an error occurred.
        require_once 'templates/teacher-header.php';
        $error_message = 'أنت لا تملك الصلاحية لإدارة أسئلة هذا النموذج لأنه لا ينتمي إليك.';
        $back_link = 'teacher/dashboard.php';
        require 'templates/access-denied.php';
        require_once 'templates/teacher-footer.php';
        exit;
    }
}
// If it's an admin, they can access any model, so no check is needed.

// Include the appropriate header
if ($is_teacher) {
    require_once 'templates/teacher-header.php';
} else {
    require_once 'templates/admin-header.php';
}
?>
<style>
.question-card {
    position: relative;
    padding-left: 35px; /* Space for the drag handle */
}
.drag-handle {
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    cursor: grab;
    font-size: 24px;
    color: #aaa;
    line-height: 1;
}
.drag-handle:active {
    cursor: grabbing;
}
.question-actions .duplicate-question-btn {
    margin-left: 5px;
    background-color: #17a2b8; /* A different color to distinguish */
    border-color: #17a2b8;
    color: white;
}
.ghost-class {
    opacity: 0.4;
    background: #f0f8ff;
}
.question-actions .move-up-btn,
.question-actions .move-down-btn {
    margin-left: 5px;
    padding: 0.375rem 0.6rem;
    font-size: 0.9rem;
    line-height: 1;
}
</style>

<div class="container">
    <h1 id="model-title-heading" class="section-title">إدارة أسئلة نموذج: ...</h1>
    <div class="text-center mb-4">
        <?php if ($is_teacher): ?>
            <a id="back-to-models-link" href="<?php echo BASE_URL; ?>manage-models.php?course_id=<?= $course_id_for_back_button ?>" class="btn btn-secondary">العودة إلى قائمة النماذج</a>
        <?php else: ?>
            <a id="back-to-models-link" href="manage-models.php?course_id=<?= $course_id_for_back_button ?>" class="btn btn-secondary">العودة إلى قائمة النماذج</a>
        <?php endif; ?>
    </div>

    <div class="admin-grid">
        <!-- Add New Question Card -->
        <div class="admin-card">
            <h2 class="card-title">إضافة سؤال جديد</h2>
            <form id="add-question-form">
                <div class="form-group">
                    <label for="question-text">نص السؤال</label>
                    <div id="question-text-toolbar" class="mb-2">
                        <button type="button" id="format-bold" title="غامق"><b>B</b></button>
                        <button type="button" id="format-italic" title="مائل"><i>I</i></button>
                        <select id="format-fontsize" title="حجم الخط">
                            <option value="">حجم الخط</option>
                            <option value="x-small">صغير جداً</option>
                            <option value="small">صغير</option>
                            <option value="medium">عادي</option>
                            <option value="large">كبير</option>
                            <option value="x-large">كبير جداً</option>
                        </select>
                        <input type="color" id="format-color" title="لون الخط">
                    </div>
                    <textarea id="question-text" name="question_text" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>صورة السؤال (اختياري)</label>
                    <input type="hidden" id="question-image" name="question_image">
                    <div id="question-image-uploader" class="image-uploader"></div>
                </div>

                <h4 class="mt-4 mb-3">الخيارات (حدد الإجابة الصحيحة)</h4>
                <div id="options-container">
                    <?php
                    $default_options = ['أ', 'ب', 'ج', 'د'];
                    for ($i = 0; $i < 4; $i++):
                    ?>
                    <div class="option-group" id="option-group-<?= $i ?>">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <input type="radio" name="correct_option" value="<?= $i ?>" title="حدد كإجابة صحيحة">
                                </div>
                                <select class="custom-select option-type-selector" data-index="<?= $i ?>">
                                    <option value="text" selected>نص</option>
                                    <option value="image">صورة</option>
                                </select>
                            </div>
                            <input type="text" name="option_value_<?= $i ?>" class="form-control option-value" placeholder="قيمة الخيار <?= $i + 1 ?>" value="<?= htmlspecialchars($default_options[$i]) ?>">
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="form-group">
                    <label for="explanation">شرح الإجابة</label>
                    <textarea id="explanation" name="explanation" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group" id="time-limit-group">
                    <label for="time-limit">الوقت المحدد للسؤال (بالثواني)</label>
                    <input type="number" id="time-limit" name="time_limit_seconds" class="form-control" value="60" required min="10">
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" id="needs-calculator" name="needs_calculator" value="1">
                    <label for="needs-calculator">يتطلب آلة حاسبة؟</label>
                </div>

                <button type="submit" class="btn btn-primary btn-full-width">إضافة السؤال</button>
            </form>
            <div id="form-response"></div>
        </div>
    </div>

    <!-- Display Existing Questions -->
    <div class="admin-card mt-5">
        <h2 id="questions-count-heading" class="card-title">الأسئلة الحالية (0)</h2>
        <div class="questions-list" id="questions-list">
            <!-- Questions will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div id="edit-question-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>تعديل السؤال</h2>
        <form id="edit-question-form">
            <input type="hidden" id="edit-question-id" name="question_id">
            <div class="form-group">
                <label for="edit-question-text">نص السؤال</label>
                <div id="edit-question-text-toolbar" class="mb-2">
                    <button type="button" id="edit-format-bold" title="غامق"><b>B</b></button>
                    <button type="button" id="edit-format-italic" title="مائل"><i>I</i></button>
                    <select id="edit-format-fontsize" title="حجم الخط">
                        <option value="">حجم الخط</option>
                        <option value="x-small">صغير جداً</option>
                        <option value="small">صغير</option>
                        <option value="medium">عادي</option>
                        <option value="large">كبير</option>
                        <option value="x-large">كبير جداً</option>
                    </select>
                    <input type="color" id="edit-format-color" title="لون الخط">
                </div>
                <textarea id="edit-question-text" name="question_text" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>صورة السؤال (اختياري)</label>
                <input type="hidden" id="edit-question-image" name="question_image">
                <div id="edit-question-image-uploader" class="image-uploader"></div>
            </div>
            <h4 class="mt-4 mb-3">الخيارات (حدد الإجابة الصحيحة)</h4>
            <div id="edit-options-container">
                <!-- Options will be populated by JS -->
            </div>
            <div class="form-group">
                <label for="edit-explanation">شرح الإجابة</label>
                <textarea id="edit-explanation" name="explanation" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="edit-time-limit">الوقت المحدد للسؤال (بالثواني)</label>
                <input type="number" id="edit-time-limit" name="time_limit_seconds" class="form-control" required min="10">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="edit-needs-calculator" name="needs_calculator" value="1">
                <label for="edit-needs-calculator">يتطلب آلة حاسبة؟</label>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width">حفظ التعديلات</button>
            <button type="button" id="cancel-edit-btn" class="btn btn-secondary btn-full-width mt-2">إلغاء</button>
        </form>
        <div id="edit-form-response"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imgbbApiKey = 'c336ec0003cbeb6d6141d600ee00a176';
    const modelId = <?= $model_id ?>;
    const modelTitleHeading = document.getElementById('model-title-heading');
    const backToModelsLink = document.getElementById('back-to-models-link');
    const questionsListDiv = document.getElementById('questions-list');
    const questionsCountHeading = document.getElementById('questions-count-heading');
    const addQuestionForm = document.getElementById('add-question-form');
    const formResponse = document.getElementById('form-response');

    // --- Edit Modal Elements ---
    const editModal = document.getElementById('edit-question-modal');
    const editForm = document.getElementById('edit-question-form');
    const editFormResponse = document.getElementById('edit-form-response');
    const closeModalBtn = editModal.querySelector('.close-btn');
    
    let allQuestionsData = []; // To store the full data of all questions
    let modelTimerType = 'per_question'; // Default value

    // --- Utility function to safely escape HTML ---
    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };
    const nl2br = (str) => escapeHTML(str).replace(/(?:\r\n|\r|\n)/g, '<br>');

    // --- API Call Function ---
    const callApi = (action, data) => {
        return fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data }),
        }).then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || `HTTP error! status: ${response.status}`) });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Failed to parse JSON:", text);
                    throw new Error("Received non-JSON response from server.");
                }
            });
        });
    };

    // --- ImgBB Upload Function ---
    const uploadToImgBB = async (file) => {
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            const response = await fetch(`https://api.imgbb.com/1/upload?key=${imgbbApiKey}`, {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();
            if (result.success) {
                return result.data.url;
            } else {
                throw new Error(result.error.message || 'فشل رفع الصورة إلى ImgBB');
            }
        } catch (error) {
            console.error('ImgBB Upload Error:', error);
            throw error;
        }
    };

    // --- Renders questions ---
    const renderQuestions = (questions) => {
        allQuestionsData = questions;
        questionsListDiv.innerHTML = '';
        questionsCountHeading.textContent = `الأسئلة الحالية (${questions.length})`;
        if (questions.length === 0) {
            questionsListDiv.innerHTML = '<p class="text-center p-4">لا توجد أسئلة في هذا الاختبار حتى الآن.</p>';
            return;
        }
        questions.forEach((question, index) => {
            const questionImageContent = (question.question_image) ? `<img src="${escapeHTML(question.question_image)}" class="question-image">` : '';
            const questionTextContent = question.question_text ? `<div class="question-text-container">${question.question_text}</div>` : '';
            const optionsList = (question.options || []).map((option, opt_index) => {
                let content = (option.type === 'image') ? `<img src="${escapeHTML(option.value)}" class="option-image">` : option.value;
                return `<li class="${opt_index === question.correct ? 'correct-answer' : ''}">${content}</li>`;
            }).join('');
            const card = document.createElement('div');
            card.className = 'question-card';
            card.dataset.questionId = question.id; // Set data attribute for sorting
            card.id = `question-card-${question.id}`;
            const totalQuestions = questions.length;
            card.innerHTML = `
                <div class="drag-handle" title="اسحب لتغيير الترتيب">⋮⋮</div>
                <div class="question-header">
                    <strong>سؤال ${question.question_order || index + 1}</strong>
                    <div class="question-meta">
                        <span><i class="fas fa-stopwatch"></i> ${escapeHTML(question.time_limit_seconds || 60)} ثانية</span>
                        ${question.needs_calculator ? '<span><i class="fas fa-calculator"></i></span>' : ''}
                    </div>
                    <div class="question-actions">
                        <button class="btn btn-light move-up-btn" data-question-id="${question.id}" title="رفع للأعلى">▲</button>
                        <button class="btn btn-light move-down-btn" data-question-id="${question.id}" title="تنزيل للأسفل">▼</button>
                        <button class="btn btn-info duplicate-question-btn" data-question-id="${question.id}" title="نسخ السؤال">📄</button>
                        <button class="btn btn-secondary edit-question-btn" data-question-id="${question.id}">تعديل</button>
                        <button class="btn btn-danger delete-question-btn" data-question-id="${question.id}">حذف</button>
                    </div>
                </div>
                <div class="question-body">
                    ${questionTextContent}
                    ${questionImageContent}
                    <ol class="options-list">${optionsList}</ol>
                    <div class="explanation"><strong>الشرح:</strong> ${question.explanation || 'لا يوجد شرح.'}</div>
                </div>`;
            questionsListDiv.appendChild(card);
        });
    };

    // --- Modal Logic ---
    const openEditModal = (question) => {
        if (!question) return;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = question.question_text;
        const plainText = tempDiv.textContent || tempDiv.innerText || "";
        document.getElementById('edit-question-id').value = question.id;
        document.getElementById('edit-question-text').value = plainText;
        // document.getElementById('edit-question-image').value = question.question_image || ''; // Replaced by uploader
        updateQuestionImageUI(document.getElementById('edit-question-image-uploader'), document.getElementById('edit-question-image'), question.question_image || '');
        document.getElementById('edit-explanation').value = question.explanation || '';
        document.getElementById('edit-time-limit').value = question.time_limit_seconds || 60;
        document.getElementById('edit-needs-calculator').checked = !!question.needs_calculator;
        const editTimeLimitGroup = document.getElementById('edit-time-limit').parentElement;
        if (modelTimerType === 'total_time') {
            editTimeLimitGroup.style.display = 'none';
            document.getElementById('edit-time-limit').required = false;
        } else {
            editTimeLimitGroup.style.display = 'block';
            document.getElementById('edit-time-limit').required = true;
        }
        const optionsContainer = document.getElementById('edit-options-container');
        optionsContainer.innerHTML = '';
        (question.options || []).forEach((option, index) => {
            const isChecked = index === question.correct;
            const optionDiv = document.createElement('div');
            optionDiv.className = 'option-group mb-3';
            optionDiv.innerHTML = `
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <input type="radio" name="edit_correct_option" value="${index}" ${isChecked ? 'checked' : ''}>
                        </div>
                        <select class="custom-select option-type-selector" data-index="${index}">
                            <option value="text" ${option.type === 'text' ? 'selected' : ''}>نص</option>
                            <option value="image" ${option.type === 'image' ? 'selected' : ''}>صورة</option>
                        </select>
                    </div>
                    <input type="hidden" class="form-control option-value" name="edit_option_value_${index}" value="${escapeHTML(option.value)}">
                    <div class="option-display"></div>
                </div>`;
            optionsContainer.appendChild(optionDiv);
            const displayContainer = optionDiv.querySelector('.option-display');
            updateOptionDisplay(displayContainer, option.type, option.value);
        });
        editModal.style.display = 'block';
    };

    const closeEditModal = () => {
        editModal.style.display = 'none';
        editForm.reset();
        editFormResponse.textContent = '';
        editFormResponse.className = '';
    };

    // --- Fetches model details and questions ---
    const loadPageData = () => {
        callApi('get_quiz_model_details', { model_id: modelId }).then(response => {
            if (response.status === 'success' && response.data) {
                const model = response.data;
                modelTimerType = model.timer_type;
                const course = model.courses;
                modelTitleHeading.innerHTML = `إدارة أسئلة نموذج: "${escapeHTML(model.title)}" <br><small>التابع لاختبار: "${escapeHTML(course.title)}"</small>`;
                if (course.id) {
                    backToModelsLink.href = `manage-models.php?course_id=${course.id}`;
                }
                const timeLimitGroup = document.getElementById('time-limit-group');
                if (model.timer_type === 'total_time') {
                    timeLimitGroup.style.display = 'none';
                    document.getElementById('time-limit').required = false;
                } else {
                    timeLimitGroup.style.display = 'block';
                    document.getElementById('time-limit').required = true;
                }
            } else {
                modelTitleHeading.textContent = 'نموذج غير موجود';
            }
        });
        questionsListDiv.innerHTML = '<p style="text-align:center;">جاري تحميل الأسئلة...</p>';
        callApi('get_course_questions', { model_id: modelId }).then(response => {
            if (response.status === 'success' && Array.isArray(response.data)) {
                renderQuestions(response.data);
            } else {
                throw new Error(response.message || 'Failed to load questions.');
            }
        }).catch(error => {
            console.error('Error fetching questions:', error);
            questionsListDiv.innerHTML = `<p style="text-align:center; color:red;">فشل تحميل الأسئلة: ${error.message}</p>`;
        });
    };

    // --- Main Form Submission Logic ---
    const handleFormSubmit = async (formElement, responseElement) => {
        responseElement.textContent = 'جاري التحقق من البيانات...';
        responseElement.className = 'loading';

        const formData = new FormData(formElement);
        const options = [];
        const optionElements = formElement.querySelectorAll('.option-group');

        optionElements.forEach(group => {
            const type = group.querySelector('.option-type-selector').value;
            const valueInput = group.querySelector('.option-value');
            if (valueInput && valueInput.value) {
                options.push({ type, value: valueInput.value });
            }
        });

        if (options.length < 2) {
            alert('يجب إدخال خيارين على الأقل.');
            responseElement.textContent = '';
            return;
        }
        const correctOption = formData.get('correct_option') || formData.get('edit_correct_option');
        if (correctOption === null) {
            alert('يجب تحديد الإجابة الصحيحة.');
            responseElement.textContent = '';
            return;
        }

        try {
            const isEdit = formElement.id === 'edit-question-form';
            const questionTextarea = formElement.querySelector(isEdit ? '#edit-question-text' : '#question-text');
            const fontsizeSelect = formElement.querySelector(isEdit ? '#edit-format-fontsize' : '#format-fontsize');
            let questionText = questionTextarea.value;
            const textareaStyle = questionTextarea.style;
            const styles = [];
            if (textareaStyle.fontStyle === 'italic') styles.push('font-style: italic');
            if (textareaStyle.fontWeight === 'bold') styles.push('font-weight: bold');
            if (fontsizeSelect.value) styles.push(`font-size: ${fontsizeSelect.value}`);
            if (textareaStyle.color) styles.push(`color: ${textareaStyle.color}`);
            let styledQuestionText = styles.length > 0 ? `<span style="${styles.join('; ')}">${questionText}</span>` : questionText;

            const data = {
                question_text: styledQuestionText,
                question_image: formData.get('question_image') || null,
                options: options,
                correct: parseInt(correctOption, 10),
                explanation: formData.get('explanation'),
                needs_calculator: formData.get('needs_calculator') ? true : false,
                time_limit_seconds: parseInt(formData.get('time_limit_seconds'), 10) || 60
            };

            let result;
            if (isEdit) {
                data.question_id = formData.get('question_id');
                responseElement.textContent = 'جاري حفظ التعديلات...';
                result = await callApi('update_question', data);
            } else {
                data.model_id = modelId;
                responseElement.textContent = 'جاري إضافة السؤال...';
                result = await callApi('add_question', data);
            }

            responseElement.textContent = result.message;
            if (result.status === 'success') {
                responseElement.className = 'success';
                if (isEdit) {
                    setTimeout(() => {
                        closeEditModal();
                        loadPageData();
                    }, 1000);
                } else {
                    formElement.reset();
                    questionTextarea.style.cssText = '';
                    const defaultOptions = ['أ', 'ب', 'ج', 'د'];
                    formElement.querySelectorAll('.option-group').forEach((group, index) => {
                        const selector = group.querySelector('.option-type-selector');
                        selector.value = 'text';
                        // Reset the display with the default value
                        const defaultValue = defaultOptions[index] || '';
                        updateOptionDisplay(group.querySelector('.option-display'), 'text', defaultValue);
                    });
                    // Also reset the question image uploader
                    updateQuestionImageUI(document.getElementById('question-image-uploader'), document.getElementById('question-image'), '');
                    loadPageData();
                }
            } else {
                responseElement.className = 'error';
            }
        } catch (error) {
            console.error('Form submission error:', error);
            responseElement.textContent = `حدث خطأ: ${error.message}`;
            responseElement.className = 'error';
        }
    };

    addQuestionForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(addQuestionForm, formResponse);
    });
    editForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(editForm, editFormResponse);
    });

    // --- Helper function to update order via API ---
    const updateOrderFromDOM = () => {
        const questionCards = questionsListDiv.querySelectorAll('.question-card');
        const orderedIds = Array.from(questionCards).map(card => card.dataset.questionId);
        
        questionsListDiv.style.opacity = '0.5';

        callApi('update_question_order', { ordered_ids: orderedIds })
            .then(result => {
                if (result.status === 'success') {
                    loadPageData(); 
                } else {
                    alert('فشل تحديث الترتيب: ' + result.message);
                    loadPageData();
                }
            })
            .catch(error => {
                console.error('Error updating order:', error);
                alert('حدث خطأ فادح أثناء تحديث الترتيب.');
                loadPageData();
            })
            .finally(() => {
                questionsListDiv.style.opacity = '1';
            });
    };

    // --- Main Event Listener for All Actions ---
    questionsListDiv.addEventListener('click', function(event) {
        const targetButton = event.target.closest('button');
        if (!targetButton) return;

        const questionId = targetButton.dataset.questionId;
        const card = targetButton.closest('.question-card');

        if (targetButton.classList.contains('delete-question-btn')) {
            if (confirm('هل أنت متأكد من حذف هذا السؤال؟')) {
                callApi('delete_question', { question_id: questionId })
                    .then(handleApiResponse)
                    .catch(handleApiError);
            }
        } else if (targetButton.classList.contains('edit-question-btn')) {
            const questionToEdit = allQuestionsData.find(q => q.id == questionId);
            openEditModal(questionToEdit);
        } else if (targetButton.classList.contains('duplicate-question-btn')) {
            if (confirm('هل أنت متأكد من نسخ هذا السؤال؟ سيتم إنشاء نسخة جديدة في نهاية القائمة.')) {
                callApi('duplicate_question', { question_id: questionId })
                    .then(handleApiResponse)
                    .catch(handleApiError);
            }
        } else if (targetButton.classList.contains('move-up-btn')) {
            if (card && card.previousElementSibling) {
                card.parentElement.insertBefore(card, card.previousElementSibling);
                updateOrderFromDOM();
            }
        } else if (targetButton.classList.contains('move-down-btn')) {
            if (card && card.nextElementSibling) {
                card.parentElement.insertBefore(card.nextElementSibling, card);
                updateOrderFromDOM();
            }
        }
    });

    // --- Helper functions for API responses ---
    const handleApiResponse = (result) => {
        // Don't show alert for successful moves, just reload
        if (result.status === 'success') {
            if (!result.message.includes('نقل')) {
                 alert(result.message);
            }
            loadPageData();
        } else {
            alert('فشل الإجراء: ' + result.message);
        }
    };

    const handleApiError = (error) => {
        console.error('API Error:', error);
        alert(`حدث خطأ: ${error.message}`);
    };

    // --- UI Update and Event Handling Logic ---
    const updateOptionDisplay = (displayContainer, type, value = '') => {
        if (!displayContainer) return;
        const optionGroup = displayContainer.closest('.option-group');
        const valueInput = optionGroup.querySelector('.option-value');
        valueInput.value = value; // Store value in hidden input

        displayContainer.innerHTML = ''; // Clear previous content

        if (type === 'text') {
            const textInput = document.createElement('input');
            textInput.type = 'text';
            textInput.className = 'form-control';
            textInput.placeholder = `قيمة الخيار...`;
            textInput.value = value;
            textInput.addEventListener('input', (e) => {
                valueInput.value = e.target.value;
            });
            displayContainer.appendChild(textInput);
        } else if (type === 'image') {
            if (value) { // Image is set
                const preview = document.createElement('div');
                preview.className = 'image-preview-container';
                preview.innerHTML = `<img src="${escapeHTML(value)}" alt="معاينة" style="max-width: 100px; max-height: 50px; vertical-align: middle; margin-right: 10px;">`;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-danger btn-sm cancel-image-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = 'إزالة الصورة';
                preview.appendChild(removeBtn);
                displayContainer.appendChild(preview);
            } else { // No image, show choices
                const choiceContainer = document.createElement('div');
                choiceContainer.className = 'image-choice-container';
                choiceContainer.innerHTML = `
                    <button type="button" class="btn btn-sm btn-primary paste-choice-btn" style="margin-left: 5px;">لصق صورة</button>
                    <button type="button" class="btn btn-sm btn-secondary select-file-choice-btn">اختيار ملف</button>
                `;
                displayContainer.appendChild(choiceContainer);
            }
        }
    };

    const handleOptionTypeChange = (event) => {
        if (!event.target.classList.contains('option-type-selector')) return;
        const displayContainer = event.target.closest('.option-group').querySelector('.option-display');
        updateOptionDisplay(displayContainer, event.target.value, '');
    };

    const handleOptionAction = async (event) => {
        const target = event.target;
        const displayContainer = target.closest('.option-display');
        if (!displayContainer) return;

        const valueInput = displayContainer.closest('.option-group').querySelector('.option-value');
        
        const showLoading = (isLoading) => {
            if (isLoading) {
                displayContainer.innerHTML = '<i>جاري الرفع...</i>';
            } else {
                updateOptionDisplay(displayContainer, 'image', ''); // Reset to choices
            }
        };

        // Handle Paste Choice
        if (target.classList.contains('paste-choice-btn')) {
            try {
                const permission = await navigator.permissions.query({ name: 'clipboard-read' });
                if (permission.state === 'denied') throw new Error('تم رفض إذن الوصول للحافظة.');
                
                const clipboardItems = await navigator.clipboard.read();
                const imageItem = clipboardItems.find(item => item.types.some(type => type.startsWith('image/')));
                if (!imageItem) {
                    alert('لم يتم العثور على صورة في الحافظة.');
                    return;
                }
                
                const imageType = imageItem.types.find(type => type.startsWith('image/'));
                const blob = await imageItem.getType(imageType);
                const file = new File([blob], "pasted_image.png", { type: imageType });
                
                showLoading(true);
                const url = await uploadToImgBB(file);
                updateOptionDisplay(displayContainer, 'image', url);

            } catch (error) {
                alert(`فشل اللصق: ${error.message}`);
                showLoading(false);
            }
        }

        // Handle Select File Choice
        if (target.classList.contains('select-file-choice-btn')) {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.style.display = 'none';
            fileInput.addEventListener('change', async () => {
                if (fileInput.files.length > 0) {
                    showLoading(true);
                    try {
                        const url = await uploadToImgBB(fileInput.files[0]);
                        updateOptionDisplay(displayContainer, 'image', url);
                    } catch (error) {
                        alert(`فشل الرفع: ${error.message}`);
                        showLoading(false);
                    }
                }
                fileInput.remove();
            });
            document.body.appendChild(fileInput);
            fileInput.click();
        }

        // Handle Cancel/Remove Image
        if (target.classList.contains('cancel-image-btn')) {
            updateOptionDisplay(displayContainer, 'image', '');
        }
    };

    // Attach Event Listeners
    document.getElementById('options-container').addEventListener('change', handleOptionTypeChange);
    document.getElementById('options-container').addEventListener('click', handleOptionAction);
    document.getElementById('edit-options-container').addEventListener('change', handleOptionTypeChange);
    document.getElementById('edit-options-container').addEventListener('click', handleOptionAction);

    // --- Question Image Uploader Logic ---
    const updateQuestionImageUI = (displayContainer, valueInput, value = '') => {
        if (!displayContainer || !valueInput) return;
        valueInput.value = value;
        displayContainer.innerHTML = '';

        if (value) { // Image is set
            const preview = document.createElement('div');
            preview.className = 'image-preview-container';
            preview.innerHTML = `<img src="${escapeHTML(value)}" alt="معاينة" style="max-width: 150px; max-height: 75px; vertical-align: middle; margin-right: 10px;">`;
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm cancel-image-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'إزالة الصورة';
            preview.appendChild(removeBtn);
            displayContainer.appendChild(preview);
        } else { // No image, show choices
            const choiceContainer = document.createElement('div');
            choiceContainer.className = 'image-choice-container';
            choiceContainer.innerHTML = `
                <button type="button" class="btn btn-sm btn-primary paste-choice-btn" style="margin-left: 5px;">لصق صورة</button>
                <button type="button" class="btn btn-sm btn-secondary select-file-choice-btn">اختيار ملف</button>
            `;
            displayContainer.appendChild(choiceContainer);
        }
    };

    const handleQuestionImageAction = async (event) => {
        const uploaderContainer = event.target.closest('.image-uploader');
        if (!uploaderContainer) return;

        const valueInput = uploaderContainer.previousElementSibling;
        const displayContainer = uploaderContainer;

        const showLoading = (isLoading) => {
            if (isLoading) {
                displayContainer.innerHTML = '<i>جاري الرفع...</i>';
            } else {
                updateQuestionImageUI(displayContainer, valueInput, '');
            }
        };

        if (event.target.classList.contains('paste-choice-btn')) {
            try {
                const clipboardItems = await navigator.clipboard.read();
                const imageItem = clipboardItems.find(item => item.types.some(type => type.startsWith('image/')));
                if (!imageItem) { alert('لم يتم العثور على صورة في الحافظة.'); return; }
                const imageType = imageItem.types.find(type => type.startsWith('image/'));
                const blob = await imageItem.getType(imageType);
                const file = new File([blob], "pasted_image.png", { type: imageType });
                showLoading(true);
                const url = await uploadToImgBB(file);
                updateQuestionImageUI(displayContainer, valueInput, url);
            } catch (error) {
                alert(`فشل اللصق: ${error.message}`);
                showLoading(false);
            }
        }

        if (event.target.classList.contains('select-file-choice-btn')) {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.addEventListener('change', async () => {
                if (fileInput.files.length > 0) {
                    showLoading(true);
                    try {
                        const url = await uploadToImgBB(fileInput.files[0]);
                        updateQuestionImageUI(displayContainer, valueInput, url);
                    } catch (error) {
                        alert(`فشل الرفع: ${error.message}`);
                        showLoading(false);
                    }
                }
                fileInput.remove();
            });
            fileInput.click();
        }

        if (event.target.classList.contains('cancel-image-btn')) {
            updateQuestionImageUI(displayContainer, valueInput, '');
        }
    };

    document.getElementById('question-image-uploader').addEventListener('click', handleQuestionImageAction);
    document.getElementById('edit-question-image-uploader').addEventListener('click', handleQuestionImageAction);


    // --- Initial Load ---
    loadPageData();

    // --- SortableJS Initialization ---
    new Sortable(questionsListDiv, {
        animation: 150,
        handle: '.drag-handle', // Restrict dragging to the handle
        ghostClass: 'ghost-class', // Class for the drop placeholder
        forceFallback: true, // يساعد على التوافق مع المتصفحات وتفعيل التمرير
        scroll: document.documentElement, // تحديد الصفحة بأكملها كعنصر قابل للتمرير
        scrollSensitivity: 100, // المسافة من الحافة (بالبكسل) لبدء التمرير
        scrollSpeed: 15, // سرعة التمرير
        onEnd: function (evt) {
            updateOrderFromDOM();
        }
    });
    
    // Initial setup for add form
    addQuestionForm.querySelectorAll('.option-group').forEach(group => {
        const display = document.createElement('div');
        display.className = 'option-display';
        group.querySelector('.input-group').appendChild(display);
        const value = group.querySelector('.option-value').value;
        const type = group.querySelector('.option-type-selector').value;
        group.querySelector('.option-value').type = 'hidden'; // Hide original input
        updateOptionDisplay(display, type, value);
    });
    // Initial setup for question image uploader
    updateQuestionImageUI(document.getElementById('question-image-uploader'), document.getElementById('question-image'), '');

    // --- Text Formatting Toolbar Logic ---
    const setupFormattingToolbar = (containerId, textareaId) => {
        const container = document.getElementById(containerId);
        const textarea = document.getElementById(textareaId);
        if (!container || !textarea) return;

        container.querySelector('button[id*="bold"]').addEventListener('click', () => {
            const isBold = textarea.style.fontWeight === 'bold';
            textarea.style.fontWeight = isBold ? '' : 'bold';
        });
        container.querySelector('button[id*="italic"]').addEventListener('click', () => {
            const isItalic = textarea.style.fontStyle === 'italic';
            textarea.style.fontStyle = isItalic ? '' : 'italic';
        });
        container.querySelector('select[id*="fontsize"]').addEventListener('change', (e) => {
            textarea.style.fontSize = e.target.value;
        });
        container.querySelector('input[id*="color"]').addEventListener('input', (e) => {
            textarea.style.color = e.target.value;
        });
    };

    setupFormattingToolbar('question-text-toolbar', 'question-text');
    setupFormattingToolbar('edit-question-text-toolbar', 'edit-question-text');
    
    closeModalBtn.addEventListener('click', closeEditModal);
    document.getElementById('cancel-edit-btn').addEventListener('click', closeEditModal);
});
</script>

<!-- Include SortableJS library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<?php
// Include the appropriate footer
if ($is_teacher) {
    require_once 'templates/teacher-footer.php';
} else {
    require_once 'templates/admin-footer.php';
}
?>
