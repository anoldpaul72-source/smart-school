<?php
session_start();
require 'db.php';

// 1. PROTECTION: Ensure the logged-in user is a Teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit;
}

$school_name = isset($_SESSION['school_name']) ? trim($_SESSION['school_name']) : '';
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['username'];

$message = '';
$message_type = '';

// 2. HANDLE EDIT / SAVE / DELETE ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $day = $_POST['day_of_week'] ?? '';
    $period = intval($_POST['period_number'] ?? 0);
    $class_name = trim($_POST['class_name'] ?? '');
    $subject_id = intval($_POST['subject_id'] ?? 0);

    if (!empty($day) && $period > 0) {
        try {
            if ($action === 'save') {
                if (!empty($class_name) && $subject_id > 0) {
                    // Check if entry exists for this day & period for this teacher
                    $chk_sql = "SELECT timetable_id FROM timetables 
                                WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND TRIM(school_name) = TRIM(?)";
                    $chk_stmt = $pdo->prepare($chk_sql);
                    $chk_stmt->execute([$teacher_id, $day, $period, $school_name]);
                    $existing = $chk_stmt->fetch();

                    if ($existing) {
                        // Update existing slot
                        $update_sql = "UPDATE timetables 
                                       SET class_name = ?, subject_id = ? 
                                       WHERE timetable_id = ?";
                        $up_stmt = $pdo->prepare($update_sql);
                        $up_stmt->execute([$class_name, $subject_id, $existing['timetable_id']]);
                    } else {
                        // Insert new slot
                        $insert_sql = "INSERT INTO timetables (teacher_id, day_of_week, period_number, class_name, subject_id, school_name) 
                                       VALUES (?, ?, ?, ?, ?, ?)";
                        $in_stmt = $pdo->prepare($insert_sql);
                        $in_stmt->execute([$teacher_id, $day, $period, $class_name, $subject_id, $school_name]);
                    }
                    $message = "Timetable slot updated successfully!";
                    $message_type = "success";
                }
            } elseif ($action === 'delete') {
                // Delete existing slot
                $del_sql = "DELETE FROM timetables 
                            WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND TRIM(school_name) = TRIM(?)";
                $del_stmt = $pdo->prepare($del_sql);
                $del_stmt->execute([$teacher_id, $day, $period, $school_name]);
                $message = "Slot cleared successfully!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

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

// 3. FETCH SUBJECTS LIST FOR DROPDOWN
$subjects_list = [];
try {
    $sub_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $subjects_list = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Keep empty array on failure
}

// 4. FETCH ONLY THIS TEACHER'S TIMETABLE SLOTS FROM THE DATABASE
$teacher_matrix = [];
try {
    $sql = "SELECT t.timetable_id, t.day_of_week, t.period_number, t.class_name, t.subject_id, s.subject_name 
            FROM timetables t
            JOIN subjects s ON t.subject_id = s.subject_id
            WHERE t.teacher_id = ? AND TRIM(t.school_name) = TRIM(?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id, $school_name]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build the matrix using Day and Period Number as keys
    foreach ($rows as $row) {
        $teacher_matrix[$row['day_of_week']][$row['period_number']] = [
            'class'      => $row['class_name'],
            'subject_id' => $row['subject_id'],
            'subject'    => $row['subject_name']
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
    <title>My Teaching Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0284c7; margin-top: 0; text-transform: uppercase; font-size: 20px; }
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px; }
        .nav-links a { font-weight: bold; text-decoration: none; color: #0284c7; font-size: 14px; margin-left: 15px; }
        
        .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; text-align: center; min-width: 950px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 6px; vertical-align: middle; }
        th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        
        .day-column { background-color: #f8fafc; font-weight: bold; color: #0f172a; text-transform: uppercase; width: 100px; }
        .break-cell { background-color: #f1f5f9; color: #64748b; font-weight: bold; font-style: italic; letter-spacing: 2px; }
        
        .slot-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 6px; border-radius: 4px; position: relative; }
        .slot-class { font-weight: bold; color: #1e40af; font-size: 13px; }
        .slot-subject { color: #475569; font-size: 11px; margin-top: 3px; display: block; }
        
        .btn-edit { background-color: #3b82f6; color: white; border: none; padding: 3px 8px; font-size: 11px; border-radius: 3px; cursor: pointer; margin-top: 5px; }
        .btn-edit:hover { background-color: #2563eb; }
        .btn-add { background-color: #e2e8f0; color: #475569; border: 1px dashed #cbd5e1; padding: 6px 10px; font-size: 12px; border-radius: 4px; cursor: pointer; display: inline-block; width: 80%; }
        .btn-add:hover { background-color: #cbd5e1; color: #0f172a; }

        .print-btn { background-color: #0d9488; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; float: right; margin-top: 20px; }
        .print-btn:hover { background-color: #0f766e; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-header { font-weight: bold; font-size: 16px; color: #0284c7; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .btn-save { background: #16a34a; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-save:hover { background: #15803d; }
        .btn-delete { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-delete:hover { background: #b91c1c; }
        .btn-cancel { background: #64748b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .btn-cancel:hover { background: #475569; }

        @media print {
            .nav-links, .print-btn, .btn-edit, .btn-add { display: none !important; }
            body { background: white; margin: 0; }
            .container { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div>
            <h2>👨‍🏫 My Weekly Teaching Schedule</h2>
            <small style="color: #64748b; font-weight: bold;">Teacher: <?php echo htmlspecialchars($teacher_name); ?> | Campus: <?php echo htmlspecialchars($school_name); ?></small>
        </div>
        <div class="nav-links">
            <a href="view_timetable.php">📊 View General Timetable</a>
            <a href="dashboard_teachers.php">Dashboard</a>
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
                    <th style="width: 30px;">TEA<br>BREAK</th>
                    <th>PERIOD 5<br><small><?php echo $period_slots[5]; ?></small></th>
                    <th>PERIOD 6<br><small><?php echo $period_slots[6]; ?></small></th>
                    <th>PERIOD 7<br><small><?php echo $period_slots[7]; ?></small></th>
                    <th style="width: 30px;">LUNCH<br>BREAK</th>
                    <th>PERIOD 8<br><small><?php echo $period_slots[8]; ?></small></th>
                    <th>PERIOD 9<br><small><?php echo $period_slots[9]; ?></small></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                    <tr>
                        <td class="day-column"><?php echo $day; ?></td>
                        
                        <!-- Helper PHP block to render cell -->
                        <?php
                        $render_cell = function($p) use ($teacher_matrix, $day) {
                            echo "<td>";
                            if (isset($teacher_matrix[$day][$p])) {
                                $class = htmlspecialchars($teacher_matrix[$day][$p]['class']);
                                $subject = htmlspecialchars($teacher_matrix[$day][$p]['subject']);
                                $subject_id = $teacher_matrix[$day][$p]['subject_id'];
                                echo "<div class='slot-box'>
                                        <div class='slot-class'>{$class}</div>
                                        <span class='slot-subject'>📚 {$subject}</span>
                                        <button class='btn-edit' onclick=\"openEditModal('{$day}', {$p}, '{$class}', {$subject_id})\">✏️ Edit</button>
                                      </div>";
                            } else {
                                echo "<button class='btn-add' onclick=\"openEditModal('{$day}', {$p}, '', '')\">+ Add</button>";
                            }
                            echo "</td>";
                        };
                        ?>

                        <!-- Periods 1 to 4 -->
                        <?php for ($p = 1; $p <= 4; $p++) $render_cell($p); ?>

                        <td class="break-cell">B<br>R<br>E<br>A<br>K</td>

                        <!-- Periods 5 to 7 -->
                        <?php for ($p = 5; $p <= 7; $p++) $render_cell($p); ?>

                        <td class="break-cell">L<br>U<br>N<br>C<br>H</td>

                        <!-- Periods 8 and 9 -->
                        <?php for ($p = 8; $p <= 9; $p++) $render_cell($p); ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print My Schedule</button>
    <div style="clear: both;"></div>
</div>

<!-- EDIT / ADD MODAL -->
<div id="timetableModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="modalTitle">Edit Timetable Slot</div>
        <form method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="save">
            <input type="hidden" name="day_of_week" id="modalDay">
            <input type="hidden" name="period_number" id="modalPeriod">

            <div class="form-group">
                <label>Class Name (e.g. Form 1A):</label>
                <input type="text" name="class_name" id="modalClass" required placeholder="Enter Class Name">
            </div>

            <div class="form-group">
                <label>Subject:</label>
                <select name="subject_id" id="modalSubject" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects_list as $sub): ?>
                        <option value="<?php echo $sub['subject_id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-delete" id="btnDelete" onclick="deleteSlot()" style="display:none;">Clear Slot</button>
                <button type="submit" class="btn-save">Save Slot</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(day, period, className, subjectId) {
    document.getElementById('modalDay').value = day;
    document.getElementById('modalPeriod').value = period;
    document.getElementById('modalClass').value = className;
    document.getElementById('modalSubject').value = subjectId;
    document.getElementById('formAction').value = 'save';

    const modalTitle = document.getElementById('modalTitle');
    const deleteBtn = document.getElementById('btnDelete');

    if (className !== '') {
        modalTitle.innerText = `Edit Slot (${day} - Period ${period})`;
        deleteBtn.style.display = 'inline-block';
    } else {
        modalTitle.innerText = `Add Slot (${day} - Period ${period})`;
        deleteBtn.style.display = 'none';
    }

    document.getElementById('timetableModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('timetableModal').style.display = 'none';
}

function deleteSlot() {
    if (confirm("Are you sure you want to clear this period slot?")) {
        document.getElementById('formAction').value = 'delete';
        document.querySelector('#timetableModal form').submit();
    }
}

// Close modal if user clicks outside content
window.onclick = function(event) {
    const modal = document.getElementById('timetableModal');
    if (event.target === modal) {
        closeModal();
    }
};
</script>

</body>
</html>