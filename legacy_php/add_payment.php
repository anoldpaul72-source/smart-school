<?php
session_start();
require 'db.php';

// STRICT ACCESS CONTROL: Only Admin and Accountant allowed
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Accountant']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$school_name = isset($_SESSION['school_name']) ? trim($_SESSION['school_name']) : '';
$message = "";

if (empty($school_name)) {
    $user_stmt = $pdo->prepare("SELECT school_name FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    $_SESSION['school_name'] = $user_data ? $user_data['school_name'] : null;
    $school_name = trim($_SESSION['school_name']);
}

// Fixed Secondary school classes array
$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];

// Process Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $student_id   = $_POST['student_id'];
    $amount_paid  = $_POST['amount_paid'];
    $receipt_no   = trim($_POST['receipt_no']);
    $payment_date = $_POST['payment_date'];
    $academic_year = date('Y', strtotime($payment_date));

    try {
        // Check if receipt number already exists
        $rcpt_stmt = $pdo->prepare("SELECT payment_id FROM student_payments WHERE receipt_no = ?");
        $rcpt_stmt->execute([$receipt_no]);
        
        if ($rcpt_stmt->fetch()) {
            $message = "<div class='alert error'>❌ Receipt Number <b>$receipt_no</b> has already been registered!</div>";
        } else {
            $sql = "INSERT INTO student_payments (student_id, amount_paid, payment_date, receipt_no, academic_year, recorded_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id, $amount_paid, $payment_date, $receipt_no, $academic_year, $user_id]);
            $message = "<div class='alert success'>✔️ Payment of " . number_format($amount_paid, 2) . " recorded successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>❌ Transaction Failed: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance - Record Student Payment</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0d9488; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; font-size: 20px; text-transform: uppercase; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; color: #0d9488; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background-color: #0d9488; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; text-transform: uppercase; }
        button:hover { background-color: #0f766e; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d1fae5; color: #065f46; }
        .error { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container">
    <h2>💰 Fee Collection Portal</h2>
    
    <div class="nav-links">
        <span>Campus: <b><?php echo htmlspecialchars($school_name); ?></b></span>
        <div>
            <a href="view_fees.php" style="margin-right: 15px; font-weight: bold;">📊 Fee Ledger Reports</a>
            <a href="matokeo.php">Dashboard</a>
        </div>
    </div>

    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="class_name">Select Student Class:</label>
        <select name="class_name" id="class_name" required>
            <option value="">-- Choose Class --</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="student_id">Select Student Name:</label>
        <select name="student_id" id="student_id" required disabled>
            <option value="">-- Choose Class First --</option>
        </select>

        <label for="amount_paid">Amount Paid (TZS):</label>
        <input type="number" name="amount_paid" id="amount_paid" min="0" step="0.01" placeholder="e.g. 50000" required>

        <label for="receipt_no">Receipt / Reference Number:</label>
        <input type="text" name="receipt_no" id="receipt_no" placeholder="Enter bank slip or receipt number" required>

        <label for="payment_date">Payment Date:</label>
        <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required>

        <button type="submit" name="submit_payment">💾 Record Statement</button>
    </form>
</div>

<script>
    const classSelect = document.getElementById('class_name');
    const studentSelect = document.getElementById('student_id');

    classSelect.addEventListener('change', function() {
        const selectedClass = this.value;

        if (selectedClass === "") {
            studentSelect.innerHTML = '<option value="">-- Choose Class First --</option>';
            studentSelect.disabled = true;
            return;
        }

        studentSelect.innerHTML = '<option value="">⌛ Fetching Classroom Roster...</option>';
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
                    studentSelect.innerHTML = '<option value="">❌ No students found</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentSelect.innerHTML = '<option value="">❌ Error loading data</option>';
            });
    });
</script>

</body>
</html>