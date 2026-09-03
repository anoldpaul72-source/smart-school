<?php
// Unganisha database
require 'db.php';

// Taarifa za Admin mpya
$username = 'admin';
$password = 'admin123'; // Hii ndio utakayochapa kwenye login page
$role     = 'Admin';

// Kuficha password kiusalama kama login.php inavyotaka
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Kuchunguza kama username ya 'admin' tayari ipo
    $check = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        // Kama ipo, tunaibadilisha iwe na sifa za Admin na password mpya
        $sql = "UPDATE Users SET password = ?, role = ? WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hashed_password, $role, $username]);
        echo "<h2 style='color:blue; font-family:Arial;'>Akaunti ya 'admin' ilikuwepo, sasa imesasishwa kuwa ya Admin kamili!</h2>";
    } else {
        // Kama haipo, tunaingiza mpya kabisa
        $sql = "INSERT INTO Users (username, password, role) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $hashed_password, $role]);
        echo "<h2 style='color:green; font-family:Arial;'>Akaunti mpya ya Admin imetengenezwa kikamilifu!</h2>";
    }
    
    echo "<p style='font-size:16px;'><b>Username:</b> admin <br> <b>Password:</b> admin123</p>";
    echo "<p><a href='login.php'>Bofya hapa kwenda Kurejea kwenye Login</a></p>";

} catch (PDOException $e) {
    echo "Hitilafu imetokea: " . $e->getMessage();
}
?>