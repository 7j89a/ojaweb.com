<?php
header('Content-Type: application/json');
require_once 'auth.php'; // Ensures only logged-in teachers can access this API

$response = ['status' => 'error', 'message' => 'Invalid action.'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if (!$action) {
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        // ----------------------------------------
        // Get Courses for the Logged-in Teacher
        // ----------------------------------------
        case 'get_teacher_courses':
            $result = supabase_request('/rest/v1/courses?teacher_id=eq.' . $teacher_id . '&select=*,quiz_models(count)', null, 'GET', true);
            if ($result['http_code'] === 200) {
                // Remap the count to models_count for consistency with the frontend
                $courses = array_map(function($course) {
                    $course['models_count'] = $course['quiz_models'][0]['count'] ?? 0;
                    unset($course['quiz_models']);
                    return $course;
                }, $result['data']);
                $response = ['status' => 'success', 'data' => $courses];
            } else {
                $response['message'] = 'Error fetching courses: ' . ($result['error_message'] ?? 'Unknown error');
            }
            break;

        // ----------------------------------------
        // Add a New Course
        // ----------------------------------------
        case 'add_course':
            $title = $data['title'] ?? null;
            if (empty($title)) {
                throw new Exception('Course title is required.');
            }
            $new_course_data = [
                'teacher_id' => $teacher_id,
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                'category' => htmlspecialchars($data['category'] ?? '', ENT_QUOTES, 'UTF-8'),
                'is_free' => !empty($data['is_free']),
                'course_type' => htmlspecialchars($data['course_type'] ?? '', ENT_QUOTES, 'UTF-8')
            ];
            $result = supabase_request('/rest/v1/courses', $new_course_data, 'POST', true);
            if ($result['http_code'] === 201) {
                $response = ['status' => 'success', 'message' => 'Course added successfully.'];
            } else {
                $response['message'] = 'Failed to add course. ' . ($result['error_message'] ?? json_encode($result['data']));
            }
            break;

        // ----------------------------------------
        // Delete a Course
        // ----------------------------------------
        case 'delete_course':
            $course_id = $data['course_id'] ?? null;
            if (empty($course_id)) {
                throw new Exception('Course ID is required.');
            }
            // Security Check: Ensure the course belongs to the logged-in teacher before deleting
            $result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id, null, 'DELETE', true);
            if ($result['http_code'] === 204) {
                $response = ['status' => 'success', 'message' => 'Course deleted successfully.'];
            } else {
                // This will fail if the row doesn't match the RLS policy or doesn't exist
                $response['message'] = 'Failed to delete course. You may not have permission or it may have already been deleted.';
            }
            break;

        // ----------------------------------------
        // Get Single Course Details
        // ----------------------------------------
        case 'get_course_details':
            $course_id = $data['course_id'] ?? null;
            if (empty($course_id)) {
                throw new Exception('Course ID is required.');
            }
            // Security Check: Ensure the course belongs to the logged-in teacher
            $result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id . '&select=*', null, 'GET', true);
            if ($result['http_code'] === 200 && !empty($result['data'])) {
                $response = ['status' => 'success', 'data' => $result['data'][0]];
            } else {
                $response['message'] = 'Error fetching course details or permission denied.';
            }
            break;

        // ----------------------------------------
        // Update a Course
        // ----------------------------------------
        case 'update_course':
            $course_id = $data['course_id'] ?? null;
            if (empty($course_id)) {
                throw new Exception('Course ID is required for update.');
            }
            $title = $data['title'] ?? null;
            if (empty($title)) {
                throw new Exception('Course title is required.');
            }
            $update_data = [
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                'category' => htmlspecialchars($data['category'] ?? '', ENT_QUOTES, 'UTF-8'),
                'is_free' => !empty($data['is_free']),
                'course_type' => htmlspecialchars($data['course_type'] ?? '', ENT_QUOTES, 'UTF-8')
            ];
            // Security Check: The RLS policy in Supabase should prevent a teacher from updating a course they don't own.
            $result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id, $update_data, 'PATCH', true);
            if ($result['http_code'] === 204 || $result['http_code'] === 200) {
                $response = ['status' => 'success', 'message' => 'Course updated successfully.'];
            } else {
                $response['message'] = 'Failed to update course. ' . ($result['error_message'] ?? json_encode($result['data']));
            }
            break;

        // ----------------------------------------
        // Toggle Course Visibility
        // ----------------------------------------
        case 'toggle_course_visibility':
            $course_id = $data['course_id'] ?? null;
            if (empty($course_id)) {
                throw new Exception('Course ID is required.');
            }
            // First, get the current visibility status
            $get_result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id . '&select=is_visible', null, 'GET', true);
            if ($get_result['http_code'] !== 200 || empty($get_result['data'])) {
                throw new Exception('Could not find the course or permission denied.');
            }
            $current_visibility = $get_result['data'][0]['is_visible'];
            $new_visibility = !$current_visibility;

            // Now, update the course with the new visibility status
            $update_result = supabase_request(
                '/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id,
                ['is_visible' => $new_visibility],
                'PATCH',
                true
            );

            if ($update_result['http_code'] === 204 || $update_result['http_code'] === 200) {
                $response = ['status' => 'success', 'message' => 'Course visibility updated successfully.'];
            } else {
                $response['message'] = 'Failed to update course visibility. ' . ($update_result['error_message'] ?? json_encode($update_result['data']));
            }
            break;

        // ----------------------------------------
        // Get Quiz Models for a Course
        // ----------------------------------------
        case 'get_quiz_models':
            $course_id = $data['course_id'] ?? null;
            if (empty($course_id)) {
                throw new Exception('Course ID is required.');
            }
            // Security check: ensure the teacher owns the course they are requesting models for
            $course_check_result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id . '&select=id', null, 'GET', true);
            if ($course_check_result['http_code'] !== 200 || empty($course_check_result['data'])) {
                throw new Exception('Permission denied to access models for this course.');
            }
            
            $result = supabase_request('/rest/v1/quiz_models?course_id=eq.' . intval($course_id) . '&select=*,questions(count)&order=created_at.asc', null, 'GET', true);
            if ($result['http_code'] === 200) {
                $models = array_map(function($model) {
                    $model['questions_count'] = $model['questions'][0]['count'] ?? 0;
                    unset($model['questions']);
                    return $model;
                }, $result['data']);
                $response = ['status' => 'success', 'data' => $models];
            } else {
                $response['message'] = 'Error fetching quiz models: ' . ($result['error_message'] ?? 'Unknown error');
            }
            break;

        // ----------------------------------------
        // Get Single Quiz Model Details
        // ----------------------------------------
        case 'get_quiz_model_details':
            $model_id = $data['model_id'] ?? null;
            if (empty($model_id)) {
                throw new Exception('Model ID is required.');
            }
            // Security check: ensure the teacher owns the model
            $result = supabase_request('/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&teacher_id=eq.' . $teacher_id . '&select=*', null, 'GET', true);
            if ($result['http_code'] === 200 && !empty($result['data'])) {
                $response = ['status' => 'success', 'data' => $result['data'][0]];
            } else {
                $response['message'] = 'Error fetching model details or permission denied.';
            }
            break;

        // ----------------------------------------
        // Add a Quiz Model
        // ----------------------------------------
        case 'add_quiz_model':
            $course_id = $data['course_id'] ?? null;
            $title = $data['title'] ?? null;
            if (empty($course_id) || empty($title)) {
                throw new Exception('Course ID and title are required.');
            }
            // Security check
            $course_check_result = supabase_request('/rest/v1/courses?id=eq.' . intval($course_id) . '&teacher_id=eq.' . $teacher_id . '&select=id', null, 'GET', true);
            if ($course_check_result['http_code'] !== 200 || empty($course_check_result['data'])) {
                throw new Exception('Permission denied to add a model to this course.');
            }
            $new_model_data = [
                'course_id' => intval($course_id),
                'teacher_id' => $teacher_id,
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'timer_type' => htmlspecialchars($data['timer_type'] ?? 'per_question', ENT_QUOTES, 'UTF-8'),
                'total_time_seconds' => isset($data['total_time_seconds']) ? intval($data['total_time_seconds']) : null,
            ];
            $result = supabase_request('/rest/v1/quiz_models', $new_model_data, 'POST', true);
            if ($result['http_code'] === 201) {
                $response = ['status' => 'success', 'message' => 'تمت إضافة النموذج بنجاح.'];
            } else {
                $response['message'] = 'Failed to add quiz model. ' . ($result['error_message'] ?? json_encode($result['data']));
            }
            break;

        // ----------------------------------------
        // Update a Quiz Model
        // ----------------------------------------
        case 'update_quiz_model':
            $model_id = $data['model_id'] ?? null;
            $title = $data['title'] ?? null;
            if (empty($model_id) || empty($title)) {
                throw new Exception('Model ID and title are required.');
            }
            $update_data = [
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'timer_type' => htmlspecialchars($data['timer_type'] ?? 'per_question', ENT_QUOTES, 'UTF-8'),
                'total_time_seconds' => isset($data['total_time_seconds']) ? intval($data['total_time_seconds']) : null,
            ];
            // RLS policy will ensure the teacher owns the model.
            $result = supabase_request('/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&teacher_id=eq.' . $teacher_id, $update_data, 'PATCH', true);
            if ($result['http_code'] === 204 || $result['http_code'] === 200) {
                $response = ['status' => 'success', 'message' => 'تم تحديث النموذج بنجاح.'];
            } else {
                $response['message'] = 'Failed to update quiz model. ' . ($result['error_message'] ?? json_encode($result['data']));
            }
            break;

        // ----------------------------------------
        // Toggle Model Visibility
        // ----------------------------------------
        case 'toggle_model_visibility':
            $model_id = $data['model_id'] ?? null;
            if (empty($model_id)) {
                throw new Exception('Model ID is required.');
            }
            // First, get the current visibility status
            $get_result = supabase_request('/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&teacher_id=eq.' . $teacher_id . '&select=is_visible', null, 'GET', true);
            if ($get_result['http_code'] !== 200 || empty($get_result['data'])) {
                throw new Exception('Could not find the model or permission denied.');
            }
            $current_visibility = $get_result['data'][0]['is_visible'];
            $new_visibility = !$current_visibility;

            // Now, update the model with the new visibility status
            $update_result = supabase_request(
                '/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&teacher_id=eq.' . $teacher_id,
                ['is_visible' => $new_visibility],
                'PATCH',
                true
            );

            if ($update_result['http_code'] === 204 || $update_result['http_code'] === 200) {
                $response = ['status' => 'success', 'message' => 'Model visibility updated successfully.'];
            } else {
                $response['message'] = 'Failed to update model visibility. ' . ($update_result['error_message'] ?? json_encode($update_result['data']));
            }
            break;

        // ----------------------------------------
        // Delete a Quiz Model
        // ----------------------------------------
        case 'delete_quiz_model':
            $model_id = $data['model_id'] ?? null;
            if (empty($model_id)) {
                throw new Exception('Model ID is required.');
            }
            // RLS policy will ensure the teacher owns the model.
            $result = supabase_request('/rest/v1/quiz_models?id=eq.' . intval($model_id) . '&teacher_id=eq.' . $teacher_id, null, 'DELETE', true);
            if ($result['http_code'] === 204) {
                $response = ['status' => 'success', 'message' => 'تم حذف النموذج بنجاح.'];
            } else {
                $response['message'] = 'Failed to delete quiz model.';
            }
            break;

        default:
            $response['message'] = "Action '$action' not recognized.";
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>
