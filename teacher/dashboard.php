<?php
require_once 'auth.php'; // Protect this page, it includes config.php
// The line below is now redundant because auth.php includes config.php, but it's good practice to be explicit.
// require_once '../config.php'; 
require_once '../templates/teacher-header.php'; // Use the new teacher header
?>

<div class="container">
    <div class="page-header">
        <p class="lead">مرحباً بك في لوحة التحكم الخاصة بك. من هنا يمكنك إدارة دوراتك ونماذج الاختبارات.</p>
    </div>

    <div class="admin-card">
        <div class="card-title-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 class="card-title" style="margin: 0;">دوراتي</h2>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addCourseModal"><i class="fas fa-plus"></i> إضافة دورة جديدة</button>
        </div>
        
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>عنوان الدورة</th>
                        <th>الحالة</th>
                        <th>الرؤية</th>
                        <th>عدد النماذج</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="courses-table-body">
                    <!-- Course rows will be populated by JavaScript -->
                </tbody>
            </table>
            <div id="no-courses-placeholder" style="display: none; text-align: center; padding: 2rem; color: var(--muted-text-color);">
                <p>لم تقم بإضافة أي دورات بعد. ابدأ بإضافة دورتك الأولى!</p>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal" id="editCourseModal">
    <div class="modal-content">
        <span class="close-btn" data-dismiss="modal">&times;</span>
        <h2>تعديل الدورة</h2>
        <form id="edit-course-form">
            <input type="hidden" id="edit-course-id" name="course_id">
            <div class="form-group">
                <label for="edit-course-title">عنوان الدورة</label>
                <input type="text" id="edit-course-title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit-course-description">وصف الدورة</label>
                <textarea id="edit-course-description" name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="edit-course-category">الفئة</label>
                <input type="text" id="edit-course-category" name="category" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit-course-type">نوع الدورة</label>
                <select id="edit-course-type" name="course_type" class="form-control" required>
                    <option value="" disabled>اختر نوع الدورة</option>
                    <optgroup label="المواد المشتركة">
                        <option value="اللغة الإنجليزية">اللغة الإنجليزية</option>
                        <option value="اللغة العربية">اللغة العربية</option>
                        <option value="التربية الإسلامية">التربية الإسلامية</option>
                        <option value="تاريخ الأردن">تاريخ الأردن</option>
                    </optgroup>
                    <optgroup label="مواد التخصص">
                        <option value="كيمياء">كيمياء</option>
                        <option value="أحياء">أحياء</option>
                        <option value="فيزياء">فيزياء</option>
                        <option value="علوم أرض">علوم أرض</option>
                        <option value="رياضيات">رياضيات</option>
                        <option value="إنجليزي متقدم">إنجليزي متقدم</option>
                        <option value="عربي تخصص">عربي تخصص</option>
                        <option value="رياضات أعمال">رياضات أعمال</option>
                        <option value="تربية إسلامية تخصص">تربية إسلامية تخصص</option>
                        <option value="ثقافة مالية">ثقافة مالية</option>
                    </optgroup>
                    <optgroup label="المواد الاختيارية">
                        <option value="علم اجتماع">علم اجتماع</option>
                        <option value="نفس وفلسفة">نفس وفلسفة</option>
                        <option value="التاريخ والجغرافيا">التاريخ والجغرافيا</option>
                        <option value="دين تخصص">دين تخصص</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-check">
                <input type="checkbox" id="edit-is-free" name="is_free" class="form-check-input">
                <label for="edit-is-free" class="form-check-label">دورة مجانية</label>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width mt-4">حفظ التعديلات</button>
        </form>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal" id="addCourseModal">
    <div class="modal-content">
        <span class="close-btn" data-dismiss="modal">&times;</span>
        <h2>إضافة دورة جديدة</h2>
        <form id="add-course-form">
            <div class="form-group">
                <label for="course-title">عنوان الدورة</label>
                <input type="text" id="course-title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="course-description">وصف الدورة</label>
                <textarea id="course-description" name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="course-category">الفئة</label>
                <input type="text" id="course-category" name="category" class="form-control">
            </div>
            <div class="form-group">
                <label for="course-type">نوع الدورة</label>
                <select id="course-type" name="course_type" class="form-control" required>
                    <option value="" disabled selected>اختر نوع الدورة</option>
                    <optgroup label="المواد المشتركة">
                        <option value="اللغة الإنجليزية">اللغة الإنجليزية</option>
                        <option value="اللغة العربية">اللغة العربية</option>
                        <option value="التربية الإسلامية">التربية الإسلامية</option>
                        <option value="تاريخ الأردن">تاريخ الأردن</option>
                    </optgroup>
                    <optgroup label="مواد التخصص">
                        <option value="كيمياء">كيمياء</option>
                        <option value="أحياء">أحياء</option>
                        <option value="فيزياء">فيزياء</option>
                        <option value="علوم أرض">علوم أرض</option>
                        <option value="رياضيات">رياضيات</option>
                        <option value="إنجليزي متقدم">إنجليزي متقدم</option>
                        <option value="عربي تخصص">عربي تخصص</option>
                        <option value="رياضات أعمال">رياضات أعمال</option>
                        <option value="تربية إسلامية تخصص">تربية إسلامية تخصص</option>
                        <option value="ثقافة مالية">ثقافة مالية</option>
                    </optgroup>
                    <optgroup label="المواد الاختيارية">
                        <option value="علم اجتماع">علم اجتماع</option>
                        <option value="نفس وفلسفة">نفس وفلسفة</option>
                        <option value="التاريخ والجغرافيا">التاريخ والجغرافيا</option>
                        <option value="دين تخصص">دين تخصص</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-check">
                <input type="checkbox" id="is-free" name="is_free" class="form-check-input">
                <label for="is-free" class="form-check-label">دورة مجانية</label>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width mt-4">حفظ الدورة</button>
        </form>
    </div>
</div>

<?php require_once '../templates/teacher-footer.php'; // Use the new teacher footer ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coursesTableBody = document.getElementById('courses-table-body');
    const noCoursesPlaceholder = document.getElementById('no-courses-placeholder');
    const addCourseForm = document.getElementById('add-course-form');
    const editCourseForm = document.getElementById('edit-course-form');
    const editCourseModal = document.getElementById('editCourseModal');

    // Basic modal handling
    const modal = document.getElementById('addCourseModal');
    document.querySelector('[data-target="#addCourseModal"]').addEventListener('click', () => modal.style.display = 'block');
    document.querySelector('.close-btn').addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
        if (event.target == editCourseModal) {
            editCourseModal.style.display = 'none';
        }
    });

    // Close edit modal
    editCourseModal.querySelector('.close-btn').addEventListener('click', () => editCourseModal.style.display = 'none');

    const callApi = (action, data = {}) => {
        return fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data }),
        }).then(res => res.json());
    };

    const renderCourses = (courses) => {
        coursesTableBody.innerHTML = '';
        if (!courses || courses.length === 0) {
            noCoursesPlaceholder.style.display = 'block';
            coursesTableBody.style.display = 'none';
            return;
        }
        
        noCoursesPlaceholder.style.display = 'none';
        coursesTableBody.style.display = '';

        courses.forEach(course => {
            const row = document.createElement('tr');
            const visibilityText = course.is_visible ? 'مرئي' : 'مخفي';
            const visibilityClass = course.is_visible ? 'status-visible' : 'status-hidden';
            const toggleVisibilityText = course.is_visible ? 'إخفاء' : 'إظهار';
            const toggleVisibilityIcon = course.is_visible ? 'fa-eye-slash' : 'fa-eye';

            row.innerHTML = `
                <td>${escapeHTML(course.title)}</td>
                <td><span class="status-badge ${course.is_free ? 'status-free' : 'status-paid'}">${course.is_free ? 'مجانية' : 'مدفوعة'}</span></td>
                <td><span class="status-badge ${visibilityClass}">${visibilityText}</span></td>
                <td>${course.models_count || 0}</td>
                <td>
                    <a href="../manage-models.php?course_id=${course.id}" class="btn btn-primary"><i class="fas fa-list-alt"></i> إدارة النماذج</a>
                    <button class="btn btn-secondary edit-course-btn" data-id="${course.id}"><i class="fas fa-edit"></i> تعديل</button>
                    <button class="btn btn-warning toggle-visibility-btn" data-id="${course.id}" data-type="course"><i class="fas ${toggleVisibilityIcon}"></i> ${toggleVisibilityText}</button>
                    <button class="btn btn-danger delete-course-btn" data-id="${course.id}"><i class="fas fa-trash"></i> حذف</button>
                </td>
            `;
            coursesTableBody.appendChild(row);
        });
    };

    const loadCourses = () => {
        callApi('get_teacher_courses').then(response => {
            if (response.status === 'success') {
                renderCourses(response.data);
            } else {
                alert('فشل تحميل الدورات: ' + response.message);
            }
        });
    };

    addCourseForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.is_free = formData.has('is_free'); // Convert checkbox value to boolean

        callApi('add_course', data).then(response => {
            if (response.status === 'success') {
                modal.style.display = 'none';
                addCourseForm.reset();
                loadCourses();
                showToast('تمت إضافة الدورة بنجاح!');
            } else {
                alert('خطأ: ' + response.message);
            }
        });
    });
    
    coursesTableBody.addEventListener('click', function(event) {
        const deleteBtn = event.target.closest('.delete-course-btn');
        const editBtn = event.target.closest('.edit-course-btn');
        const toggleBtn = event.target.closest('.toggle-visibility-btn');

        if (toggleBtn) {
            const courseId = toggleBtn.dataset.id;
            callApi('toggle_course_visibility', { course_id: courseId }).then(response => {
                if (response.status === 'success') {
                    loadCourses();
                    showToast('تم تحديث حالة الدورة بنجاح!');
                } else {
                    alert('فشل تحديث الحالة: ' + response.message);
                }
            });
        } else if (deleteBtn) {
            const courseId = deleteBtn.dataset.id;
            if (confirm('هل أنت متأكد من حذف هذه الدورة؟ سيتم حذف جميع النماذج والأسئلة المرتبطة بها بشكل نهائي.')) {
                callApi('delete_course', { course_id: courseId }).then(response => {
                    if (response.status === 'success') {
                        loadCourses();
                        showToast('تم حذف الدورة بنجاح.', 'error');
                    } else {
                        alert('فشل الحذف: ' + response.message);
                    }
                });
            }
        } else if (editBtn) {
            const courseId = editBtn.dataset.id;
            callApi('get_course_details', { course_id: courseId }).then(response => {
                if (response.status === 'success') {
                    const course = response.data;
                    document.getElementById('edit-course-id').value = course.id;
                    document.getElementById('edit-course-title').value = course.title;
                    document.getElementById('edit-course-description').value = course.description;
                    document.getElementById('edit-course-category').value = course.category;
                    document.getElementById('edit-course-type').value = course.course_type;
                    document.getElementById('edit-is-free').checked = course.is_free;
                    editCourseModal.style.display = 'block';
                } else {
                    alert('فشل تحميل تفاصيل الدورة: ' + response.message);
                }
            });
        }
    });

    editCourseForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.is_free = formData.has('is_free');

        callApi('update_course', data).then(response => {
            if (response.status === 'success') {
                editCourseModal.style.display = 'none';
                loadCourses();
                showToast('تم تحديث الدورة بنجاح!');
            } else {
                alert('خطأ في التحديث: ' + response.message);
            }
        });
    });

    function escapeHTML(str) {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }
    
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    loadCourses();
});
</script>
