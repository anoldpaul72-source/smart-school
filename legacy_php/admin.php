<?php
session_start();
require 'db.php';

// 1. ACCESS CONTROL: Ensure only Admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. LOGIC: ADD NEW SUBJECT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);

    if (!empty($subject_name)) {
        try {
            $check_stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ?");
            $check_stmt->execute([$subject_name]);
            
            if ($check_stmt->fetch()) {
                $message = "<div class='alert error'>❌ The subject <b>" . htmlspecialchars($subject_name) . "</b> already exists in the system!</div>";
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
                $insert_stmt->execute([$subject_name]);
                $message = "<div class='alert success'>✔️ Subject <b>" . htmlspecialchars($subject_name) . "</b> has been successfully added!</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ Please enter a subject name!</div>";
    }
}

// 3. LOGIC: DELETE SUBJECT
if (isset($_GET['delete_subject_id'])) {
    $delete_subject_id = $_GET['delete_subject_id'];

    try {
        $delete_stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $delete_stmt->execute([$delete_subject_id]);
        
        $message = "<div class='alert success'>✔️ Subject has been completely removed from the system!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>❌ Failed to delete: This subject already has student marks linked to it in the system!</div>";
    }
}

// 4. FETCH SCHOOLS, ROLES, AND CLASSES FOR FILTER DROPDOWNS
try {
    $schools_stmt = $pdo->query("SELECT school_name FROM schools ORDER BY school_name ASC");
    $all_schools = $schools_stmt->fetchAll();

    $roles_stmt = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role != '' ORDER BY role ASC");
    $all_roles = $roles_stmt->fetchAll();

    // Fetch distinct classes from students table
    $classes_stmt = $pdo->query("SELECT DISTINCT class_name FROM students WHERE class_name IS NOT NULL AND class_name != '' ORDER BY class_name ASC");
    $all_classes = $classes_stmt->fetchAll();
} catch (PDOException $e) {
    $all_schools = [];
    $all_roles = [];
    $all_classes = [];
}

// 5. FILTER LOGIC FOR REGISTERED USERS (USING teacher_assignments)
$selected_school = isset($_GET['filter_school']) ? trim($_GET['filter_school']) : '';
$selected_role   = isset($_GET['filter_role']) ? trim($_GET['filter_role']) : '';
$selected_class  = isset($_GET['filter_class']) ? trim($_GET['filter_class']) : '';

try {
    $params = [];
    $query = "SELECT DISTINCT u.user_id, u.username, u.role, u.school_name FROM users u ";

    if (!empty($selected_class)) {
        // Kuunganisha wazazi (kupitia students) na walimu (kupitia teacher_assignments)
        $query .= " LEFT JOIN students s ON u.user_id = s.parent_id 
                    LEFT JOIN teacher_assignments ta ON u.user_id = ta.teacher_id 
                    WHERE (s.class_name = ? OR ta.class_name = ?)";
        $params[] = $selected_class;
        $params[] = $selected_class;
    } else {
        $query .= " WHERE 1=1";
    }

    if (!empty($selected_school)) {
        $query .= " AND u.school_name = ?";
        $params[] = $selected_school;
    }

    if (!empty($selected_role)) {
        $query .= " AND u.role = ?";
        $params[] = $selected_role;
    }

    $query .= " ORDER BY u.school_name ASC, u.username ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_users = $stmt->fetchAll();

    // Fetch all subjects
    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $subjects_stmt->fetchAll();
} catch (PDOException $e) {
    $all_users = [];
    $all_subjects = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; color: #333; }
        .dashboard-container { max-width: 1000px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h2 { color: #0056b3; text-transform: uppercase; margin-top: 0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        h3 { color: #475569; margin-top: 30px; border-left: 4px solid #0056b3; padding-left: 10px; }
        
        /* Menu Buttons */
        .menu-links { margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px; border: 1px solid #e2e8f0; }
        .btn { display: inline-block; padding: 10px 15px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; color: white; border: none; cursor: pointer; }
        .btn-blue { background-color: #0056b3; }
        .btn-blue:hover { background-color: #004085; }
        .btn-green { background-color: #22c55e; }
        .btn-green:hover { background-color: #16a34a; }
        .btn-orange { background-color: #ea580c; }
        .btn-orange:hover { background-color: #c2410c; }
        .btn-teal { background-color: #0d9488; }
        .btn-teal:hover { background-color: #0f766e; }
        .btn-red { background-color: #ef4444; padding: 6px 12px; font-size: 13px; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-red:hover { background-color: #dc2626; }
        
        /* Small Actions Buttons */
        .btn-orange-sm { background-color: #ea580c; padding: 6px 12px; font-size: 13px; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 5px; }
        .btn-orange-sm:hover { background-color: #c2410c; }

        .logout-link { float: right; color: #ef4444; text-decoration: none; font-weight: bold; background: #fee2e2; padding: 8px 15px; border-radius: 6px; border: 1px solid #fecaca; }
        .logout-link:hover { background: #fecaca; }
        
        /* Filter Component Panel */
        .filter-panel { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 15px; display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-weight: bold; color: #475569; font-size: 13px; }
        .filter-select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #fff; min-width: 180px; }
        
        /* Subject Form Styling */
        .subject-form { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 20px; }
        .form-group { display: flex; gap: 10px; margin-top: 8px; }
        input[type="text"], select.bulk-select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        input[type="text"] { flex: 1; }
        
        /* Alert Styles */
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Tables Layout */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #0056b3; color: white; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f8fafc; }
        .no-data { text-align: center; color: #94a3b8; font-style: italic; padding: 20px; }

        /* Modal Popup Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 420px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; }
        .modal-header { font-weight: bold; font-size: 16px; color: #0056b3; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 20px; font-weight: bold; color: #64748b; cursor: pointer; }
        .modal-close:hover { color: #ef4444; }
        .modal-body { display: flex; flex-direction: column; gap: 15px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <a href="logout.php" class="logout-link">🚪 Logout</a>
    <h2>Admin Dashboard</h2>

    <?php echo $message; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div style="background: #eff6ff; border: 1px dashed #2563eb; padding: 20px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; border-left: 5px solid #2563eb;">
        <h4 style="margin-top: 0; color: #1d4ed8; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">💡 Bulk Student Registration (Excel/Offline)</h4>
        <p style="margin: 5px 0 15px 0; color: #475569;">Select a class/form below to download its matching Excel template, fill it offline, and upload it back to register all students instantly.</p>
        
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label for="bulk_class_name" style="font-weight: bold; display: block; margin-bottom: 6px; color: #334155;">Choose Target Class/Form:</label>
                <select id="bulk_class_name" class="bulk-select" style="width: 100%;">
                    <option value="">-- Select Class --</option>
                    <option value="Form 1">Form 1</option>
                    <option value="Form 2">Form 2</option>
                    <option value="Form 3">Form 3</option>
                    <option value="Form 4">Form 4</option>
                    <option value="Form 5">Form 5</option>
                    <option value="Form 6">Form 6</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; flex: 1.5; min-width: 280px;">
                <button type="button" onclick="downloadStudentTemplate()" style="background-color: #2563eb; color: white; padding: 10px 15px; font-size: 13px; font-weight: bold; border: none; border-radius: 6px; flex: 1; cursor: pointer; transition: background 0.2s;">
                    📥 Download Template
                </button>
                <a href="upload_students.php" style="text-align: center; text-decoration: none; background-color: #059669; color: white; padding: 10px 15px; font-size: 13px; font-weight: bold; border-radius: 6px; flex: 1; transition: background 0.2s;">
                    📤 Upload Completed Excel
                </a>
            </div>
        </div>
    </div>

    <div class="menu-links">
        <a href="register_student.php" class="btn btn-blue">👶 Register New Student</a>
        
        <!-- Action Modals -->
        <button type="button" class="btn btn-green" onclick="openNavModal('view_students.php', '👁️ View Registered Students')">👁️ View Registered Students</button>
        <button type="button" class="btn btn-blue" onclick="openNavModal('admin_view_marks.php', '📊 View Student Marks')">📊 View Student Marks</button>
        
        <a href="register_user.php" class="btn btn-blue">👤 Register New User</a>
        <a href="add_school.php" class="btn btn-green">🏫 Add New School</a>
        
        <button type="button" class="btn btn-teal" onclick="openNavModal('add_payment.php', '💰 Fee Collection Portal')">💰 Fee Collection Portal</button>
        <button type="button" class="btn btn-teal" onclick="openNavModal('view_fees.php', '📊 Financial Ledger Reports')">📊 Financial Ledger Reports</button>
        
        <a href="#subjects-section" class="btn btn-orange">📚 Manage School Subjects</a>
    </div>

    <h3>👤 Registered Users (<?php echo count($all_users); ?>)</h3>
    
    <form method="GET" action="" class="filter-panel">
        <div class="filter-group">
            <label for="filter_school">🏫 Filter By School:</label>
            <select name="filter_school" id="filter_school" class="filter-select">
                <option value="">-- All Schools --</option>
                <?php foreach ($all_schools as $sch): ?>
                    <option value="<?php echo htmlspecialchars($sch['school_name']); ?>" <?php echo $selected_school === $sch['school_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sch['school_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="filter_role">👤 Filter By Role:</label>
            <select name="filter_role" id="filter_role" class="filter-select" onchange="toggleClassFilter()">
                <option value="">-- All Roles --</option>
                <?php foreach ($all_roles as $r): ?>
                    <option value="<?php echo htmlspecialchars($r['role']); ?>" <?php echo $selected_role === $r['role'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['role']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Class Filter Option -->
        <div class="filter-group" id="class_filter_group" style="<?php echo (!empty($selected_role) || !empty($selected_class)) ? 'display: flex;' : 'display: none;'; ?>">
            <label for="filter_class">🏫 Filter By Class:</label>
            <select name="filter_class" id="filter_class" class="filter-select">
                <option value="">-- All Classes --</option>
                <?php if (!empty($all_classes)): ?>
                    <?php foreach ($all_classes as $cls): ?>
                        <option value="<?php echo htmlspecialchars($cls['class_name']); ?>" <?php echo $selected_class === $cls['class_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cls['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="Form 1" <?php echo $selected_class === 'Form 1' ? 'selected' : ''; ?>>Form 1</option>
                    <option value="Form 2" <?php echo $selected_class === 'Form 2' ? 'selected' : ''; ?>>Form 2</option>
                    <option value="Form 3" <?php echo $selected_class === 'Form 3' ? 'selected' : ''; ?>>Form 3</option>
                    <option value="Form 4" <?php echo $selected_class === 'Form 4' ? 'selected' : ''; ?>>Form 4</option>
                    <option value="Form 5" <?php echo $selected_class === 'Form 5' ? 'selected' : ''; ?>>Form 5</option>
                    <option value="Form 6" <?php echo $selected_class === 'Form 6' ? 'selected' : ''; ?>>Form 6</option>
                <?php endif; ?>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-green" style="padding: 9px 15px;">🔍 Filter Users</button>
            <?php if (!empty($selected_school) || !empty($selected_role) || !empty($selected_class)): ?>
                <a href="admin.php" class="btn btn-orange" style="padding: 9px 15px; text-decoration: none;">Reset 🔄</a>
            <?php endif; ?>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>School</th>
                <th style="text-align: center; width: 160px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($all_users) > 0): ?>
                <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($user['username']); ?></b></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo $user['school_name'] ? htmlspecialchars($user['school_name']) : '<i>All Schools</i>'; ?></td>
                        <td style="text-align: center; display: flex; justify-content: center; align-items: center; gap: 5px;">
                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn-orange-sm">✏️ Edit</a>
                            
                            <a href="futa_user.php?id=<?php echo $user['user_id']; ?>" 
                               class="btn-red" 
                               onclick="return confirm('Are you sure you want to delete this user?');">
                                🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">No registered users found matching the selected filters.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr style="border: 0; height: 1px; background: #e2e8f0; margin-top: 40px;">

    <div id="subjects-section"></div>
    <h3>📚 Manage School Subjects (<?php echo count($all_subjects); ?>)</h3>
    
    <form method="POST" action="" class="subject-form">
        <label for="subject_name" style="font-weight: bold; color: #475569; font-size: 14px;">Add New Subject:</label>
        <div class="form-group">
            <input type="text" name="subject_name" id="subject_name" placeholder="Example: Physics, Chemistry, History..." required>
            <button type="submit" name="add_subject" class="btn btn-blue">➕ Add Subject</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">S/N</th>
                <th>Subject Name</th>
                <th style="text-align: center; width: 100px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($all_subjects) > 0): ?>
                <?php $sn = 1; foreach ($all_subjects as $subject): ?>
                    <tr>
                        <td style="text-align: center; color: #64748b; font-weight: bold;"><?php echo $sn++; ?></td>
                        <td><b><?php echo htmlspecialchars($subject['subject_name']); ?></b></td>
                        <td style="text-align: center;">
                            <a href="?delete_subject_id=<?php echo $subject['subject_id']; ?>#subjects-section" 
                               class="btn-red" 
                               onclick="return confirm('Are you sure you want to permanently delete the subject: <?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>? Deleting this subject might remove all its linked marks from the database!');">
                                🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="no-data">No subjects registered yet. Add one above!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Navigation Modal -->
<div id="navModal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeNavModal()">&times;</span>
        <div class="modal-header" id="modalTitle">Select School & Class</div>
        <div class="modal-body">
            <div class="filter-group">
                <label for="modal_school">🏫 Select School:</label>
                <select id="modal_school" class="filter-select" style="width: 100%;">
                    <option value="">-- All Schools --</option>
                    <?php foreach ($all_schools as $sch): ?>
                        <option value="<?php echo htmlspecialchars($sch['school_name']); ?>">
                            <?php echo htmlspecialchars($sch['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="modal_class">🏫 Select Class/Form:</label>
                <select id="modal_class" class="filter-select" style="width: 100%;">
                    <option value="">-- All Classes --</option>
                    <?php if (!empty($all_classes)): ?>
                        <?php foreach ($all_classes as $cls): ?>
                            <option value="<?php echo htmlspecialchars($cls['class_name']); ?>">
                                <?php echo htmlspecialchars($cls['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="Form 1">Form 1</option>
                        <option value="Form 2">Form 2</option>
                        <option value="Form 3">Form 3</option>
                        <option value="Form 4">Form 4</option>
                        <option value="Form 5">Form 5</option>
                        <option value="Form 6">Form 6</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="button" class="btn btn-blue" style="width: 100%; margin-top: 10px;" onclick="submitNavModal()">Proceed ➡️</button>
        </div>
    </div>
</div>

<script>
    let targetPage = '';

    function openNavModal(page, title) {
        targetPage = page;
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modal_school').value = '';
        document.getElementById('modal_class').value = '';
        document.getElementById('navModal').style.display = 'flex';
    }

    function closeNavModal() {
        document.getElementById('navModal').style.display = 'none';
    }

    function submitNavModal() {
        const school = document.getElementById('modal_school').value;
        const className = document.getElementById('modal_class').value;
        
        let url = targetPage + '?';
        let params = [];

        if (school !== '') {
            params.push('school=' + encodeURIComponent(school));
        }
        if (className !== '') {
            params.push('class=' + encodeURIComponent(className));
        }

        url += params.join('&');
        window.location.href = url;
    }

    function toggleClassFilter() {
        const roleSelect = document.getElementById('filter_role');
        const classFilterGroup = document.getElementById('class_filter_group');

        if (roleSelect.value !== "") {
            classFilterGroup.style.display = 'flex';
        } else {
            classFilterGroup.style.display = 'none';
            document.getElementById('filter_class').value = "";
        }
    }

    function downloadStudentTemplate() {
        const selectedClass = document.getElementById('bulk_class_name').value;

        if (selectedClass === "") {
            alert("❌ Please select a Class/Form first from the dropdown before downloading the registration template!");
            return;
        }

        window.location.href = 'download_student_template.php?class_name=' + encodeURIComponent(selectedClass);
    }

    window.onclick = function(event) {
        const modal = document.getElementById('navModal');
        if (event.target === modal) {
            closeNavModal();
        }
    }
</script>

</body>
</html>