<?php
// All validation logic is now handled in index.php before the header is included.
// The necessary variables ($course, $models) are already available from index.php.
$course_id = intval($_GET['course_id']);
$models = get_quiz_models_for_course($course_id); 
?>

<!-- Styles are now in main style.css -->

<div class="container courses-section">
    <div class="hero-section" style="padding: 2rem 0;">
        <h1 class="hero-title" style="font-size: 3rem;"><?= htmlspecialchars($course['title']) ?></h1>
        <p class="hero-subtitle" style="font-size: 1.2rem;"><?= htmlspecialchars($course['description']) ?></p>
    </div>

    <h2 class="section-title" style="font-size: 2.5rem; margin-bottom: 2.5rem;">النماذج المتاحة</h2>

    <?php if (empty($models)): ?>
        <div class="message info reveal-on-scroll">لا توجد نماذج اختبار متاحة لهذا الاختبار حاليًا.</div>
    <?php else: ?>
        <div class="courses-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php foreach ($models as $model): ?>
                <div class="interactive-card course-card">
                    <div class="course-header" style="padding: 2rem;">
                        <div class="course-icon" style="font-size: 3rem;"><i class="fas fa-file-alt"></i></div>
                        <h3 class="course-title" style="font-size: 1.4rem;"><?= htmlspecialchars($model['title']) ?></h3>
                    </div>
                    <div class="course-body" style="padding: 1.5rem;">
                        <div class="course-stats" style="margin-bottom: 1.5rem;">
                            <div class="stat-item">
                                <span class="stat-number"><?= $model['questions_count'] ?? 0 ?></span>
                                <span class="stat-label">أسئلة</span>
                            </div>
                            <div class="stat-item">
                                <?php
                                    $timer_text = 'لكل سؤال';
                                    if ($model['timer_type'] === 'total_time') {
                                        $timer_text = ($model['total_time_seconds'] / 60) . ' د';
                                    } elseif ($model['timer_type'] === 'both') {
                                        $timer_text = 'مزدوج';
                                    }
                                ?>
                                <span class="stat-number"><?= $timer_text ?></span>
                                <span class="stat-label">المؤقت</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $model['attempts_count'] ?? '0' ?></span>
                                <span class="stat-label">محاولات</span>
                            </div>
                        </div>
                        <form method="POST" action="?" style="margin: 0; width: 100%;">
                            <input type="hidden" name="action" value="start_quiz">
                            <input type="hidden" name="course_id" value="<?= $course_id ?>">
                            <input type="hidden" name="model_id" value="<?= $model['id'] ?>">
                            <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-rocket"></i> ابدأ الاختبار</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
