<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المعلم</title>
    <!-- Corrected path to CSS file -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body data-theme="light"> <!-- Default to light theme -->

<header class="admin-header">
    <div class="header-container">
        <div class="logo">
            <a href="#" onclick="return false;" style="cursor: default;">لوحة التحكم</a>
        </div>
        <nav class="admin-nav">
            <!-- Add teacher-specific nav links here if needed -->
        </nav>
        <div class="header-actions">
            <span style="margin-left: 15px;">مرحباً, <?php echo htmlspecialchars($_SESSION['teacher_name'] ?? 'معلم'); ?></span>
            <a href="<?php echo BASE_URL; ?>teacher/logout.php" class="btn btn-danger">تسجيل الخروج</a>
        </div>
    </div>
</header>

<main class="main-content">
