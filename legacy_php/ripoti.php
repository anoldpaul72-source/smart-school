<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Parent') {
    header("Location: login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

// 1. Kamata mtihani uliochaguliwa na mzazi (Default ni 'Annual Examination')
$selected_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Annual Examination';
$current_year  = date('Y');

// Grading scale, points, and remarks (Mfumo mpya wa alama)
function getGradeAndRemarks($score) {
    if ($score >= 75 && $score <= 100) {
        return ['grade' => 'A', 'points' => 1, 'remarks' => 'Excellent! Outstanding performance.', 'color' => '#28a745'];
    } elseif ($score >= 61 && $score <= 74) {
        return ['grade' => 'B', 'points' => 2, 'remarks' => 'Very Good! Keep it up.', 'color' => '#17a2b8'];
    } elseif ($score >= 45 && $score <= 60) {
        return ['grade' => 'C', 'points' => 3, 'remarks' => 'Good effort, can perform better.', 'color' => '#ffc107'];
    } elseif ($score >= 30 && $score <= 44) {
        return ['grade' => 'D', 'points' => 4, 'remarks' => 'Below average. More effort required.', 'color' => '#fd7e14'];
    } else {
        return ['grade' => 'F', 'points' => 5, 'remarks' => 'Fail. Needs close academic supervision.', 'color' => '#dc3545'];
    }
}

// Mfumo wa kukokotoa Division na Best 7 Points (NECTA Standard)
function calculateDivisionAndPoints($student_marks) {
    $points_array = [];
    
    foreach ($student_marks as $row) {
        $grade_info = getGradeAndRemarks($row['score']);
        $points_array[] = $grade_info['points'];
    }

    // Panga pointi kuanzia ndogo kwenda kubwa (1 ni bora kuliko 5)
    sort($points_array);

    // Chukua masomo 7 bora (Best 7)
    $best_7 = array_slice($points_array, 0, 7);
    $total_points = array_sum($best_7);

    // Kama mwanafunzi amefanya chini ya masomo 7, hesabu pointi za masomo yote aliyofanya
    if (count($points_array) < 7) {
        $total_points = array_sum($points_array);
    }

    // Tafuta Division kulingana na Best 7 Points
    if ($total_points >= 7 && $total_points <= 17) {
        $division = 'Division I';
    } elseif ($total_points >= 18 && $total_points <= 21) {
        $division = 'Division II';
    } elseif ($total_points >= 22 && $total_points <= 25) {
        $division = 'Division III';
    } elseif ($total_points >= 26 && $total_points <= 33) {
        $division = 'Division IV';
    } else {
        $division = 'Division IV (0)';
    }

    return [
        'points' => $total_points,
        'division' => $division
    ];
}

// Nafasi/Position ya mwanafunzi kwenye darasa kulingana na aina ya mtihani uliochaguliwa
function getClassRanking($pdo, $class, $student_id, $exam_type) {
    $sql = "
        SELECT s.student_id, AVG(m.score) as average
        FROM students s
        JOIN marks m ON s.student_id = m.student_id
        WHERE (s.class = ? OR s.class_name = ?) AND m.term = ?
        GROUP BY s.student_id
        ORDER BY average DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class, $class, $exam_type]);
    $rankings = $stmt->fetchAll();
    
    $position = 0;
    $total_students = count($rankings);
    
    foreach ($rankings as $index => $row) {
        if ($row['student_id'] == $student_id) {
            $position = $index + 1;
            break;
        }
    }
    
    return ['position' => $position, 'total' => $total_students];
}

try {
    // Tafuta watoto wa mzazi huyu
    $stmt_students = $pdo->prepare("SELECT student_id, student_name, COALESCE(class, class_name) as class, school_name FROM students WHERE parent_id = ?");
    $stmt_students->execute([$parent_id]);
    $children = $stmt_students->fetchAll();

    $report_data = [];

    foreach ($children as $child) {
        $student_id  = $child['student_id'];
        $class_name  = $child['class'];
        $school_name = $child['school_name'] ?? '';
        
        // 1. Kuchuja alama za masomo
        $sql_marks = "
            SELECT sub.subject_name, m.score, m.term, m.exam_date, m.year 
            FROM marks m
            JOIN subjects sub ON m.subject_id = sub.subject_id
            WHERE m.student_id = ? AND m.term = ?
            ORDER BY sub.subject_name ASC
        ";
        $stmt_marks = $pdo->prepare($sql_marks);
        $stmt_marks->execute([$student_id, $selected_exam]);
        $marks = $stmt_marks->fetchAll();
        
        // 2. Kuchukua taarifa za Ada (Fee Status)
        $fee_required = 0;
        $fee_stmt = $pdo->prepare("SELECT total_amount FROM fee_structures WHERE LOWER(TRIM(class_name)) = LOWER(TRIM(?)) AND academic_year = ? AND LOWER(TRIM(school_name)) = LOWER(TRIM(?))");
        $fee_stmt->execute([$class_name, $current_year, $school_name]);
        $fee_res = $fee_stmt->fetch();
        if ($fee_res) {
            $fee_required = $fee_res['total_amount'];
        }

        $paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) AS total_paid FROM student_payments WHERE student_id = ? AND academic_year = ?");
        $paid_stmt->execute([$student_id, $current_year]);
        $paid_res = $paid_stmt->fetch();
        $total_paid = $paid_res ? $paid_res['total_paid'] : 0;

        $balance = $fee_required - $total_paid;
        if ($balance < 0) $balance = 0;
        $fee_status = ($balance <= 0 && $fee_required > 0) ? "Cleared" : "Owing";

        $report_data[] = [
            'info' => $child,
            'marks' => $marks,
            'fee_info' => [
                'required' => $fee_required,
                'paid'     => $total_paid,
                'balance'  => $balance,
                'status'   => $fee_status
            ]
        ];
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Academic Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .wrapper { max-width: 900px; margin: 0 auto; }
        .nav-info { background: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .filter-section { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .filter-section label { font-weight: bold; color: #475569; }
        .filter-section select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #f8fafc; }
        .filter-btn { background-color: #1e293b; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .filter-btn:hover { background-color: #0f172a; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: bold; }
        .print-btn { background-color: #0056b3; color: white; border: none; padding: 10px 20px; font-size: 14px; font-weight: bold; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .print-btn:hover { background-color: #004085; }
        .student-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .student-header { border-bottom: 3px solid #0056b3; padding-bottom: 10px; margin-bottom: 20px; }
        .student-header h2 { margin: 0; color: #0056b3; }
        .student-header p { margin: 5px 0 0 0; color: #666; font-weight: 500; }
        
        /* Table ya Masomo */
        table.marks-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.marks-table, table.marks-table th, table.marks-table td { border: 1px solid #e2e8f0; }
        table.marks-table th, table.marks-table td { padding: 12px; text-align: center; }
        table.marks-table th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        
        /* Table ya Muhtasari (Summary Box) */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background-color: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .summary-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: bold;
            padding: 14px 10px 4px 10px;
            border: none;
            text-align: center;
        }
        .summary-table td {
            font-size: 18px;
            font-weight: bold;
            color: #0056b3;
            padding: 4px 10px 14px 10px;
            border: none;
            text-align: center;
        }

        /* Fee Summary Box */
        .fee-card {
            margin-top: 25px;
            padding: 15px 20px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fee-card h4 { margin: 0 0 5px 0; color: #334155; font-size: 14px; text-transform: uppercase; }
        .fee-info-item { text-align: center; }
        .fee-info-item label { font-size: 11px; color: #64748b; display: block; text-transform: uppercase; font-weight: bold; }
        .fee-info-item span { font-size: 15px; font-weight: bold; }

        .badge { padding: 4px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 14px; }
        .status-badge { font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; }
        .status-cleared { background-color: #dcfce7; color: #15803d; }
        .status-owing { background-color: #fee2e2; color: #b91c1c; }

        @media print {
            body { background-color: white; padding: 0; margin: 0; }
            .nav-info, .filter-section, .print-btn { display: none !important; }
            .student-card { box-shadow: none; padding: 0; margin-bottom: 50px; page-break-after: always; }
            th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge { color: #333 !important; border: 1px solid #ccc; background-color: transparent !important; }
            .summary-table, .fee-card { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="nav-info">
        <span>Logged in as Parent: <b><?php echo htmlspecialchars($_SESSION['username'] ?? 'Parent'); ?></b></span>
        <div>
            <button onclick="window.print()" class="print-btn">📥 Download Report (PDF)</button>
            <a href="logout.php" class="logout-btn" style="margin-left: 15px;">Logout</a>
        </div>
    </div>

    <div class="filter-section">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 12px; margin: 0; width: 100%;">
            <label for="exam_type">Select Report Type:</label>
            <select name="exam_type" id="exam_type">
                <option value="Weekly Test" <?php if($selected_exam == 'Weekly Test') echo 'selected'; ?>>Weekly Test</option>
                <option value="Monthly Test" <?php if($selected_exam == 'Monthly Test') echo 'selected'; ?>>Monthly Test</option>
                <option value="Midterm Test" <?php if($selected_exam == 'Midterm Test') echo 'selected'; ?>>Midterm Test</option>
                <option value="Terminal Examination" <?php if($selected_exam == 'Terminal Examination') echo 'selected'; ?>>Terminal Examination</option>
                <option value="Annual Examination" <?php if($selected_exam == 'Annual Examination') echo 'selected'; ?>>Annual Examination</option>
            </select>
            <button type="submit" class="filter-btn">View Report</button>
        </form>
    </div>

    <h1 style="text-align: center; color: #1e293b; margin-bottom: 30px;">STUDENT PROGRESS REPORT</h1>

    <?php if (!empty($report_data)): ?>
        <?php foreach ($report_data as $item): 
            $student = $item['info'];
            $student_marks = $item['marks'];
            $fee_info = $item['fee_info'];
            
            $total_marks = 0;
            $total_subjects = count($student_marks);
            
            $display_date = "N/A";
            if ($total_subjects > 0 && !empty($student_marks[0]['exam_date'])) {
                $display_date = date('d-M-Y', strtotime($student_marks[0]['exam_date']));
            }
        ?>
            <div class="student-card">
                <div class="student-header">
                    <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
                    <p>Class: <b><?php echo htmlspecialchars($student['class']); ?></b> | Assessment: <b style="color: #0056b3;"><?php echo htmlspecialchars($selected_exam); ?></b> | Date Done: <b><?php echo $display_date; ?></b></p>
                </div>

                <?php if ($total_subjects > 0): ?>
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Points</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_marks as $row): 
                                $total_marks += $row['score'];
                                $grade_data = getGradeAndRemarks($row['score']);
                            ?>
                                <tr>
                                    <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td style="font-weight: bold;"><?php echo $row['score']; ?>%</td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $grade_data['color']; ?>;">
                                            <?php echo $grade_data['grade']; ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: bold;"><?php echo $grade_data['points']; ?></td>
                                    <td style="color: #64748b; font-size: 14px; text-align: left;"><?php echo $grade_data['remarks']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php 
                        $average = round($total_marks / $total_subjects, 1);
                        $overall_grade_data = getGradeAndRemarks($average);
                        $div_info = calculateDivisionAndPoints($student_marks);
                        $ranking = getClassRanking($pdo, $student['class'], $student['student_id'], $selected_exam);
                    ?>
                    
                    <!-- SUMMARY TABLE -->
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Total Marks</th>
                                <th>Average Score</th>
                                <th>Overall Grade</th>
                                <th>Points</th>
                                <th>Division</th>
                                <th>Class Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $total_marks; ?></td>
                                <td><?php echo $average; ?>%</td>
                                <td style="color: <?php echo $overall_grade_data['color']; ?>;">
                                    <?php echo $overall_grade_data['grade']; ?>
                                </td>
                                <td><?php echo $div_info['points']; ?></td>
                                <td><?php echo $div_info['division']; ?></td>
                                <td><?php echo $ranking['position'] . " / " . $ranking['total']; ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #94a3b8; font-style: italic; margin-top: 20px;">No marks have been recorded for <b><?php echo htmlspecialchars($selected_exam); ?></b> yet.</p>
                <?php endif; ?>

                <!-- FEE STATUS CARD (Taarifa za Ada) -->
                <div class="fee-card">
                    <div>
                        <h4>💳 Financial Status (Academic Year: <?php echo $current_year; ?>)</h4>
                        <span class="status-badge <?php echo ($fee_info['status'] === 'Cleared') ? 'status-cleared' : 'status-owing'; ?>">
                            Account Status: <?php echo $fee_info['status']; ?>
                        </span>
                    </div>
                    <div class="fee-info-item">
                        <label>Total Fee Required</label>
                        <span style="color: #334155;"><?php echo number_format($fee_info['required'], 2); ?> TZS</span>
                    </div>
                    <div class="fee-info-item">
                        <label>Total Paid</label>
                        <span style="color: #16a34a;"><?php echo number_format($fee_info['paid'], 2); ?> TZS</span>
                    </div>
                    <div class="fee-info-item">
                        <label>Balance Due</label>
                        <span style="color: #dc3545;"><?php echo number_format($fee_info['balance'], 2); ?> TZS</span>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; color: gray;">
            <p>Your account is not linked to any student. Please contact the administrator.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>