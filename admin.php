<?php
session_start();

// Page Protection: Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Include the main config file for BASE_URL and other settings
require_once 'config.php';

// Include the admin header
require_once 'templates/admin-header.php';
?>

<div class="container">
    <h1 class="section-title">لوحة التحكم الرئيسية</h1>

    <div class="admin-grid">
        <!-- Add New Course Card -->
        <div class="admin-card">
            <h3 class="card-title">إضافة اختبار جديد</h3>
            <form id="add-course-form">
                <div class="form-group">
                    <label for="course-title">عنوان الاختبار</label>
                    <input type="text" id="course-title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="course-description">وصف الاختبار</label>
                    <textarea id="course-description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" id="is-free" name="is_free" value="1">
                    <label for="is-free">اختبار مجاني؟</label>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">إضافة الاختبار</button>
            </form>
            <div id="form-response"></div>
        </div>

        <!-- Manage Courses Card -->
        <div class="admin-card">
            <h3 class="card-title">إدارة الاختبارات الحالية</h3>
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>عنوان الاختبار</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="courses-tbody">
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Teacher Management Section -->
    <div class="admin-grid" style="margin-top: 2rem;">
        <div class="admin-card">
            <h3 class="card-title">إضافة معلم جديد</h3>
            <form id="add-teacher-form">
                <div class="form-group">
                    <label for="teacher-fullname">الاسم الكامل</label>
                    <input type="text" id="teacher-fullname" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="teacher-username">اسم المستخدم (للدخول)</label>
                    <input type="text" id="teacher-username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="teacher-password">كلمة المرور</label>
                    <input type="password" id="teacher-password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="teacher-email">البريد الإلكتروني</label>
                    <input type="email" id="teacher-email" name="email" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">إضافة المعلم</button>
            </form>
            <div id="teacher-form-response"></div>
        </div>

        <div class="admin-card">
            <h3 class="card-title">إدارة المعلمين</h3>
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>الاسم الكامل</th>
                            <th>اسم المستخدم</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="teachers-tbody">
                        <!-- Teacher rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coursesTbody = document.getElementById('courses-tbody');
    const addCourseForm = document.getElementById('add-course-form');
    const formResponse = document.getElementById('form-response');

    // --- Utility function to safely escape HTML ---
    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };

    // --- Toast Notification Function ---
    const showToast = (message, isError = false) => {
        const toast = document.getElementById('toast-notification');
        if (!toast) return;

        toast.textContent = message;
        toast.className = 'toast-notification show';
        if (isError) {
            toast.classList.add('error');
        }

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000); // Hide after 3 seconds
    };

    // --- API Call Function ---
    const callApi = (action, data) => {
        return fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data }),
        }).then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        });
    };

    // --- Renders courses into the table ---
    const renderCourses = (courses) => {
        coursesTbody.innerHTML = ''; // Clear existing rows
        if (!courses || courses.length === 0) {
            coursesTbody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><strong>لا توجد اختبارات حاليًا.</strong><br>يرجى إضافة اختبار جديد من النموذج أعلاه للبدء.</td></tr>';
            return;
        }

        courses.forEach(course => {
            const statusClass = course.is_free ? 'status-free' : 'status-paid';
            const statusText = course.is_free ? 'مجانية' : 'مدفوعة';
            const toggleButtonText = course.is_free ? 'اجعلها مدفوعة' : 'اجعلها مجانية';

            const row = document.createElement('tr');
            row.id = `course-row-${course.id}`;
            row.innerHTML = `
                <td>${course.id}</td>
                <td>${escapeHTML(course.title)}</td>
                <td class="text-center" id="status-cell-${course.id}">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td class="text-center">
                    <a href="manage-models.php?course_id=${course.id}" class="btn btn-primary">إدارة النماذج</a>
                    <button class="btn btn-warning toggle-status-btn" data-id="${course.id}" data-status="${course.is_free}">${toggleButtonText}</button>
                    <button class="btn btn-danger delete-btn" data-id="${course.id}">حذف</button>
                </td>
            `;
            coursesTbody.appendChild(row);
        });
    };

    // --- Fetches courses from the API and renders them ---
    const fetchCourses = () => {
        coursesTbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">جاري تحميل الاختبارات...</td></tr>';
        callApi('get_courses', {})
            .then(response => {
                if (response.status === 'success' && Array.isArray(response.data)) {
                    renderCourses(response.data);
                } else {
                    throw new Error(response.message || 'Failed to load tests.');
                }
            })
            .catch(error => {
                console.error('Error fetching courses:', error);
                coursesTbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red; padding: 2rem;">فشل تحميل الاختبارات: ${error.message}</td></tr>`;
            });
    };

    // --- Event Handler for Add Course Form ---
    addCourseForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const title = document.getElementById('course-title').value;
        const description = document.getElementById('course-description').value;
        const is_free = document.getElementById('is-free').checked;

        formResponse.textContent = 'جاري الإضافة...';
        formResponse.className = 'loading';

        callApi('add_course', { title, description, is_free })
            .then(result => {
                formResponse.textContent = result.message;
                if (result.status === 'success') {
                    formResponse.className = 'success';
                    addCourseForm.reset();
                    fetchCourses(); // Refresh the list
                } else {
                    formResponse.className = 'error';
                }
            })
            .catch(error => {
                console.error('Error adding course:', error);
                formResponse.textContent = `حدث خطأ في الاتصال: ${error.message}`;
                formResponse.className = 'error';
            });
    });

    // --- Event Delegation for Table Action Buttons ---
    coursesTbody.addEventListener('click', function(event) {
        const target = event.target;
        const courseId = target.dataset.id;

        // Handle Delete
        if (target.classList.contains('delete-btn')) {
            if (confirm('هل أنت متأكد أنك تريد حذف هذا الاختبار؟ هذا الإجراء سيحذف جميع أسئلته أيضاً.')) {
                callApi('delete_course', { course_id: courseId })
                    .then(result => {
                        showToast(result.message, result.status !== 'success');
                        if (result.status === 'success') {
                            fetchCourses();
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting course:', error);
                        showToast(`فشل الحذف: ${error.message}`, true);
                    });
            }
        }

        // Handle Toggle Status
        if (target.classList.contains('toggle-status-btn')) {
            const currentStatus = target.dataset.status === 'true';
            
            callApi('toggle_course_status', { course_id: courseId, current_status: currentStatus })
                .then(result => {
                    showToast(result.message, result.status !== 'success');
                    if (result.status === 'success') {
                        fetchCourses();
                    }
                })
                .catch(error => {
                    console.error('Error toggling status:', error);
                    showToast(`فشل تغيير الحالة: ${error.message}`, true);
                });
        }
    });

    // --- Initial Load ---
    fetchCourses();

    // --- Teacher Management Logic ---
    const teachersTbody = document.getElementById('teachers-tbody');
    const addTeacherForm = document.getElementById('add-teacher-form');
    const teacherFormResponse = document.getElementById('teacher-form-response');

    const renderTeachers = (teachers) => {
        teachersTbody.innerHTML = '';
        if (!teachers || teachers.length === 0) {
            teachersTbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">لا يوجد معلمون حاليًا.</td></tr>';
            return;
        }
        teachers.forEach(teacher => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${teacher.id}</td>
                <td>${escapeHTML(teacher.full_name)}</td>
                <td>${escapeHTML(teacher.username)}</td>
                <td>
                    <button class="btn btn-danger delete-teacher-btn" data-id="${teacher.id}">حذف</button>
                </td>
            `;
            teachersTbody.appendChild(row);
        });
    };

    const fetchTeachers = () => {
        callApi('get_teachers', {})
            .then(response => {
                if (response.status === 'success') {
                    renderTeachers(response.data);
                } else {
                    showToast(response.message, true);
                }
            });
    };

    addTeacherForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        teacherFormResponse.textContent = 'جاري إضافة المعلم...';
        teacherFormResponse.className = 'loading';

        callApi('add_teacher', data)
            .then(result => {
                teacherFormResponse.textContent = result.message;
                if (result.status === 'success') {
                    teacherFormResponse.className = 'success';
                    addTeacherForm.reset();
                    fetchTeachers();
                } else {
                    teacherFormResponse.className = 'error';
                }
            })
            .catch(error => {
                teacherFormResponse.textContent = `حدث خطأ: ${error.message}`;
                teacherFormResponse.className = 'error';
            });
    });
    
    teachersTbody.addEventListener('click', function(event) {
        const target = event.target.closest('.delete-teacher-btn');
        if (target) {
            const teacherId = target.dataset.id;
            if (confirm('هل أنت متأكد من حذف هذا المعلم؟ سيتم إلغاء ربطه بالدورات التي أنشأها.')) {
                callApi('delete_teacher', { teacher_id: teacherId }).then(response => {
                    if (response.status === 'success') {
                        fetchTeachers();
                        showToast('تم حذف المعلم بنجاح.');
                    } else {
                        showToast(response.message, true);
                    }
                });
            }
        }
    });

    fetchTeachers();
});
</script>

<?php
// Include the admin footer
require_once 'templates/admin-footer.php'; 
?>
