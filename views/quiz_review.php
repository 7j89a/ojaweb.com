<?php
// 1. التحقق من أن المستخدم مسجل دخوله وأن بيانات المراجعة موجودة
// تم التحقق من تسجيل الدخول وجلب $review_data في index.php
if (!isset($review_data)) {
    set_flash_message('لا يمكن العثور على بيانات المراجعة لهذه المحاولة.', 'error');
    header("Location: ?view=profile");
    exit();
}

// 2. استخراج البيانات من متغير $review_data
$questions = $review_data['questions'];
$user_answers = $review_data['user_answers'];
$model_title = htmlspecialchars($review_data['model_title']);

// إنشاء خريطة لإجابات المستخدم لتسهيل الوصول إليها
$user_answers_map = [];
foreach ($user_answers as $answer) {
    $user_answers_map[$answer['question_id']] = $answer;
}
?>

<div class="quiz-review-container">
    <h1 class="section-title">مراجعة اختبار: <?= $model_title ?></h1>
    
    <?php foreach ($questions as $index => $question): ?>
        <?php
            $user_answer_details = $user_answers_map[$question['id']] ?? null;
            $user_selected_option = $user_answer_details['selected_answer'] ?? null;
            $correct_option = $question['correct'];
            $options = $question['options']; 
        ?>
        <div class="review-question-card reveal-on-scroll">
            <div class="quiz-header" style="padding: 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                 <h2 style="margin: 0;">مراجعة السؤال <?= $index + 1 ?></h2>
                 <?php if ($user_answer_details && $user_answer_details['is_correct']): ?>
                    <span class="badge badge-success">صحيحة</span>
                 <?php else: ?>
                    <span class="badge badge-danger">خاطئة</span>
                 <?php endif; ?>
            </div>

            <?php if (!empty($question['question_text'])): ?>
            <div class="question-text-container">
                <div class="question-text"><?= $question['question_text'] ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($question['question_image'])): ?>
                <div class="question-image-container review-image-container">
                    <div class="question-image" style="background-image: url('<?= htmlspecialchars($question['question_image']) ?>');"></div>
                </div>
            <?php endif; ?>

            <div class="options-grid" style="margin-top: 1.5rem;">
                <?php if (is_array($options)): ?>
                    <?php foreach ($options as $opt_index => $option):
                        $classes = 'option-btn';
                        $is_correct_answer = ($opt_index == $correct_option);
                        $is_user_choice = ($user_selected_option !== null && $opt_index == $user_selected_option);

                        if ($is_correct_answer) {
                            $classes .= ' correct-answer';
                        } elseif ($is_user_choice) {
                            $classes .= ' user-answer wrong-answer';
                        }
                    ?>
                        <button class="<?= $classes ?>" disabled>
                            <span class="option-letter"><?= chr(65 + $opt_index) ?></span>
                            <span class="option-value">
                                <?php if ($option['type'] === 'image'): ?>
                                    <img src="<?= htmlspecialchars($option['value']) ?>" alt="Option Image" class="option-image-in-button" style="pointer-events: auto;">
                                <?php else: ?>
                                    <?= $option['value'] ?>
                                <?php endif; ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($question['explanation'])): ?>
                <div class="explanation-box" style="width:100%; max-width: 1100px; margin: 1.5rem auto 0 auto;">
                    <strong>الشرح:</strong> <?= $question['explanation'] ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <div style="text-align:center; margin-top: 3rem;">
        <a href="?view=profile" class="btn btn-primary">العودة للملف الشخصي</a>
    </div>
</div>
