<?php
session_start();
require 'db.php';

// 1. PROTECTION: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 2. HANDLE DELETE REQUEST WITH CASCADE CLEANUP
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    try {
        // Anzisha Transaction ili kuhakikisha kufuta kote kunafanikiwa
        $pdo->beginTransaction();

        // A. Futa matokeo (marks) ya mwanafunzi huyu kwanza
        $stmt1 = $pdo->prepare("DELETE FROM marks WHERE student_id = ?");
        $stmt1->execute([$delete_id]);

        // B. Futa mahudhurio (attendance) ikiwa ipo
        $stmt2 = $pdo->prepare("DELETE FROM attendance WHERE student_id = ?");
        $stmt2->execute([$delete_id]);

        // C. Sasa mfute mwanafunzi kutoka jedwali la students
        $stmt3 = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt3->execute([$delete_id]);

        // Thibitisha mabadiliko
        $pdo->commit();
        
        $message = "<div class='alert success'>✔️ Student and all related records deleted successfully!</div>";
    } catch (PDOException $e) {
        // Ikitokea shida, batilisha mabadiliko yote
        $pdo->rollBack();
        $message = "<div class='alert error'>❌ Cannot delete student. Database Error: " . $e->getMessage() . "</div>";
    }
}

// 3. FETCH ALL STUDENTS
try {
    $sql = "SELECT s.student_id, s.reg_number, s.student_name, s.sex, s.class_name, s.school_name, u.username as parent_name
            FROM students s
            LEFT JOIN users u ON s.parent_id = u.user_id
            ORDER BY s.class_name ASC, s.student_name ASC";
            
    $stmt = $pdo->query($sql);
    $all_students = $stmt->fetchAll();

    // 4. GROUP STUDENTS BY CLASS
    $categorized_students = [];
    foreach ($all_students as $student) {
        $className = !empty($student['class_name']) ? $student['class_name'] : 'Unassigned Class';
        $categorized_students[$className][] = $student;
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Registered Students</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; padding: 20px; color: #1e293b; }
        .container { max-width: 1150px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; }
        h2 { color: #0f172a; margin: 0; text-transform: uppercase; font-size: 24px; }
        .back-btn { padding: 10px 16px; background-color: #64748b; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .back-btn:hover { background-color: #475569; }
        
        .class-group { margin-bottom: 35px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; }
        .class-heading { font-size: 18px; font-weight: bold; color: #0284c7; margin-top: 0; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #e2e8f0; display: inline-block; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #cbd5e1; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }
        
        .badge-parent { background: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600; display: inline-block; }
        .sex-badge { font-weight: bold; color: #0284c7; background: #e0f2fe; padding: 3px 8px; border-radius: 4px; }
        
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .edit-btn { background-color: #ea580c; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px; }
        .edit-btn:hover { background-color: #c2410c; }
        .delete-btn { background-color: #ef4444; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px; }
        .delete-btn:hover { background-color: #dc2626; }
        
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; color: #94a3b8; font-style: italic; padding: 30px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h2>👥 Registered Students (By Class)</h2>
        <a href="admin.php" class="back-btn">⬅️ Admin Dashboard</a>
    </div>

    <?php echo $message; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert success">✔️ <?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (count($categorized_students) > 0): ?>
        <?php foreach ($categorized_students as $className => $students_list): ?>
            
            <div class="class-group">
                <div class="class-heading">🏫 <?php echo htmlspecialchars($className); ?> (<?php echo count($students_list); ?> Students)</div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">S/N</th>
                            <th style="width: 140px;">Reg Number</th>
                            <th>Student Full Name</th>
                            <th style="width: 60px; text-align: center;">Sex</th>
                            <th>Registered School</th>
                            <th>Linked Parent</th>
                            <th style="text-align: center; width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($students_list as $student): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td style="font-weight: bold; color: #475569;"><?php echo htmlspecialchars($student['reg_number']); ?></td>
                                <td style="font-weight: 500; color: #0f172a;"><?php echo htmlspecialchars($student['student_name']); ?></td>
                                
                                <td style="text-align: center;">
                                    <span class="sex-badge">
                                        <?php echo !empty($student['sex']) ? htmlspecialchars($student['sex']) : '-'; ?>
                                    </span>
                                </td>
                                
                                <td><?php echo htmlspecialchars($student['school_name']); ?></td>
                                <td>
                                    <?php if (!empty($student['parent_name'])): ?>
                                        <span class="badge badge-parent">👤 <?php echo htmlspecialchars($student['parent_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #ef4444; font-size: 13px;">⚠️ No Parent Linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="edit-btn">
                                            ✏️ Edit
                                        </a>
                                        <a href="view_students.php?delete_id=<?php echo $student['student_id']; ?>" 
                                           class="delete-btn" 
                                           onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($student['student_name']); ?>? This will also remove their marks and attendance.');">
                                            🗑️ Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data">❌ No students found in the database.</div>
    <?php endif; ?>
</div>

</body>
</html>