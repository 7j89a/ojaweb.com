<?php
require 'config.php';
require 'functions.php';

$phone = '+962 791852642';  // بدون فراغات
$course_id = 6;
$activation_code = '91739c074b0f';

$result = activate_course_with_code($phone, $course_id, $activation_code);
var_dump($result);
