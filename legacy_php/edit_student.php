<?php
session_start();
require 'db.php';

// 1. PROTECTION: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";
$student = null;

// 2. FETCH CURRENT STUDENT DATA
if (isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            die("<div style='padding:20px; color:red; font-weight:bold;'>❌ Student not found!</div>");
        }
    } catch (PDOException $e) {
        die("Error fetching data: " . $e->getMessage());
    }
} else {
    header("Location: view_students.php");
    exit;
}

// 3. FETCH SCHOOLS FOR DROPDOWN
try {
    $all_schools = $pdo->query("SELECT school_name FROM schools ORDER BY school_name ASC")->fetchAll();
} catch (PDOException $e) {
    $all_schools = [];
}

// 4. FETCH PARENTS FOR DROPDOWN
try {
    $all_parents = $pdo->query("SELECT user_id, username FROM users WHERE role = 'Parent' ORDER BY username ASC")->fetchAll();
} catch (PDOException $e) {
    $all_parents = [];
}

// 5. HANDLE FORM UPDATE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_number   = trim($_POST['reg_number']);
    $student_name = trim($_POST['student_name']);
    $sex          = trim($_POST['sex'] ?? ''); // 🌟 Kamata Jinsia Hapa
    $class_name   = trim($_POST['class_name']);
    $school_name  = trim($_POST['school_name']);
    $parent_id    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    try {
        // Check if Reg Number is taken by ANOTHER student
        $check_stmt = $pdo->prepare("SELECT student_id FROM students WHERE reg_number = ? AND student_id != ?");
        $check_stmt->execute([$reg_number, $student_id]);
        
        if ($check_stmt->fetch()) {
            $message = "<div class='alert error'>❌ Registration Number '$reg_number' is already assigned to another student!</div>";
        } else {
            // 🌟 SQL imesasishwa ku-update column ya 'sex' pia
            $sql = "UPDATE students SET reg_number = ?, student_name = ?, sex = ?, class_name = ?, school_name = ?, parent_id = ? WHERE student_id = ?";
            $update_stmt = $pdo->prepare($sql);
            $update_stmt->execute([$reg_number, $student_name, $sex, $class_name, $school_name, $parent_id, $student_id]);
            
            // Redirect tukiwa na ujumbe wa mafanikio kwenda kwenye orodha
            header("Location: view_students.php?success=Student updated successfully!");
            exit;
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Database Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Edit Student</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .form-container { max-width: 500px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #ea580c; margin-top: 0; text-transform: uppercase; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #334155; }
        input, select { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 15px; background-color: #fff; margin-bottom: 10px; }
        input:focus, select:focus { border-color: #ea580c; outline: none; }
        button { width: 100%; padding: 12px; background-color: #ea580c; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button:hover { background-color: #c2410c; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #64748b; font-weight: bold; }
        .back-link:hover { color: #334155; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Student Details</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="reg_number">Registration Number (Reg No):</label>
        <input type="text" name="reg_number" id="reg_number" value="<?php echo htmlspecialchars($student['reg_number']); ?>" required>

        <label for="student_name">Student Full Name:</label>
        <input type="text" name="student_name" id="student_name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>

        <label for="sex">Student Gender (Sex):</label>
        <select name="sex" id="sex" required>
            <option value="">-- Select Sex --</option>
            <option value="M" <?php echo ($student['sex'] === 'M') ? 'selected' : ''; ?>>M (Male)</option>
            <option value="F" <?php echo ($student['sex'] === 'F') ? 'selected' : ''; ?>>F (Female)</option>
        </select>

        <label for="class_name">Class / Form:</label>
        <select name="class_name" id="class_name" required>
            <?php 
            $classes = ["Form 1", "Form 2", "Form 3", "Form 4", "Form 5", "Form 6"];
            foreach ($classes as $cl) {
                $selected = ($student['class_name'] == $cl) ? "selected" : "";
                echo "<option value='$cl' $selected>$cl</option>";
            }
            ?>
        </select>

        <label for="school_name">Select Student's School:</label>
        <select name="school_name" id="school_name" required>
            <?php foreach ($all_schools as $school): ?>
                <?php $selected = ($student['school_name'] == $school['school_name']) ? "selected" : ""; ?>
                <option value="<?php echo htmlspecialchars($school['school_name']); ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($school['school_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="parent_id">Assign Parent Account:</label>
        <select name="parent_id" id="parent_id" required>
            <option value="">-- Select Linked Parent --</option>
            <?php foreach ($all_parents as $parent): ?>
                <?php $selected = ($student['parent_id'] == $parent['user_id']) ? "selected" : ""; ?>
                <option value="<?php echo $parent['user_id']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($parent['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Update Student Info</button>
    </form>
    
    <a href="view_students.php" class="back-link">⬅️ Back to Student List</a>
</div>

</body>
</html>