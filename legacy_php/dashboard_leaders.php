<?php
session_start();
require 'db.php';

// 1. PROTECTION: Allow only Headmaster and Academic Master
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Headmaster' && $_SESSION['role'] !== 'Academic Master')) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['school_name'])) {
    die("<div style='color: #dc2626; font-family: Arial, sans-serif; font-weight: bold; text-align: center; margin-top: 100px; padding: 20px; border: 2px dashed #dc2626; display: inline-block; background: #fef2f2; border-radius: 8px;'>
            ❌ Error: School session configuration is missing or has expired. Please log in again to restore it.
          </div>");
}

$school_name = $_SESSION['school_name'];
$user_role = $_SESSION['role'];

// 2. FETCH FILTERS
$selected_class = isset($_GET['class']) ? $_GET['class'] : 'Form 1';
$selected_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Weekly Test';
$exam_month_name = "Not Conducted Yet"; 

// 3. NECTA GRADING & POINTS FUNCTION
function getGrade($score) {
    if ($score === null || $score === '') return ['G' => '-', 'C' => '#000', 'P' => null];
    if ($score >= 75) return ['G' => 'A', 'C' => '#16a34a', 'P' => 1];
    if ($score >= 65) return ['G' => 'B', 'C' => '#2563eb', 'P' => 2];
    if ($score >= 45) return ['G' => 'C', 'C' => '#ca8a04', 'P' => 3];
    if ($score >= 30) return ['G' => 'D', 'C' => '#ea580c', 'P' => 4];
    return ['G' => 'F', 'C' => '#dc2626', 'P' => 5];
}

// 4. EXCEL FORMULA IMPLEMENTATION (NECTA DIVISION LOGIC)
function calculateDivisionFromPoints($total_points) {
    if ($total_points === null || $total_points === '') return '-';
    if ($total_points >= 34) return '0';
    if ($total_points >= 26) return 'IV';
    if ($total_points >= 22) return 'III';
    if ($total_points >= 18) return 'II';
    if ($total_points >= 7)  return 'I';
    return '-';
}

try {
    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $subjects = $subjects_stmt->fetchAll();

    $marks_sql = "SELECT s.student_id, s.student_name, s.reg_number, s.sex, m.subject_id, m.score, m.exam_date 
                  FROM students s
                  LEFT JOIN marks m ON s.student_id = m.student_id AND m.term = ?
                  WHERE s.class_name = ? OR s.class = ?
                  ORDER BY s.student_name ASC";
    
    $marks_stmt = $pdo->prepare($marks_sql);
    $marks_stmt->execute([$selected_exam, $selected_class, $selected_class]);
    $raw_data = $marks_stmt->fetchAll();

    $students_data = [];
    $latest_exam_date = null;

    $gpa_counters = [
        'F' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
        'M' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0]
    ];
    $div_counters = [
        'F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
        'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0]
    ];
    
    $gender_totals = ['F' => 0, 'M' => 0];

    // Initialize Detailed NECTA Subject Statistics Array
    $subject_stats = [];
    foreach ($subjects as $sub) {
        $subject_stats[$sub['subject_id']] = [
            'id'           => $sub['subject_id'],
            'name'         => $sub['subject_name'],
            'grades'       => [
                'F' => ['A'=>0, 'B'=>0, 'C'=>0, 'D'=>0, 'F'=>0],
                'M' => ['A'=>0, 'B'=>0, 'C'=>0, 'D'=>0, 'F'=>0]
            ],
            'reg'          => ['F' => 0, 'M' => 0],
            'sat'          => ['F' => 0, 'M' => 0],
            'total_score'  => ['F' => 0, 'M' => 0],
        ];
    }

    foreach ($raw_data as $row) {
        $studentId = $row['student_id'];
        $db_sex = (!empty($row['sex']) && strtoupper(trim($row['sex'])) === 'M') ? 'M' : 'F';

        if (!isset($students_data[$studentId])) {
            $students_data[$studentId] = [
                'reg_number'   => $row['reg_number'],
                'student_name' => $row['student_name'],
                'scores'       => [],
                'points_array' => [],
                'total'        => 0,
                'count'        => 0,
                'average'      => 0,
                'points'       => 0,
                'sex'          => $db_sex
            ];
            // Track total registered students by gender
            $gender_totals[$db_sex]++;
        }

        if ($row['subject_id'] !== null && $row['score'] !== null && $row['score'] !== '') {
            $students_data[$studentId]['scores'][$row['subject_id']] = $row['score'];
            $students_data[$studentId]['total'] += $row['score'];
            $students_data[$studentId]['count']++;
            
            $gInfo = getGrade($row['score']);
            if ($gInfo['P'] !== null) {
                $students_data[$studentId]['points_array'][] = $gInfo['P'];
            }

            // Populate subject stats
            $subId = $row['subject_id'];
            if (isset($subject_stats[$subId])) {
                $subject_stats[$subId]['sat'][$db_sex]++;
                $subject_stats[$subId]['total_score'][$db_sex] += $row['score'];
                if (isset($gInfo['G']) && isset($subject_stats[$subId]['grades'][$db_sex][$gInfo['G']])) {
                    $subject_stats[$subId]['grades'][$db_sex][$gInfo['G']]++;
                }
            }
            
            if ($row['exam_date'] !== null) {
                $latest_exam_date = $row['exam_date'];
            }
        }
    }

    if ($latest_exam_date) {
        $exam_month_name = date('F Y', strtotime($latest_exam_date));
    }

    // Process Student Performance & Divisions based on Best 7 Subjects
    foreach ($students_data as $id => $data) {
        $s = $data['sex']; 

        if ($data['count'] > 0) {
            $avg = round($data['total'] / $data['count'], 2);
            $students_data[$id]['average'] = $avg;
            
            $avgGrade = getGrade($avg)['G'];
            if (isset($gpa_counters[$s][$avgGrade])) {
                $gpa_counters[$s][$avgGrade]++;
            }
            
            // Calculate Points for Best 7 Subjects (NECTA Standard)
            $ptsArray = $data['points_array'];
            sort($ptsArray); // Sort ascending (Best grades first: 1, 2, 3...)
            $best7 = array_slice($ptsArray, 0, 7);
            
            // If student sat for less than 7 subjects, sum available
            $total_points = array_sum($best7);
            $students_data[$id]['points'] = $total_points;

            // Apply Division Excel Formula Logic
            $div = calculateDivisionFromPoints($total_points);
            
            if (isset($div_counters[$s][$div])) {
                $div_counters[$s][$div]++;
            }
            $students_data[$id]['division'] = $div;
        } else {
            $students_data[$id]['division'] = '-';
            $students_data[$id]['points'] = '-';
        }
    }

    // Process Subject Detailed Statistics
    foreach ($subject_stats as $subId => &$st) {
        $st['reg']['F'] = $gender_totals['F'];
        $st['reg']['M'] = $gender_totals['M'];
        $st['reg']['T'] = $st['reg']['F'] + $st['reg']['M'];
        
        $st['sat']['T'] = $st['sat']['F'] + $st['sat']['M'];
        
        $st['abs']['F'] = $st['reg']['F'] - $st['sat']['F'];
        $st['abs']['M'] = $st['reg']['M'] - $st['sat']['M'];
        $st['abs']['T'] = $st['reg']['T'] - $st['sat']['T'];

        foreach (['A', 'B', 'C', 'D', 'F'] as $g) {
            $st['grades']['T'][$g] = $st['grades']['F'][$g] + $st['grades']['M'][$g];
        }

        $st['ad_pass']['F'] = $st['grades']['F']['A'] + $st['grades']['F']['B'] + $st['grades']['F']['C'] + $st['grades']['F']['D'];
        $st['ad_pass']['M'] = $st['grades']['M']['A'] + $st['grades']['M']['B'] + $st['grades']['M']['C'] + $st['grades']['M']['D'];
        $st['ad_pass']['T'] = $st['ad_pass']['F'] + $st['ad_pass']['M'];

        $st['ad_pct']['F'] = $st['sat']['F'] > 0 ? round(($st['ad_pass']['F'] / $st['sat']['F']) * 100, 1) : 0;
        $st['ad_pct']['M'] = $st['sat']['M'] > 0 ? round(($st['ad_pass']['M'] / $st['sat']['M']) * 100, 1) : 0;
        $st['ad_pct']['T'] = $st['sat']['T'] > 0 ? round(($st['ad_pass']['T'] / $st['sat']['T']) * 100, 1) : 0;

        $total_score_all = $st['total_score']['F'] + $st['total_score']['M'];
        $st['avg'] = $st['sat']['T'] > 0 ? round($total_score_all / $st['sat']['T']) : 0;

        // GPA Formula: ((A*1)+(B*2)+(C*3)+(D*4)+(F*5)) / Total Sat
        if ($st['sat']['T'] > 0) {
            $gpa_points = ($st['grades']['T']['A'] * 1) + 
                          ($st['grades']['T']['B'] * 2) + 
                          ($st['grades']['T']['C'] * 3) + 
                          ($st['grades']['T']['D'] * 4) + 
                          ($st['grades']['T']['F'] * 5);
            $st['gpa'] = round($gpa_points / $st['sat']['T'], 4);
        } else {
            $st['gpa'] = 0;
        }
    }
    unset($st);

    // Sort Subjects by GPA Ascending (Lowest GPA = Position 1)
    uasort($subject_stats, function($a, $b) {
        if ($a['gpa'] == 0) return 1;
        if ($b['gpa'] == 0) return -1;
        return $a['gpa'] <=> $b['gpa'];
    });

    $psn = 1;
    foreach ($subject_stats as $subId => &$st) {
        if ($st['sat']['T'] > 0) {
            $st['psn'] = $psn++;
        } else {
            $st['psn'] = '-';
        }
    }
    unset($st);

    // Rank Students by Average Score
    uasort($students_data, function($a, $b) {
        return $b['average'] <=> $a['average']; 
    });

    $rank = 1;
    $prev_average = null;
    $count = 0;
    foreach ($students_data as $id => $data) {
        $count++;
        if ($prev_average !== null && $data['average'] < $prev_average) {
            $rank = $count;
        }
        $students_data[$id]['rank'] = $rank;
        $prev_average = $data['average'];
    }

    // Reset to Alphabetical order for broadsheet table
    uasort($students_data, function($a, $b) {
        return strcasecmp($a['student_name'], $b['student_name']);
    });

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$total_div_I   = $div_counters['F']['I'] + $div_counters['M']['I'];
$total_div_II  = $div_counters['F']['II'] + $div_counters['M']['II'];
$total_div_III = $div_counters['F']['III'] + $div_counters['M']['III'];
$total_div_IV  = $div_counters['F']['IV'] + $div_counters['M']['IV'];
$total_div_0   = $div_counters['F']['0'] + $div_counters['M']['0'];
$max_students  = max(count($students_data), 1);

// LINK ILIYOREKEBISHWA: Inahakikisha watumiaji wote wanaenda kwenye view_timetable.php bila kukwama
$timetable_url = 'view_timetable.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Dashboard - Necta Broadsheet Format</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 15px; font-size: 11px; color: #000; }
        .broadsheet-container { width: 100%; max-width: 1500px; margin: 0 auto; background: white; padding: 15px; border: 2px solid #000; box-sizing: border-box; }
        
        .system-header { text-align: center; font-weight: bold; font-size: 16px; color: #0284c7; text-transform: uppercase; margin-bottom: 2px; }
        .school-header { text-align: center; font-weight: bold; font-size: 15px; color: #b91c1c; text-transform: uppercase; margin-bottom: 2px; }
        .exam-header { text-align: center; font-weight: bold; font-size: 12px; color: #16a34a; text-transform: uppercase; margin-bottom: 15px; }

        .filter-panel { background: #0f172a; color: white; padding: 12px; display: flex; gap: 12px; align-items: center; border-radius: 6px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-panel select, .filter-panel button, .filter-panel .btn-timetable { padding: 8px 12px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 13px; font-weight: bold; }
        .filter-panel button { background: #10b981; color: white; border: none; cursor: pointer; }
        .filter-panel button:hover { background: #059669; }
        
        /* Timetable Button Styling */
        .btn-timetable { background: #2563eb; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; border: none; }
        .btn-timetable:hover { background: #1d4ed8; }

        .logout-link { margin-left: auto; color: #f87171; text-decoration: none; font-weight: bold; }

        .top-summary-grid { display: grid; grid-template-columns: 350px 1fr; gap: 15px; margin-bottom: 20px; align-items: start; }
        
        .graph-box { 
            border: 1px solid #cbd5e1; 
            height: 185px; 
            padding: 10px; 
            text-align: center; 
            background: #ffffff; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .graph-title { 
            font-weight: 800; 
            color: #1e293b; 
            font-size: 11px; 
            text-transform: uppercase; 
            margin-bottom: 12px; 
            letter-spacing: 0.5px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 5px;
        }
        .bar-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            height: 105px; 
            padding: 0 10px;
        }
        .division-group { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            width: 18%; 
        }
        .bars-flex { 
            display: flex; 
            gap: 4px; 
            align-items: flex-end; 
            width: 100%; 
            height: 90px; 
            border-bottom: 2px solid #64748b; 
            padding-bottom: 2px;
        }
        .g-bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            width: 50%;
            height: 100%;
        }
        .bar-value {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
            color: #334155;
        }
        .g-bar { 
            width: 100%; 
            border-radius: 3px 3px 0 0;
            transition: all 0.3s ease;
            box-shadow: 1px -1px 3px rgba(0,0,0,0.15); 
        }
        .g-bar.female { 
            background: linear-gradient(to top, #ec4899, #f472b6); 
            border: 1px solid #db2777;
        } 
        .g-bar.male { 
            background: linear-gradient(to top, #0284c7, #38bdf8); 
            border: 1px solid #0369a1;
        }
        .div-label { 
            font-weight: 800; 
            font-size: 11px; 
            margin-top: 5px; 
            padding: 2px 6px;
            background: #f1f5f9;
            border-radius: 4px;
            width: 80%;
            text-align: center;
        }
        
        .summary-tables { display: flex; gap: 15px; align-items: start; }
        .mini-table { border-collapse: collapse; background: white; }
        .mini-table th, .mini-table td { border: 1px solid #000; padding: 4px 6px; text-align: center; font-size: 10px; }
        .mini-table th { background-color: #e0f2fe; font-weight: bold; }

        .f-row { color: #db2777; font-weight: bold; }
        .m-row { color: #0284c7; font-weight: bold; }
        .ttl-row { font-weight: bold; background-color: #f1f5f9; }
        
        .gpa-box { border: 1px solid #000; background: #fef2f2; padding: 8px; text-align: center; min-width: 120px; }
        .gpa-title { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #475569; }
        .gpa-flex { display: flex; justify-content: space-around; margin-top: 5px; font-size: 11px; }

        .main-table-container { overflow-x: auto; width: 100%; border: 1px solid #000; margin-top: 10px; }
        .broadsheet-table { width: 100%; border-collapse: collapse; }
        .broadsheet-table th, .broadsheet-table td { border: 1px solid #000; padding: 6px 4px; font-size: 11px; text-align: center; }
        .broadsheet-table th { background-color: #e0f2fe; color: #000; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        
        .student-name-left { text-align: left !important; padding-left: 6px !important; text-transform: uppercase; font-weight: bold !important; color: #0f172a; }
        .fail-score { color: #dc2626; font-weight: bold; }

        /* NECTA Subject Breakdown Styling */
        .subject-breakdown-box { margin-top: 25px; width: 100%; background: white; }
        .subject-breakdown-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #000; margin-bottom: 6px; }
        .subject-necta-table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        .subject-necta-table th, .subject-necta-table td { border: 1px solid #000; padding: 3px 4px; text-align: center; font-size: 10px; }
        .subject-necta-table th { background-color: #ffffff; font-weight: bold; color: #000; text-transform: uppercase; }
        .sub-title-td { font-weight: bold; vertical-align: middle; text-transform: uppercase; font-size: 11px; }
        
        @media print {
            .filter-panel { display: none !important; }
            body { margin: 0; background: white; }
            .broadsheet-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="filter-panel">
    <form method="GET" action="" style="display:flex; gap:12px; align-items:center;">
        <label>Class Level:</label>
        <select name="class">
            <option value="Form 1" <?php echo $selected_class == 'Form 1' ? 'selected' : ''; ?>>Form 1</option>
            <option value="Form 2" <?php echo $selected_class == 'Form 2' ? 'selected' : ''; ?>>Form 2</option>
            <option value="Form 3" <?php echo $selected_class == 'Form 3' ? 'selected' : ''; ?>>Form 3</option>
            <option value="Form 4" <?php echo $selected_class == 'Form 4' ? 'selected' : ''; ?>>Form 4</option>
        </select>

        <label>Assessment Type:</label>
        <select name="exam_type">
            <option value="Weekly Test" <?php echo $selected_exam == 'Weekly Test' ? 'selected' : ''; ?>>Weekly Test</option>
            <option value="Monthly Test" <?php echo $selected_exam == 'Monthly Test' ? 'selected' : ''; ?>>Monthly Test</option>
            <option value="Terminal Examination" <?php echo $selected_exam == 'Terminal Examination' ? 'selected' : ''; ?>>Terminal Examination</option>
            <option value="Annual Examination" <?php echo $selected_exam == 'Annual Examination' ? 'selected' : ''; ?>>Annual Examination</option>
        </select>

        <button type="submit">🔍 Generate Broadsheet</button>
    </form>
    
    <button onclick="window.print();">🖨️ Print Broadsheet</button>
    
    <!-- LINK INAYOELEKEZA VIEW_TIMETABLE.PHP BILA MATRIX YA ERROR -->
    <a href="<?php echo $timetable_url; ?>" class="btn-timetable">
        📅 School Timetable
    </a>

    <a href="logout.php" class="logout-link">🚪 Sign Out</a>
</div>

<div class="broadsheet-container">
    <div class="system-header">THE UNITED REPUBLIC OF TANZANIA</div>
    <div class="system-header">THE PRIME MINISTER'S OFFICE, REGIONAL ADMINSTRATION AND LOCAL GOVERNMENT</div>
    <div class="school-header"><?php echo htmlspecialchars($school_name); ?></div>
    <div class="exam-header"><?php echo strtoupper($selected_class); ?> — <?php echo strtoupper($selected_exam); ?> SUMMARY REPORT (Month: <?php echo $exam_month_name; ?>)</div>

    <div class="top-summary-grid">
        <div class="graph-box">
            <div class="graph-title">📊 Graph of Division Distribution</div>
            <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 8px; font-size: 10px; font-weight: bold;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <div style="width: 12px; height: 12px; background: linear-gradient(to top, #ec4899, #f472b6); border: 1px solid #db2777; border-radius: 2px;"></div>
                    <span>Female (F)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <div style="width: 12px; height: 12px; background: linear-gradient(to top, #0284c7, #38bdf8); border: 1px solid #0369a1; border-radius: 2px;"></div>
                    <span>Male (M)</span>
                </div>
            </div>

            <div class="bar-container">
                <?php foreach (['I', 'II', 'III', 'IV', '0'] as $d_name): ?>
                <div class="division-group">
                    <div class="bars-flex">
                        <div class="g-bar-wrapper">
                            <span class="bar-value"><?php echo $div_counters['F'][$d_name] > 0 ? $div_counters['F'][$d_name] : ''; ?></span>
                            <div class="g-bar female" style="height: <?php echo max(($div_counters['F'][$d_name] / $max_students) * 75, 2); ?>%;"></div>
                            <span style="font-size: 8px; color: #64748b; font-weight: bold; margin-top: 2px;">F</span>
                        </div>
                        <div class="g-bar-wrapper">
                            <span class="bar-value"><?php echo $div_counters['M'][$d_name] > 0 ? $div_counters['M'][$d_name] : ''; ?></span>
                            <div class="g-bar male" style="height: <?php echo max(($div_counters['M'][$d_name] / $max_students) * 75, 2); ?>%;"></div>
                            <span style="font-size: 8px; color: #64748b; font-weight: bold; margin-top: 2px;">M</span>
                        </div>
                    </div>
                    <?php 
                        $lbl_colors = ['I'=>'#16a34a', 'II'=>'#2563eb', 'III'=>'#ca8a04', 'IV'=>'#ea580c', '0'=>'#dc2626'];
                    ?>
                    <div class="div-label" style="color: <?php echo $lbl_colors[$d_name]; ?>; border-left: 3px solid <?php echo $lbl_colors[$d_name]; ?>;"><?php echo $d_name; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="summary-tables">
            <table class="mini-table">
                <thead>
                    <tr><th rowspan="2">SEX</th><th colspan="5">DIVISION SUMMARY</th><th rowspan="2">TOTAL</th></tr>
                    <tr><th>I</th><th>II</th><th>III</th><th>IV</th><th>0</th></tr>
                </thead>
                <tbody>
                    <tr class="f-row"><td>F</td><td><?php echo $div_counters['F']['I']; ?></td><td><?php echo $div_counters['F']['II']; ?></td><td><?php echo $div_counters['F']['III']; ?></td><td><?php echo $div_counters['F']['IV']; ?></td><td><?php echo $div_counters['F']['0']; ?></td><td><?php echo $gender_totals['F']; ?></td></tr>
                    <tr class="m-row"><td>M</td><td><?php echo $div_counters['M']['I']; ?></td><td><?php echo $div_counters['M']['II']; ?></td><td><?php echo $div_counters['M']['III']; ?></td><td><?php echo $div_counters['M']['IV']; ?></td><td><?php echo $div_counters['M']['0']; ?></td><td><?php echo $gender_totals['M']; ?></td></tr>
                    <tr class="ttl-row"><td>TTL</td><td><?php echo $total_div_I; ?></td><td><?php echo $total_div_II; ?></td><td><?php echo $total_div_III; ?></td><td><?php echo $total_div_IV; ?></td><td><?php echo $total_div_0; ?></td><td><?php echo count($students_data); ?></td></tr>
                </tbody>
            </table>

            <table class="mini-table">
                <thead>
                    <tr><th rowspan="2">SEX</th><th colspan="5">AVERAGE GRADES OVERVIEW</th><th rowspan="2">TOTAL</th></tr>
                    <tr><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th></tr>
                </thead>
                <tbody>
                    <tr class="f-row"><td>F</td><td><?php echo $gpa_counters['F']['A']; ?></td><td><?php echo $gpa_counters['F']['B']; ?></td><td><?php echo $gpa_counters['F']['C']; ?></td><td><?php echo $gpa_counters['F']['D']; ?></td><td><?php echo $gpa_counters['F']['F']; ?></td><td><?php echo $gender_totals['F']; ?></td></tr>
                    <tr class="m-row"><td>M</td><td><?php echo $gpa_counters['M']['A']; ?></td><td><?php echo $gpa_counters['M']['B']; ?></td><td><?php echo $gpa_counters['M']['C']; ?></td><td><?php echo $gpa_counters['M']['D']; ?></td><td><?php echo $gpa_counters['M']['F']; ?></td><td><?php echo $gender_totals['M']; ?></td></tr>
                    <tr class="ttl-row"><td>TTL</td><td><?php echo $gpa_counters['F']['A'] + $gpa_counters['M']['A']; ?></td><td><?php echo $gpa_counters['F']['B'] + $gpa_counters['M']['B']; ?></td><td><?php echo $gpa_counters['F']['C'] + $gpa_counters['M']['C']; ?></td><td><?php echo $gpa_counters['F']['D'] + $gpa_counters['M']['D']; ?></td><td><?php echo $gpa_counters['F']['F'] + $gpa_counters['M']['F']; ?></td><td><?php echo count($students_data); ?></td></tr>
                </tbody>
            </table>

            <div class="gpa-box">
                <div class="gpa-title">Registered</div>
                <div style="font-size: 16px; font-weight: bold; color: #0284c7; margin-top: 2px;"><?php echo count($students_data); ?></div>
                <div class="gpa-flex">
                    <span class="f-row">F:<?php echo $gender_totals['F']; ?></span>
                    <span class="m-row">M:<?php echo $gender_totals['M']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="main-table-container">
        <table class="broadsheet-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 45px;">INDEX</th>
                    <th rowspan="2" style="width: 130px;">REG NUMBER</th>
                    <th rowspan="2" style="text-align: left; padding-left: 6px;">NAME OF STUDENTS</th>
                    <th rowspan="2" style="width: 35px;">SEX</th>
                    
                    <?php foreach ($subjects as $subject): ?>
                        <th colspan="2"><?php echo htmlspecialchars($subject['subject_name']); ?></th>
                    <?php endforeach; ?>
                    
                    <th rowspan="2" style="width: 55px;">TOTAL</th>
                    <th rowspan="2" style="width: 55px;">AVRG</th>
                    <th rowspan="2" style="width: 45px;">A.GRD</th>
                    <th rowspan="2" style="width: 45px;">PTS</th>
                    <th rowspan="2" style="width: 45px;">DVSN</th>
                    <th rowspan="2" style="width: 45px;">RANK</th>
                </tr>
                <tr>
                    <?php foreach ($subjects as $subject): ?>
                        <th>MRK</th>
                        <th>GRD</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students_data) > 0): ?>
                    <?php $count = 1; foreach ($students_data as $id => $student): 
                        $index_number = str_pad($count++, 4, '0', STR_PAD_LEFT);
                        $sex_color = ($student['sex'] === 'F') ? '#db2777' : '#0284c7'; 
                    ?>
                        <tr>
                            <td><?php echo $index_number; ?></td>
                            <td style="font-weight: bold; color: #475569;"><?php echo htmlspecialchars($student['reg_number']); ?></td>
                            <td class="student-name-left"><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td style="font-weight: bold; color: <?php echo $sex_color; ?>;"><?php echo htmlspecialchars($student['sex']); ?></td>
                            
                            <?php foreach ($subjects as $subject): 
                                $subId = $subject['subject_id'];
                                $score = isset($student['scores'][$subId]) ? $student['scores'][$subId] : null;
                                $gData = getGrade($score);
                                $fail_style = ($gData['G'] == 'F' || $gData['G'] == 'D') ? 'fail-score' : '';
                            ?>
                                <td class="<?php echo $fail_style; ?>"><?php echo ($score !== null) ? $score . '%' : '-'; ?></td>
                                <td style="font-weight: bold; color: <?php echo $gData['C']; ?>;"><?php echo $gData['G']; ?></td>
                            <?php endforeach; ?>
                            
                            <td style="font-weight: bold; background: #f8fafc;"><?php echo $student['total']; ?></td>
                            <td style="font-weight: bold; background: #f0f9ff; color: #0284c7;"><?php echo $student['count'] > 0 ? $student['average'] : '-'; ?></td>
                            <td style="font-weight: bold;"><?php echo $student['count'] > 0 ? getGrade($student['average'])['G'] : '-'; ?></td>
                            <td style="font-weight: bold; color: #d97706;"><?php echo $student['points']; ?></td>
                            <td style="font-weight: bold; color: #16a34a;"><?php echo $student['division']; ?></td>
                            <td style="font-weight: bold; background: #f0fdf4; color: #166534; font-size: 12px;"><?php echo $student['count'] > 0 ? $student['rank'] : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo (count($subjects) * 2) + 9; ?>" style="padding: 30px; text-align: center; font-style: italic; color: #94a3b8;">
                            ❌ No academic records found for this class on the selected assessment type.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Subject Performance & Ranking Summary Table -->
    <div class="subject-breakdown-box">
        <div class="subject-breakdown-title">SUBJECTS SUMMARY</div>
        <table class="subject-necta-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 120px;">SUBJECT'S</th>
                    <th rowspan="2" style="width: 30px;">SEX</th>
                    <th rowspan="2" style="width: 35px;">REG</th>
                    <th rowspan="2" style="width: 35px;">ABS</th>
                    <th rowspan="2" style="width: 35px;">CSE</th>
                    <th rowspan="2" style="width: 35px;">CD</th>
                    <th colspan="5">GRADE'S</th>
                    <th rowspan="2" style="width: 40px;">A-D</th>
                    <th rowspan="2" style="width: 45px;">(A-D)%</th>
                    <th rowspan="2" style="width: 40px;">AVG</th>
                    <th rowspan="2" style="width: 55px;">GPA</th>
                    <th rowspan="2" style="width: 35px;">PSN</th>
                </tr>
                <tr>
                    <th style="width: 25px;">A</th>
                    <th style="width: 25px;">B</th>
                    <th style="width: 25px;">C</th>
                    <th style="width: 25px;">D</th>
                    <th style="width: 25px;">F</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subject_stats as $subId => $st): ?>
                    <!-- Female Row -->
                    <tr class="f-row">
                        <td rowspan="3" class="sub-title-td" style="color:#000;"><?php echo htmlspecialchars($st['name']); ?></td>
                        <td>F</td>
                        <td><?php echo $st['reg']['F']; ?></td>
                        <td><?php echo $st['abs']['F'] > 0 ? '-'.$st['abs']['F'] : '0'; ?></td>
                        <td><?php echo $st['sat']['F']; ?></td>
                        <td><?php echo $st['sat']['F']; ?></td>
                        <td><?php echo $st['grades']['F']['A']; ?></td>
                        <td><?php echo $st['grades']['F']['B']; ?></td>
                        <td><?php echo $st['grades']['F']['C']; ?></td>
                        <td><?php echo $st['grades']['F']['D']; ?></td>
                        <td><?php echo $st['grades']['F']['F']; ?></td>
                        <td><?php echo $st['ad_pass']['F']; ?></td>
                        <td><?php echo $st['ad_pct']['F']; ?>%</td>
                        <td rowspan="3" style="vertical-align: middle; font-weight: bold; color: #000;"><?php echo $st['avg']; ?></td>
                        <td rowspan="3" style="vertical-align: middle; font-weight: bold; color: #000;"><?php echo $st['gpa'] > 0 ? sprintf("%.4f", $st['gpa']) : '-'; ?></td>
                        <td rowspan="3" style="vertical-align: middle; font-weight: bold; color: #166534; background: #f0fdf4;"><?php echo $st['psn']; ?></td>
                    </tr>
                    <!-- Male Row -->
                    <tr class="m-row">
                        <td>M</td>
                        <td><?php echo $st['reg']['M']; ?></td>
                        <td><?php echo $st['abs']['M'] > 0 ? '-'.$st['abs']['M'] : '0'; ?></td>
                        <td><?php echo $st['sat']['M']; ?></td>
                        <td><?php echo $st['sat']['M']; ?></td>
                        <td><?php echo $st['grades']['M']['A']; ?></td>
                        <td><?php echo $st['grades']['M']['B']; ?></td>
                        <td><?php echo $st['grades']['M']['C']; ?></td>
                        <td><?php echo $st['grades']['M']['D']; ?></td>
                        <td><?php echo $st['grades']['M']['F']; ?></td>
                        <td><?php echo $st['ad_pass']['M']; ?></td>
                        <td><?php echo $st['ad_pct']['M']; ?>%</td>
                    </tr>
                    <!-- Total Row -->
                    <tr class="ttl-row" style="color:#000;">
                        <td>T</td>
                        <td><?php echo $st['reg']['T']; ?></td>
                        <td><?php echo $st['abs']['T'] > 0 ? '-'.$st['abs']['T'] : '0'; ?></td>
                        <td><?php echo $st['sat']['T']; ?></td>
                        <td><?php echo $st['sat']['T']; ?></td>
                        <td><?php echo $st['grades']['T']['A']; ?></td>
                        <td><?php echo $st['grades']['T']['B']; ?></td>
                        <td><?php echo $st['grades']['T']['C']; ?></td>
                        <td><?php echo $st['grades']['T']['D']; ?></td>
                        <td><?php echo $st['grades']['T']['F']; ?></td>
                        <td><?php echo $st['ad_pass']['T']; ?></td>
                        <td><?php echo $st['ad_pct']['T']; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>