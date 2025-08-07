<?php
// templates/access-denied.php
// This template should be included after a header file.

$error_title = $error_title ?? 'وصول مرفوض';
$error_message = $error_message ?? 'ليس لديك الصلاحية لعرض هذه الصفحة.';
$back_link = $back_link ?? (isset($_SESSION['teacher_id']) ? 'teacher/dashboard.php' : 'admin.php');
$back_link_text = $back_link_text ?? 'العودة إلى لوحة التحكم';
?>

<style>
.access-denied-container {
    text-align: center;
    padding: 4rem 2rem;
    margin: 2rem auto;
    max-width: 700px;
    background-color: var(--surface-color);
    border: 1px solid var(--border-color);
    border-left: 5px solid var(--danger-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}
.access-denied-container .icon {
    font-size: 4rem;
    color: var(--danger-color);
    margin-bottom: 1.5rem;
    font-family: "Segoe UI Symbol", sans-serif; /* Use a font that supports symbols */
}
.access-denied-container h1 {
    color: var(--text-color);
    font-size: 2.5rem;
    margin-bottom: 1rem;
}
.access-denied-container p {
    font-size: 1.2rem;
    color: var(--muted-text-color);
    margin-bottom: 2rem;
}
</style>

<div class="container">
    <div class="access-denied-container">
        <div class="icon">&#9888;</div> <!-- Unicode warning sign -->
        <h1><?php echo htmlspecialchars($error_title); ?></h1>
        <p><?php echo htmlspecialchars($error_message); ?></p>
        <a href="<?php echo htmlspecialchars($back_link); ?>" class="btn btn-primary"><?php echo htmlspecialchars($back_link_text); ?></a>
    </div>
</div>
