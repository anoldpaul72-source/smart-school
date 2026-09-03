<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the Teacher role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id']; // Logged-in teacher ID
$message = "";

// SECURITY CONTEXT: Ensure school_name exists in session; fallback to database fetch
if (!isset($_SESSION['school_name'])) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$teacher_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
}

$school_name = trim($_SESSION['school_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $score      = $_POST['score'];
    $term       = $_POST['term']; 
    $exam_date  = $_POST['exam_date']; 
    $year       = date('Y', strtotime($exam_date)); 

    try {
        $check_sql = "SELECT mark_id FROM marks WHERE student_id = ? AND subject_id = ? AND term = ? AND exam_date = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$student_id, $subject_id, $term, $exam_date]);

        if ($check_stmt->fetch()) {
            $message = "<div class='alert error'>❌ Marks for this exam type on this date already exist for this student!</div>";
        } else {
            $sql = "INSERT INTO marks (student_id, subject_id, score, term, exam_date, year) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id, $subject_id, $score, $term, $exam_date, $year]);
            $message = "<div class='alert success'>✔️ Marks submitted successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

try {
    // 1. FETCH ASSIGNED SUBJECTS: Only load subjects mapped to this specific instructor
    $subject_sql = "SELECT DISTINCT s.subject_id, s.subject_name 
                    FROM subjects s 
                    JOIN teacher_assignments ta ON s.subject_id = ta.subject_id 
                    WHERE ta.teacher_id = ? 
                    ORDER BY s.subject_name ASC";
    $subjects_stmt = $pdo->prepare($subject_sql);
    $subjects_stmt->execute([$teacher_id]);
    $subjects = $subjects_stmt->fetchAll();

    // 2. FETCH ASSIGNED CLASSES: Only load classes mapped to this specific instructor
    $class_sql = "SELECT DISTINCT class_name FROM teacher_assignments WHERE teacher_id = ? ORDER BY class_name ASC";
    $class_stmt = $pdo->prepare($class_sql);
    $class_stmt->execute([$teacher_id]);
    $my_classes = $class_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel - Enter Marks</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 650px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0056b3; margin-top: 0; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; font-size: 20px; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background-color: #0056b3; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button:hover { background-color: #004085; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>TEACHER PANEL: ENTER MARKS</h2>
    
    <div class="nav-links">
        <span>Logged in: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="teacher_timetable.php" style="color: #0284c7; font-weight: bold;">🗓️ My Timetable</a>
            <a href="add_attendance.php" style="color: #16a34a; font-weight: bold;">📝 Take Attendance</a>
            <a href="matokeo.php" style="color: #0056b3;">View All Marks</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </div>
    </div>

    <div style="background: #f0fdf4; border: 1px dashed #22c55e; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
        <h4 style="margin-top: 0; color: #16a34a; text-transform: uppercase; font-size: 13px;">💡 Bulk Marks Entry (Excel/Offline)</h4>
        <p style="margin: 5px 0 10px 0; color: #475569;">Select a Subject and a Class from the form below, then click to download the pre-populated template:</p>
        
        <div style="display: flex; gap: 10px;">
            <button type="button" onclick="downloadExcelTemplate()" style="margin: 0; background-color: #16a34a; padding: 8px; font-size: 13px; width: auto; flex: 1;">
                📥 Download Template (Excel)
            </button>
            <a href="upload_marks.php" style="text-align: center; text-decoration: none; background-color: #ea580c; color: white; padding: 8px; font-size: 13px; font-weight: bold; border-radius: 4px; flex: 1;">
                📤 Upload Completed Template
            </a>
        </div>
    </div>

    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="subject_id">Select Subject:</label>
        <select name="subject_id" id="subject_id" required>
            <option value="">-- Select Your Subject --</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?php echo $subject['subject_id']; ?>">
                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="class_name">Select Class:</label>
        <select name="class_name" id="class_name" required>
            <option value="">-- Select Class First --</option>
            <?php foreach ($my_classes as $class): ?>
                <option value="<?php echo htmlspecialchars($class['class_name']); ?>">
                    <?php echo htmlspecialchars($class['class_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="student_id">Select Student Name:</label>
        <select name="student_id" id="student_id" required disabled>
            <option value="">-- Choose Class First --</option>
        </select>

        <label for="score">Score (0 - 100):</label>
        <input type="number" name="score" id="score" min="0" max="100" placeholder="Enter student score" required>

        <label for="term">Exam Assessment Type:</label>
        <select name="term" id="term" required>
            <option value="">-- Select Assessment Type --</option>
            <option value="Weekly Test">Weekly Test</option>
            <option value="Monthly Test">Monthly Test</option>
            <option value="Midterm Test">Midterm Test</option>
            <option value="Terminal Examination">Terminal Examination</option>
            <option value="Annual Examination">Annual Examination</option>
        </select>

        <label for="exam_date">Exam Date:</label>
        <input type="date" name="exam_date" id="exam_date" required>

        <button type="submit">Submit Marks</button>
    </form>
</div>

<script>
    const classSelect = document.getElementById('class_name');
    const studentSelect = document.getElementById('student_id');

    // 1. AJAX Classroom Dependent Filtering Engine
    classSelect.addEventListener('change', function() {
        const selectedClass = this.value;

        if (selectedClass === "") {
            studentSelect.innerHTML = '<option value="">-- Choose Class First --</option>';
            studentSelect.disabled = true;
            return;
        }

        studentSelect.innerHTML = '<option value="">⌛ Loading Students...</option>';
        studentSelect.disabled = false;

        fetch('get_students.php?class_name=' + encodeURIComponent(selectedClass))
            .then(response => response.json())
            .then(data => {
                studentSelect.innerHTML = '<option value="">-- Select Student --</option>';
                
                if (data.length > 0) {
                    data.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.student_id;
                        option.textContent = student.student_name;
                        studentSelect.appendChild(option);
                    });
                } else {
                    studentSelect.innerHTML = '<option value="">❌ No students found in this class</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching students:', error);
                studentSelect.innerHTML = '<option value="">❌ Error loading students</option>';
            });
    });

    // 2. Automated Excel/CSV Spreadsheet Manifest Request
    function downloadExcelTemplate() {
        const cls = classSelect.value;
        const sub = document.getElementById('subject_id').value;

        if (cls === "" || sub === "") {
            alert("❌ Please select both a 'Subject' and a 'Class' from the form below before downloading the template!");
            return;
        }

        window.location.href = 'download_template.php?class_name=' + encodeURIComponent(cls) + '&subject_id=' + sub;
    }
</script>

</body>
</html>