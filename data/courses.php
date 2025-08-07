<?php
// هذا الملف يعيد مصفوفة الدورات

// دالة مساعدة لتحميل الأسئلة من ملف JSON خاص بالدورة
function load_course_questions($course_id) {
    $questions_path = __DIR__ . "/questions/{$course_id}.json";
    if (file_exists($questions_path)) {
        $json_content = file_get_contents($questions_path);
        return json_decode($json_content, true);
    }
    return []; // إرجاع مصفوفة فارغة إذا لم يتم العثور على الملف
}

// تحميل الأسئلة للدورة 1
$course1_questions = load_course_questions(1);

return [
    [
        'id' => 1,
        'title' => 'العلوم الحياتية',
        'description' => 'استكشف عجائب علم الأحياء والكائنات الحية.',
        'questions_count' => count($course1_questions), // حساب عدد الأسئلة ديناميكيًا
        'difficulty' => 'متوسط',
        'category' => 'علوم',
        'icon' => '🔬',
        'rating' => 4.9,
        'students' => 1350,
        'is_free' => false,
        'questions' => $course1_questions // استخدام الأسئلة المحملة
    ],
    // يمكنك إضافة دورات أخرى هنا بنفس الطريقة
    // مثال:
    // [
    //     'id' => 2,
    //     'title' => 'الفيزياء',
    //     ...
    //     'questions' => load_course_questions(2),
    // ]
];
