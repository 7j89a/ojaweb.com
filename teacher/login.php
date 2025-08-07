<?php
session_start();
require_once '../config.php';
require_once '../functions.php'; // Include supabase functions

// If already logged in, redirect to dashboard
if (isset($_SESSION['teacher_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'الرجاء إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $result = supabase_request('/rest/v1/teachers?username=eq.' . urlencode($username) . '&select=id,password_hash,full_name', null, 'GET', true);

        if ($result['http_code'] === 200 && !empty($result['data'])) {
            $teacher = $result['data'][0];
            if (password_verify($password, $teacher['password_hash'])) {
                // Password is correct, start session
                $_SESSION['teacher_id'] = $teacher['id'];
                $_SESSION['teacher_name'] = $teacher['full_name'];
                header('Location: dashboard.php');
                exit;
            } else {
                // Invalid credentials
                $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            }
        } else {
            // User not found or other error
            $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المعلمين</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        /* Use styles from admin-style.css but override for a dedicated login page */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--background-color);
        }
        .login-container {
            background: var(--surface-color);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .login-container h1 {
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        .error-message {
            color: var(--danger-color);
            background-color: rgba(231, 76, 60, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(231, 76, 60, 0.2);
            display: <?php echo empty($error_message) ? 'none' : 'block'; ?>;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>لوحة تحكم المعلمين</h1>
        <p style="color: var(--muted-text-color); margin-top: -1.5rem; margin-bottom: 2rem;">الرجاء تسجيل الدخول للمتابعة</p>
        
        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>

        <form method="POST" action="login.php">
            <div class="form-group" style="text-align: right;">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group" style="text-align: right;">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
