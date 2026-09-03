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

// Retrieve the school name if missing from session
if (empty($school_name)) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$teacher_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
    $school_name = trim($_SESSION['school_name']);
}

// 1. Fetch assigned classes for the filter dropdown
try {
    $class_stmt = $pdo->prepare("SELECT DISTINCT class_name FROM teacher_assignments WHERE teacher_id = ?");
    $class_stmt->execute([$teacher_id]);
    $assigned_classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get filter inputs
$selected_class = isset($_GET['class_name']) ? trim($_GET['class_name']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01'); // Default to 1st of current month
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d');     // Default to today

$attendance_records = [];

// 2. Fetch records if a class is selected
if (!empty($selected_class)) {
    try {
        $sql = "
            SELECT 
                s.student_name,
                a.status,
                a.attendance_date
            FROM attendance a
            INNER JOIN students s ON a.student_id = s.student_id
            WHERE TRIM(a.class_name) = TRIM(?) 
              AND TRIM(s.school_name) = TRIM(?)
              AND a.attendance_date BETWEEN ? AND ?
            ORDER BY a.attendance_date DESC, s.student_name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_class, $school_name, $start_date, $end_date]);
        $all_records = $stmt->fetchAll();

        // Group data by Date to display neatly in tables
        foreach ($all_records as $row) {
            $attendance_records[$row['attendance_date']][] = $row;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs & Reports</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0056b3; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; text-transform: uppercase; text-align: center; font-size: 20px; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; color: #0056b3; }
        
        .filter-box { background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-weight: bold; font-size: 13px; color: #475569; }
        select, input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; }
        .btn-filter { background-color: #0f172a; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-filter:hover { background-color: #1e293b; }
        
        .date-section { margin-bottom: 35px; }
        .date-header { background: #e2e8f0; padding: 10px 15px; font-weight: bold; font-size: 15px; border-radius: 4px; color: #1e293b; margin-bottom: 10px; display: flex; justify-content: space-between; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 15px; text-align: left; font-size: 14px; }
        th { background-color: #f1f5f9; color: #1e293b; text-transform: uppercase; font-size: 12px; width: 50%; }
        
        .status-badge { font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; display: inline-block; }
        .Present { background-color: #dcfce7; color: #15803d; }
        .Absent { background-color: #fee2e2; color: #b91c1c; }
        .Permission { background-color: #ffedd5; color: #c2410c; }

        @media print {
            .nav-links, .filter-box { display: none !important; }
            body { background: white; margin: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📊 Historical Attendance Logs</h2>

    <div class="nav-links">
        <span>Institution: <b><?php echo htmlspecialchars($school_name); ?></b></span>
        <div>
            <a href="add_attendance.php" style="margin-right: 15px; color: #16a34a; font-weight: bold;">📝 Take Attendance</a>
            <a href="weka_alama.php" style="margin-right: 15px;">➕ Enter Marks</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </div>
    </div>

    <div class="filter-box">
        <form method="GET" action="">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="class_name">Classroom Structure:</label>
                    <select name="class_name" id="class_name" required>
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($assigned_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $selected_class === $class ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="start_date">From Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="end_date">To Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <button type="submit" class="btn-filter">🔍 Generate Report</button>
            </div>
        </form>
    </div>

    <?php if (!empty($selected_class)): ?>
        <?php if (count($attendance_records) > 0): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #475569; margin: 0;">Logs for Class <?php echo htmlspecialchars($selected_class); ?></h3>
                <button onclick="window.print()" class="btn-filter" style="background-color: #0056b3;">🖨️ Print Logs</button>
            </div>

            <?php foreach ($attendance_records as $date => $students): ?>
                <div class="date-section">
                    <div class="date-header">
                        <span>📅 <?php echo date('d-M-Y', strtotime($date)); ?></span>
                        <span style="font-size: 13px; font-weight: normal;">Total Checked: <?php echo count($students); ?></span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th style="text-align: center; width: 150px;">Roll Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($student['student_name']); ?></b></td>
                                    <td style="text-align: center;">
                                        <span class="status-badge <?php echo $student['status']; ?>">
                                            <?php echo $student['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div style="text-align: center; color: #94a3b8; padding: 30px; border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 6px;">
                No attendance signatures found within the selected dates.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>