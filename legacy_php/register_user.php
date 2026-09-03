<?php
session_start();
require 'db.php';

// 1. PROTECTION: Ensure only an Admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. FETCH SCHOOL NAMES, SUBJECTS, AND CLASSES FOR THE FORM
try {
    $schools_stmt = $pdo->query("SELECT school_name FROM schools ORDER BY school_name ASC");
    $all_schools = $schools_stmt->fetchAll();

    // Leta masomo yote kwa ajili ya walimu
    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $all_subjects = $subjects_stmt->fetchAll();
} catch (PDOException $e) {
    $all_schools = [];
    $all_subjects = [];
}

// SAHIHISHO: Tumeongeza Form 5 na Form 6 hapa kwenye Array
$all_classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];

// 3. HANDLE REGISTRATION FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username']);
    $password    = $_POST['password'];
    $role        = $_POST['role'];
    $school_name = !empty($_POST['school_name']) ? trim($_POST['school_name']) : null;

    // Masomo na madarasa yaliyochaguliwa (Kama mtumiaji ni mwalimu)
    $selected_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    $selected_classes  = isset($_POST['classes']) ? $_POST['classes'] : [];

    try {
        // Check if username is already taken
        $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        
        if ($check_stmt->fetch()) {
            $message = "<div class='alert error'>❌ Username is already taken! Try another one.</div>";
        } else {
            // Anza Transaction ili kuhakikisha data zote zinasave kwa pamoja bila hitilafu
            $pdo->beginTransaction();

            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user along with the selected school
            $sql = "INSERT INTO users (username, password, role, school_name) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $role, $school_name]);
            
            // Pata ID ya huyu mtumiaji mpya aliyesajiliwa hivi punde
            $new_user_id = $pdo->lastInsertId();

            // Kama role ni Teacher, wapangie masomo na madarasa yao kwenye jedwali la mahusiano
            if ($role === 'Teacher' && !empty($selected_subjects) && !empty($selected_classes)) {
                $assign_stmt = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_name) VALUES (?, ?, ?)");
                
                foreach ($selected_subjects as $sub_id) {
                    foreach ($selected_classes as $class_name) {
                        $assign_stmt->execute([$new_user_id, $sub_id, $class_name]);
                    }
                }
            }

            // Kamilisha mchakato mzima kwenye database (Commit)
            $pdo->commit();
            
            $school_info = $school_name ? " for " . htmlspecialchars($school_name) : "";
            $message = "<div class='alert success'>✔️ New user with the role of **$role** registered successfully$school_info!</div>";
        }
    } catch (PDOException $e) {
        // Mambo yakifeli katikati, rudisha database nyuma kama ilivyokuwa (Rollback)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "<div class='alert error'>Database Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Register User</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #0056b3; margin-top: 0; text-transform: uppercase; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #334155; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 15px; background-color: #fff; }
        input:focus, select:focus { border-color: #0056b3; outline: none; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 18px; padding: 0; color: #64748b; }
        .toggle-password:focus { outline: none; }

        /* Workload Section */
        .teacher-section { 
            display: none; 
            background: #f8fafc; 
            border: 2px dashed #0056b3; 
            border-radius: 8px; 
            padding: 20px; 
            margin-top: 15px; 
        }
        .teacher-section-title { font-weight: bold; color: #0056b3; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; font-size: 14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        
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
        .checkbox-label:hover { border-color: #0056b3; background-color: #eff6ff; }
        .checkbox-label input[type="checkbox"] { 
            width: auto; 
            margin-right: 8px; 
            transform: scale(1.1); 
            cursor: pointer; 
        }

        button.submit-btn { width: 100%; padding: 12px; background-color: #0056b3; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button.submit-btn:hover { background-color: #004085; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #64748b; font-weight: bold; }
        .back-link:hover { color: #334155; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Register New User</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" placeholder="Example: accountant_kome" required>

        <label for="password">Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Enter initial password" required>
            <button type="button" id="togglePasswordBtn" class="toggle-password" title="Show/Hide Password">👁️</button>
        </div>

        <label for="role">User Role / Position:</label>
        <select name="role" id="role" required>
            <option value="">-- Select Role --</option>
            <option value="Headmaster">Headmaster</option>
            <option value="Academic Master">Academic Master</option>
            <option value="Teacher">Teacher</option>
            <option value="Accountant">Accountant</option>
            <option value="Parent">Parent</option>
            <option value="Admin">Admin</option>
        </select>

        <label for="school_name">Assign School:</label>
        <select name="school_name" id="school_name" required>
            <option value="">-- Select Target School --</option>
            <?php foreach ($all_schools as $school): ?>
                <option value="<?php echo htmlspecialchars($school['school_name']); ?>">
                    <?php echo htmlspecialchars($school['school_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="teacherDutiesSection" class="teacher-section">
            <div class="teacher-section-title">📚 Teacher Workload Allocation</div>
            
            <label style="margin-top:0;">Select Subjects:</label>
            <div class="checkbox-grid">
                <?php if (!empty($all_subjects)): ?>
                    <?php foreach ($all_subjects as $sub): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="subjects[]" value="<?php echo $sub['subject_id']; ?>" class="teacher-req">
                            <span><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="color:red; font-size:12px; width: 100%;">⚠️ No subjects found in database!</span>
                <?php endif; ?>
            </div>

            <label>Select Classes:</label>
            <div class="checkbox-grid">
                <?php foreach ($all_classes as $cls): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="classes[]" value="<?php echo $cls; ?>" class="teacher-req">
                        <span><?php echo $cls; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="submit-btn">Complete Registration</button>
    </form>
    
    <a href="admin.php" class="back-link">⬅️ Back to Admin Dashboard</a>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const roleSelect = document.getElementById('role');
    const teacherDutiesSection = document.getElementById('teacherDutiesSection');
    const teacherRequiredFields = document.querySelectorAll('.teacher-req');

    // Show/Hide Password
    togglePasswordBtn.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });

    // Kuonyesha/kuficha sehemu ya masomo kulingana na Role ya mtumiaji
    roleSelect.addEventListener('change', function() {
        if (this.value === 'Teacher') {
            teacherDutiesSection.style.display = 'block';
        } else {
            teacherDutiesSection.style.display = 'none';
            // Uncheck zote kama amebadilisha jukumu kutoka mwalimu kwenda kwingine kabla ya kusave
            teacherRequiredFields.forEach(cb => cb.checked = false);
        }
    });
</script>

</body>
</html>