<?php
session_start();

// This page can be accessed by a logged-in admin OR a logged-in teacher.
// We need to handle both scenarios.

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_teacher = isset($_SESSION['teacher_id']);

if (!$is_admin && !$is_teacher) {
    // If neither is logged in, redirect to admin login as a default.
    header('Location: admin-login.php');
    exit;
}

// Check for course_id in the URL
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: admin.php');
    exit();
}
$course_id = intval($_GET['course_id']);

require_once 'config.php';
require_once 'functions.php'; // We need the supabase functions

// Security Check: Verify that the user has permission to view this page.
if ($is_teacher) {
    // If it's a teacher, they must own the course.
    $result = supabase_request('/rest/v1/courses?id=eq.' . $course_id . '&teacher_id=eq.' . $_SESSION['teacher_id'] . '&select=id', null, 'GET', true);
    if ($result['http_code'] !== 200 || empty($result['data'])) {
        // This teacher does not own this course, or an error occurred.
        require_once 'templates/teacher-header.php';
        $error_message = 'أنت لا تملك الصلاحية لإدارة نماذج هذا الاختبار لأنه لا ينتمي إليك.';
        $back_link = 'teacher/dashboard.php';
        require 'templates/access-denied.php';
        require_once 'templates/teacher-footer.php';
        exit;
    }
}
// If it's an admin, they can access any course, so no check is needed.

// Include the appropriate header
if ($is_teacher) {
    require_once 'templates/teacher-header.php';
} else {
    require_once 'templates/admin-header.php';
}
?>

<div class="container main-content">
<div class="page-header">
        <div class="header-actions">
            <h1 id="course-title-heading">إدارة نماذج الاختبار</h1>
            <?php if ($is_teacher): ?>
                <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> العودة للوحة التحكم</a>
            <?php else: ?>
                <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> العودة للاختبارات</a>
            <?php endif; ?>
        </div>
        <p class="lead">أضف نماذج جديدة أو قم بإدارة النماذج الحالية لهذا الاختبار.</p>
    </div>

    <div class="manage-models-container">
        <!-- Add New Quiz Model Panel -->
        <div class="add-model-panel">
            <h3><i class="fas fa-plus-circle"></i> إضافة نموذج جديد</h3>
            <form id="add-model-form">
                <div class="form-group">
                    <label for="model-title">عنوان النموذج</label>
                    <input type="text" id="model-title" name="title" class="form-control" placeholder="مثال: اختبار منتصف الفصل" required>
                </div>
                <div class="form-group">
                    <label for="timer-type">نوع المؤقت</label>
                    <select id="timer-type" name="timer_type" class="form-control" required>
                        <option value="per_question">مؤقت لكل سؤال</option>
                        <option value="total_time">مؤقت كلي للاختبار</option>
                        <option value="both">مؤقت لكل سؤال + مؤقت كلي</option>
                    </select>
                </div>
                <div class="form-group" id="total-time-group" style="display: none;">
                    <label for="total-time">الوقت الكلي (بالدقائق)</label>
                    <input type="number" id="total-time" name="total_time_minutes" class="form-control" min="1" placeholder="مثال: 45">
                </div>
                <div class="form-group">
                    <label for="is-visible">الحالة</label>
                    <select id="is-visible" name="is_visible" class="form-control">
                        <option value="true" selected>مرئي (يظهر للطلاب)</option>
                        <option value="false">مخفي (لا يظهر للطلاب)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width"><i class="fas fa-save"></i> إضافة النموذج</button>
            </form>
            <div id="form-response"></div>
        </div>

        <!-- Models List Panel -->
        <div class="models-list-panel">
            <div id="models-grid" class="models-grid-container">
                <!-- Model cards will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Model Modal -->
<div id="edit-model-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3><i class="fas fa-edit"></i> تعديل النموذج</h3>
        <form id="edit-model-form">
            <input type="hidden" id="edit-model-id" name="model_id">
            <div class="form-group">
                <label for="edit-model-title">عنوان النموذج</label>
                <input type="text" id="edit-model-title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit-timer-type">نوع المؤقت</label>
                <select id="edit-timer-type" name="timer_type" class="form-control" required>
                    <option value="per_question">مؤقت لكل سؤال</option>
                    <option value="total_time">مؤقت كلي للاختبار</option>
                    <option value="both">مؤقت لكل سؤال + مؤقت كلي</option>
                </select>
            </div>
            <div class="form-group" id="edit-total-time-group" style="display: none;">
                <label for="edit-total-time">الوقت الكلي (بالدقائق)</label>
                <input type="number" id="edit-total-time" name="total_time_minutes" class="form-control" min="1">
            </div>
            <div class="form-group">
                <label for="edit-is-visible">الحالة</label>
                <select id="edit-is-visible" name="is_visible" class="form-control">
                    <option value="true">مرئي (يظهر للطلاب)</option>
                    <option value="false">مخفي (لا يظهر للطلاب)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width"><i class="fas fa-save"></i> حفظ التعديلات</button>
        </form>
        <div id="edit-form-response"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
const courseId = <?= $course_id ?>;
    const isTeacher = <?= $is_teacher ? 'true' : 'false' ?>;
    const teacherId = <?= $is_teacher ? $_SESSION['teacher_id'] : 'null' ?>;
    const modelsGrid = document.getElementById('models-grid');
    const addModelForm = document.getElementById('add-model-form');
    const formResponse = document.getElementById('form-response');
    const courseTitleHeading = document.getElementById('course-title-heading');
    const timerTypeSelect = document.getElementById('timer-type');
    const totalTimeGroup = document.getElementById('total-time-group');

    // Edit Modal Elements
    const editModal = document.getElementById('edit-model-modal');
    const editForm = document.getElementById('edit-model-form');
    const editTimerTypeSelect = document.getElementById('edit-timer-type');
    const editTotalTimeGroup = document.getElementById('edit-total-time-group');
    const editFormResponse = document.getElementById('edit-form-response');

    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };
    
    timerTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        if (selectedType === 'total_time' || selectedType === 'both') {
            totalTimeGroup.style.display = 'block';
            document.getElementById('total-time').required = true;
        } else {
            totalTimeGroup.style.display = 'none';
            document.getElementById('total-time').required = false;
        }
    });

    const callApi = (action, data) => {
        return fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data }),
        }).then(res => res.json());
    };

    const renderModels = (models) => {
        modelsGrid.innerHTML = '';
        if (!models || models.length === 0) {
            modelsGrid.innerHTML = '<div class="models-placeholder"><h4>لا توجد نماذج بعد</h4><p>استخدم النموذج على اليسار لإضافة أول نموذج اختبار لهذا الاختبار.</p></div>';
            return;
        }

        const timerTypeMap = {
            'per_question': 'لكل سؤال',
            'total_time': 'إجمالي',
            'both': 'إجمالي ولكل سؤال'
        };

        models.forEach(model => {
            const timerTypeText = timerTypeMap[model.timer_type] || 'غير محدد';
            const totalTimeText = (model.timer_type === 'total_time' || model.timer_type === 'both') 
                ? `<li><i class="fas fa-clock"></i> الوقت الإجمالي: <strong>${model.total_time_seconds / 60} دقيقة</strong></li>`
                : '';

            const card = document.createElement('div');
            card.className = 'model-card';
            card.id = `model-card-${model.id}`;
            const visibilityText = model.is_visible ? 'مرئي' : 'مخفي';
            const visibilityClass = model.is_visible ? 'status-visible' : 'status-hidden';
            const toggleVisibilityText = model.is_visible ? 'إخفاء' : 'إظهار';
            const toggleVisibilityIcon = model.is_visible ? 'fa-eye-slash' : 'fa-eye';

            card.innerHTML = `
                <div class="model-card-header">
                    <h4>${escapeHTML(model.title)}</h4>
                    <span class="status-badge ${visibilityClass}">${visibilityText}</span>
                </div>
                <div class="model-card-body">
                    <ul class="model-card-stats">
                        <li><i class="fas fa-question-circle"></i> عدد الأسئلة: <strong>${model.questions_count || 0}</strong></li>
                        <li><i class="fas fa-stopwatch"></i> نوع المؤقت: <strong>${timerTypeText}</strong></li>
                        ${totalTimeText}
                        <li><i class="fas fa-star"></i> متوسط التقييم: <strong>${model.average_rating || 'N/A'}</strong></li>
                        <li><i class="fas fa-user-check"></i> عدد المحاولات: <strong>${model.attempts_count || 0}</strong></li>
                    </ul>
                </div>
                <div class="model-card-footer">
                    <a href="manage-questions.php?course_id=${courseId}&model_id=${model.id}" class="btn btn-primary"><i class="fas fa-edit"></i> إدارة الأسئلة</a>
                    <button class="btn btn-secondary edit-model-btn" data-id="${model.id}"><i class="fas fa-pencil-alt"></i> تعديل</button>
                    <button class="btn btn-warning toggle-visibility-btn" data-id="${model.id}"><i class="fas ${toggleVisibilityIcon}"></i> ${toggleVisibilityText}</button>
                    <button class="btn btn-danger delete-model-btn" data-id="${model.id}"><i class="fas fa-trash"></i> حذف</button>
                </div>
            `;
            modelsGrid.appendChild(card);
        });
    };

    const loadPageData = () => {
        callApi('get_course_details', { course_id: courseId })
            .then(response => {
                if (response.status === 'success') {
                    courseTitleHeading.textContent = `إدارة نماذج: ${escapeHTML(response.data.title)}`;
                } else {
                    courseTitleHeading.textContent = 'اختبار غير موجود';
                }
            });

        modelsGrid.innerHTML = '<div class="models-placeholder">جاري تحميل النماذج...</div>';
        callApi('get_quiz_models', { course_id: courseId })
            .then(response => {
                if (response.status === 'success' && Array.isArray(response.data)) {
                    renderModels(response.data);
                } else {
                    throw new Error(response.message || 'Failed to load models.');
                }
            })
            .catch(error => {
                console.error('Error fetching models:', error);
                modelsGrid.innerHTML = `<div class="models-placeholder" style="color:red;">فشل تحميل النماذج: ${error.message}</div>`;
            });
    };

    addModelForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const title = document.getElementById('model-title').value;
        const timer_type = timerTypeSelect.value;
        const total_time_minutes = document.getElementById('total-time').value;
        const is_visible = document.getElementById('is-visible').value === 'true';

        const data = {
            course_id: courseId,
            title: title,
            timer_type: timer_type,
            total_time_seconds: (timer_type === 'total_time' || timer_type === 'both') ? total_time_minutes * 60 : null,
            is_visible: is_visible,
            teacher_id: teacherId // Pass teacher_id if it's a teacher
        };

        formResponse.textContent = 'جاري الإضافة...';
        formResponse.className = 'loading';

        callApi('add_quiz_model', data)
            .then(result => {
                formResponse.textContent = result.message;
                formResponse.className = result.status;
                if (result.status === 'success') {
                    addModelForm.reset();
                    timerTypeSelect.dispatchEvent(new Event('change'));
                    loadPageData();
                }
                setTimeout(() => { formResponse.style.display = 'none'; }, 3000);
            })
            .catch(error => {
                console.error('Error adding model:', error);
                formResponse.textContent = `حدث خطأ في الاتصال: ${error.message}`;
                formResponse.className = 'error';
                setTimeout(() => { formResponse.style.display = 'none'; }, 3000);
            });
    });
    
    // --- Modal Handling ---
    const openEditModal = () => editModal.style.display = 'block';
    const closeEditModal = () => editModal.style.display = 'none';

    editModal.querySelector('.close-btn').addEventListener('click', closeEditModal);
    window.addEventListener('click', (event) => {
        if (event.target == editModal) {
            closeEditModal();
        }
    });

    editTimerTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        if (selectedType === 'total_time' || selectedType === 'both') {
            editTotalTimeGroup.style.display = 'block';
            document.getElementById('edit-total-time').required = true;
        } else {
            editTotalTimeGroup.style.display = 'none';
            document.getElementById('edit-total-time').required = false;
        }
    });

    modelsGrid.addEventListener('click', function(event) {
        const deleteBtn = event.target.closest('.delete-model-btn');
        const editBtn = event.target.closest('.edit-model-btn');
        const toggleBtn = event.target.closest('.toggle-visibility-btn');

        if (toggleBtn) {
            const modelId = toggleBtn.dataset.id;
            callApi('toggle_model_visibility', { model_id: modelId })
                .then(result => {
                    if (result.status === 'success') {
                        loadPageData();
                        // Optionally show a toast notification
                    } else {
                        alert('فشل تحديث حالة النموذج: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error toggling visibility:', error);
                    alert(`فشل تحديث الحالة: ${error.message}`);
                });
        } else if (deleteBtn) {
            const modelId = deleteBtn.dataset.id;
            if (confirm('هل أنت متأكد من حذف هذا النموذج؟ سيتم حذف جميع الأسئلة المرتبطة به بشكل نهائي.')) {
                callApi('delete_quiz_model', { model_id: modelId })
                    .then(result => {
                        alert(result.message);
                        if (result.status === 'success') {
                            loadPageData();
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting model:', error);
                        alert(`فشل الحذف: ${error.message}`);
                    });
            }
        } else if (editBtn) {
            const modelId = editBtn.dataset.id;
            callApi('get_quiz_model_details', { model_id: modelId })
                .then(response => {
                    if (response.status === 'success') {
                        const model = response.data;
                        document.getElementById('edit-model-id').value = model.id;
                        document.getElementById('edit-model-title').value = model.title;
                        document.getElementById('edit-is-visible').value = model.is_visible ? 'true' : 'false';
                        editTimerTypeSelect.value = model.timer_type;
                        if (model.timer_type === 'total_time' || model.timer_type === 'both') {
                            document.getElementById('edit-total-time').value = model.total_time_seconds / 60;
                        }
                        // Trigger change to show/hide time input correctly
                        editTimerTypeSelect.dispatchEvent(new Event('change'));
                        openEditModal();
                    } else {
                        alert('فشل تحميل تفاصيل النموذج: ' + response.message);
                    }
                });
        }
    });

    editForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const modelId = document.getElementById('edit-model-id').value;
        const title = document.getElementById('edit-model-title').value;
        const timer_type = editTimerTypeSelect.value;
        const total_time_minutes = document.getElementById('edit-total-time').value;
        const is_visible = document.getElementById('edit-is-visible').value === 'true';

        const data = {
            model_id: modelId,
            title: title,
            timer_type: timer_type,
            total_time_seconds: (timer_type === 'total_time' || timer_type === 'both') ? total_time_minutes * 60 : null,
            is_visible: is_visible,
        };

        editFormResponse.textContent = 'جاري التحديث...';
        editFormResponse.className = 'loading';

        callApi('update_quiz_model', data)
            .then(result => {
                editFormResponse.textContent = result.message;
                editFormResponse.className = result.status;
                if (result.status === 'success') {
                    setTimeout(() => {
                        closeEditModal();
                        loadPageData();
                        editFormResponse.textContent = '';
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error updating model:', error);
                editFormResponse.textContent = `حدث خطأ: ${error.message}`;
                editFormResponse.className = 'error';
            });
    });

    loadPageData();
});
</script>

<?php
// Include the appropriate footer
if ($is_teacher) {
    require_once 'templates/teacher-footer.php';
} else {
    require_once 'templates/admin-footer.php';
}
?>
