<?php
session_start();
require_once 'admin-config.php';

$error_message = '';

// معالجة طلب تسجيل الخروج
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// إذا كان المدير مسجل دخوله بالفعل، يتم توجيهه إلى لوحة التحكم
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // استخدام password_verify لمقارنة كلمة المرور المدخلة بالهاش المخزن
    // للتجربة الآن، سنستخدم مقارنة نصية بسيطة ومؤقتة
    if ($email === ADMIN_EMAIL && $password === 'obada123') { // مقارنة مؤقتة وغير آمنة
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error_message = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول المدير</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css?v=<?= time() ?>">
</head>
<body class="login-wrapper">
    <div class="login-container">
        <h1>لوحة التحكم</h1>
        <p>يرجى تسجيل الدخول للمتابعة</p>
        <br>
        <?php if ($error_message): ?>
            <p class="error-message"><?= $error_message ?></p>
        <?php endif; ?>
        <form method="POST" action="admin-login.php">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">دخول</button>
        </form>
    </div>
</body>
</html>
