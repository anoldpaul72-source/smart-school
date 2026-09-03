<?php
session_start();
require 'db.php';

// 1. PROTECTION: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

try {
    // 2. Vuta masomo yote kwanza ili tuyatumie kama vichwa vya nguzo (Headers) kwenye jedwali
    $subjects_stmt = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_id ASC");
    $subjects = $subjects_stmt->fetchAll();

    // 3. Vuta wanafunzi wote pamoja na alama zao
    // Query hii inaunganisha wanafunzi na alama zao kulingana na muundo wako wa database (Normalized table)
    $marks_sql = "SELECT s.student_id, s.student_name, s.class, s.reg_number, m.subject_id, m.score 
                  FROM students s
                  LEFT JOIN marks m ON s.student_id = m.student_id
                  ORDER BY s.class ASC, s.student_name ASC";
    
    $marks_stmt = $pdo->query($marks_sql);
    $raw_data = $marks_stmt->fetchAll();

    // 4. Kupanga data kulingana na Darasa -> Mwanafunzi -> Alama za masomo yake
    $classes_data = [];
    foreach ($raw_data as $row) {
        $className = !empty($row['class']) ? $row['class'] : 'Unassigned Class';
        $studentId = $row['student_id'];

        // Kama darasa halijatengenezwa kwenye array, litengeneze
        if (!isset($classes_data[$className])) {
            $classes_data[$className] = [];
        }

        // Kama mwanafunzi hayupo kwenye darasa hili, weka taarifa zake za msingi
        if (!isset($classes_data[$className][$studentId])) {
            $classes_data[$className][$studentId] = [
                'reg_number'   => $row['reg_number'],
                'student_name' => $row['student_name'],
                'scores'       => [] // Hapa zitakaa alama zake za kila subject_id
            ];
        }

        // Kama mwanafunzi ana alama ya somo husika, iweke hapa
        if ($row['subject_id'] !== null) {
            $classes_data[$className][$studentId]['scores'][$row['subject_id']] = $row['score'];
        }
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
    <title>Admin - View All Student Marks</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; }
        h2 { color: #0f172a; margin: 0; text-transform: uppercase; font-size: 24px; }
        .back-btn { padding: 10px 16px; background-color: #64748b; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .back-btn:hover { background-color: #475569; }

        /* Mtindo wa Makundi ya Madarasa (Class Sections) */
        .class-section { margin-bottom: 40px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; }
        .class-title { font-size: 20px; font-weight: bold; color: #0284c7; margin-top: 0; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #e2e8f0; display: inline-block; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background: #f8fafc; }
        
        .score-cell { text-align: center; font-weight: bold; color: #334155; background-color: #fafafa; }
        .no-data { text-align: center; color: #94a3b8; font-style: italic; padding: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h2>📊 Overall Student Marks (By Class)</h2>
        <a href="admin.php" class="back-btn">⬅️ Admin Dashboard</a>
    </div>

    <?php if (count($classes_data) > 0): ?>
        <?php foreach ($classes_data as $className => $students_list): ?>
            
            <div class="class-section">
                <div class="class-title">🏫 <?php echo htmlspecialchars($className); ?></div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">S/N</th>
                            <th style="width: 130px;">Reg Number</th>
                            <th>Student Name</th>
                            
                            <?php foreach ($subjects as $subject): ?>
                                <th style="text-align: center; width: 70px;"><?php echo htmlspecialchars($subject['subject_name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($students_list as $studentId => $studentData): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td style="color: #64748b; font-size: 13px; font-weight: bold;"><?php echo htmlspecialchars($studentData['reg_number']); ?></td>
                                <td style="font-weight: 500; color: #0f172a;"><?php echo htmlspecialchars($studentData['student_name']); ?></td>
                                
                                <?php foreach ($subjects as $subject): ?>
                                    <td class="score-cell">
                                        <?php 
                                            $subId = $subject['subject_id'];
                                            echo isset($studentData['scores'][$subId]) ? $studentData['scores'][$subId] : '-'; 
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data">❌ No student records or marks found in the system.</div>
    <?php endif; ?>
</div>

</body>
</html>