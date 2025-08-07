<?php
session_start();

// Check if the teacher is logged in. If not, redirect to the login page.
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

// You can also store the teacher's ID in a variable for easy access on protected pages
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Include the database configuration and functions
require_once '../config.php';
require_once '../functions.php';
?>
