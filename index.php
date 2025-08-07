<?php
// منع التخزين المؤقت للمتصفح (Caching)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ابدأ الجلسة في كل طلب
session_start();

// 1. تضمين الملفات الأساسية
require_once 'config.php';
require_once 'functions.php';

// Fetch courses from Supabase
$courses_result = supabase_request('/rest/v1/courses?select=*&is_visible=eq.true&order=id.asc', null, 'GET', false); // Explicitly use anon/user key
$courses = [];
if ($courses_result['http_code'] === 200) {
    $courses = $courses_result['data'];
} else {
    // Log error or show a message
    error_log("Failed to fetch courses from Supabase: " . json_encode($courses_result));
}

// 2. التحقق من وجود cURL
if (!function_exists('curl_init')) {
    die('cURL is not installed or enabled. Please contact your hosting provider.');
}

// 3. إعداد الرسائل والإعدادات الافتراضية
$message = $_SESSION['flash_message'] ?? "";
$message_type = $_SESSION['flash_message_type'] ?? "";
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// تحديد العرض (الصفحة) الحالية
$current_view = $_GET['view'] ?? 'home';

// 4. معالجة طلبات POST (تسجيل دخول، تسجيل، تفعيل، إلخ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'login') {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            set_flash_message("يرجى ملء جميع الحقول", "error");
        } else {
            $login_result = verify_login($email, $password);
            if ($login_result['status'] === 'success') {
                $_SESSION['user'] = $login_result['user'];
                $_SESSION['activated_courses'] = get_user_activated_courses($_SESSION['user']['phone']);
                
                $redirect_url = $_POST['redirect_url'] ?? '?view=home';
                header("Location: " . $redirect_url);
                exit();
            } else {
                $error_message = "بيانات الدخول غير صحيحة.";
                set_flash_message($error_message, "error");
            }
        }
        header("Location: ?view=login");
        exit();
    }
    elseif ($action == 'register') {
        $first_name = htmlspecialchars($_POST['first_name']);
        $middle_name = htmlspecialchars($_POST['middle_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $message = "كلمتا المرور غير متطابقتين.";
            $message_type = 'error';
            $current_view = 'register';
        } else {
            $result = register_user($first_name, $middle_name, $last_name, $email, $phone, $password);
            if ($result['status'] === 'success') {
                // After successful registration, redirect to the login page with a message to check email.
                set_flash_message('تم إنشاء حسابك بنجاح! لقد أرسلنا رابط تحقق إلى بريدك الإلكتروني. يرجى النقر على الرابط لتفعيل حسابك قبل تسجيل الدخول.', 'success');
                header("Location: ?view=login");
                exit();
            } else {
                $message = $result['message'];
                $message_type = 'error';
                $current_view = 'register';
            }
        }
    }
    elseif ($action == 'activate_course') {
        if (!isset($_SESSION['user']['phone'])) {
            header("Location: ?view=login");
            exit();
        }
        $code = trim($_POST['code']);
        $course_id = intval($_POST['course_id']);
        if (empty($code)) {
            set_flash_message('الرجاء إدخال كود التفعيل', 'error');
        } else {
            $result = activate_course_with_code($_SESSION['user']['phone'], $course_id, $code);
            set_flash_message($result['message'], $result['success'] ? 'success' : 'error');
            if ($result['success']) {
                // عند النجاح، أعد تحميل الدورات المفعلة من قاعدة البيانات
                // هذا يضمن أن الجلسة محدثة دائمًا مع المصدر الموثوق
                $_SESSION['activated_courses'] = get_user_activated_courses($_SESSION['user']['phone']);
                header("Location: ?view=courses");
                exit();
            }
        }
        header("Location: ?view=activate&course_id=" . $course_id);
        exit();
    }
    elseif ($action == 'start_quiz') {
        // This action now simply redirects to the new API-driven quiz page.
        if (!isset($_SESSION['user']['phone'])) { 
            set_flash_message('يرجى تسجيل الدخول أولاً لبدء الاختبار.', 'warning'); 
            header("Location: ?view=login"); 
            exit(); 
        }
        
        $course_id = intval($_POST['course_id']);
        $model_id = intval($_POST['model_id']);
        
        // Basic validation: Check if the user is allowed to access this course.
        $course = get_current_course($course_id);
        $is_activated = in_array($course_id, $_SESSION['activated_courses'] ?? []);

        if ($course && ($course['is_free'] || $is_activated)) {
            // Clear any old quiz session data before starting a new one
            unset($_SESSION['quiz_session']);
            // Redirect to the quiz view with the model_id
            header("Location: ?view=quiz&model_id=" . $model_id);
            exit();
        } else { 
            set_flash_message('يجب تفعيل هذا الاختبار أولاً.', 'error'); 
            header("Location: ?view=course_details&course_id=" . $course_id); 
            exit();
        }
    }
    elseif ($action == 'delete_quiz_history') {
        if (isset($_POST['session_id']) && isset($_SESSION['user'])) {
            $session_id_to_hide = $_POST['session_id'];
            // Initialize the array if it doesn't exist
            if (!isset($_SESSION['hidden_history'])) {
                $_SESSION['hidden_history'] = [];
            }
            // Add the session_id to the list of hidden items
            $_SESSION['hidden_history'][$session_id_to_hide] = true;
            set_flash_message('تم حذف سجل الاختبار من العرض بنجاح.', 'success');
        } else {
            set_flash_message('حدث خطأ أثناء محاولة حذف السجل.', 'error');
        }
        header("Location: ?view=profile");
        exit();
    }
    // The 'answer_question' and 'next_question' actions are now handled by api.php
}

// 4.5 معالجة طلبات إعادة تعيين كلمة المرور
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'request_password_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (empty($email)) {
        set_flash_message("يرجى إدخال البريد الإلكتروني.", "error");
        header("Location: ?view=password_reset_request");
    } else {
        $result = request_password_reset($email);
        if ($result['success']) {
            // Redirect to a confirmation page instead of the same page
            header("Location: ?view=password_reset_sent");
        } else {
            set_flash_message($result['message'], 'error');
            header("Location: ?view=password_reset_request");
        }
    }
    exit();
}
elseif ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $access_token = $_POST['access_token'] ?? '';
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($access_token) || empty($password) || empty($password_confirm)) {
        set_flash_message("يرجى ملء جميع الحقول.", "error");
    } elseif ($password !== $password_confirm) {
        set_flash_message("كلمتا المرور غير متطابقتين.", "error");
    } else {
        $result = reset_password($access_token, $password);
        if ($result['success']) {
            set_flash_message($result['message'], 'success');
            header("Location: ?view=login");
            exit();
        } else {
            set_flash_message($result['message'], 'error');
        }
    }
    // Redirect back to the same reset page with the token to allow another attempt
    header("Location: ?view=reset_password#access_token=" . urlencode($access_token));
    exit();
}


// 5. معالجة طلب تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?view=home");
    exit();
}

// Check if trying to access activation page without being logged in
if ($current_view === 'activate' && !isset($_SESSION['user'])) {
    $course_id_to_activate = $_GET['course_id'] ?? null;
    $redirect_url = '?view=activate';
    if ($course_id_to_activate) {
        $redirect_url .= '&course_id=' . $course_id_to_activate;
    }
    set_flash_message('يرجى تسجيل الدخول أولاً لتتمكن من تفعيل الاختبار.', 'warning');
    header('Location: ?view=login&redirect_url=' . urlencode($redirect_url));
    exit();
}

// **THE FIX**: Check if the course exists before showing the activation page.
if ($current_view === 'activate' && isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $course_exists_result = supabase_request('/rest/v1/courses?id=eq.' . $course_id . '&select=id', null, 'GET', true);
    
    if ($course_exists_result['http_code'] !== 200 || empty($course_exists_result['data'])) {
        // If the course does not exist, set a flash message and redirect.
        set_flash_message('سيتم إضافة اختبار الأسئلة لهذه المادة قريبًا.', 'info');
        header('Location: ?view=home');
        exit();
    }
}

// حماية صفحة الاختبار من الوصول المباشر بدون تسجيل دخول
if ($current_view === 'quiz') {
    // التحقق من وجود جلسة مستخدم نشطة
    if (!isset($_SESSION['user']['phone'])) {
        set_flash_message('يرجى تسجيل الدخول أولاً لبدء الاختبار.', 'warning');
        
        // حفظ رابط الاختبار لإعادة التوجيه إليه بعد تسجيل الدخول
        $redirect_url = '?view=quiz';
        if (isset($_GET['model_id'])) {
            $redirect_url .= '&model_id=' . intval($_GET['model_id']);
        }
        
        header('Location: ?view=login&redirect_url=' . urlencode($redirect_url));
        exit();
    }
    
    // التحقق من وجود معرّف النموذج (model_id)
    if (!isset($_GET['model_id'])) {
         set_flash_message('رقم نموذج الاختبار غير محدد.', 'error');
         header('Location: ?view=courses'); // توجيه إلى صفحة الدورات
         exit();
    }

    // التحقق من صلاحية الوصول إلى الاختبار (مفعل أو مجاني)
    $model_id = intval($_GET['model_id']);
    $model_details = get_quiz_model_details($model_id);

    if (!$model_details || !isset($model_details['course_id'])) {
        set_flash_message('نموذج الاختبار غير صالح أو لم يتم العثور عليه.', 'error');
        header('Location: ?view=courses');
        exit();
    }

    $course_id = $model_details['course_id'];
    $course = get_current_course($course_id);

    if (!$course) {
        set_flash_message('الاختبار المرتبط بهذا النموذج غير موجود.', 'error');
        header('Location: ?view=courses');
        exit();
    }

    $is_activated = in_array($course_id, $_SESSION['activated_courses'] ?? []);

    if (!$course['is_free'] && !$is_activated) {
        set_flash_message('يجب عليك تفعيل هذا الاختبار أولاً لتتمكن من بدء الاختبار.', 'error');
        header('Location: ?view=course_details&course_id=' . $course_id);
        exit();
    }
}


// **THE FIX IS HERE**: All page-specific validation logic is now placed BEFORE the header is included.

// Handle password reset view
if ($current_view === 'reset_password') {
    // The access token is expected in the URL fragment, e.g., #access_token=...
    // We can't access this server-side. We'll need JavaScript to handle it.
    // This view will now require a special script.
}
// Handle quiz review from profile
elseif ($current_view === 'quiz_review' && isset($_GET['session_id'])) {
    if (!isset($_SESSION['user']['phone'])) {
        set_flash_message('يرجى تسجيل الدخول لعرض هذه الصفحة.', 'warning');
        header('Location: ?view=login');
        exit();
    }
    $session_id = $_GET['session_id'];
    // This variable will be used in views/quiz_review.php
    $review_data = get_quiz_review_data_by_session($session_id, $_SESSION['user']['phone']);

    if (!$review_data) {
        set_flash_message('لم يتم العثور على بيانات المراجعة أو لا تملك صلاحية الوصول إليها.', 'error');
        header('Location: ?view=profile');
        exit();
    }
}
// Redirect to course_details if a course_id is provided on the courses view
elseif ($current_view === 'courses' && isset($_GET['course_id'])) {
    $current_view = 'course_details';
}

// Validation logic for course_details page
if ($current_view === 'course_details') {
    if (!isset($_SESSION['user']) || !isset($_GET['course_id'])) {
        header('Location: ?view=login&redirect_url=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $course_id = intval($_GET['course_id']);
    $course = get_current_course($course_id); 

    if (!$course) {
        set_flash_message('الاختبار غير موجود.', 'error');
        header('Location: ?view=courses');
        exit();
    }

    $is_activated = in_array($course_id, $_SESSION['activated_courses'] ?? []);

    if (!$course['is_free'] && !$is_activated) {
        set_flash_message('يجب تفعيل هذا الاختبار أولاً لعرض نماذج الاختبار.', 'error');
        header('Location: ?view=courses');
        exit();
    }
}

// 6. تضمين أجزاء القالب (Header, Footer) والعرض (View) المطلوب
require_once 'templates/header.php';

// توجيه العرض (Router)
$view_path = "views/{$current_view}.php";
if (file_exists($view_path)) {
    require_once $view_path;
} else {
    // إذا لم يتم العثور على الصفحة، يتم توجيهه إلى الصفحة الرئيسية
    require_once 'views/home.php';
}

require_once 'templates/footer.php';
