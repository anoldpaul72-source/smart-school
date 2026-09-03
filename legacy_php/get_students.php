<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// 1. MABORESHO YA ULINZI: Ruhusu Admin, Accountant, Teacher, Headmaster, na Academic Master
$allowed_roles = ['Admin', 'Accountant', 'Teacher', 'Headmaster', 'Academic Master'];

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles) || !isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$class_name = isset($_GET['class_name']) ? trim($_GET['class_name']) : '';

// USALAMA WA SHULE: Hakikisha school_name ipo kwenye session
if (!isset($_SESSION['school_name'])) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
}

$school_name = trim($_SESSION['school_name']);

if (!empty($class_name) && !empty($school_name)) {
    try {
        $allowed_to_fetch = false;

        // 2. KAMA NI MWALIMU: Hakikisha ameruhusiwa kufundisha darasa hili
        if ($user_role === 'Teacher') {
            $check_sql = "SELECT id FROM teacher_assignments WHERE teacher_id = ? AND TRIM(class_name) = TRIM(?)";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$user_id, $class_name]);
            if ($check_stmt->fetch()) {
                $allowed_to_fetch = true;
            }
        } else {
            // Admin, Accountant, na Viongozi wengine wanaruhusiwa kuona madarasa yote ya shule yao
            $allowed_to_fetch = true;
        }

        // 3. KAMA ANARUHUSIWA: Vuta wanafunzi sasa
        if ($allowed_to_fetch) {
            $sql = "SELECT student_id, student_name FROM students 
                    WHERE TRIM(class_name) = TRIM(?) 
                    AND TRIM(school_name) = TRIM(?) 
                    ORDER BY student_name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$class_name, $school_name]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($students);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode([]);
        exit;
    }
}

echo json_encode([]);