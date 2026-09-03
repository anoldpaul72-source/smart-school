<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the Admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$class_name = isset($_GET['class_name']) ? trim($_GET['class_name']) : '';

if (empty($class_name)) {
    die("Please select a Class/Form before downloading the template.");
}

$filename = "Registration_Template_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $class_name) . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Added 'Registration Number' and 'School Name' columns into the CSV structure
fputcsv($output, ['Registration Number', 'Full Name', 'Gender (M/F)', 'Class Name', 'School Name', 'Parent Account Username']);

// Provide clear sample data rows for the Admin
fputcsv($output, ['S.0102.0001.2026', 'John Joseph Doe', 'M', $class_name, 'Kome Secondary School', 'parent_musa']);
fputcsv($output, ['S.0102.0002.2026', 'Mary Juma Anna', 'F', $class_name, 'Kome Secondary School', 'parent_ana']);

fclose($output);
exit;