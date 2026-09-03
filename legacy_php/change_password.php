<?php
session_start();
require 'db.php';

// Hakikisha mtumiaji yeyote amelogin (Admin, Teacher, au Parent)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<div class='alert error'>❌ All fields are required!</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert error'>❌ New password and Confirm password do not match!</div>";
    } elseif (strlen($new_password) < 4) {
        // Unaweza kuongeza kiwango cha ugumu hapa (mfano herufi 6 au 8)
        $message = "<div class='alert error'>❌ New password must be at least 4 characters long!</div>";
    } else {
        try {
            // 1. Leta password ya sasa iliyopo kwenye database ili tuilinganishe
            $stmt = $pdo->prepare("SELECT password FROM Users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                // 2. Kama password ya sasa ni sahihi, fanya hashing ya password mpya
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

                // 3. Sasisha password mpya kwenye database
                $update_stmt = $pdo->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
                $update_stmt->execute([$new_password_hashed, $user_id]);

                $message = "<div class='alert success'>✔️ Password changed successfully! You can now use your new password.</div>";
            } else {
                $message = "<div class='alert error'>❌ Incorrect current password!</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Angalia ukurasa wa kurudi (Back Link) kulingana na nani amelogin
$back_link = "login.php"; // Default redirection
if ($user_role === 'Admin') {
    $back_link = "admin.php";
} elseif ($user_role === 'Teacher') {
    $back_link = "weka_alama.php";
} elseif ($user_role === 'Parent') {
    $back_link = "matokeo.php"; // Badilisha jina hili kulingana na ukurasa wa mzazi ulivyouita
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .password-container { max-width: 450px; margin: 60px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #1e3a8a; margin-top: 0; text-align: center; border-bottom: 2px solid #e9ecef; padding-bottom: 12px; text-transform: uppercase; font-size: 20px; }
        label { font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px; color: #475569; font-size: 14px; }
        input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 14px; transition: border 0.2s; }
        input:focus { border-color: #3b82f6; outline: none; }
        button { width: 100%; padding: 12px; background-color: #1e3a8a; color: white; border: none; font-size: 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 25px; transition: background 0.2s; }
        button:hover { background-color: #1d4ed8; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #1e3a8a; font-weight: bold; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="password-container">
    <h2>🔒 Change Account Password</h2>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <label for="current_password">Current Password:</label>
        <input type="password" name="current_password" id="current_password" placeholder="Enter your current password" required>

        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat new password" required>

        <button type="submit">🔄 Update Password</button>
    </form>

    <a href="<?php echo $back_link; ?>" class="back-link">⬅️ Back to Dashboard</a>
</div>

</body>
</html>