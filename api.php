<?php
// Start the session unconditionally at the very beginning of the script.
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'functions.php'; // Include shared functions

$input = json_decode(file_get_contents('php://input'), true);
$response = ['status' => 'error', 'message' => 'Invalid Request'];

if (!isset($input['action'])) {
    echo json_encode($response);
    exit();
}

switch ($input['action']) {
    case 'get_teachers':
        $result = supabase_request('/rest/v1/teachers?select=id,full_name,username', null, 'GET', true);
        if ($result['http_code'] === 200) {
            $response = ['status' => 'success', 'data' => $result['data']];
        } else {
            $response['message'] = 'Error fetching teachers: ' . ($result['error_message'] ?? 'Unknown error');
        }
        break;

    case 'add_teacher':
        if (empty($input['full_name']) || empty($input['username']) || empty($input['password'])) {
            $response['message'] = 'Full name, username, and password are required.';
            break;
        }
        $new_teacher_data = [
            'full_name' => htmlspecialchars($input['full_name'], ENT_QUOTES, 'UTF-8'),
            'username' => htmlspecialchars($input['username'], ENT_QUOTES, 'UTF-8'),
            'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
            'email' => filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL)
        ];
        $result = supabase_request('/rest/v1/teachers', $new_teacher_data, 'POST', true);
        if ($result['http_code'] === 201) {
            $response = ['status' => 'success', 'message' => 'Teacher added successfully.'];
        } else {
            $response['message'] = 'Failed to add teacher. ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'delete_teacher':
        if (empty($input['teacher_id'])) {
            $response['message'] = 'Teacher ID is required.';
            break;
        }
        $teacher_id = intval($input['teacher_id']);
        $result = supabase_request('/rest/v1/teachers?id=eq.' . $teacher_id, null, 'DELETE', true);
        if ($result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Teacher deleted successfully.'];
        } else {
            $response['message'] = 'Failed to delete teacher. ' . ($result['error_message'] ?? '');
        }
        break;

    case 'get_courses':
        // Public view should only get visible courses
        $result = supabase_request('/rest/v1/courses?select=*&is_visible=eq.true', null, 'GET');
        if ($result['http_code'] === 200) {
            $response = ['status' => 'success', 'data' => $result['data']];
        } else {
            $response['message'] = 'Error fetching courses: ' . ($result['error_message'] ?? 'Unknown error');
        }
        break;

    case 'get_course_details':
        if (empty($input['course_id'])) {
            $response['message'] = 'Course ID is required.';
            break;
        }
        $course_id = intval($input['course_id']);
        $result = supabase_request('/rest/v1/courses?id=eq.' . $course_id . '&select=id,title');
        if ($result['http_code'] === 200 && !empty($result['data'])) {
            $response = ['status' => 'success', 'data' => $result['data'][0]];
        } else {
            $response['message'] = 'Course not found or error fetching details.';
        }
        break;

    case 'get_course_questions':
        if (empty($input['model_id'])) {
            $response['message'] = 'Model ID is required.';
            break;
        }
        $model_id = intval($input['model_id']);
        $is_admin_or_teacher = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || isset($_SESSION['teacher_id']);

        // Security check: Public can only see visible models.
        if (!$is_admin_or_teacher) {
            $model_check = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&is_visible=eq.true&select=id', null, 'GET', false);
            if ($model_check['http_code'] !== 200 || empty($model_check['data'])) {
                $response['message'] = 'Quiz model not found or is currently hidden.';
                break;
            }
        }

        // Admins and teachers can see all questions for a model they have access to.
        // **THE FIX**: Now ordering by the new 'question_order' column.
        $select_query = 'id,question_text,question_image,options,correct,explanation,needs_calculator,time_limit_seconds,question_order';
        $result = supabase_request('/rest/v1/questions?quiz_model_id=eq.' . $model_id . '&select=' . $select_query . '&order=question_order.asc,created_at.asc', null, 'GET', true);
        
        if ($result['http_code'] === 200) {
            $questions = $result['data'];
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
            $response = ['status' => 'success', 'data' => $questions];
        } else {
            $response['message'] = 'Error fetching questions: ' . ($result['error_message'] ?? 'Unknown error');
        }
        break;

    case 'get_quiz_models':
        if (empty($input['course_id'])) {
            $response['message'] = 'Course ID is required.';
            break;
        }
        $course_id = intval($input['course_id']);
        $is_admin_or_teacher = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || isset($_SESSION['teacher_id']);

        // For admins/teachers, we fetch all models and their visibility status.
        // For the public, we only fetch visible models.
        if ($is_admin_or_teacher) {
            $select_query = 'id,title,timer_type,total_time_seconds,is_visible,questions_count';
            $api_url = '/rest/v1/quiz_models?course_id=eq.' . $course_id . '&select=' . $select_query . '&order=created_at.asc';
            $use_auth = true; // Use admin/teacher credentials
        } else {
            $select_query = 'id,title,timer_type,total_time_seconds,questions_count';
            $api_url = '/rest/v1/quiz_models?course_id=eq.' . $course_id . '&is_visible=eq.true&select=' . $select_query . '&order=created_at.asc';
            $use_auth = false; // Use public anon key
        }

        $result = supabase_request($api_url, null, 'GET', $use_auth);
        
        if ($result['http_code'] === 200) {
            $response = ['status' => 'success', 'data' => $result['data']];
        } else {
            $response['message'] = 'Error fetching quiz models: ' . ($result['error_message'] ?? 'Unknown error');
        }
        break;

    case 'get_quiz_model_details':
        if (empty($input['model_id'])) {
            $response['message'] = 'Model ID is required.';
            break;
        }
        $model_id = intval($input['model_id']);
        $is_admin_or_teacher = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || isset($_SESSION['teacher_id']);

        // Admins/teachers can get details for any model, public can only get visible ones.
        $api_url = '/rest/v1/quiz_models?id=eq.' . $model_id . '&select=*,courses(id,title)';
        if (!$is_admin_or_teacher) {
            $api_url .= '&is_visible=eq.true';
        }

        $result = supabase_request($api_url, null, 'GET', $is_admin_or_teacher);

        if ($result['http_code'] === 200 && !empty($result['data'])) {
            $response = ['status' => 'success', 'data' => $result['data'][0]];
        } else {
            $response['message'] = 'Quiz model not found or you do not have permission to view it.';
        }
        break;

    case 'add_quiz_model':
        if (empty($input['course_id']) || empty($input['title']) || empty($input['timer_type'])) {
            $response['message'] = 'Incomplete data for quiz model.';
            break;
        }
        
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

        // A teacher must be logged in to create a model. Admins currently don't create models directly, but this could be adjusted.
        if (!$teacher_id && !$is_admin) {
             $response['message'] = 'Authentication required to add a model.';
             break;
        }

        // If a teacher is creating it, we must verify they own the course.
        if ($teacher_id) {
            $course_check_result = supabase_request('/rest/v1/courses?id=eq.' . intval($input['course_id']) . '&teacher_id=eq.' . $teacher_id . '&select=id', null, 'GET', true);
            if ($course_check_result['http_code'] !== 200 || empty($course_check_result['data'])) {
                $response['message'] = 'Error: You do not have permission to add a model to this course.';
                break;
            }
        }

        $new_model_data = [
            'course_id' => intval($input['course_id']),
            'title' => htmlspecialchars($input['title'], ENT_QUOTES, 'UTF-8'),
            'timer_type' => htmlspecialchars($input['timer_type'], ENT_QUOTES, 'UTF-8'),
            'total_time_seconds' => isset($input['total_time_seconds']) ? intval($input['total_time_seconds']) : null,
            'is_visible' => isset($input['is_visible']) ? (bool)$input['is_visible'] : true, // Default to visible
            // Assign the teacher_id if a teacher is creating it.
            'teacher_id' => $teacher_id
        ];

        $result = supabase_request('/rest/v1/quiz_models', $new_model_data, 'POST', true);
        if ($result['http_code'] === 201) {
            $response = ['status' => 'success', 'message' => 'Quiz model added successfully.'];
        } else {
            $response['message'] = 'Failed to add quiz model. HTTP Code: ' . $result['http_code'] . '. Response: ' . json_encode($result['data']);
        }
        break;

    case 'update_quiz_model':
        if (empty($input['model_id']) || empty($input['title']) || empty($input['timer_type'])) {
            $response['message'] = 'Incomplete data for updating quiz model.';
            break;
        }
        $model_id = intval($input['model_id']);
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

        // Security Check: User must be admin or owning teacher
        $can_update = false;
        if ($is_admin) {
            $can_update = true;
        } elseif ($teacher_id) {
            $model_check = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&teacher_id=eq.' . $teacher_id . '&select=id', null, 'GET', true);
            if ($model_check['http_code'] === 200 && !empty($model_check['data'])) {
                $can_update = true;
            }
        }

        if (!$can_update) {
            $response['message'] = 'Unauthorized: You do not have permission to update this model.';
            http_response_code(403);
            break;
        }

        $update_data = [
            'title' => htmlspecialchars($input['title'], ENT_QUOTES, 'UTF-8'),
            'timer_type' => htmlspecialchars($input['timer_type'], ENT_QUOTES, 'UTF-8'),
            'total_time_seconds' => isset($input['total_time_seconds']) ? intval($input['total_time_seconds']) : null,
            'is_visible' => isset($input['is_visible']) ? (bool)$input['is_visible'] : true,
        ];

        $result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id, $update_data, 'PATCH', true);

        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Quiz model updated successfully.'];
        } else {
            $response['message'] = 'Failed to update quiz model. ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'add_course':
        if (empty($input['title'])) {
            $response['message'] = 'Course title is required.';
            break;
        }
        $new_course_data = [
            'title' => htmlspecialchars($input['title'], ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($input['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'is_free' => isset($input['is_free']) ? (bool)$input['is_free'] : false,
            'difficulty' => 'متوسط',
            'category' => 'عام',
            'icon' => '📚',
            'rating' => 0,
            'students' => 0
        ];
        $result = supabase_request('/rest/v1/courses', $new_course_data, 'POST', true);
        if ($result['http_code'] === 201) {
            $response = ['status' => 'success', 'message' => 'Course added successfully.'];
        } else {
            $response['message'] = 'Failed to add course. HTTP Code: ' . $result['http_code'] . '. Response: ' . json_encode($result['data']);
        }
        break;

    case 'add_question':
        // Security check: Only logged-in admins or teachers can add questions.
        $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        if (!$is_admin && !$teacher_id) {
            $response['message'] = 'Authentication required to add a question.';
            http_response_code(401);
            break;
        }

        if (empty($input['model_id']) || empty($input['options']) || !isset($input['correct'])) {
            $response['message'] = 'Incomplete data for adding a question.';
            break;
        }
        
        $sanitized_options = [];
        if (is_array($input['options'])) {
            foreach ($input['options'] as $option) {
                if (isset($option['type'], $option['value'])) {
                    $sanitized_options[] = [
                        'type' => htmlspecialchars($option['type'], ENT_QUOTES, 'UTF-8'),
                        'value' => ($option['type'] === 'image') 
                            ? filter_var($option['value'], FILTER_SANITIZE_URL) 
                            : htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8')
                    ];
                }
            }
        }

        $model_id = intval($input['model_id']);

        // **THE FIX**: Get the max order number to place the new question at the end.
        $order_result = supabase_request('/rest/v1/questions?quiz_model_id=eq.' . $model_id . '&select=question_order&order=question_order.desc&limit=1', null, 'GET', true);
        $max_order = 0;
        if ($order_result['http_code'] === 200 && !empty($order_result['data'])) {
            $max_order = $order_result['data'][0]['question_order'] ?? 0;
        }

        $new_question_data = [
            'quiz_model_id' => $model_id,
            'question_order' => $max_order + 1,
            'question_text' => $input['question_text'] ?? '',
            'question_image' => filter_var($input['question_image'] ?? '', FILTER_SANITIZE_URL),
            'options' => json_encode($sanitized_options, JSON_UNESCAPED_UNICODE),
            'correct' => intval($input['correct']),
            'explanation' => $input['explanation'] ?? '',
            'needs_calculator' => isset($input['needs_calculator']) ? (bool)$input['needs_calculator'] : false,
            'time_limit_seconds' => isset($input['time_limit_seconds']) ? intval($input['time_limit_seconds']) : 60
        ];
        $result = supabase_request('/rest/v1/questions', $new_question_data, 'POST', true);
        if ($result['http_code'] === 201) {
            $response = ['status' => 'success', 'message' => 'Question added successfully.'];
        } else {
            $response['message'] = 'Failed to add question. HTTP Code: ' . $result['http_code'] . '. Response: ' . json_encode($result['data']);
        }
        break;

    case 'duplicate_question':
        if (!isset($input['question_id'])) {
            $response['message'] = 'Question ID is required.';
            break;
        }
        $question_id = intval($input['question_id']);
        // Security check could be added here to ensure user owns the question's model
        
        $params = ['p_question_id' => $question_id];
        $result = supabase_request('/rest/v1/rpc/duplicate_question', $params, 'POST', true);

        if ($result['http_code'] === 200) {
            $response = ['status' => 'success', 'message' => 'تم نسخ السؤال بنجاح.'];
        } else {
            $response['message'] = 'Failed to duplicate question: ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'update_question_order':
        if (empty($input['ordered_ids']) || !is_array($input['ordered_ids'])) {
            $response['message'] = 'An array of ordered question IDs is required.';
            break;
        }
        // Security check could be added here to ensure user owns the questions' model
        
        $params = ['p_question_ids' => $input['ordered_ids']];
        $result = supabase_request('/rest/v1/rpc/update_question_order', $params, 'POST', true);

        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'تم تحديث ترتيب الأسئلة بنجاح.'];
        } else {
            $response['message'] = 'Failed to update question order: ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'delete_course':
        if (empty($input['course_id'])) {
            $response['message'] = 'Course ID is required.';
            break;
        }
        $course_id = intval($input['course_id']);
        $result = supabase_request('/rest/v1/courses?id=eq.' . $course_id, null, 'DELETE', true);
        if ($result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Course deleted successfully.'];
        } else {
            $response['message'] = 'Failed to delete course. ' . ($result['error_message'] ?? '');
        }
        break;

    case 'delete_question':
        if (!isset($input['question_id'])) {
            $response['message'] = 'Question ID is required.';
            break;
        }
        $question_id = intval($input['question_id']);
        $result = supabase_request('/rest/v1/questions?id=eq.' . $question_id, null, 'DELETE', true);
        if ($result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Question deleted successfully.'];
        } else {
            $response['message'] = 'Failed to delete question. ' . ($result['error_message'] ?? '');
        }
        break;

    case 'update_question':
        if (empty($input['question_id']) || empty($input['options']) || !isset($input['correct'])) {
            $response['message'] = 'Incomplete data for updating a question.';
            break;
        }
        
        $question_id = intval($input['question_id']);

        $sanitized_options = [];
        if (is_array($input['options'])) {
            foreach ($input['options'] as $option) {
                if (isset($option['type'], $option['value'])) {
                    $sanitized_options[] = [
                        'type' => htmlspecialchars($option['type'], ENT_QUOTES, 'UTF-8'),
                        'value' => ($option['type'] === 'image') 
                            ? filter_var($option['value'], FILTER_SANITIZE_URL) 
                            : htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8')
                    ];
                }
            }
        }

        $update_data = [
            'question_text' => $input['question_text'] ?? '',
            'question_image' => filter_var($input['question_image'] ?? '', FILTER_SANITIZE_URL),
            'options' => json_encode($sanitized_options, JSON_UNESCAPED_UNICODE),
            'correct' => intval($input['correct']),
            'explanation' => $input['explanation'] ?? '',
            'needs_calculator' => isset($input['needs_calculator']) ? (bool)$input['needs_calculator'] : false,
            'time_limit_seconds' => isset($input['time_limit_seconds']) ? intval($input['time_limit_seconds']) : 60
        ];

        $result = supabase_request('/rest/v1/questions?id=eq.' . $question_id, $update_data, 'PATCH', true);

        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Question updated successfully.'];
        } else {
            $response['message'] = 'Failed to update question. HTTP Code: ' . $result['http_code'] . '. Response: ' . json_encode($result['data']);
        }
        break;

    case 'toggle_model_visibility':
        if (empty($input['model_id'])) {
            $response['message'] = 'Model ID is required.';
            break;
        }
        $model_id = intval($input['model_id']);
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

        // Security Check: User must be admin or owning teacher
        $can_toggle = false;
        $current_model = null;
        if ($is_admin) {
            $can_toggle = true;
        } elseif ($teacher_id) {
            $model_check = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&teacher_id=eq.' . $teacher_id . '&select=id,is_visible', null, 'GET', true);
            if ($model_check['http_code'] === 200 && !empty($model_check['data'])) {
                $can_toggle = true;
                $current_model = $model_check['data'][0];
            }
        } else {
             $response['message'] = 'Authentication required.';
             http_response_code(401);
             break;
        }
        
        if (!$can_toggle && !$is_admin) {
             $response['message'] = 'Unauthorized: You do not have permission to modify this model.';
             http_response_code(403);
             break;
        }

        // If admin, we need to fetch the current state first
        if ($is_admin && !$current_model) {
             $model_check = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&select=is_visible', null, 'GET', true);
             if ($model_check['http_code'] === 200 && !empty($model_check['data'])) {
                $current_model = $model_check['data'][0];
             } else {
                $response['message'] = 'Model not found.';
                break;
             }
        }

        $new_visibility = !($current_model['is_visible']);
        $update_data = ['is_visible' => $new_visibility];
        $result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id, $update_data, 'PATCH', true);

        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'Model visibility updated successfully.'];
        } else {
            $response['message'] = 'Failed to update model visibility. ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'delete_quiz_model':
        if (!isset($input['model_id'])) {
            $response['message'] = 'Model ID is required.';
            break;
        }
        $model_id = intval($input['model_id']);
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

        // Security Check: Only allow deletion if user is an admin or the owning teacher.
        $can_delete = false;
        if ($is_admin) {
            $can_delete = true;
        } elseif ($teacher_id) {
            // Verify teacher owns this model
            $model_check_result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&teacher_id=eq.' . $teacher_id . '&select=id', null, 'GET', true);
            if ($model_check_result['http_code'] === 200 && !empty($model_check_result['data'])) {
                $can_delete = true;
            }
        }

        if ($can_delete) {
            $result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id, null, 'DELETE', true);
            if ($result['http_code'] === 204) {
                $response = ['status' => 'success', 'message' => 'Quiz model deleted successfully.'];
            } else {
                $response['message'] = 'Failed to delete quiz model. ' . ($result['error_message'] ?? '');
            }
        } else {
            $response['message'] = 'Unauthorized: You do not have permission to delete this model.';
            http_response_code(403); // Forbidden
        }
        break;

    case 'toggle_course_status':
        if (empty($input['course_id']) || !isset($input['current_status'])) {
            $response['message'] = 'Course ID and current status are required.';
            break;
        }
        $course_id = intval($input['course_id']);
        $new_status = !((bool)$input['current_status']);
        $update_data = ['is_free' => $new_status];
        
        $result = supabase_request('/rest/v1/courses?id=eq.' . $course_id, $update_data, 'PATCH', true);

        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $response = ['status' => 'success', 'message' => 'تم تحديث حالة الاختبار بنجاح.', 'is_free' => $new_status];
        } else {
            $response['message'] = 'فشل تحديث حالة الاختبار. رمز الخطأ: ' . $result['http_code'] . '. التفاصيل: ' . ($result['error_message'] ?? json_encode($result['data']));
        }
        break;

    case 'ensure_quiz_session':
        // Security First: Ensure a user is logged in before starting any quiz session.
        if (!isset($_SESSION['user']) || empty($_SESSION['user']['phone'])) {
            $response['message'] = 'انتهت صلاحية جلسة الاختبار. سيتم إعادة توجيهك لتسجيل الدخول.';
            // Use a specific status to indicate that a reload/redirect is needed.
            $response['status'] = 'session_expired'; 
            http_response_code(401); // Unauthorized
            break;
        }

        if (empty($input['model_id'])) {
            $response['message'] = 'Model ID is required.';
            break;
        }
        $model_id = intval($input['model_id']);

        // Check if a session for this specific model already exists.
        if (isset($_SESSION['quiz_session']) && isset($_SESSION['quiz_session']['model_id']) && $_SESSION['quiz_session']['model_id'] == $model_id) {
            $response = ['status' => 'success', 'message' => 'Existing quiz session resumed.'];
            break; // Session is valid, just break and let the client fetch the state.
        }

        // If no session or a different model's session exists, create a new one.
        // **THE FIX**: Directly call Supabase with the correct ordering instead of using old helper functions.
        $model_details_result = supabase_request('/rest/v1/quiz_models?id=eq.' . $model_id . '&select=*', null, 'GET', false);
        if ($model_details_result['http_code'] !== 200 || empty($model_details_result['data'])) {
            $response['message'] = 'Could not load quiz model details.';
            break;
        }
        $model_details = $model_details_result['data'][0];

        $questions_result = supabase_request('/rest/v1/questions?quiz_model_id=eq.' . $model_id . '&select=*&order=question_order.asc,created_at.asc', null, 'GET', false);
        if ($questions_result['http_code'] !== 200) {
            $response['message'] = 'Could not load quiz questions.';
            break;
        }
        $questions = $questions_result['data'];

        // Decode HTML entities for questions fetched for the session
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

        $_SESSION['quiz_session'] = [
            'model_id' => $model_id,
            'model_details' => $model_details,
            'questions' => $questions,
            'answers' => array_fill(0, count($questions), null),
            'question_statuses' => array_fill(0, count($questions), 'unanswered'),
            'question_remaining_times' => array_map(function($q) {
                return $q['time_limit_seconds'] ?? 60;
            }, $questions),
            'current_question_index' => 0,
            'start_time' => time(),
        ];

        $response = ['status' => 'success', 'message' => 'New quiz session started.'];
        break;

    case 'get_quiz_state':
        if (!isset($_SESSION['quiz_session'])) {
            $response['message'] = 'No active quiz session.';
            break;
        }
        $response = ['status' => 'success', 'data' => $_SESSION['quiz_session']];
        break;

    case 'answer_question':
        if (!isset($_SESSION['quiz_session'])) {
            $response['message'] = 'No active quiz session.';
            break;
        }
        if (!isset($input['question_index'], $input['answer_index'])) {
            $response['message'] = 'Question index and answer index are required.';
            break;
        }
        $q_idx = intval($input['question_index']);
        $a_idx = intval($input['answer_index']);

        if (isset($_SESSION['quiz_session']['question_statuses'][$q_idx]) && $_SESSION['quiz_session']['question_statuses'][$q_idx] === 'timed_out') {
            $response['message'] = 'Time is up for this question.';
            break;
        }

        if (array_key_exists($q_idx, $_SESSION['quiz_session']['answers'])) {
            $_SESSION['quiz_session']['answers'][$q_idx] = $a_idx;
            $_SESSION['quiz_session']['question_statuses'][$q_idx] = 'answered';
            $response = ['status' => 'success', 'message' => 'Answer saved.'];
        } else {
            $response['message'] = 'Invalid question index.';
        }
        break;

    case 'time_up_question':
        if (!isset($_SESSION['quiz_session'])) {
            $response['message'] = 'No active quiz session.';
            break;
        }
        if (!isset($input['question_index'])) {
            $response['message'] = 'Question index is required.';
            break;
        }
        $q_idx = intval($input['question_index']);

        if (array_key_exists($q_idx, $_SESSION['quiz_session']['question_statuses'])) {
            // Only mark as timed out if it hasn't been answered yet
            if ($_SESSION['quiz_session']['question_statuses'][$q_idx] === 'unanswered') {
                $_SESSION['quiz_session']['question_statuses'][$q_idx] = 'timed_out';
                $_SESSION['quiz_session']['answers'][$q_idx] = -1; // Special value for timed out
                $_SESSION['quiz_session']['question_remaining_times'][$q_idx] = 0;
                $response = ['status' => 'success', 'message' => 'Question marked as timed out.'];
            } else {
                $response = ['status' => 'success', 'message' => 'Question was already answered.'];
            }
        } else {
            $response['message'] = 'Invalid question index for time out.';
        }
        break;

    case 'navigate_question':
        if (!isset($_SESSION['quiz_session'])) {
            $response['message'] = 'No active quiz session.';
            break;
        }
        if (!isset($input['new_index'])) {
            $response['message'] = 'New index is required.';
            break;
        }
        $new_idx = intval($input['new_index']);
        $total_questions = count($_SESSION['quiz_session']['questions']);

        if (isset($input['last_question_index']) && isset($input['remaining_time'])) {
            $last_q_idx = intval($input['last_question_index']);
            $remaining_s = intval($input['remaining_time']);
            if (array_key_exists($last_q_idx, $_SESSION['quiz_session']['question_remaining_times'])) {
                $_SESSION['quiz_session']['question_remaining_times'][$last_q_idx] = $remaining_s;
            }
        }

        if ($new_idx >= 0 && $new_idx < $total_questions) {
            $_SESSION['quiz_session']['current_question_index'] = $new_idx;
            $response = ['status' => 'success', 'message' => 'Navigated to question ' . ($new_idx + 1)];
        } else {
            $response['message'] = 'Invalid navigation index.';
        }
        break;

    case 'finish_quiz':
        if (!isset($_SESSION['quiz_session'])) {
            $response['message'] = 'No active quiz session found on the server. Please start the quiz again.';
            break;
        }

        $quiz_session = $_SESSION['quiz_session'];
        // Use the server's session as the single source of truth
        $user_answers = $quiz_session['answers'];
        $questions = $quiz_session['questions'];
        
        $unanswered = array_filter($user_answers, function($a) { return $a === null; });
        if (count($unanswered) > 0) {
            $response['message'] = 'Please answer all questions before finishing the quiz.';
            $response['unanswered_count'] = count($unanswered);
            break;
        }

        $score = 0;
        $total_questions = count($questions);
        for ($i = 0; $i < $total_questions; $i++) {
            if (isset($questions[$i]['correct'], $user_answers[$i]) && $user_answers[$i] == $questions[$i]['correct']) {
                $score++;
            }
        }

        $user_phone = $_SESSION['user']['phone'] ?? null;
        if (!$user_phone) {
            $response['message'] = 'User session is invalid. Cannot save result.';
            break;
        }

        $course_id = $quiz_session['model_details']['course_id'] ?? 0;
        if (empty($course_id)) {
            $response['message'] = 'Course ID is missing from quiz session. Cannot save result.';
            break;
        }

        $percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;
        $attempt_uuid = uniqid('attempt_', true);

        $result_data = [
            'phone_number' => $user_phone,
            'course_id' => $course_id,
            'quiz_model_id' => $quiz_session['model_id'],
            'score' => $score,
            'total_questions' => $total_questions,
            'percentage' => round($percentage, 2),
            'session_id' => $attempt_uuid,
            'completed_at' => date('c') // Update timestamp
        ];

        // **THE FIX**: Always insert a new quiz result to maintain a complete history.
        // The previous logic was overwriting the old result, preventing a history from being stored.
        $db_result = supabase_request('/rest/v1/quiz_results', $result_data, 'POST', true);

        if ($db_result['http_code'] === 201) { // A successful INSERT returns 201
            $user_answers_payload = [];
            $full_questions_data = $quiz_session['questions'];
            for ($i = 0; $i < $total_questions; $i++) {
                $question = $full_questions_data[$i];
                $selected_answer = $user_answers[$i];
                $correct_answer = $question['correct'];
                $user_answers_payload[] = [
                    'phone_number' => $user_phone,
                    'quiz_model_id' => $quiz_session['model_id'],
                    'question_id' => $question['id'],
                    'question_number' => $i + 1,
                    'question_text' => $question['question_text'],
                    'selected_answer' => $selected_answer,
                    'correct_answer' => $correct_answer,
                    'is_correct' => ($selected_answer == $correct_answer),
                    'quiz_session_id' => $attempt_uuid
                ];
            }
            if (!empty($user_answers_payload)) {
                supabase_request('/rest/v1/user_answers', $user_answers_payload, 'POST');
            }

            $user_info_result = supabase_request("/rest/v1/phone?phone_number=eq.{$user_phone}&select=first_name,middle_name,last_name", null, 'GET');
            $student_name = "Unknown Student";
            if ($user_info_result['http_code'] === 200 && !empty($user_info_result['data'])) {
                $user_info = $user_info_result['data'][0];
                $student_name = trim("{$user_info['first_name']} {$user_info['middle_name']} {$user_info['last_name']}");
            }

            $course_info_result = supabase_request("/rest/v1/courses?id=eq.{$course_id}&select=title", null, 'GET');
            $course_title = "Unknown Course";
            if ($course_info_result['http_code'] === 200 && !empty($course_info_result['data'])) {
                $course_title = $course_info_result['data'][0]['title'];
            }

            $first_attempt_data = [
                'phone_number' => $user_phone,
                'course_id' => $course_id,
                'student_name' => $student_name,
                'course_title' => $course_title,
                'final_score' => $score,
                'total_questions' => $total_questions,
                'percentage' => $result_data['percentage']
            ];
            
            supabase_request('/rest/v1/student_first_attempt_results', $first_attempt_data, 'POST');

            // **الإصلاح الجذري**: تخزين كل بيانات المراجعة في الجلسة مباشرة
            $_SESSION['quiz_review_data'] = [
                'questions' => $full_questions_data, // الأسئلة الكاملة مع الشرح والإجابات الصحيحة
                'user_answers' => $user_answers,      // إجابات المستخدم
                'model_title' => $quiz_session['model_details']['title']
            ];

            // تخزين النتيجة فقط للعرض الفوري في quiz_result.php
            $_SESSION['quiz_result'] = [
                'score' => $score,
                'total_questions' => $total_questions,
                'percentage' => round($percentage, 2),
                'model_title' => $quiz_session['model_details']['title'],
                'session_id' => $attempt_uuid // Pass session_id for immediate review
            ];
            
            // **THE FIX**: Preserve the main user session, only clear the quiz data.
            unset($_SESSION['quiz_session']);
            $response = ['status' => 'success', 'message' => 'Quiz finished and result saved.', 'data' => ['session_id' => $attempt_uuid]];
        } else {
            $error_detail = $db_result['error_message'] ?? 'Unknown database error';
            $response['message'] = 'Error saving quiz result: ' . $error_detail;
        }
        break;

    default:
        $response['message'] = 'Unknown action specified.';
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
