<?php
// Get the subject from the URL and decode it
$subject = isset($_GET['subject']) ? urldecode($_GET['subject']) : 'Unknown';

// Fetch courses for the selected subject
$params = [
    'course_type' => 'eq.' . $subject,
    'is_visible' => 'eq.true'
];
$courses_response = supabase_request('/rest/v1/courses', $params, 'GET');
$courses = $courses_response['data'] ?? [];

// Get activated courses for the logged-in user
$is_user_logged_in = isset($_SESSION['user']);
$activated_courses = $_SESSION['activated_courses'] ?? [];
$activated_courses_int = array_map('intval', $activated_courses);
?>

<section class="courses-section">
    <div class="container">
        <h1 class="section-title">دورات مادة: <?= htmlspecialchars($subject) ?></h1>
        <p style="text-align: center; color: var(--color-text-muted); margin-bottom: 3rem;">
            تصفح جميع الدورات المتاحة لهذه المادة.
        </p>

        <?php if (empty($courses)): ?>
            <div class="empty-state reveal-on-scroll">
                <div class="empty-state-icon"></div>
                <p class="empty-state-text">عفواً، لا توجد دورات متاحة حالياً في مادة "<?= htmlspecialchars($subject) ?>".</p>
                <a href="?view=courses" class="btn btn-primary" style="margin-top: 1.5rem;">العودة إلى قائمة المواد</a>
            </div>
        <?php else: ?>
            <div class="courses-grid" id="courses-grid">
                <?php foreach ($courses as $index => $course): ?>
                    <div class="course-card interactive-card reveal-on-scroll" 
                         id="course-card-<?= $course['id'] ?>"
                         data-title="<?= htmlspecialchars($course['title']) ?>"
                         data-difficulty="<?= htmlspecialchars($course['difficulty']) ?>"
                         data-category="<?= htmlspecialchars($course['category']) ?>"
                         style="transition-delay: <?= $index * 0.05 ?>s;">
                        <div class="aurora-effect"></div>
                        <?php 
                            $is_activated = $is_user_logged_in && in_array((int)$course['id'], $activated_courses_int, true);
                            
                            if ($course['is_free']) { /* The user wants to hide the "Free" status */ } 
                            elseif ($is_activated) { echo '<div class="course-status status-premium">مفعل</div>'; } 
                            else { echo '<div class="course-status status-locked">مدفوع</div>'; }
                        ?>
                        <div class="course-header">
                            <div class="course-icon"><?= $course['icon'] ?? '' ?></div>
                            <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <div class="course-meta">
                                <span class="course-tag"><?= htmlspecialchars($course['difficulty']) ?></span>
                                <span class="course-tag"><?= htmlspecialchars($course['category']) ?></span>
                            </div>
                        </div>
                        <div class="course-body">
                            <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
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
                                    <div class="stat-number"><?= $course['students'] ?? 0 ?></div>
                                    <div class="stat-label">طالب</div>
                                </div>
                            </div>
                            <?php if ($course['is_free'] || $is_activated): ?>
                                <a href="?view=course_details&course_id=<?= $course['id'] ?>" class="btn btn-primary" style="width: 100%;"><i class="fas fa-arrow-right"></i> عرض النماذج</a>
                            <?php else: ?>
                                <?php if ($is_user_logged_in): ?>
                                    <a href="?view=activate&course_id=<?= $course['id'] ?>" class="btn btn-secondary" style="width: 100%;"><i class="fas fa-lock-open"></i> تفعيل الاختبار</a>
                                <?php else: ?>
                                    <a href="?view=login&redirect_url=<?= urlencode('?view=subject_courses&subject=' . $subject . '#course-card-' . $course['id']) ?>" class="btn btn-login-prompt" style="width: 100%;"><i class="fas fa-sign-in-alt"></i> سجل للتفعيل</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
