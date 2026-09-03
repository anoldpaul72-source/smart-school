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

// 1. PROCESS: Set/Update Fee Structure for a Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_fee_structure'])) {
    $class_name   = trim($_POST['config_class']);
    $total_amount = $_POST['total_amount'];
    $academic_year = intval($_POST['academic_year']);

    try {
        $sql = "INSERT INTO fee_structures (class_name, total_amount, academic_year, school_name) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE total_amount = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class_name, $total_amount, $academic_year, $school_name, $total_amount]);
        $message = "<div class='alert success'>✔️ Fee structure for $class_name ($academic_year) updated to " . number_format($total_amount, 2) . " TZS</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>❌ Configuration Failed: " . $e->getMessage() . "</div>";
    }
}

// 2. FIXED CONTEXT: Array of all standard secondary school classes
$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];

// 3. FETCH: Ledger statements if a filter is active
$filter_class = isset($_GET['filter_class']) ? trim($_GET['filter_class']) : '';
$filter_year  = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : date('Y');
$ledger = [];
$target_fee_required = 0;

if (!empty($filter_class)) {
    try {
        // Find required fee structure (Case-insensitive check)
        $fee_stmt = $pdo->prepare("SELECT total_amount FROM fee_structures WHERE LOWER(TRIM(class_name)) = LOWER(TRIM(?)) AND academic_year = ? AND LOWER(TRIM(school_name)) = LOWER(TRIM(?))");
        $fee_stmt->execute([$filter_class, $filter_year, $school_name]);
        $fee_structure = $fee_stmt->fetch();
        $target_fee_required = $fee_structure ? $fee_structure['total_amount'] : 0;

        // Fetch all students and sums
        $ledger_sql = "
            SELECT 
                s.student_id,
                s.student_name,
                COALESCE(SUM(p.amount_paid), 0) AS total_paid
            FROM students s
            LEFT JOIN student_payments p ON s.student_id = p.student_id AND p.academic_year = ?
            WHERE LOWER(TRIM(s.class_name)) = LOWER(TRIM(?)) 
              AND LOWER(TRIM(s.school_name)) = LOWER(TRIM(?))
            GROUP BY s.student_id, s.student_name
            ORDER BY s.student_name ASC
        ";
        $ledger_stmt = $pdo->prepare($ledger_sql);
        $ledger_stmt->execute([$filter_year, $filter_class, $school_name]);
        $ledger = $ledger_stmt->fetchAll();

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Ledger & Configurations</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 950px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #0f766e; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; text-transform: uppercase; text-align: center; font-size: 20px; }
        .nav-links { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 14px; background: #e9ecef; padding: 10px; border-radius: 4px; align-items: center; }
        .nav-links a { font-weight: bold; text-decoration: none; color: #0f766e; }
        
        .panel-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 25px; }
        .card { background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0; height: fit-content; }
        h3 { margin-top: 0; color: #334155; font-size: 15px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; }
        
        label { font-weight: bold; font-size: 13px; color: #475569; display: block; margin-top: 10px; margin-bottom: 4px; }
        select, input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        
        .btn { background-color: #0f172a; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; width: 100%; margin-top: 15px; text-transform: uppercase; }
        .btn:hover { background-color: #1e293b; }
        .btn-accent { background-color: #0d9488; }
        .btn-accent:hover { background-color: #0f766e; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 12px; text-align: left; font-size: 14px; }
        th { background-color: #f1f5f9; color: #1e293b; font-size: 12px; text-transform: uppercase; }
        
        .badge { font-weight: bold; padding: 3px 6px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
        .cleared { background-color: #dcfce7; color: #15803d; }
        .owing { background-color: #fee2e2; color: #b91c1c; }
        .debug-box { background-color: #fffbeb; border: 1px dashed #f59e0b; padding: 12px; color: #b45309; font-size: 13px; margin-top: 15px; border-radius: 4px; }
        
        /* MODERN PRINT CSS FIXED RULE */
        @media print {
            .nav-links, .no-print, form, button, .btn { 
                display: none !important; 
            }
            .panel-grid { 
                display: block !important; 
            }
            .card { 
                background: white !important; 
                border: none !important; 
                box-shadow: none !important; 
                padding: 0 !important;
                width: 100% !important;
            }
            body { 
                background: white; 
                margin: 0; 
            }
            .container { 
                box-shadow: none; 
                padding: 0; 
                max-width: 100%; 
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📊 Financial Ledger and Accounts Statement</h2>

    <div class="nav-links">
        <span>Campus: <b><?php echo htmlspecialchars($school_name); ?></b></span>
        <div style="display: flex; align-items: center;">
            <a href="add_payment.php" style="margin-right: 15px; font-weight: bold; color: #0d9488;">➕ Collect New Fee Payment</a>
            <a href="matokeo.php" style="margin-right: 20px;">Dashboard</a>
            <!-- Added Modern Logout Option -->
            <a href="logout.php" class="no-print" style="color: #ef4444; font-weight: bold; text-decoration: none; padding: 5px 12px; border: 1px solid #fecaca; background-color: #fee2e2; border-radius: 4px;">Logout ↩</a>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="panel-grid">
        <!-- LEFT PANEL: Manage Fee Configuration Structure -->
        <div class="card no-print">
            <h3>⚙️ Config Required Fee</h3>
            <form method="POST" action="">
                <label for="config_class">Class Template:</label>
                <select name="config_class" id="config_class" required>
                    <option value="">-- Choose Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="total_amount">Required Amount (TZS):</label>
                <input type="number" name="total_amount" id="total_amount" placeholder="e.g. 400000" min="0" step="0.01" required>

                <label for="academic_year">Academic Year:</label>
                <input type="number" name="academic_year" id="academic_year" value="<?php echo date('Y'); ?>" required>

                <button type="submit" name="set_fee_structure" class="btn">Save Configuration</button>
            </form>
        </div>

        <!-- RIGHT PANEL: View Ledger Roster Statement Filtering -->
        <div class="card" style="width: 100%;">
            <h3>🔍 Look Up Accounts Statement</h3>
            <form method="GET" action="" class="no-print">
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 2;">
                        <label for="filter_class">Target Class Structure:</label>
                        <select name="filter_class" id="filter_class" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $filter_class === $class ? 'selected' : ''; ?>><?php echo htmlspecialchars($class); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="filter_year">Year:</label>
                        <input type="number" name="filter_year" id="filter_year" value="<?php echo htmlspecialchars($filter_year); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-accent">Generate Financial Statement</button>
            </form>

            <?php if (!empty($filter_class)): ?>
                <div style="margin-top: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #e2e8f0; padding: 10px; border-radius: 4px;">
                        <span style="font-size: 14px; font-weight: bold; color: #1e293b;">Roster: Class <?php echo htmlspecialchars($filter_class); ?> (Year: <?php echo $filter_year; ?>)</span>
                        <span style="font-size: 13px; font-weight: bold; color: #0f766e;">Class Mandatory Fee: <?php echo number_format($target_fee_required, 2); ?> TZS</span>
                    </div>

                    <?php if (count($ledger) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Full Student Name</th>
                                    <th>Paid (TZS)</th>
                                    <th>Balance Due (TZS)</th>
                                    <th style="text-align: center;">Account State</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ledger as $row): 
                                    $paid = $row['total_paid'];
                                    $balance = $target_fee_required - $paid;
                                    if ($balance < 0) $balance = 0; 
                                    $status = ($balance <= 0 && $target_fee_required > 0) ? "Cleared" : "Owing";
                                ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($row['student_name']); ?></b></td>
                                        <td style="color: #16a34a; font-weight: bold;"><?php echo number_format($paid, 2); ?></td>
                                        <td style="color: #ef4444; font-weight: bold;"><?php echo number_format($balance, 2); ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge <?php echo strtolower($status); ?>"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button onclick="window.print()" class="btn no-print" style="background-color: #0d9488; max-width: 200px; float: right;">🖨️ Print Report</button>
                    <?php else: ?>
                        <div class="debug-box">
                            ⚠️ <b>Debug Alert:</b> Tumepata wanafunzi 0 kwenye darasa la <b>"<?php echo htmlspecialchars($filter_class); ?>"</b> kwa shule ya <b>"<?php echo htmlspecialchars($school_name); ?>"</b>.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>