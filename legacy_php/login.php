<?php
session_start();
require 'db.php';

// If user is already logged in, redirect them to their respective page
if (isset($_SESSION['role']) && !isset($_POST['username'])) {
    if ($_SESSION['role'] === 'Admin') { header("Location: admin.php"); exit; }
    if ($_SESSION['role'] === 'Teacher') { header("Location: weka_alama.php"); exit; }
    if ($_SESSION['role'] === 'Parent') { header("Location: ripoti.php"); exit; }
    if ($_SESSION['role'] === 'Accountant') { header("Location: view_fees.php"); exit; } // MABORESHO YA AUTODIRECT
    if ($_SESSION['role'] === 'Headmaster' || $_SESSION['role'] === 'Academic Master') { 
        header("Location: dashboard_leaders.php"); 
        exit; 
    }
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // USALAMA: Badilisha Session ID kila user anapologin ili kuzuia udukuzi
            session_regenerate_id(true);
            
            // Tunatengeneza Session data mpya hapa
            $_SESSION['user_id']     = $user['user_id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['school_name'] = $user['school_name'];

            // Redirect kulingana na jukumu (Role) la mtumiaji
            if ($user['role'] === 'Admin') {
                header("Location: admin.php");
                exit;
            } elseif ($user['role'] === 'Teacher') {
                header("Location: weka_alama.php");
                exit;
            } elseif ($user['role'] === 'Parent') {
                header("Location: ripoti.php");
                exit;
            } elseif ($user['role'] === 'Accountant') { // MABORESHO: Mruhusu Accountant na mpeleke kwenye jopo la fedha
                header("Location: view_fees.php");
                exit;
            } elseif ($user['role'] === 'Headmaster' || $user['role'] === 'Academic Master') {
                header("Location: dashboard_leaders.php");
                exit;
            }
        } else {
            $error_message = "❌ Invalid username or password!";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        <h2> { text-align: center; color: #0056b3; margin-top: 0; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 15px; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 18px; padding: 0; color: #64748b; }
        .toggle-password:focus { outline: none; }

        button.login-btn { width: 100%; padding: 12px; background-color: #0056b3; color: white; border: none; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; }
        button.login-btn:hover { background-color: #004085; }
        .error-alert { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>SYSTEM LOGIN</h2>
    
    <?php if (!empty($error_message)): ?>
        <div class="error-alert"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" placeholder="Enter your username" required>

        <label for="password">Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
            <button type="button" id="togglePasswordBtn" class="toggle-password" title="Show/Hide Password">👁️</button>
        </div>

        <button type="submit" class="login-btn">Login</button>
    </form>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');

    togglePasswordBtn.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });
</script>

</body>
</html>