<?php
session_start();
require 'db.php';

// 1. ACCESS CONTROL: Admin pekee
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header("Location: admin.php"); // Imerekebishwa iende kwenye admin.php kulingana na link yako ya chini
    exit;
}

// 2. FETCH SCHOOLS, SUBJECTS, AND USER DATA
try {
    $schools_stmt = $pdo->query("SELECT school_name FROM schools ORDER BY school_name ASC");
    $all_schools = $schools_stmt->fetchAll();

    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $subjects_stmt->fetchAll();

    // Leta data za huyu mtumiaji anayehaririwa
    $user_stmt = $pdo->prepare("SELECT user_id, username, role, school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();

    if (!$user_data) {
        die("User not found!");
    }

    // Kama ni mwalimu, leta masomo na madarasa yake ya sasa kutoka kwenye 'teacher_assignments'
    $assigned_subjects = [];
    $assigned_classes = [];
    if ($user_data['role'] === 'Teacher') {
        $assign_stmt = $pdo->prepare("SELECT subject_id, class_name FROM teacher_assignments WHERE teacher_id = ?");
        $assign_stmt->execute([$user_id]);
        while ($row = $assign_stmt->fetch()) {
            $assigned_subjects[] = $row['subject_id'];
            $assigned_classes[] = $row['class_name'];
        }
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// SAHIHISHO: Tumeongeza Form 5 na Form 6 hapa kwenye Array
$all_classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];

// 3. HANDLE UPDATE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username']);
    $password    = $_POST['password']; // Hiari: Kama akitaka kubadilisha password
    $role        = $_POST['role'];
    $school_name = !empty($_POST['school_name']) ? trim($_POST['school_name']) : null;

    $selected_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    $selected_classes  = isset($_POST['classes']) ? $_POST['classes'] : [];

    if (!empty($username) && !empty($role)) {
        try {
            $pdo->beginTransaction();

            // Kwanza fanya update ya taarifa za kawaida za mtumiaji
            if (!empty($password)) {
                // Kama amejaza password mpya, ifunge kwa usalama (hash)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $update_sql = "UPDATE users SET username = ?, password = ?, role = ?, school_name = ? WHERE user_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$username, $hashed_password, $role, $school_name, $user_id]);
            } else {
                // Kama hajaandika password mpya, iache ya zamani
                $update_sql = "UPDATE users SET username = ?, role = ?, school_name = ? WHERE user_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$username, $role, $school_name, $user_id]);
            }

            // Kufuta majukumu ya zamani ya mwalimu na kuweka mapya
            $delete_assigns = $pdo->prepare("DELETE FROM teacher_assignments WHERE teacher_id = ?");
            $delete_assigns->execute([$user_id]);

            if ($role === 'Teacher' && !empty($selected_subjects) && !empty($selected_classes)) {
                $insert_assign = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_name) VALUES (?, ?, ?)");
                foreach ($selected_subjects as $sub_id) {
                    foreach ($selected_classes as $class_name) {
                        $insert_assign->execute([$user_id, $sub_id, $class_name]);
                    }
                }
            }

            $pdo->commit();
            
            // Re-fetch updated data ili fomu ionekane na data mpya zilizosaviwa
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $user_id . "&success=User updated successfully!");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $message = "<div class='alert error'>Update Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ Fields cannot be empty!</div>";
    }
}

$success_msg = isset($_GET['success']) ? $_GET['success'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Edit User</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #ea580c; margin-top: 0; text-transform: uppercase; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #334155; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 15px; background-color: #fff; }
        input:focus, select:focus { border-color: #ea580c; outline: none; }
        
        /* MABORESHO YA CSS: Muundo mpya wa kisasa wa Workload Section */
        .teacher-section { 
            display: <?php echo $user_data['role'] === 'Teacher' ? 'block' : 'none'; ?>; 
            background: #f8fafc; 
            border: 2px dashed #ea580c; 
            border-radius: 8px; 
            padding: 20px; 
            margin-top: 20px; 
        }
        .teacher-section-title { font-weight: bold; color: #ea580c; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; font-size: 14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        
        /* Flexbox grid inayozuia maandishi kukatika au kubanana */
        .checkbox-grid { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            margin-bottom: 15px; 
        }
        .checkbox-label { 
            display: flex; 
            align-items: center; 
            background: white; 
            border: 1px solid #cbd5e1; 
            padding: 8px 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px; 
            min-width: 135px; 
            box-sizing: border-box;
            transition: all 0.2s; 
        }
        .checkbox-label:hover { border-color: #ea580c; background-color: #fff7ed; }
        .checkbox-label input[type="checkbox"] { 
            width: auto; 
            margin-right: 8px; 
            transform: scale(1.1); 
            cursor: pointer; 
        }
        
        button.submit-btn { width: 100%; padding: 12px; background-color: #ea580c; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button.submit-btn:hover { background-color: #c2410c; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #64748b; font-weight: bold; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>✏️ Edit User Profile</h2>
    
    <?php echo $message; ?>
    <?php if (!empty($success_msg)): ?>
        <div class="alert success">✔️ <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>

        <label for="password">Password (Leave blank to keep old password):</label>
        <input type="password" name="password" id="password" placeholder="Enter new password only if changing">

        <label for="role">User Role / Position:</label>
        <select name="role" id="role" required>
            <option value="Headmaster" <?php echo $user_data['role'] === 'Headmaster' ? 'selected' : ''; ?>>Headmaster</option>
            <option value="Academic Master" <?php echo $user_data['role'] === 'Academic Master' ? 'selected' : ''; ?>>Academic Master</option>
            <option value="Teacher" <?php echo $user_data['role'] === 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
            <option value="Parent" <?php echo $user_data['role'] === 'Parent' ? 'selected' : ''; ?>>Parent</option>
            <option value="Admin" <?php echo $user_data['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
        </select>

        <label for="school_name">Assign School:</label>
        <select name="school_name" id="school_name">
            <option value="">-- Select School --</option>
            <?php foreach ($all_schools as $school): ?>
                <option value="<?php echo htmlspecialchars($school['school_name']); ?>" <?php echo $user_data['school_name'] === $school['school_name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($school['school_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="teacherDutiesSection" class="teacher-section">
            <div class="teacher-section-title">📚 Update Teacher Workload</div>
            
            <label style="margin-top:0;">Select Subjects:</label>
            <div class="checkbox-grid">
                <?php foreach ($all_subjects as $sub): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="subjects[]" value="<?php echo $sub['subject_id']; ?>" 
                            <?php echo in_array($sub['subject_id'], $assigned_subjects) ? 'checked' : ''; ?> class="teacher-req">
                        <span><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Select Classes:</label>
            <div class="checkbox-grid">
                <?php foreach ($all_classes as $cls): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="classes[]" value="<?php echo $cls; ?>" 
                            <?php echo in_array($cls, $assigned_classes) ? 'checked' : ''; ?> class="teacher-req">
                        <span><?php echo $cls; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="submit-btn">Update User Info</button>
    </form>
    
    <a href="admin.php" class="back-link">⬅️ Back to Admin Dashboard</a>
</div>

<script>
    const roleSelect = document.getElementById('role');
    const teacherDutiesSection = document.getElementById('teacherDutiesSection');
    const teacherRequiredFields = document.querySelectorAll('.teacher-req');

    // Njia ya kuficha/kuonyesha sehemu ya mwalimu kulingana na chaguo la Role
    roleSelect.addEventListener('change', function() {
        if (this.value === 'Teacher') {
            teacherDutiesSection.style.display = 'block';
        } else {
            teacherDutiesSection.style.display = 'none';
            teacherRequiredFields.forEach(cb => cb.checked = false);
        }
    });
</script>

</body>
</html>