<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the Teacher role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$school_name = isset($_SESSION['school_name']) ? trim($_SESSION['school_name']) : '';

if (empty($school_name)) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$teacher_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
    $school_name = trim($_SESSION['school_name']);
}

$message = "";

// 1. Fetch all distinct classes assigned to this teacher
try {
    $class_stmt = $pdo->prepare("SELECT DISTINCT class_name FROM teacher_assignments WHERE teacher_id = ?");
    $class_stmt->execute([$teacher_id]);
    $assigned_classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 2. If a class is selected, fetch the list of students enrolled in that class
$selected_class = isset($_GET['class_name']) ? trim($_GET['class_name']) : '';
$students = [];

if (!empty($selected_class)) {
    try {
        $student_stmt = $pdo->prepare("SELECT student_id, student_name FROM students WHERE BINARY TRIM(class_name) = BINARY TRIM(?) AND BINARY TRIM(school_name) = BINARY TRIM(?) ORDER BY student_name ASC");
        $student_stmt->execute([$selected_class, $school_name]);
        $students = $student_stmt->fetchAll();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// 3. Process form submission to save attendance records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $attendance_date = date('Y-m-d'); 
    $status_data = isset($_POST['status']) ? $_POST['status'] : [];

    if (!empty($status_data) && !empty($selected_class)) {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO attendance (student_id, class_name, status, attendance_date, marked_by) 
                    VALUES (?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE status = ?, marked_by = ?";
            $stmt = $pdo->prepare($sql);

            foreach ($status_data as $student_id => $status) {
                $stmt->execute([$student_id, $selected_class, $status, $attendance_date, $teacher_id, $status, $teacher_id]);
            }

            $pdo->commit();
            $message = "<div class='alert success'>✔️ Attendance for " . htmlspecialchars($selected_class) . " has been successfully updated and recorded!</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert error'>❌ System Exception: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Daily Student Attendance</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0056b3; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; text-transform: uppercase; text-align: center; font-size: 20px; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; }
        
        .selection-box { background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; min-width: 200px; }
        .btn-select { background-color: #0f172a; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #0056b3; color: white; text-transform: uppercase; font-size: 12px; }
        
        .radio-group { display: flex; gap: 20px; }
        .radio-group label { cursor: pointer; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; }
        .present { color: #16a34a; }
        .absent { color: #ef4444; }
        .permission { color: #ea580c; }
        
        .btn-save { background-color: #16a34a; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px; margin-top: 20px; text-transform: uppercase; }
        .btn-save:hover { background-color: #15803d; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .debug-notice { background-color: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📋 Student Roll Call Protocol</h2>

    <div class="nav-links">
        <span>Institution: <b><?php echo htmlspecialchars($school_name); ?></b></span>
        <div>
            <a href="view_attendance.php" style="margin-right: 15px; font-weight: bold; color: #0056b3;">📊 Attendance History</a>
            <a href="weka_alama.php" style="color: #475569; margin-right: 15px;">➕ Enter Marks</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </div>
    </div>

    <?php echo $message; ?>

    <?php if (count($assigned_classes) === 0): ?>
        <div class="debug-notice">
            ⚠️ <b>Debug Info:</b> No classes found for Teacher ID <b><?php echo $teacher_id; ?></b> in the `teacher_assignments` table. Please assign a class to this teacher in your database.
        </div>
    <?php endif; ?>

    <div class="selection-box">
        <form method="GET" action="">
            <label for="class_name" style="font-size: 14px;"><b>Select Target Class:</b> </label>
            <select name="class_name" id="class_name" required>
                <option value="">-- Choose Class --</option>
                <?php foreach ($assigned_classes as $class): ?>
                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $selected_class === $class ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-select">Load Classroom</button>
        </form>
    </div>

    <?php if (!empty($selected_class)): ?>
        <form method="POST" action="">
            <h3 style="color: #475569; font-size: 15px;">Student Roster: Class <?php echo htmlspecialchars($selected_class); ?> (Date: <?php echo date('d-M-Y'); ?>)</h3>
            
            <?php if (count($students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">S/N</th>
                            <th>Full Student Name</th>
                            <th>Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($students as $student): ?>
                            <tr>
                                <td style="text-align: center; color: #64748b;"><?php echo $sn++; ?></td>
                                <td><b><?php echo htmlspecialchars($student['student_name']); ?></b></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="present">
                                            <input type="radio" name="status[<?php echo $student['student_id']; ?>]" value="Present" checked> Present
                                        </label>
                                        <label class="absent">
                                            <input type="radio" name="status[<?php echo $student['student_id']; ?>]" value="Absent"> Absent
                                        </label>
                                        <label class="permission">
                                            <input type="radio" name="status[<?php echo $student['student_id']; ?>]" value="Permission"> Permission
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="submit_attendance" class="btn-save">💾 Save Today's Attendance</button>
            <?php else: ?>
                <div class="debug-notice" style="margin-top: 15px;">
                    ⚠️ <b>Debug Info:</b> Found 0 students matching Class: <b>"<?php echo htmlspecialchars($selected_class); ?>"</b> and School: <b>"<?php echo htmlspecialchars($school_name); ?>"</b>. Verify that student profiles exactly match these values in the database.
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

</body>
</html>