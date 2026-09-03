<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the Admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['student_csv'])) {
    $file = $_FILES['student_csv'];

    if (empty($file['name'])) {
        $message = "<div class='alert error'>❌ Please select a CSV file to upload!</div>";
    } else {
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) !== 'csv') {
            $message = "<div class='alert error'>❌ Invalid format! Please upload a .csv file.</div>";
        } else {
            try {
                $handle = fopen($file['tmp_name'], 'r');
                fgetcsv($handle); // Skip the first header row

                $pdo->beginTransaction();
                
                $inserted_students = 0;
                $created_parents = 0;
                $skipped_count = 0;

                // 1. Check if the parent account already exists (users - lowercase)
                $check_parent_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND role = 'Parent'");
                
                // 2. Create a new Parent account automatically if it does not exist (users - lowercase)
                $create_parent_stmt = $pdo->prepare("INSERT INTO users (username, password, role, school_name) VALUES (?, ?, 'Parent', ?)");
                
                // 3. Insert student record directly using Excel data mapping (students - lowercase)
                $insert_student_stmt = $pdo->prepare("INSERT INTO students (reg_number, student_name, sex, class_name, school_name, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
                
                $default_password_hashed = password_hash('123', PASSWORD_DEFAULT);

                while (($row = fgetcsv($handle)) !== FALSE) {
                    // Mapping variables directly to column index locations
                    $reg_number      = trim($row[0]);
                    $student_name    = trim($row[1]);
                    $sex             = strtoupper(trim($row[2])); 
                    $class_name      = trim($row[3]);            
                    $school_name     = trim($row[4]);
                    $parent_username = isset($row[5]) ? trim($row[5]) : '';

                    // Basic Validation: Skip rows if essential fields are omitted
                    if (empty($reg_number) || empty($student_name) || empty($class_name) || empty($school_name)) {
                        $skipped_count++;
                        continue;
                    }

                    $parent_id = null;

                    // Handle Parent Account Automation
                    if (!empty($parent_username)) {
                        $check_parent_stmt->execute([$parent_username]);
                        $parent_user = $check_parent_stmt->fetch();

                        if ($parent_user) {
                            $parent_id = $parent_user['user_id']; // Link to existing parent
                        } else {
                            // Automatically provision a parent account assigned to the row's specified school
                            $create_parent_stmt->execute([$parent_username, $default_password_hashed, $school_name]);
                            $parent_id = $pdo->lastInsertId();
                            $created_parents++;
                        }
                    }

                    // Insert student
                    $insert_student_stmt->execute([$reg_number, $student_name, $sex, $class_name, $school_name, $parent_id]);
                    $inserted_students++;
                }

                fclose($handle);
                $pdo->commit();

                $message = "<div class='alert success'>
                                ✔️ Data Upload Handled Successfully!<br>
                                👤 Total Students Saved: <b>$inserted_students</b><br>
                                👪 New Parent Accounts Provisioned (Password: 123): <b>$created_parents</b><br>
                                ⚠️ Skipped Rows (Missing Key Data): <b>$skipped_count</b>
                            </div>";

            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $message = "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manual Bulk Student Upload</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 650px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #2563eb; margin-top: 0; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; }
        input { width: 100%; padding: 11px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button.upload-btn { width: 100%; padding: 12px; background-color: #2563eb; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button.upload-btn:hover { background-color: #1d4ed8; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 15px; text-align: left; font-weight: bold; font-size: 14px; line-height: 1.6; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #2563eb; font-weight: bold; }
        .notice-box { background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 12px; font-size: 13px; color: #166534; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📤 DIRECT CSV DATA INGESTION SYSTEM</h2>
    
    <?php echo $message; ?>

    <div class="notice-box">
        📝 <b>Manual Entry Mode:</b> The system will directly pull the Registration Numbers and School Names typed in your offline spreadsheet. Make sure those columns are fully filled before processing.
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <label for="student_csv">Choose the Completed Student CSV File:</label>
        <input type="file" name="student_csv" id="student_csv" accept=".csv" required>

        <button type="submit" class="upload-btn">🚀 Upload & Process Student Records</button>
    </form>

    <a href="admin.php" class="back-link">⬅️ Back to Admin Dashboard</a>
</div>

</body>
</html>