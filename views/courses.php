<?php
// You can fetch this data from a database in a real application
$subjects = [
    'shared' => [
        ['name' => 'اللغة الإنجليزية', 'icon' => 'fas fa-language', 'color' => '#4a90e2'],
        ['name' => 'اللغة العربية', 'icon' => 'fas fa-book-reader', 'color' => '#50e3c2'],
        ['name' => 'التربية الإسلامية', 'icon' => 'fas fa-mosque', 'color' => '#b8e986'],
        ['name' => 'تاريخ الأردن', 'icon' => 'fas fa-landmark', 'color' => '#f5a623']
    ],
    'field' => [
        ['name' => 'كيمياء', 'icon' => 'fas fa-flask', 'color' => '#f8b7d3'],
        ['name' => 'أحياء', 'icon' => 'fas fa-dna', 'color' => '#9dffb0'],
        ['name' => 'فيزياء', 'icon' => 'fas fa-atom', 'color' => '#a7d2ff'],
        ['name' => 'علوم أرض', 'icon' => 'fas fa-globe-americas', 'color' => '#d0a7ff'],
        ['name' => 'رياضيات', 'icon' => 'fas fa-calculator', 'color' => '#ffcda7'],
        ['name' => 'إنجليزي متقدم', 'icon' => 'fas fa-spell-check', 'color' => '#a7ffeb'],
        ['name' => 'عربي تخصص', 'icon' => 'fas fa-pen-fancy', 'color' => '#e3a7ff'],
        ['name' => 'رياضات أعمال', 'icon' => 'fas fa-chart-line', 'color' => '#ffbda7'],
        ['name' => 'تربية إسلامية تخصص', 'icon' => 'fas fa-quran', 'color' => '#a7ffc3'],
        ['name' => 'ثقافة مالية', 'icon' => 'fas fa-money-bill-wave', 'color' => '#ffdda7']
    ],
    'optional' => [
        ['name' => 'علم اجتماع ونفس', 'icon' => 'fas fa-users', 'color' => '#ff9a8b'],
        ['name' => 'الفلسفة', 'icon' => 'fas fa-brain', 'color' => '#f6d365'],
        ['name' => 'التاريخ والجغرافيا', 'icon' => 'fas fa-atlas', 'color' => '#fda085'],
        ['name' => 'دين تخصص', 'icon' => 'fas fa-kaaba', 'color' => '#fbc2eb']
    ]
];
?>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="assets/css/courses-style.css">

<section class="courses-v2-section">
    <div class="container">
        <div class="section-header">
            <h1 class="main-title">استكشف مساراتك التعليمية</h1>
            <p class="subtitle">اختر المادة اللتي تريد تقديم الأمتحان فيها</p>
        </div>

        <!-- Shared Subjects -->
        <div class="subject-category-wrapper">
            <h2 class="category-title">المواد المشتركة</h2>
            <div class="courses-grid">
                <?php foreach ($subjects['shared'] as $subject): 
                    $subject_class = 'subject-' . strtolower(str_replace(' ', '-', $subject['name']));
                ?>
                <div class="course-card <?php echo $subject_class; ?>">
                    <div class="card-content">
                        <div class="card-icon"><i class="<?php echo $subject['icon']; ?>"></i></div>
                        <h3 class="card-title"><?php echo $subject['name']; ?></h3>
                        <p class="card-description">اختبارات شاملة ومراجعات.</p>
                        <a href="?view=subject_courses&subject=<?php echo urlencode($subject['name']); ?>" class="card-button">عرض الاختبارات</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Field Subjects -->
        <div class="subject-category-wrapper">
            <h2 class="category-title">مواد التخصص</h2>
            <div class="courses-grid">
                <?php foreach ($subjects['field'] as $subject): 
                    $subject_class = 'subject-' . strtolower(str_replace(' ', '-', $subject['name']));
                ?>
                <div class="course-card <?php echo $subject_class; ?>">
                    <div class="card-content">
                        <div class="card-icon"><i class="<?php echo $subject['icon']; ?>"></i></div>
                        <h3 class="card-title"><?php echo $subject['name']; ?></h3>
                        <p class="card-description">اختبارات متخصصة وتدريبات.</p>
                        <a href="?view=subject_courses&subject=<?php echo urlencode($subject['name']); ?>" class="card-button">عرض الاختبارات</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Optional Subjects -->
        <div class="subject-category-wrapper">
            <h2 class="category-title">المواد الاختيارية</h2>
            <div class="courses-grid">
                <?php foreach ($subjects['optional'] as $subject): 
                    $subject_class = 'subject-' . strtolower(str_replace(' ', '-', $subject['name']));
                ?>
                <div class="course-card <?php echo $subject_class; ?>">
                    <div class="card-content">
                        <div class="card-icon"><i class="<?php echo $subject['icon']; ?>"></i></div>
                        <h3 class="card-title"><?php echo $subject['name']; ?></h3>
                        <p class="card-description">اختر ما يناسب طموحك.</p>
                        <a href="?view=subject_courses&subject=<?php echo urlencode($subject['name']); ?>" class="card-button">عرض الاختبارات</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<script src="assets/js/courses-animation.js"></script>
