<?php
session_start();
require 'db.php';

// Hakikisha mtumiaji amelogin na ni Mwalimu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// USALAMA WA SHULE: Hakikisha jina la shule ya mwalimu ipo kwenye session
if (!isset($_SESSION['school_name'])) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$teacher_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
}

$school_name = trim($_SESSION['school_name']);

// Kuchukua aina ya mtihani iliyochaguliwa kwenye Filter
$selected_term = isset($_GET['filter_term']) ? trim($_GET['filter_term']) : '';

// Function ya kukokotoa Grade kulingana na viwango vipya
function calculateGrade($score) {
    if ($score >= 75 && $score <= 100) {
        return ['grade' => 'A', 'color' => '#16a34a']; // Green
    } elseif ($score >= 61 && $score <= 74) {
        return ['grade' => 'B', 'color' => '#0284c7']; // Blue
    } elseif ($score >= 45 && $score <= 60) {
        return ['grade' => 'C', 'color' => '#ca8a04']; // Yellow/Gold
    } elseif ($score >= 30 && $score <= 44) {
        return ['grade' => 'D', 'color' => '#ea580c']; // Orange
    } else {
        return ['grade' => 'F', 'color' => '#dc2626']; // Red
    }
}

try {
    // SQL Query ya msingi
    $sql = "
        SELECT 
            m.mark_id,
            m.score,
            m.term,
            m.exam_date,
            m.year,
            s.student_name,
            s.class_name,       
            sub.subject_name
        FROM marks m
        INNER JOIN students s ON m.student_id = s.student_id
        INNER JOIN subjects sub ON m.subject_id = sub.subject_id
        INNER JOIN teacher_assignments ta ON (
            TRIM(s.class_name) = TRIM(ta.class_name) 
            AND m.subject_id = ta.subject_id
        )
        WHERE ta.teacher_id = ? 
        AND TRIM(s.school_name) = TRIM(?)
    ";
    
    $params = [$teacher_id, $school_name];

    if (!empty($selected_term)) {
        $sql .= " AND m.term = ? ";
        $params[] = $selected_term;
    }

    // SAHIHISHO LA ORDER BY: Tunasaka m.score DESC ili mwenye maksi za juu awe wa kwanza
    $sql .= " ORDER BY s.class_name ASC, sub.subject_name ASC, m.term ASC, CAST(m.score AS UNSIGNED) DESC, s.student_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_marks = $stmt->fetchAll();

    // Kupanga data kwa makundi (Grouping) kwa ajili ya Heading za Kitaaluma
    $grouped_marks = [];
    foreach ($all_marks as $mark) {
        $group_key = $mark['class_name'] . '_' . $mark['subject_name'] . '_' . $mark['term'];
        $grouped_marks[$group_key]['info'] = [
            'class_name' => $mark['class_name'],
            'subject_name' => $mark['subject_name'],
            'term' => $mark['term']
        ];
        $grouped_marks[$group_key]['students'][] = $mark;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Results Report</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .report-header { text-align: center; margin-bottom: 25px; border-bottom: 3px double #0056b3; padding-bottom: 15px; }
        .report-header h1 { margin: 0; font-size: 24px; color: #1e293b; text-transform: uppercase; letter-spacing: 1px; }
        .report-header h2 { margin: 5px 0 0 0; font-size: 18px; color: #0056b3; text-transform: uppercase; }
        .report-header h3 { margin: 5px 0 0 0; font-size: 16px; color: #475569; text-transform: uppercase; }
        
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; }
        
        .filter-box { background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .filter-box label { font-weight: bold; font-size: 14px; }
        .filter-box select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; min-width: 250px; }
        .filter-box button { background-color: #0f172a; color: white; padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
        
        .report-section { margin-bottom: 50px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #f1f5f9; color: #1e293b; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f8fafc; }
        
        .score-badge { font-weight: bold; color: #0f172a; }
        .high-score { color: #16a34a; }
        .low-score { color: #ef4444; }
        
        .grade-badge {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            display: inline-block;
            min-width: 20px;
            text-align: center;
        }

        .btn-edit { background-color: #ea580c; color: white; padding: 4px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .btn-edit:hover { background-color: #c2410c; }
        .btn-print { background-color: #0056b3; color: white; padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; margin-right: 15px; }

        @media print {
            body { background-color: white; margin: 0; padding: 0; color: #000; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .nav-links, .filter-box, .btn-print, .btn-edit, [style*="background-color: #d4edda"] { display: none !important; }
            th:last-child, td:last-child { display: none !important; }
            table { border: 1px solid #000; }
            th, td { border: 1px solid #000; padding: 8px; }
            th { background-color: #f1f5f9 !important; color: black !important; }
            .grade-badge { color: black !important; border: 1px solid #000; background: transparent !important; }
            .report-section { page-break-after: always; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="nav-links">
        <span>Logged in: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> (<?php echo htmlspecialchars($school_name); ?>)</span>
        <div>
            <button onclick="window.print()" class="btn-print">🖨️ Print Academic Report</button>
            <a href="weka_alama.php" style="color: #0056b3; margin-right: 15px;">➕ Enter New Marks</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </div>
    </div>

    <div class="filter-box">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 15px; width: 100%;">
            <label for="filter_term">Filter by Assessment Type:</label>
            <select name="filter_term" id="filter_term">
                <option value="">-- All Assessments --</option>
                <option value="Weekly Test" <?php echo $selected_term === 'Weekly Test' ? 'selected' : ''; ?>>Weekly Test</option>
                <option value="Monthly Test" <?php echo $selected_term === 'Monthly Test' ? 'selected' : ''; ?>>Monthly Test</option>
                <option value="Midterm Test" <?php echo $selected_term === 'Midterm Test' ? 'selected' : ''; ?>>Midterm Test</option>
                <option value="Terminal Examination" <?php echo $selected_term === 'Terminal Examination' ? 'selected' : ''; ?>>Terminal Examination</option>
                <option value="Annual Examination" <?php echo $selected_term === 'Annual Examination' ? 'selected' : ''; ?>>Annual Examination</option>
            </select>
            <button type="submit">🔍 Filter Results</button>
            <?php if (!empty($selected_term)): ?>
                <a href="matokeo.php" style="text-decoration: none; color: #ef4444; font-weight: bold; font-size: 14px;">❌ Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; text-align: center;">
            ✔️ <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($grouped_marks) > 0): ?>
        <?php foreach ($grouped_marks as $group): ?>
            
            <div class="report-section">
                <div class="report-header">
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <h2><?php echo htmlspecialchars($group['info']['subject_name']); ?> <?php echo htmlspecialchars($group['info']['term']); ?> REPORT</h2>
                    <h3><?php echo htmlspecialchars($group['info']['class_name']); ?></h3>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Rank</th>
                            <th>Student Name</th>
                            <th style="width: 80px; text-align: center;">Score</th>
                            <th style="width: 60px; text-align: center;">Grade</th>
                            <th>Exam Date</th>
                            <th style="text-align: center; width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($group['students'] as $mark): 
                            $grade_info = calculateGrade($mark['score']);
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold; color: #1e293b;"><?php echo $sn++; ?></td>
                                <td><b><?php echo htmlspecialchars($mark['student_name']); ?></b></td>
                                <td style="text-align: center;">
                                    <span class="score-badge <?php echo $mark['score'] >= 45 ? 'high-score' : 'low-score'; ?>">
                                        <?php echo htmlspecialchars($mark['score']); ?>%
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="grade-badge" style="background-color: <?php echo $grade_info['color']; ?>;">
                                        <?php echo $grade_info['grade']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $timestamp = strtotime($mark['exam_date']);
                                    echo ($timestamp && $timestamp > 0 && $mark['exam_date'] !== '0000-00-00') ? htmlspecialchars(date('d-M-Y', $timestamp)) : "Not Specified";
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="edit_marks.php?id=<?php echo $mark['mark_id']; ?>" class="btn-edit">✏️ Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: #94a3b8; font-style: italic; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;">No marks found matching the selected criteria.</div>
    <?php endif; ?>
</div>

</body>
</html>