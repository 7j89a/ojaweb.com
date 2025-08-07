<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-style.css?v=<?= time() ?>">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body data-theme="light">
    <div id="toast-notification" class="toast-notification"></div>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo">
                <a href="admin.php">لوحة التحكم</a>
            </div>
            <nav class="admin-nav">
                <!-- Navigation links removed as per request -->
            </nav>
            <div class="header-actions">
                <?php if (isset($_SESSION['admin_logged_in'])): ?>
                    <a href="admin-login.php?logout=1" class="btn btn-danger">تسجيل الخروج</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="main-content">
