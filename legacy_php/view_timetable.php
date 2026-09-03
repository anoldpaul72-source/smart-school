<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$school_name = isset($_SESSION['school_name']) ? trim($_SESSION['school_name']) : '';
$selected_class = isset($_GET['class_name']) ? trim($_GET['class_name']) : 'Form 1';
$user_role = $_SESSION['role'] ?? '';

// Check if current user is Academic
$is_academic = in_array($user_role, ['Academic Master', 'Academic', 'Admin']);

$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

$period_slots = [
    1 => '08:00 AM - 08:40 AM',
    2 => '08:40 AM - 09:20 AM',
    3 => '09:20 AM - 10:00 AM',
    4 => '10:00 AM - 10:40 AM',
    5 => '11:10 AM - 11:50 AM',
    6 => '11:50 AM - 12:30 PM',
    7 => '12:30 PM - 01:10 PM',
    8 => '02:00 PM - 02:40 PM',
    9 => '02:40 PM - 03:20 PM'
];

$message = '';
$message_type = '';

// ==========================================
// 1. AUTOMATIC TIMETABLE GENERATOR ALGORITHM
// ==========================================
if (isset($_POST['generate_timetable']) && $is_academic) {
    try {
        $pdo->beginTransaction();

        // Clear existing timetable for this school
        $del_stmt = $pdo->prepare("DELETE FROM timetables WHERE TRIM(school_name) = TRIM(?)");
        $del_stmt->execute([$school_name]);

        // Check if teacher_id column exists in subjects table dynamically to avoid crashes
        $check_col = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'teacher_id'")->fetch();
        
        $assigned_subjects = [];
        if ($check_col) {
            $sub_sql = "SELECT s.subject_id, s.subject_name, s.teacher_id 
                        FROM subjects s 
                        WHERE s.teacher_id IS NOT NULL AND s.teacher_id > 0";
            $sub_stmt = $pdo->query($sub_sql);
            $assigned_subjects = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fallback: If teacher_id is not in subjects, fetch all subjects paired with active teachers
        if (empty($assigned_subjects)) {
            $fallback_sql = "SELECT s.subject_id, s.subject_name, u.user_id AS teacher_id 
                             FROM subjects s 
                             CROSS JOIN users u 
                             WHERE u.role = 'Teacher' AND TRIM(u.school_name) = TRIM(?)";
            $fb_stmt = $pdo->prepare($fallback_sql);
            $fb_stmt->execute([$school_name]);
            $assigned_subjects = $fb_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($assigned_subjects)) {
            $teacher_schedule = []; // Tracks [day][period][teacher_id] to avoid collisions
            $insert_stmt = $pdo->prepare("INSERT INTO timetables (school_name, class_name, day_of_week, period_number, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($classes as $class) {
                $subject_index = 0;
                $total_subjects = count($assigned_subjects);

                foreach ($days as $day) {
                    for ($p = 1; $p <= 9; $p++) {
                        // Loop to find a subject whose teacher is FREE at this day & period
                        $attempts = 0;
                        while ($attempts < $total_subjects) {
                            $current_sub = $assigned_subjects[$subject_index % $total_subjects];
                            $t_id = $current_sub['teacher_id'];
                            $s_id = $current_sub['subject_id'];

                            // Check if teacher is already teaching another class in this period
                            if (!isset($teacher_schedule[$day][$p][$t_id])) {
                                // Reserve teacher for this slot
                                $teacher_schedule[$day][$p][$t_id] = true;

                                // Insert into database
                                $insert_stmt->execute([$school_name, $class, $day, $p, $s_id, $t_id]);
                                $subject_index++;
                                break;
                            }

                            $subject_index++;
                            $attempts++;
                        }
                    }
                }
            }
            $pdo->commit();
            $message = "Timetable generated automatically without teacher clashes!";
            $message_type = "success";
        } else {
            $pdo->rollBack();
            $message = "No registered subjects or teachers found. Please add subjects and teachers first.";
            $message_type = "error";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Generation Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// ==========================================
// 2. MANUAL EDIT / DELETE BY ACADEMIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $is_academic) {
    $action = $_POST['action'];
    $day = $_POST['day_of_week'] ?? '';
    $period = intval($_POST['period_number'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $teacher_id = intval($_POST['teacher_id'] ?? 0);

    if (!empty($day) && $period > 0) {
        try {
            if ($action === 'save') {
                // Check if teacher is occupied elsewhere in the same slot
                $check_sql = "SELECT class_name FROM timetables 
                              WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? 
                              AND class_name != ? AND TRIM(school_name) = TRIM(?)";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$teacher_id, $day, $period, $selected_class, $school_name]);
                $clash = $check_stmt->fetch();

                if ($clash) {
                    $message = "Clash detected! Teacher is already teaching " . $clash['class_name'] . " during this period.";
                    $message_type = "error";
                } else {
                    // Update or Insert slot
                    $chk_exist = $pdo->prepare("SELECT timetable_id FROM timetables WHERE class_name = ? AND day_of_week = ? AND period_number = ? AND TRIM(school_name) = TRIM(?)");
                    $chk_exist->execute([$selected_class, $day, $period, $school_name]);
                    $exists = $chk_exist->fetch();

                    if ($exists) {
                        $up = $pdo->prepare("UPDATE timetables SET subject_id = ?, teacher_id = ? WHERE timetable_id = ?");
                        $up->execute([$subject_id, $teacher_id, $exists['timetable_id']]);
                    } else {
                        $in = $pdo->prepare("INSERT INTO timetables (school_name, class_name, day_of_week, period_number, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $in->execute([$school_name, $selected_class, $day, $period, $subject_id, $teacher_id]);
                    }
                    $message = "Slot updated successfully!";
                    $message_type = "success";
                }
            } elseif ($action === 'delete') {
                $del = $pdo->prepare("DELETE FROM timetables WHERE class_name = ? AND day_of_week = ? AND period_number = ? AND TRIM(school_name) = TRIM(?)");
                $del->execute([$selected_class, $day, $period, $school_name]);
                $message = "Slot cleared!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch subjects & teachers for dropdowns
$all_subjects = [];
$all_teachers = [];
if ($is_academic) {
    try {
        $all_subjects = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $all_teachers = $pdo->query("SELECT user_id, username FROM users WHERE role = 'Teacher' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// 3. FETCH TIMETABLE FOR DISPLAY
$timetable_matrix = [];
try {
    $sql = "SELECT t.day_of_week, t.period_number, s.subject_name, s.subject_id, u.username AS teacher_name, u.user_id AS teacher_id 
            FROM timetables t
            JOIN subjects s ON t.subject_id = s.subject_id
            JOIN users u ON t.teacher_id = u.user_id
            WHERE TRIM(t.school_name) = TRIM(?) AND t.class_name = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$school_name, $selected_class]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $timetable_matrix[$row['day_of_week']][$row['period_number']] = [
            'subject'    => $row['subject_name'],
            'subject_id' => $row['subject_id'],
            'teacher'    => $row['teacher_name'],
            'teacher_id' => $row['teacher_id']
        ];
    }
} catch (PDOException $e) {
    echo "System Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Timetable - <?php echo htmlspecialchars($selected_class); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0284c7; margin-top: 0; text-transform: uppercase; font-size: 20px; }
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        
        .filter-form { display: flex; align-items: center; gap: 10px; }
        .filter-form select { padding: 9px 15px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; background: white; cursor: pointer; }

        .nav-links a, .nav-links button { font-weight: bold; text-decoration: none; color: #0284c7; font-size: 14px; margin-left: 10px; }
        .btn-auto { background: #0284c7; color: white !important; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-auto:hover { background: #0369a1; }

        .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; text-align: center; min-width: 950px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 6px; vertical-align: middle; }
        th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        
        .day-column { background-color: #f8fafc; font-weight: bold; color: #0f172a; text-transform: uppercase; width: 100px; }
        .break-cell { background-color: #f1f5f9; color: #64748b; font-weight: bold; font-style: italic; letter-spacing: 2px; }
        
        .slot-box { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px; border-radius: 4px; }
        .slot-subject { font-weight: bold; color: #166534; font-size: 13px; }
        .slot-teacher { color: #64748b; font-size: 11px; margin-top: 3px; display: block; }
        .empty-slot { color: #cbd5e1; font-style: italic; }

        .btn-edit { background-color: #3b82f6; color: white; border: none; padding: 2px 6px; font-size: 10px; border-radius: 3px; cursor: pointer; margin-top: 4px; }
        .btn-add { background-color: #e2e8f0; color: #475569; border: 1px dashed #cbd5e1; padding: 4px 8px; font-size: 11px; border-radius: 3px; cursor: pointer; }

        .print-btn { background-color: #0d9488; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-top: 20px; float: right; }
        .print-btn:hover { background-color: #0f766e; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-header { font-weight: bold; font-size: 16px; color: #0284c7; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        .form-group select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .modal-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .btn-save { background: #16a34a; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-delete { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background: #64748b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }

        @media print {
            body { background: white; margin: 0; padding: 0; }
            .container { box-shadow: none; max-width: 100%; padding: 0; }
            .filter-form, .nav-links, .print-btn, .btn-edit, .btn-add { display: none !important; }
            th { background-color: #eaeaea !important; -webkit-print-color-adjust: exact; }
            .break-cell { background-color: #f5f5f5 !important; -webkit-print-color-adjust: exact; }
            .slot-box { background: none !important; border: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div>
            <h2>🗓️ Class Schedule: <?php echo htmlspecialchars($selected_class); ?></h2>
            <small style="color: #64748b; font-weight: bold;">Campus: <?php echo htmlspecialchars($school_name); ?></small>
        </div>
        
        <div class="filter-form">
            <form method="GET" action="">
                <select name="class_name" onchange="this.form.submit()">
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?php echo $cls; ?>" <?php echo ($selected_class === $cls) ? 'selected' : ''; ?>>
                            <?php echo $cls; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div class="nav-links">
                <?php if ($is_academic): ?>
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Generate automatic timetable for all classes? This will update the schedule.');">
                        <button type="submit" name="generate_timetable" class="btn-auto">⚡ Auto-Generate Timetable</button>
                    </form>
                <?php endif; ?>
                <a href="dashboard_leaders.php">Dashboard</a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>DAY</th>
                    <th>PERIOD 1<br><small><?php echo $period_slots[1]; ?></small></th>
                    <th>PERIOD 2<br><small><?php echo $period_slots[2]; ?></small></th>
                    <th>PERIOD 3<br><small><?php echo $period_slots[3]; ?></small></th>
                    <th>PERIOD 4<br><small><?php echo $period_slots[4]; ?></small></th>
                    <th style="width: 30px;">TEA<br>BREAK<br><small>10:40 - 11:10</small></th>
                    <th>PERIOD 5<br><small><?php echo $period_slots[5]; ?></small></th>
                    <th>PERIOD 6<br><small><?php echo $period_slots[6]; ?></small></th>
                    <th>PERIOD 7<br><small><?php echo $period_slots[7]; ?></small></th>
                    <th style="width: 30px;">LUNCH<br>BREAK<br><small>01:10 - 02:00</small></th>
                    <th>PERIOD 8<br><small><?php echo $period_slots[8]; ?></small></th>
                    <th>PERIOD 9<br><small><?php echo $period_slots[9]; ?></small></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                    <tr>
                        <td class="day-column"><?php echo $day; ?></td>
                        
                        <?php
                        $render_cell = function($p) use ($timetable_matrix, $day, $is_academic) {
                            echo "<td>";
                            if (isset($timetable_matrix[$day][$p])) {
                                $sub = htmlspecialchars($timetable_matrix[$day][$p]['subject']);
                                $teacher = htmlspecialchars($timetable_matrix[$day][$p]['teacher']);
                                $sub_id = $timetable_matrix[$day][$p]['subject_id'];
                                $t_id = $timetable_matrix[$day][$p]['teacher_id'];

                                echo "<div class='slot-box'>
                                        <div class='slot-subject'>{$sub}</div>
                                        <span class='slot-teacher'>👤 {$teacher}</span>";
                                if ($is_academic) {
                                    echo "<button class='btn-edit' onclick=\"openAcademicModal('{$day}', {$p}, {$sub_id}, {$t_id})\">✏️ Edit</button>";
                                }
                                echo "</div>";
                            } else {
                                if ($is_academic) {
                                    echo "<button class='btn-add' onclick=\"openAcademicModal('{$day}', {$p}, '', '')\">+ Add</button>";
                                } else {
                                    echo "<span class='empty-slot'>-</span>";
                                }
                            }
                            echo "</td>";
                        };
                        ?>

                        <?php for ($p = 1; $p <= 4; $p++) $render_cell($p); ?>
                        <td class="break-cell">B<br>R<br>E<br>A<br>K</td>
                        <?php for ($p = 5; $p <= 7; $p++) $render_cell($p); ?>
                        <td class="break-cell">L<br>U<br>N<br>C<br>H</td>
                        <?php for ($p = 8; $p <= 9; $p++) $render_cell($p); ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print Timetable</button>
    <div style="clear: both;"></div>
</div>

<?php if ($is_academic): ?>
<!-- EDIT / ADD MODAL FOR ACADEMIC -->
<div id="academicModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="modalTitle">Edit Timetable Slot</div>
        <form method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="save">
            <input type="hidden" name="day_of_week" id="modalDay">
            <input type="hidden" name="period_number" id="modalPeriod">

            <div class="form-group">
                <label>Select Subject:</label>
                <select name="subject_id" id="modalSubject" required>
                    <option value="">-- Choose Subject --</option>
                    <?php foreach ($all_subjects as $sub): ?>
                        <option value="<?php echo $sub['subject_id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Assign Teacher:</label>
                <select name="teacher_id" id="modalTeacher" required>
                    <option value="">-- Choose Teacher --</option>
                    <?php foreach ($all_teachers as $t): ?>
                        <option value="<?php echo $t['user_id']; ?>"><?php echo htmlspecialchars($t['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-delete" id="btnDelete" onclick="deleteSlot()" style="display:none;">Clear</button>
                <button type="submit" class="btn-save">Save Slot</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAcademicModal(day, period, subjectId, teacherId) {
    document.getElementById('modalDay').value = day;
    document.getElementById('modalPeriod').value = period;
    document.getElementById('modalSubject').value = subjectId;
    document.getElementById('modalTeacher').value = teacherId;
    document.getElementById('formAction').value = 'save';

    const deleteBtn = document.getElementById('btnDelete');
    if (subjectId !== '') {
        document.getElementById('modalTitle').innerText = `Edit Slot (${day} - Period ${period})`;
        deleteBtn.style.display = 'inline-block';
    } else {
        document.getElementById('modalTitle').innerText = `Add Slot (${day} - Period ${period})`;
        deleteBtn.style.display = 'none';
    }

    document.getElementById('academicModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('academicModal').style.display = 'none';
}

function deleteSlot() {
    if (confirm("Are you sure you want to clear this period?")) {
        document.getElementById('formAction').value = 'delete';
        document.querySelector('#academicModal form').submit();
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('academicModal');
    if (event.target === modal) closeModal();
};
</script>
<?php endif; ?>

</body>
</html>