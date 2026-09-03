<?php
session_start();
require 'db.php';

// 1. PROTECTION: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. FETCH ALL SCHOOLS FOR THE DROPDOWN
try {
    $schools_stmt = $pdo->query("SELECT school_name FROM Schools ORDER BY school_name ASC");
    $all_schools = $schools_stmt->fetchAll();
} catch (PDOException $e) {
    $all_schools = [];
}

// 3. FETCH ALL PARENTS FOR THE DROPDOWN
try {
    $parents_stmt = $pdo->query("SELECT user_id, username FROM Users WHERE role = 'Parent' ORDER BY username ASC");
    $all_parents = $parents_stmt->fetchAll();
} catch (PDOException $e) {
    $all_parents = [];
}

// 4. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $reg_number   = trim($_POST['reg_number'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');
    $sex          = trim($_POST['sex'] ?? ''); // 🌟 Kamata Jinsia Hapa
    $school_name  = trim($_POST['school_name'] ?? '');
    $parent_id    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // Kamata darasa kwa namna yoyote ile iliyotumwa
    $class_name = "";
    if (!empty($_POST['class_name'])) {
        $class_name = trim($_POST['class_name']);
    } elseif (!empty($_POST['class'])) {
        $class_name = trim($_POST['class']);
    }

    // Uhakiki wa makosa
    if (empty($class_name)) {
        $message = "<div class='alert error'>❌ Error: Form did not submit any Class value! Please check your select input name.</div>";
    } elseif (empty($sex)) {
        $message = "<div class='alert error'>❌ Error: Please select student's gender (Sex)!</div>";
    } else {
        try {
            // Check if Registration Number already exists
            $check_stmt = $pdo->prepare("SELECT student_id FROM students WHERE reg_number = ?");
            $check_stmt->execute([$reg_number]);

            if ($check_stmt->fetch()) {
                $message = "<div class='alert error'>❌ Registration Number '$reg_number' is already registered!</div>";
            } else {
                // 🌟 Tumeongeza 'sex' kwenye INSERT SQL na kuipitisha kwenye execute array
                $sql = "INSERT INTO students (reg_number, student_name, sex, class_name, school_name, parent_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reg_number, $student_name, $sex, $class_name, $school_name, $parent_id]);

                $message = "<div class='alert success'>✔️ Student <b>$student_name</b> ($sex) registered successfully with class <b>$class_name</b>!</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Database Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Register Student</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .form-container { max-width: 500px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #0056b3; margin-top: 0; text-transform: uppercase; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #334155; }
        input, select { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 15px; background-color: #fff; margin-bottom: 10px; }
        input:focus, select:focus { border-color: #0056b3; outline: none; }
        button { width: 100%; padding: 12px; background-color: #0056b3; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        button:hover { background-color: #004085; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; text-align: left; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #64748b; font-weight: bold; }
        .back-link:hover { color: #334155; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Register New Student</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="reg_number">Registration Number (Reg No):</label>
        <input type="text" name="reg_number" id="reg_number" placeholder="Example: S.0101/0001/2026" required>

        <label for="student_name">Student Full Name:</label>
        <input type="text" name="student_name" id="student_name" placeholder="Example: John Joseph" required>

        <label for="sex">Student Gender (Sex):</label>
        <select name="sex" id="sex" required>
            <option value="">-- Select Sex --</option>
            <option value="M">M (Male)</option>
            <option value="F">F (Female)</option>
        </select>

        <label for="class_name">Class / Form:</label>
        <select name="class_name" id="class_name" required>
            <option value="">-- Select Class --</option>
            <option value="Form 1">Form 1</option>
            <option value="Form 2">Form 2</option>
            <option value="Form 3">Form 3</option>
            <option value="Form 4">Form 4</option>
            <option value="Form 5">Form 5</option>
            <option value="Form 6">Form 6</option>
        </select>

        <label for="school_name">Select Student's School:</label>
        <select name="school_name" id="school_name" required>
            <option value="">-- Select Student's School --</option>
            <?php foreach ($all_schools as $school): ?>
                <option value="<?php echo htmlspecialchars($school['school_name']); ?>">
                    <?php echo htmlspecialchars($school['school_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="parent_id">Assign Parent Account:</label>
        <select name="parent_id" id="parent_id" required>
            <option value="">-- Select Linked Parent --</option>
            <?php foreach ($all_parents as $parent): ?>
                <option value="<?php echo $parent['user_id']; ?>">
                    <?php echo htmlspecialchars($parent['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Register Student</button>
    </form>
    
    <a href="admin.php" class="back-link">⬅️ Back to Admin Dashboard</a>
</div>

</body>
</html>