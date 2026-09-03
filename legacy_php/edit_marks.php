<?php
session_start();
require 'db.php';

// Hakikisha ni mwalimu tu anaingia hapa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit;
}

$message = "";

// 1. Angalia kama kuna ID ya alama iliyochaguliwa kwenye URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: matokeo.php");
    exit;
}

$mark_id = $_GET['id'];

// 2. Logic ya Ku-update data mwalimu akibonyeza "Update Marks"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score     = $_POST['score'];
    $term      = $_POST['term'];
    $exam_date = $_POST['exam_date'];
    
    // Ulinzi wa tarehe: Kama mwalimu ataacha tarehe tupu au muundo mbovu, weka tarehe ya leo
    if (empty($exam_date) || strtotime($exam_date) === false || $exam_date === '0000-00-00') {
        $exam_date = date('Y-m-d');
    }
    
    $year = date('Y', strtotime($exam_date));

    try {
        $update_sql = "UPDATE marks SET score = ?, term = ?, exam_date = ?, year = ? WHERE mark_id = ?";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$score, $term, $exam_date, $year, $mark_id]);
        
        // Ikikamilika, inamrudisha mwalimu kwenye list ikiwa na ujumbe wa mafanikio
        header("Location: matokeo.php?success=student marks updated successfully!");
        exit;
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error updating record: " . $e->getMessage() . "</div>";
    }
}

// 3. Vuta data zilizopo sasa hivi za mwanafunzi huyu ili zijae kwenye fomu kiotomatiki
try {
    // SAHIHISHO: Tumesafisha 'Marks m' kuwa 'marks m' kwa herufi ndogo ili isilete error mtandaoni
    $query = "
        SELECT m.*, s.student_name, sub.subject_name 
        FROM marks m
        INNER JOIN students s ON m.student_id = s.student_id
        INNER JOIN subjects sub ON m.subject_id = sub.subject_id
        WHERE m.mark_id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$mark_id]);
    $current_mark = $stmt->fetch();

    if (!$current_mark) {
        // Kama ID haipo kabisa kwenye mfumo
        header("Location: matokeo.php");
        exit;
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
    <title>Teacher Panel - Edit Marks</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { color: #0056b3; margin-top: 0; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        .info-box { background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 14px; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background-color: #ea580c; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        .btn-submit:hover { background-color: #c2410c; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-weight: bold; font-size: 14px; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ EDIT STUDENT MARKS</h2>
    
    <?php echo $message; ?>

    <div class="info-box">
        Student Name: <b><?php echo htmlspecialchars($current_mark['student_name']); ?></b><br>
        Subject: <b><?php echo htmlspecialchars($current_mark['subject_name']); ?></b>
    </div>

    <form method="POST" action="">
        <label for="score">Score (0 - 100):</label>
        <input type="number" name="score" id="score" min="0" max="100" value="<?php echo htmlspecialchars($current_mark['score']); ?>" required>

        <label for="term">Exam Assessment Type:</label>
        <select name="term" id="term" required>
            <option value="Weekly Test" <?php echo $current_mark['term'] === 'Weekly Test' ? 'selected' : ''; ?>>Weekly Test</option>
            <option value="Monthly Test" <?php echo $current_mark['term'] === 'Monthly Test' ? 'selected' : ''; ?>>Monthly Test</option>
            <option value="Midterm Test" <?php echo $current_mark['term'] === 'Midterm Test' ? 'selected' : ''; ?>>Midterm Test</option>
            <option value="Terminal Examination" <?php echo $current_mark['term'] === 'Terminal Examination' ? 'selected' : ''; ?>>Terminal Examination</option>
            <option value="Annual Examination" <?php echo $current_mark['term'] === 'Annual Examination' ? 'selected' : ''; ?>>Annual Examination</option>
        </select>

        <label for="exam_date">Exam Date:</label>
        <input type="date" name="exam_date" id="exam_date" value="<?php echo htmlspecialchars($current_mark['exam_date']); ?>" required>

        <button type="submit" class="btn-submit">💾 Update Marks</button>
        <a href="matokeo.php" class="btn-cancel">❌ Cancel & Go Back</a>
    </form>
</div>

</body>
</html>