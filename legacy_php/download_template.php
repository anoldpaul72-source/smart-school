<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the Teacher role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher' || !isset($_SESSION['user_id'])) {
    die("Access Denied.");
}

$class_name = isset($_GET['class_name']) ? trim($_GET['class_name']) : '';
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// USALAMA WA SHULE: Hakikisha school_name ya mwalimu ipo kwenye session
if (!isset($_SESSION['school_name'])) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
}

$school_name = trim($_SESSION['school_name']);

if (empty($class_name) || $subject_id === 0 || empty($school_name)) {
    die("Please select both Subject and Class before downloading the template.");
}

try {
    // MAREKEBISHO 1: Tumesafisha 'subjects' kuwa herufi ndogo kabisa kufuatana na Linux server yako
    $sub_stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    $sub_stmt->execute([$subject_id]);
    $subject = $sub_stmt->fetch();
    $subject_name = $subject ? $subject['subject_name'] : 'Subject';

    // MAREKEBISHO 2: Tumesafisha 'students' na kuongeza chujio la 'school_name' kulinda data za shule husika
    $sql = "SELECT student_id, student_name FROM students 
            WHERE TRIM(class_name) = TRIM(?) 
            AND TRIM(school_name) = TRIM(?) 
            ORDER BY student_name ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_name, $school_name]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($students) === 0) {
        die("No students found in the selected class for your school.");
    }

    // Prepare CSV file download
    $filename = clean_filename($subject_name) . "_" . clean_filename($class_name) . "_Template.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write header row
    fputcsv($output, ['Student ID', 'Student Name', 'Score', 'Exam Date (YYYY-MM-DD)']);

    // Write student records
    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_id'],
            $student['student_name'],
            '', // Leave score blank for the teacher to fill offline
            date('Y-m-d') // Populate with current date as a default placeholder
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

function clean_filename($string) {
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
}