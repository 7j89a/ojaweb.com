<?php
// -----------------------------------------------------------------------------
// وظائف التفاعل مع SUPABASE (Functions for Supabase Interaction)
// -----------------------------------------------------------------------------

function supabase_request($endpoint, $data = null, $method = 'GET', $use_service_key = false) {
    global $supabase_url, $supabase_key, $supabase_service_key;

    $api_key = $use_service_key ? $supabase_service_key : $supabase_key;

    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $api_key,
        'Authorization: Bearer ' . $api_key
    ];

    // Special handling for specific POST requests to add required headers.
    // This is a targeted fix to avoid changing the function signature and breaking other calls.
    if ($method === 'POST') {
        if (strpos($endpoint, '/rest/v1/quiz_results') === 0) {
            // For saving quiz results, we need the inserted row back to get the attempt_id.
            $headers[] = 'Prefer: return=representation';
        }
        if (strpos($endpoint, '/rest/v1/student_first_attempt_results') === 0) {
            // For saving the first attempt, we want to ignore duplicates silently.
            $headers[] = 'Prefer: resolution=ignore-duplicates';
        }
    }

    if ($method === 'GET' && $data) {
        $separator = strpos($endpoint, '?') === false ? '?' : '&';
        $endpoint .= $separator . http_build_query($data);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabase_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($data && $method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $decoded_response = json_decode($response_body, true);

    if ($curl_error) {
        error_log("cURL Error for endpoint " . $endpoint . ": " . $curl_error);
        return [
            'data' => null,
            'http_code' => $http_code,
            'error_message' => 'cURL Error: ' . $curl_error
        ];
    }

    if ($http_code >= 400) {
        error_log("Supabase Error for endpoint " . $endpoint . " (HTTP " . $http_code . "): " . $response_body);
        $error_message = $decoded_response['message'] ?? 'An unknown database error occurred.';
        return [
            'data' => $decoded_response,
            'http_code' => $http_code,
            'error_message' => $error_message
        ];
    }

    return [
        'data' => $decoded_response,
        'http_code' => $http_code,
        'error_message' => null
    ];
}

// تم تعديل هذه الدالة لتعمل مع رقم الهاتف بدلاً من user_id
function get_user_activated_courses($phone_number) {
    if (empty($phone_number)) {
        error_log("get_user_activated_courses called with empty phone number.");
        return [];
    }

    $result = supabase_request('/rest/v1/rpc/get_user_activated_courses', ['p_phone_number' => $phone_number], 'POST');
    
    if ($result['http_code'] === 200 && isset($result['data'])) {
        // تسجيل الاستجابة الخام من Supabase للمساعدة في التصحيح
        error_log("Supabase response for get_user_activated_courses (phone: $phone_number): " . json_encode($result['data']));

        $activated_ids = [];
        if (is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                if (isset($item['course_id'])) {
                    $activated_ids[] = (int)$item['course_id'];
                }
            }
        }
        error_log("Processed activated courses for $phone_number: " . json_encode($activated_ids));
        return $activated_ids;
    }

    error_log("Failed to get activated courses for $phone_number. HTTP code: " . $result['http_code']);
    return [];
}

// تم تعديل هذه الدالة لتعمل مع رقم الهاتف بدلاً من user_id
function save_quiz_result($phone_number, $course_id, $model_id, $score, $total_questions) {
    // This should now call a new or modified RPC function in Supabase
    // For now, let's assume we are inserting directly into the table
    $data = [
        'phone_number' => $phone_number,
        'course_id' => $course_id,
        'quiz_model_id' => $model_id,
        'score' => $score,
        'total_questions' => $total_questions,
        'percentage' => $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0,
        'session_id' => session_id()
    ];
    $result = supabase_request('/rest/v1/quiz_results', $data, 'POST', true);
    return $result['http_code'] === 201;
}

// تم تعديل هذه الدالة لتعمل مع رقم الهاتف بدلاً من user_id
function save_user_answer($phone_number, $model_id, $question_id, $question_number, $question_text, $selected_answer_index, $correct_answer_index, $is_correct) {
    // This should also call a new or modified RPC function or insert directly
    $data = [
        'phone_number' => $phone_number,
        'quiz_model_id' => $model_id,
        'question_id' => $question_id,
        'question_number' => $question_number,
        'question_text' => $question_text,
        'selected_answer' => $selected_answer_index,
        'correct_answer' => $correct_answer_index,
        'is_correct' => $is_correct,
        'quiz_session_id' => session_id()
    ];
     $result = supabase_request('/rest/v1/user_answers', $data, 'POST', true);
    return $result['http_code'] === 201;
}

// دالة محدثة للتسجيل - تقوم بإنشاء حساب في Supabase Auth وإرسال بريد تحقق
function signup_user($first_name, $middle_name, $last_name, $email, $phone, $password) {
    $full_name = $first_name . ' ' . $middle_name . ' ' . $last_name;
    
    // Use the public signup endpoint which sends a verification email by default.
    $auth_data = [
        'email' => $email,
        'password' => $password,
        'data' => [ // user_metadata is nested under 'data' for the public signup endpoint
            'full_name' => $full_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'phone' => $phone
        ]
    ];
    // Use the standard signup endpoint, not the admin one. No service key needed.
    $auth_result = supabase_request('/auth/v1/signup', $auth_data, 'POST', false);

    $http_code = $auth_result['http_code'];
    $response_data = $auth_result['data'];

    // A successful signup returns a 200 OK with the user object.
    if ($http_code === 200 && isset($response_data['id'])) {
        // Supabase part was successful, now add to our public.phone table.
        add_phone_registration($first_name, $middle_name, $last_name, $phone, $email);
        return ['success' => true];
    }

    // If we are here, the signup failed.
    $error_message = $response_data['msg'] ?? 'حدث خطأ غير متوقع أثناء إنشاء الحساب.';
    if (isset($response_data['message'])) {
        $error_message = $response_data['message'];
    }
    error_log("Supabase signup failed with code " . $http_code . ": " . json_encode($response_data));
    return ['success' => false, 'message' => $error_message];
}

// دالة جديدة لإضافة بيانات التسجيل إلى جدول phone
function add_phone_registration($first_name, $middle_name, $last_name, $phone_number, $email) {
    $result = supabase_request('/rest/v1/rpc/add_phone_registration', [
        'p_first_name' => $first_name,
        'p_middle_name' => $middle_name,
        'p_last_name' => $last_name,
        'p_phone_number' => $phone_number,
        'p_email' => $email
    ], 'POST', true); // Use service key for this internal operation
    
    // A successful RPC call can return 200 or 204. Checking for any 2xx is safer.
    if (!($result['http_code'] >= 200 && $result['http_code'] < 300)) {
        error_log("Failed to add phone registration for " . $email . ". Response: " . json_encode($result));
    }
    return $result['http_code'] >= 200 && $result['http_code'] < 300;
}

// دالة محدثة للتحقق من تسجيل الدخول
function verify_login($email, $password) {
    $data = [
        'grant_type' => 'password',
        'email' => $email,
        'password' => $password
    ];
    $result = supabase_request('/auth/v1/token?grant_type=password', $data, 'POST');

    if ($result['http_code'] === 200 && isset($result['data']['user'])) {
        $supa_user = $result['data']['user'];

        // **THE FIX**: Re-enable email confirmation check.
        if (empty($supa_user['email_confirmed_at'])) {
            return ['status' => 'error', 'message' => 'يرجى تفعيل حسابك أولاً. لقد أرسلنا رابط تحقق إلى بريدك الإلكتروني.'];
        }

        // الحصول على معلومات المستخدم من جدول phone
        $phone_info = get_user_info_by_email($supa_user['email']);
        error_log("Phone info for " . $supa_user['email'] . ": " . json_encode($phone_info));
        
        // إذا لم يكن المستخدم موجودًا في جدول phone، قم بإضافته
        if (!$phone_info) {
            // Extract names from metadata
            $first_name = $supa_user['user_metadata']['first_name'] ?? 'مستخدم';
            $middle_name = $supa_user['user_metadata']['middle_name'] ?? 'جديد';
            $last_name = $supa_user['user_metadata']['last_name'] ?? '';
            $phone_number = $supa_user['user_metadata']['phone'] ?? null;

            if ($phone_number) {
                add_phone_registration($first_name, $middle_name, $last_name, $phone_number, $supa_user['email']);
                $phone_info = get_user_info_by_email($supa_user['email']);
            }
        }

        // The device token logic was removed because the 'device_token' column does not exist in the 'phone' table.

        return [
            'status' => 'success',
            'user' => [
                'user_id' => $supa_user['id'],
                'email' => $supa_user['email'],
                'first_name' => $phone_info['first_name'] ?? '',
                'middle_name' => $phone_info['middle_name'] ?? '',
                'last_name' => $phone_info['last_name'] ?? '',
                'full_name' => ($phone_info['first_name'] ?? '') . ' ' . ($phone_info['middle_name'] ?? '') . ' ' . ($phone_info['last_name'] ?? ''),
                'phone' => $phone_info['phone_number'] ?? ($supa_user['user_metadata']['phone'] ?? '')
            ]
        ];
    }
    return ['status' => 'error', 'message' => 'invalid_credentials'];
}

// دالة جديدة للحصول على معلومات المستخدم من جدول phone بواسطة الإيميل
function get_user_info_by_email($email) {
    $result = supabase_request('/rest/v1/phone?email=eq.' . urlencode($email), null, 'GET');
    
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }
    
    return null;
}

// دالة جديدة للحصول على معلومات المستخدم من جدول phone بواسطة رقم الهاتف
function get_user_info_by_phone($phone_number) {
    $result = supabase_request('/rest/v1/rpc/get_user_info', ['p_phone_number' => $phone_number], 'POST');
    
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }
    
    return null;
}

// تم تعديل هذه الدالة لتعمل مع رقم الهاتف بدلاً من user_id
function get_user_quiz_history($phone_number) {
    // **THE FIX**: The database schema in suba.sql is missing the foreign keys needed for Supabase to join tables automatically.
    // This function is updated to fetch the main results first, then manually "join" the course and model titles in PHP.

    // Step 1: Fetch the raw quiz results.
    $endpoint = '/rest/v1/quiz_results?phone_number=eq.' . urlencode($phone_number) . '&select=*&order=completed_at.desc&limit=10';
    $result = supabase_request($endpoint, null, 'GET', true);
    
    if ($result['http_code'] !== 200 || empty($result['data'])) {
        return []; // Return empty if no results or an error occurred.
    }

    $quiz_history = $result['data'];

    // Step 2: Manually enrich the results with course and model titles.
    foreach ($quiz_history as &$item) {
        // Fetch course title
        if (isset($item['course_id'])) {
            $course_result = supabase_request('/rest/v1/courses?id=eq.' . $item['course_id'] . '&select=title', null, 'GET', true);
            // The structure needs to match what the profile view expects: $item['courses']['title']
            $item['courses'] = ['title' => $course_result['data'][0]['title'] ?? 'اختبار غير معروف'];
        }

        // Fetch quiz model title
        if (isset($item['quiz_model_id'])) {
            $model_result = supabase_request('/rest/v1/quiz_models?id=eq.' . $item['quiz_model_id'] . '&select=title', null, 'GET', true);
            // The structure needs to match what the profile view expects: $item['quiz_models']['title']
            $item['quiz_models'] = ['title' => $model_result['data'][0]['title'] ?? 'نموذج غير معروف'];
        }
    }
    unset($item); // Unset the reference to the last element.

    // **THE FIX**: Filter out any history items that the user has "deleted".
    if (isset($_SESSION['hidden_history']) && is_array($_SESSION['hidden_history'])) {
        $quiz_history = array_filter($quiz_history, function($item) {
            return !isset($_SESSION['hidden_history'][$item['session_id']]);
        });
    }

    return $quiz_history;
}

function get_quiz_review_data_by_session($session_id, $phone_number) {
    if (empty($session_id) || empty($phone_number)) {
        return null;
    }

    // First, verify the session belongs to the user and get the quiz_model_id
    $result_check = supabase_request('/rest/v1/quiz_results?session_id=eq.' . urlencode($session_id) . '&phone_number=eq.' . urlencode($phone_number) . '&select=quiz_model_id', null, 'GET', true);
    if ($result_check['http_code'] !== 200 || empty($result_check['data'])) {
        error_log("Review access denied or session not found for session_id: $session_id");
        return null;
    }
    $model_id = $result_check['data'][0]['quiz_model_id'];

    // Get all user answers for this session
    $user_answers_result = supabase_request('/rest/v1/user_answers?quiz_session_id=eq.' . urlencode($session_id) . '&select=*&order=question_number.asc', null, 'GET', true);
    if ($user_answers_result['http_code'] !== 200) {
        return null;
    }
    $user_answers = $user_answers_result['data'];

    // Get all original questions for the model
    $questions = get_quiz_questions($model_id);
    if ($questions === null) {
        return null;
    }
    
    // Get model title
    $model_details = get_quiz_model_details($model_id);
    $model_title = $model_details['title'] ?? 'Unknown Model';

    return [
        'user_answers' => $user_answers,
        'questions' => $questions,
        'model_title' => $model_title
    ];
}

function get_current_course($course_id) {
    // This function now fetches just the course details.
    $result = supabase_request('/rest/v1/courses?id=eq.' . $course_id . '&select=*', null, 'GET', false);

    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }

    error_log("Failed to fetch course for course_id: " . $course_id);
    return null;
}

// دالة جديدة لجلب نماذج الاختبار لاختبار معين
function get_quiz_models_for_course($course_id) {
    $endpoint = '/rest/v1/quiz_models?course_id=eq.' . $course_id . '&is_visible=eq.true&select=*';
    $result = supabase_request($endpoint, null, 'GET', false);
    if ($result['http_code'] === 200) {
        return $result['data'];
    }
    return [];
}

// دالة جديدة لجلب نموذج اختبار واحد مع أسئلته
function get_quiz_model_with_questions($model_id) {
    $model_id = intval($model_id);
    
    // First, get the model details
    $model_result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&select=*', null, 'GET', true);
    if ($model_result['http_code'] !== 200 || empty($model_result['data'])) {
        return null;
    }
    $model = $model_result['data'][0];

    // Then, get the questions for that model
    $questions_result = supabase_request('/rest/v1/questions?quiz_model_id=eq.' . $model_id . '&select=*&order=created_at.asc', null, 'GET', true);
    if ($questions_result['http_code'] === 200) {
        $questions = $questions_result['data'];
        // **THE FIX IS HERE**: Decode options for each question
        foreach ($questions as &$question) {
            if (isset($question['options']) && is_string($question['options'])) {
                $decoded_options = json_decode($question['options'], true);
                $question['options'] = $decoded_options !== null ? $decoded_options : [];
            } else {
                $question['options'] = [];
            }
        }
        $model['questions'] = $questions;
    } else {
        $model['questions'] = [];
    }

    return $model;
}

function set_flash_message($message, $type) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $type;
}

// دالة جديدة للتحقق من تفعيل الاختبار للمستخدم
function is_course_activated_for_user($phone_number, $course_id) {
    $result = supabase_request('/rest/v1/rpc/is_course_activated_for_user', [
        'p_phone_number' => $phone_number,
        'p_course_id' => $course_id
    ], 'POST', true); // Use service key for this check
    
    // This RPC returns a single boolean value directly in the response body.
    if ($result['http_code'] === 200) {
        return $result['data']; // This will be true or false
    }
    
    error_log("Failed to check if course is activated. Supabase response: " . json_encode($result));
    return false;
}

// دالة تفعيل الاختبار المبسطة التي تستدعي الدالة الشاملة في قاعدة البيانات
function activate_course_with_code($phone_number, $course_id, $activation_code) {
    $payload = [
        'p_phone_number' => $phone_number,
        'p_course_id' => $course_id,
        'p_activation_code' => $activation_code
    ];
    // تسجيل البيانات المرسلة للتشخيص
    error_log("--- [تفعيل الاختبار] البيانات المرسلة: " . json_encode($payload));

    // استدعاء الدالة الشاملة `master_activate_course` في Supabase
    $result = supabase_request('/rest/v1/rpc/master_activate_course', $payload, 'POST', true);

    // تسجيل الاستجابة الكاملة من قاعدة البيانات للتشخيص
    error_log("--- [تفعيل الاختبار] الاستجابة من قاعدة البيانات: " . json_encode($result));

    // الدالة في قاعدة البيانات ترجع كائن JSON يحتوي على 'success' و 'message'
    if ($result['http_code'] === 200 && isset($result['data'])) {
        return $result['data'];
    }

    // في حالة فشل استدعاء الـ RPC نفسه
    error_log("--- [تفعيل الدورة] فشل استدعاء RPC: " . json_encode($result));
    return ['success' => false, 'message' => 'حدث خطأ فني أثناء محاولة التفعيل. يرجى مراجعة سجلات الخادم.'];
}

// دالة جديدة للحصول على إحصائيات الأكواد
function get_codes_statistics() {
    $result = supabase_request('/rest/v1/rpc/get_codes_statistics', null, 'POST');
    
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }
    
    return ['total' => 0, 'used' => 0, 'unused' => 0, 'active' => 0];
}

// دالة جديدة لإنشاء كود تفعيل جديد
function create_new_activation_code() {
    $result = supabase_request('/rest/v1/rpc/create_new_activation_code', null, 'POST');
    
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0] ?? null;
    }
    
    return null;
}

// دالة جديدة لإنشاء عدة أكواد دفعة واحدة
function generate_bulk_codes($count = 10) {
    $result = supabase_request('/rest/v1/rpc/generate_bulk_codes', ['codes_count' => $count], 'POST');
    
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return array_column($result['data'], 'generated_code');
    }
    
    return [];
}

// دالة جديدة للحصول على إحصائيات المستخدم
function get_user_stats($quiz_history) {
    // **THE FIX**: This function now accepts the filtered quiz history to calculate stats dynamically.
    
    // Step 1: If there's no data, return default zero values.
    if (empty($quiz_history)) {
        return ['total_quizzes' => 0, 'average_score' => 0];
    }

    // Step 2: Calculate stats based on the provided history.
    $total_quizzes = count($quiz_history);
    $total_percentage = 0;

    foreach ($quiz_history as $row) {
        $total_percentage += $row['percentage'] ?? 0;
    }

    $average_score = ($total_quizzes > 0) ? ($total_percentage / $total_quizzes) : 0;

    // Step 3: Return the calculated stats.
    return [
        'total_quizzes' => $total_quizzes,
        'average_score' => round($average_score)
    ];
}

// --- Functions needed for the new API-driven quiz ---

function get_quiz_model_details($model_id) {
    // **THE FIX IS HERE**: Added course_id to the select query.
    $result = supabase_request('/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&select=id,title,timer_type,total_time_seconds,course_id', null, 'GET', true);
    if ($result['http_code'] === 200 && !empty($result['data'])) {
        return $result['data'][0];
    }
    return null;
}

function get_quiz_questions($model_id) {
    $select_query = 'id,question_text,question_image,options,correct,explanation,needs_calculator,time_limit_seconds';
    $result = supabase_request('/rest/v1/questions?quiz_model_id=eq.' . intval($model_id) . '&select=' . $select_query . '&order=created_at.asc', null, 'GET', true);
    
    if ($result['http_code'] === 200) {
        $questions = $result['data'];
        // Decode text fields to render HTML correctly and decode options JSON
        foreach ($questions as &$question) {
            $question['question_text'] = isset($question['question_text']) ? htmlspecialchars_decode($question['question_text'], ENT_QUOTES) : '';
            $question['explanation'] = isset($question['explanation']) ? htmlspecialchars_decode($question['explanation'], ENT_QUOTES) : '';
            
            $options_raw = $question['options'] ?? '[]';
            $decoded_options = json_decode($options_raw, true);
            if (is_array($decoded_options)) {
                foreach ($decoded_options as &$option) {
                    if (isset($option['value']) && $option['type'] === 'text') {
                        $option['value'] = htmlspecialchars_decode($option['value'], ENT_QUOTES);
                    }
                }
                $question['options'] = $decoded_options;
            } else {
                $question['options'] = [];
            }
        }
        return $questions;
    }
    return null;
}

// --- New function to get the currently logged-in user's ID ---
function get_current_user_id() {
    // The session is now started reliably in index.php and api.php.
    // No need to start it here.
    return $_SESSION['user']['user_id'] ?? null;
}

// Wrapper function to fix the undefined function error
function register_user($first_name, $middle_name, $last_name, $email, $phone, $password) {
    // First, check if a user with the same phone number or email already exists.
    $existing_phone = supabase_request('/rest/v1/phone?phone_number=eq.' . urlencode($phone) . '&select=phone_number', null, 'GET', true);
    if (!empty($existing_phone['data'])) {
        return ['status' => 'error', 'message' => 'رقم الهاتف هذا مسجل بالفعل.'];
    }

    // Correctly check for an existing user in Supabase Auth using the admin endpoint
    $auth_check_result = supabase_request('/auth/v1/admin/users', null, 'GET', true);
    if (isset($auth_check_result['data']['users']) && is_array($auth_check_result['data']['users'])) {
        foreach ($auth_check_result['data']['users'] as $user) {
            if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
                return ['status' => 'error', 'message' => 'هذا البريد الإلكتروني مسجل بالفعل.'];
            }
        }
    }

    // If no existing user, proceed with signup.
    $result = signup_user($first_name, $middle_name, $last_name, $email, $phone, $password);
    
    if ($result['success']) {
        return ['status' => 'success'];
    } else {
        return ['status' => 'error', 'message' => $result['message']];
    }
}

// -----------------------------------------------------------------------------
// وظائف إعادة تعيين كلمة المرور (Password Reset Functions)
// -----------------------------------------------------------------------------

/**
 * يبدأ عملية إعادة تعيين كلمة المرور.
 * ينشئ رمزًا مميزًا، ويخزنه، ويرسل بريدًا إلكترونيًا للمستخدم.
 *
 * @param string $email البريد الإلكتروني للمستخدم.
 * @return array نتيجة العملية.
 */
function request_password_reset($email) {
    global $supabase_url, $supabase_key;

    // الخطوة 1: التحقق من وجود المستخدم بهذا البريد الإلكتروني في جدول "phone"
    $user_check = supabase_request('/rest/v1/phone?email=eq.' . urlencode($email) . '&select=email', null, 'GET', true);
    if (empty($user_check['data'])) {
        // لا نكشف ما إذا كان البريد الإلكتروني موجودًا أم لا لأسباب أمنية.
        // نرجع دائمًا نجاحًا لمنع تعداد المستخدمين.
        return ['success' => true, 'message' => 'إذا كان بريدك الإلكتروني موجودًا في نظامنا، فسيتم إرسال رابط إعادة تعيين كلمة المرور.'];
    }

    // الخطوة 2: استدعاء دالة Supabase Auth لإرسال بريد إعادة التعيين
    // هذا يتطلب أن تكون قوالب البريد الإلكتروني معدة في Supabase
    $reset_result = supabase_request('/auth/v1/recover', ['email' => $email], 'POST');

    if ($reset_result['http_code'] === 200) {
        return ['success' => true, 'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.'];
    } else {
        error_log("Supabase password recovery failed for email " . $email . ": " . json_encode($reset_result));
        return ['success' => false, 'message' => 'فشل إرسال بريد إعادة تعيين كلمة المرور. يرجى المحاولة مرة أخرى.'];
    }
}

/**
 * يعيد تعيين كلمة مرور المستخدم باستخدام الرمز المميز.
 *
 * @param string $token الرمز المميز من البريد الإلكتروني.
 * @param string $new_password كلمة المرور الجديدة.
 * @return array نتيجة العملية.
 */
function reset_password($access_token, $new_password) {
    global $supabase_url, $supabase_key;

    // Supabase JS library handles the user update based on the access token in the session fragment.
    // In PHP, we need to pass the access token explicitly in the Authorization header.
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $access_token
    ];

    $data = ['password' => $new_password];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabase_url . '/auth/v1/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.'];
    } else {
        error_log("Supabase password update failed. HTTP Code: " . $http_code . " Body: " . $response_body);
        $decoded_response = json_decode($response_body, true);
        $error_message = $decoded_response['msg'] ?? 'فشل تحديث كلمة المرور. قد يكون الرابط غير صالح أو منتهي الصلاحية.';
        return ['success' => false, 'message' => $error_message];
    }
}

/**
 * Helper function to check if a user is an authenticated admin or teacher.
 *
 * @return bool True if the user is logged in as an admin or teacher, false otherwise.
 */
function is_authenticated_user() {
    // The session is assumed to be started already by the calling script (e.g., api.php).
    $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    $is_teacher = isset($_SESSION['teacher_id']);
    return $is_admin || $is_teacher;
}
?>
