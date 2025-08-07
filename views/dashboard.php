<?php 
// **THE FIX IS HERE**: Updated the session check to use the new structured array.
if (!isset($_SESSION['user']['phone'])) {
    header("Location: ?view=login&redirect_url=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_name = htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['middle_name']);
$activated_courses = $_SESSION['activated_courses'] ?? [];
$activated_courses_int = array_map('intval', $activated_courses);
$user_courses = array_filter($courses, function($course) use ($activated_courses_int) {
    $is_activated = in_array((int)$course['id'], $activated_courses_int, true);
    return $course['is_free'] || $is_activated;
});

// **THE FIX**: Fetch the user's quiz history and calculate the real stats.
$quiz_history = get_user_quiz_history($_SESSION['user']['phone']);
$user_stats = get_user_stats($quiz_history);
?>
<div class="profile-container">
    <div class="profile-card interactive-card">
        <div class="profile-avatar">
            <i class="fas fa-user-graduate"></i>
        </div>
        <h1 class="profile-name">مرحباً بعودتك، <?= $user_name ?>!</h1>
        <p class="profile-email">مستعد لجولة جديدة من التعلم؟</p>
    </div>

    <div class="profile-stats">
        <div class="profile-stat-item interactive-card">
            <div class="stat-value"><?= count($user_courses) ?></div>
            <div class="stat-title">الاختبارات المتاحة</div>
        </div>
        <div class="profile-stat-item interactive-card">
            <div class="stat-value"><?= $user_stats['total_quizzes'] ?></div>
            <div class="stat-title">الاختبارات المنجزة</div>
        </div>
        <div class="profile-stat-item interactive-card">
            <div class="stat-value"><?= round($user_stats['average_score']) ?>%</div>
            <div class="stat-title">متوسط الدرجات</div>
        </div>
    </div>

    <div class="courses-section" style="padding-top: 2rem;">
        <h2 class="section-title" style="font-size: 2.5rem;">اختباراتك المتاحة</h2>
        <?php if (empty($user_courses)): ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                <p class="empty-state-text">ليس لديك اختبارات متاحة بعد. <a href="?view=courses" style="color:var(--color-accent); font-weight: bold;">تصفح الاختبارات الآن!</a></p>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($user_courses as $course): ?>
                    <div class="course-card interactive-card reveal-on-scroll">
                        <div class="aurora-effect"></div>
                        <div class="course-status <?= $course['is_free'] ? 'status-free' : 'status-premium' ?>"><?= $course['is_free'] ? 'مجاني' : 'مفعل' ?></div>
                        <div class="course-header">
                            <div class="course-icon"><?= $course['icon'] ?></div>
                            <h3 class="course-title"><?= $course['title'] ?></h3>
                        </div>
                        <div class="course-body">
                            <p class="course-description"><?= $course['description'] ?></p>
                             <div class="course-stats">
                                <div class="stat-item">
                                    <i class="fas fa-question-circle"></i>
                                    <div class="stat-number"><?= $course['questions_count'] ?? 0 ?></div>
                                    <div class="stat-label">سؤال</div>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-star"></i>
                                    <div class="stat-number"><?= $course['rating'] ?? 0 ?></div>
                                    <div class="stat-label">تقييم</div>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <div class="stat-number"><?= $course['students'] ?></div>
                                    <div class="stat-label">طالب</div>
                                </div>
                            </div>
                            <a href="?view=course_details&course_id=<?= $course['id'] ?>" class="btn btn-primary" style="width: 100%;"><i class="fas fa-arrow-right"></i> ابدأ الآن</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
