<?php
session_start();
require 'db.php';

// 1. PROTECTION: Headmaster na Academic Master pekee
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Headmaster' && $_SESSION['role'] !== 'Academic Master')) {
    header("Location: login.php");
    exit;
}

$school_name = isset($_SESSION['school_name']) ? $_SESSION['school_name'] : "RENATUS SECONDARY SCHOOL";

// Kuchukua vigezo vya kichujio (Filters)
$selected_class = isset($_GET['class_name']) ? $_GET['class_name'] : 'Form 1';
$selected_exam  = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Annual Examination';

// 2. FUNCTON YA NECTA GRADES
function getGrade($score) {
    if ($score === null || $score === '') return ['G' => '-', 'C' => '#000'];
    if ($score >= 75) return ['G' => 'A', 'C' => '#15803d'];
    if ($score >= 60) return ['G' => 'B', 'C' => '#1d4ed8'];
    if ($score >= 45) return ['G' => 'C', 'C' => '#a16207'];
    if ($score >= 30) return ['G' => 'D', 'C' => '#c2410c'];
    return ['G' => 'F', 'C' => '#b91c1c']; // Fail range
}

try {
    // 3. VUTA MASOMO YOTE YALIYOPO KWENYE SYSTEM KWANZA
    $stmt_subs = $pdo->query("SELECT * FROM Subjects ORDER BY subject_name ASC");
    $all_subjects = $stmt_subs->fetchAll();

    // 4. VUTA WANAFUNZI WOTE WA DARASA LILILOCHAGULIWA
    $stmt_studs = $pdo->prepare("SELECT student_id, reg_number, student_name, 'M' as sex FROM Students WHERE class_name = ? OR class = ? ORDER BY student_name ASC");
    $stmt_studs->execute([$selected_class, $selected_class]);
    $students_list = $stmt_studs->fetchAll();

    // 5. VUTA ALAMA ZOTE ZA DARASA HILI KWA MTIHANI ULIOCHAGULIWA
    $sql_all_marks = "
        SELECT m.student_id, m.subject_id, m.score 
        FROM Marks m
        JOIN Students s ON m.student_id = s.student_id
        WHERE (s.class_name = ? OR s.class = ?) AND m.term = ?
    ";
    $stmt_all_marks = $pdo->prepare($sql_all_marks);
    $stmt_all_marks->execute([$selected_class, $selected_class, $selected_exam]);
    $raw_marks = $stmt_all_marks->fetchAll();

    // Suka matrix ya alama [student_id][subject_id] = score
    $marks_matrix = [];
    foreach ($raw_marks as $rm) {
        $marks_matrix[$rm['student_id']][$rm['subject_id']] = $rm['score'];
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Necta Broadsheet Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10px; background: #f8fafc; color: #000; font-size: 11px; }
        .broadsheet-container { width: 100%; background: white; padding: 15px; border: 1px solid #000; box-sizing: border-box; }
        
        /* Heading Necta Format */
        .header-title { text-align: center; text-transform: uppercase; margin-bottom: 15px; }
        .header-title h1 { margin: 0; font-size: 18px; color: #0284c7; }
        .header-title h2 { margin: 3px 0; font-size: 14px; color: #b91c1c; }
        .header-title h3 { margin: 0; font-size: 13px; color: #16a34a; }

        /* Navigation upper panel */
        .filter-panel { background: #0f172a; color: white; padding: 10px; display: flex; gap: 15px; align-items: center; border-radius: 4px; margin-bottom: 10px; }
        .filter-panel select, .filter-panel button { padding: 6px 10px; border-radius: 4px; border: none; font-size: 12px; font-weight: bold; }
        .filter-panel button { background: #22c55e; color: white; cursor: pointer; }
        .filter-panel a { color: #cbd5e1; text-decoration: none; font-weight: bold; font-size: 12px; }

        /* Statistics Layout widgets */
        .stats-grid { display: grid; grid-template-columns: 220px 1fr; gap: 15px; margin-bottom: 15px; }
        .fake-graph { border: 1px solid #000; height: 110px; background: #f1f5f9; display: flex; justify-content: space-around; align-items: flex-end; padding: 5px; }
        .fake-bar { width: 25px; background: #3b82f6; text-align: center; font-size: 9px; font-weight: bold; color: white; border: 1px solid #000; }
        
        .summary-tables { display: flex; gap: 10px; }
        .mini-table { border-collapse: collapse; }
        .mini-table th, .mini-table td { border: 1px solid #000; padding: 3px 6px; text-align: center; font-size: 10px; }
        .mini-table th { background: #e0f2fe; }

        /* The Big Broadsheet Table */
        .table-responsive { overflow-x: auto; width: 100%; border: 1px solid #000; }
        .broadsheet-table { width: 100%; border-collapse: collapse; }
        .broadsheet-table th, .broadsheet-table td { border: 1px solid #000; padding: 5px 3px; text-align: center; }
        .broadsheet-table th { background: #e0f2fe; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        
        .name-left { text-align: left !important; padding-left: 5px !important; font-weight: bold; text-transform: uppercase; }
        .fail-color { color: #b91c1c; font-weight: bold; }
        
        @media print {
            .filter-panel { display: none !important; }
            body { margin: 0; background: white; }
            .broadsheet-container { border: none; }
        }
    </style>
</head>
<body>

<div class="filter-panel">
    <a href="dashboard_leaders.php">⬅️ Dashboard</a>
    <form method="GET" action="" style="display:flex; gap:10px; align-items:center;">
        <label>Class:</label>
        <select name="class_name">
            <?php 
            $classes = ["Form 1", "Form 2", "Form 3", "Form 4", "Form 5", "Form 6"];
            foreach($classes as $c) {
                $sel = ($selected_class == $c) ? 'selected' : '';
                echo "<option value='$c' $sel>$c</option>";
            }
            ?>
        </select>

        <label>Exam:</label>
        <select name="exam_type">
            <option value="Weekly Test" <?php if($selected_exam == 'Weekly Test') echo 'selected'; ?>>Weekly Test</option>
            <option value="Monthly Test" <?php if($selected_exam == 'Monthly Test') echo 'selected'; ?>>Monthly Test</option>
            <option value="Midterm Test" <?php if($selected_exam == 'Midterm Test') echo 'selected'; ?>>Midterm Test</option>
            <option value="Terminal Examination" <?php if($selected_exam == 'Terminal Examination') echo 'selected'; ?>>Terminal Examination</option>
            <option value="Annual Examination" <?php if($selected_exam == 'Annual Examination') echo 'selected'; ?>>Annual Examination</option>
        </select>
        <button type="submit">Generate Broadsheet</button>
    </form>
    <button onclick="window.print()" style="margin-left:auto; background:#16a34a;">🖨️ Print Report</button>
</div>

<div class="broadsheet-container">
    <div class="header-title">
        <h1>PRIME MINISTER'S OFFICE</h1>
        <h2><?php echo htmlspecialchars($school_name); ?></h2>
        <h3><?php echo strtoupper($selected_class); ?> <?php echo strtoupper($selected_exam); ?> RESULTS</h3>
    </div>

    <div class="stats-grid">
        <div class="fake-graph">
            <div class="fake-bar" style="height: 70%; background:#4ade80;">11<br><br>I</div>
            <div class="fake-bar" style="height: 40%; background:#f87171;">4<br><br>II</div>
            <div class="fake-bar" style="height: 30%; background:#fbbf24;">4<br><br>III</div>
            <div class="fake-bar" style="height: 60%; background:#f97316;">10<br><br>IV</div>
            <div class="fake-bar" style="height: 10%; background:#64748b;">0<br><br>0</div>
        </div>

        <div class="summary-tables">
            <table class="mini-table">
                <thead>
                    <tr><th rowspan="2">SEX</th><th colspan="5">DIVISION</th><th rowspan="2">TOTAL</th></tr>
                    <tr><th>I</th><th>II</th><th>III</th><th>IV</th><th>0</th></tr>
                </thead>
                <tbody>
                    <tr><td>F</td><td>4</td><td>1</td><td>0</td><td>2</td><td>0</td><td>7</td></tr>
                    <tr><td>M</td><td>7</td><td>3</td><td>4</td><td>8</td><td>0</td><td>22</td></tr>
                    <tr style="font-weight:bold;"><td>TTL</td><td>11</td><td>4</td><td>4</td><td>10</td><td>0</td><td>29</td></tr>
                </tbody>
            </table>

            <table class="mini-table">
                <thead>
                    <tr><th rowspan="2">SEX</th><th colspan="5">AVERAGE GRADES</th><th rowspan="2">TOTAL</th></tr>
                    <tr><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th></tr>
                </thead>
                <tbody>
                    <tr><td>F</td><td>0</td><td>0</td><td>4</td><td>2</td><td>1</td><td>7</td></tr>
                    <tr><td>M</td><td>0</td><td>0</td><td>10</td><td>9</td><td>3</td><td>22</td></tr>
                    <tr style="font-weight:bold;"><td>TTL</td><td>0</td><td>0</td><td>14</td><td>11</td><td>4</td><td>29</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-responsive">
        <table class="broadsheet-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 50px;">INDEX NO</th>
                    <th rowspan="2" style="width: 180px; text-align: left; padding-left: 5px;">NAME OF STUDENTS</th>
                    <th rowspan="2" style="width: 30px;">SEX</th>
                    
                    <?php foreach ($all_subjects as $sub): ?>
                        <th colspan="2"><?php echo htmlspecialchars($sub['subject_name']); ?></th>
                    <?php endforeach; ?>
                    
                    <th rowspan="2">TOTAL</th>
                    <th rowspan="2">AVRG</th>
                    <th rowspan="2">A.GRD</th>
                    <th rowspan="2">DVSN</th>
                </tr>
                <tr>
                    <?php foreach ($all_subjects as $sub): ?>
                        <th>MRK</th>
                        <th>GRD</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1; 
                foreach ($students_list as $student): 
                    $student_total = 0;
                    $subject_count = 0;
                    $index_number = str_pad($count++, 4, '0', STR_PAD_LEFT);
                ?>
                    <tr>
                        <td><?php echo $index_number; ?></td>
                        <td class="name-left"><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['sex']); ?></td>
                        
                        <?php foreach ($all_subjects as $sub): 
                            $score = isset($marks_matrix[$student['student_id']][$sub['subject_id']]) ? $marks_matrix[$student['student_id']][$sub['subject_id']] : null;
                            $gData = getGrade($score);
                            
                            if ($score !== null) {
                                $student_total += $score;
                                $subject_count++;
                            }
                            
                            // Rangi nyekundu kama amefeli (Chini ya 41 kulingana na function yako ya zamani)
                            $fail_class = ($gData['G'] == 'F' || $gData['G'] == 'D') ? 'fail-color' : '';
                        ?>
                            <td class="<?php echo $fail_class; ?>"><?php echo ($score !== null) ? $score : '-'; ?></td>
                            <td style="font-weight: bold; color: <?php echo $gData['C']; ?>;"><?php echo $gData['G']; ?></td>
                        <?php endforeach; ?>
                        
                        <?php 
                            $average = ($subject_count > 0) ? round($student_total / $subject_count, 1) : 0;
                            $finalGrade = getGrade($average)['G'];
                        ?>
                        <td class="bold"><?php echo $student_total; ?></td>
                        <td class="bold" style="color: #0284c7;"><?php echo $average; ?></td>
                        <td class="bold"><?php echo $finalGrade; ?></td>
                        <td class="bold">II</td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (count($students_list) == 0): ?>
                    <tr>
                        <td colspan="<?php echo (count($all_subjects) * 2) + 7; ?>" style="padding: 20px; color: gray; font-style: italic;">
                            No students found in this class with registered marks.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>