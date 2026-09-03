<?php
session_start();
require 'db.php';

// 1. PROTECTION: Ensure only an Admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. HANDLE NEW SCHOOL DATA SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name']);

    if (!empty($school_name)) {
        try {
            // Check if the school already exists to prevent duplicate entries
            $check_stmt = $pdo->prepare("SELECT school_id FROM schools WHERE school_name = ?");
            $check_stmt->execute([$school_name]);

            if ($check_stmt->fetch()) {
                $message = "<div class='alert error'>❌ The school '$school_name' already exists in the system!</div>";
            } else {
                // Insert the new school into the database
                $sql = "INSERT INTO schools (school_name) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$school_name]);

                $message = "<div class='alert success'>✔️ School '$school_name' registered successfully!</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Database Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ Please enter a school name!</div>";
    }
}

// 3. FETCH EXISTING SCHOOLS LIST TO DISPLAY BELOW THE FORM
try {
    $schools_stmt = $pdo->query("SELECT * FROM schools ORDER BY school_name ASC");
    $all_schools = $schools_stmt->fetchAll();
} catch (PDOException $e) {
    $all_schools = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add School</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 500px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2, h3 { text-align: center; color: #0056b3; margin-top: 0; text-transform: uppercase; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #334155; }
        input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 15px; }
        input:focus { border-color: #0056b3; outline: none; }
        button { width: 100%; padding: 12px; background-color: #22c55e; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 20px; }
        button:hover { background-color: #16a34a; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #64748b; font-weight: bold; }
        .back-link:hover { color: #334155; }
        
        /* Registered Schools Table Styling */
        .school-list { margin-top: 30px; border-top: 2px solid #e2e8f0; padding-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; font-size: 14px; }
        th { background-color: #f1f5f9; color: #334155; }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New School</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="school_name">School Name:</label>
        <input type="text" name="school_name" id="school_name" placeholder="Example: Dodoma Tech Academy" required>

        <button type="submit">Save School</button>
    </form>

    <div class="school-list">
        <h3>Registered Schools (<?php echo count($all_schools); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">ID</th>
                    <th>School Name</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($all_schools) > 0): ?>
                    <?php foreach ($all_schools as $school): ?>
                        <tr>
                            <td><?php echo $school['school_id']; ?></td>
                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align:center;">No schools registered yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <a href="admin.php" class="back-link">⬅️ Back to Admin Dashboard</a>
</div>

</body>
</html>