<div class="result-container">
    <div class="result-card reveal-on-scroll">
        <div style="font-size: 5rem; margin-bottom: 1.5rem;"></div>
        <h2 class="form-title">النتيجة النهائية</h2>
        <?php
        // Updated to use the new session structure from api.php
        if (isset($_SESSION['quiz_result'])) {
            $result = $_SESSION['quiz_result'];
            $score = $result['score'];
            $total = $result['total_questions'];
            $percentage = $result['percentage'];
            $model_title = htmlspecialchars($result['model_title'] ?? 'الاختبار');
            $session_id = $result['session_id'] ?? null;
            // Build the review link with the session_id if it exists
            $review_link = $session_id ? "?view=quiz_review&session_id=" . urlencode($session_id) : "#";
            
            // Unset the result from session to prevent showing it again on refresh
            unset($_SESSION['quiz_result']);
        } else {
            // Fallback or redirect if the result is not in the session
            echo "<p>لم يتم العثور على نتيجة. ربما أكملت هذا الاختبار بالفعل.</p>";
            echo '<a href="?view=dashboard" class="btn btn-primary">العودة للوحة التحكم</a>';
            // Stop further rendering of the page
            return;
        }
        ?>
        <p class="result-text">نتيجة اختبار: <strong><?= $model_title ?></strong></p>
        <div class="result-score"><?= $score ?> / <?= $total ?></div>
        <p class="result-text">أحسنت! لقد أجبت على <?= $score ?> من <?= $total ?> أسئلة بشكل صحيح.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
            <a href="<?= $review_link ?>" class="btn btn-secondary">مراجعة الإجابات</a>
            <a href="?view=courses" class="btn btn-primary">العودة إلى الاختبارات</a>
        </div>
    </div>
</div>
