<?php
// **THE FIX IS HERE**: Updated the session check to use the new structured array.
if (isset($_SESSION['user'])) {
    $course_id = (int)($_GET['course_id'] ?? 0);
    $course = get_current_course($course_id);
    if (!$course) { 
        echo "<p style='text-align:center; padding: 5rem;'>الاختبار غير موجود.</p>"; 
    } else {
?>
<div class="form-container">
    <div class="form-card interactive-card reveal-on-scroll">
        <div class="form-header"><div class="form-icon">🔑</div><h2 class="form-title">تفعيل الاختبار</h2></div>
        <div style="background: var(--color-glass-bg); border-radius: var(--border-radius-md); padding: 2rem; margin-bottom: 2.5rem; text-align: center; border: 1px solid var(--color-glass-border);">
            <div style="font-size: 2.5rem; margin-bottom: 0.8rem;"><?= $course['icon'] ?></div>
            <h3 style="color: var(--color-accent); margin-bottom: 0.8rem; font-size: 1.4rem;"><?= $course['title'] ?></h3>
            <p style="color: var(--color-text-muted); line-height: 1.6;"><?= $course['description'] ?></p>
        </div>
        <?php if (!empty($message)): ?> <div class="message <?= $message_type ?>"><?= $message ?></div> <?php endif; ?>
        <a href="https://t.me/Wbbdhdhdh_bot" target="_blank" class="btn btn-telegram" style="width:100%; margin-bottom: 2rem;">🤖 احصل على كود من بوت التليجرام</a>
        <form method="POST" action="?">
            <input type="hidden" name="action" value="activate_course">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <div class="form-group"><label class="form-label">كود التفعيل:</label><input type="text" name="code" class="form-input" placeholder="أدخل كود التفعيل هنا" required></div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">🔓 تفعيل</button>
        </form>
        <div style="text-align: center; margin-top: 2rem;"><a href="?view=courses" style="color: var(--color-text-muted); text-decoration: none; font-weight: 500;">← العودة للاختبارات</a></div>
    </div>
</div>
<?php 
    }
} else {
    set_flash_message('يرجى تسجيل الدخول اولا للوصول لهذه الصفحة.', 'warning');
    $redirect_url = urlencode("?view=activate&course_id=" . ($_GET['course_id'] ?? 0));
    header("Location: ?view=login&redirect_url=" . $redirect_url);
    exit();
}
?>
