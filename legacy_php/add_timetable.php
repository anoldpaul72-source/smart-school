<?php
session_start();
require 'db.php';

// 1. PROTECTION: Only Admin and Academic Master can manage the timetable
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Academic Master'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$school_name = isset($_SESSION['school_name']) ? trim($_SESSION['school_name']) : '';

// 2. FETCH TEACHERS AND SUBJECTS FOR THE CURRENT SCHOOL
try {
    // Fetch all teachers registered under this school
    $teachers_stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role = 'Teacher' AND TRIM(school_name) = TRIM(?) ORDER BY username ASC");
    $teachers_stmt->execute([$school_name]);
    $all_teachers = $teachers_stmt->fetchAll();

    // Fetch all available subjects
    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $subjects_stmt->fetchAll();
} catch (PDOException $e) {
    $all_teachers = [];
    $all_subjects = [];
}

$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Standard Secondary School Routine (9 Periods)
$period_slots = [
    1 => '08:00 AM - 08:40 AM',
    2 => '08:40 AM - 09:20 AM',
    3 => '09:20 AM - 10:00 AM',
    4 => '10:00 AM - 10:40 AM',
    // 10:40 AM - 11:10 AM (Porridge / Tea Break)
    5 => '11:10 AM - 11:50 AM',
    6 => '11:50 AM - 12:30 PM',
    7 => '12:30 PM - 01:10 PM',
    // 01:10 PM - 02:00 PM (Lunch Break)
    8 => '02:00 PM - 02:40 PM',
    9 => '02:40 PM - 03:20 PM'
];

// 3. HANDLE TIMETABLE SLOT SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_slot'])) {
    $class_name    = $_POST['class_name'];
    $day_of_week   = $_POST['day_of_week'];
    $period_number = (int)$_POST['period_number'];
    $subject_id    = (int)$_POST['subject_id'];
    $teacher_id    = (int)$_POST['teacher_id'];
    $time_slot     = $period_slots[$period_number]; // Automatically set based on period number

    if (empty($school_name)) {
        $message = "<div class='alert error'>❌ Your school session could not be identified. Please log out and log back in.</div>";
    } else {
        try {
            $sql = "INSERT INTO timetables (school_name, class_name, day_of_week, period_number, time_slot, subject_id, teacher_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$school_name, $class_name, $day_of_week, $period_number, $time_slot, $subject_id, $teacher_id]);
            
            $message = "<div class='alert success'>✔️ Period allocation saved successfully to the timetable!</div>";
        } catch (PDOException $e) {
            // Check if error is due to a UNIQUE KEY constraint (Clash Detection)
            if ($e->errorInfo[1] == 1062) {
                $message = "<div class='alert error'>⚠️ <b>Clash Detected:</b> The teacher is already assigned to another class during this period, OR this class already has a scheduled subject at this time!</div>";
            } else {
                $message = "<div class='alert error'>❌ Database Error: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic - Manage School Timetable</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 650px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0284c7; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; font-size: 20px; text-transform: uppercase; margin-top: 0; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; background: #e0f2fe; padding: 12px; border-radius: 4px; align-items: center; border: 1px solid #bae6fd; }
        .nav-links a { font-weight: bold; text-decoration: none; color: #0369a1; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; font-size: 14px; color: #1e293b; }
        select { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 14px; background: #fff; }
        select:focus { border-color: #0284c7; outline: none; }
        button { width: 100%; padding: 12px; background-color: #0284c7; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; text-transform: uppercase; }
        button:hover { background-color: #0369a1; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        .error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="container">
    <h2>🗓️ Class Timetable Allocation</h2>
    
    <div class="nav-links">
        <span>School: <b><?php echo htmlspecialchars($school_name); ?></b></span>
        <div>
            <a href="view_timetable.php" style="margin-right: 15px;">📊 View Timetable</a>