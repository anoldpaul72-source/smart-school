<?php
session_start();
require 'db.php';

// Ensure the user is logged in as a Teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $subject_id = intval($_POST['subject_id']);
    $term       = trim($_POST['term']);
    $file       = $_FILES['excel_file'];

    if ($subject_id === 0 || empty($term) || empty($file['name'])) {
        $message = "<div class='alert error'>❌ Please fill all required fields and select a CSV file!</div>";
    } else {
        // Validate file extension
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) !== 'csv') {
            $message = "<div class='alert error'>❌ Invalid file format! Only .csv files are supported. In Excel, click 'Save As' and choose 'CSV (Comma delimited)'.</div>";
        } else {
            try {
                $handle = fopen($file['tmp_name'], 'r');
                
                // Skip the first row (Header row containing column names)
                fgetcsv($handle);

                $pdo->beginTransaction();
                
                $inserted_count = 0;
                $skipped_count = 0;

                // Prepared SQL statements
                $check_stmt = $pdo->prepare("SELECT mark_id FROM marks WHERE student_id = ? AND subject_id = ? AND term = ? AND exam_date = ?");
                $insert_stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, score, term, exam_date, year) VALUES (?, ?, ?, ?, ?, ?)");

                // Parse the CSV row by row
                while (($row = fgetcsv($handle)) !== FALSE) {
                    // $row[0] = Student ID, $row[1] = Student Name, $row[2] = Score, $row[3] = Exam Date
                    $student_id = intval($row[0]);
                    $score      = trim($row[2]);
                    $exam_date  = trim($row[3]);

                    // Skip the row if score is blank or student ID is invalid
                    if ($score === '' || $student_id === 0) {
                        $skipped_count++;
                        continue;
                    }

                    // MAREKEBISHO YA TAREHE: Kama tarehe ni tupu au muundo wake haueleweki, weka tarehe ya leo
                    if (empty($exam_date) || strtotime($exam_date) === false || $exam_date === '0000-00-00') {
                        $exam_date = date('Y-m-d'); 
                    }

                    $year = date('Y', strtotime($exam_date));

                    // Check if marks for this specific exam and date already exist
                    $check_stmt->execute([$student_id, $subject_id, $term, $exam_date]);
                    
                    if ($check_stmt->fetch()) {
                        // Mark already exists, skip to prevent duplicates
                        $skipped_count++;
                    } else {
                        // Insert new mark entry
                        $insert_stmt->execute([$student_id, $subject_id, $score, $term, $exam_date, $year]);
                        $inserted_count++;
                    }
                }

                fclose($handle);
                $pdo->commit();

                $message = "<div class='alert success'>✔️ Marks processed successfully! Records added: $inserted_count. Records skipped (empty or already existing): $skipped_count.</div>";

            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $message = "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// Fetch assigned subjects for the upload form context
try {
    $subjects = $pdo->prepare("SELECT DISTINCT s.subject_id, s.subject_name FROM subjects s JOIN teacher_assignments ta ON s.subject_id = ta.subject_id WHERE ta.teacher_id = ? ORDER BY s.subject_name ASC");
    $subjects->execute([$_SESSION['user_id']]);
    $my_subjects = $subjects->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel - Upload Marks</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #22c55e; margin-top: 0; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; }
        input, select { width: 100%; padding: 11px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button.upload-btn { width: 100%; padding: 12px; background-color: #22c55e; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button.upload-btn:hover { background-color: #16a34a; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #0056b3; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>📤 UPLOAD MARKS VIA EXCEL/CSV</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <label for="subject_id">1. Select Subject to Upload Marks For:</label>
        <select name="subject_id" id="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php foreach ($my_subjects as $sub): ?>
                <option value="<?php echo $sub['subject_id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="term">2. Select Assessment Type:</label>
        <select name="term" id="term" required>
            <option value="">-- Select Assessment Type --</option>
            <option value="Weekly Test">Weekly Test</option>
            <option value="Monthly Test">Monthly Test</option>
            <option value="Midterm Test">Midterm Test</option>
            <option value="Terminal Examination">Terminal Examination</option>
            <option value="Annual Examination">Annual Examination</option>
        </select>

        <label for="excel_file">3. Choose the Completed CSV File:</label>
        <input type="file" name="excel_file" id="excel_file" accept=".csv" required>

        <button type="submit" class="upload-btn">🚀 Parse & Submit Marks</button>
    </form>

    <a href="weka_alama.php" class="back-link">⬅️ Back to Single Student Entry Form</a>
</div>

</body>
</html>