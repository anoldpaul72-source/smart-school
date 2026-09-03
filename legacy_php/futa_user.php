<?php
session_start();
require 'db.php';

// 1. PROTECTION: Ensure only an Admin can access this script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// 2. RECEIVE USER ID
if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Security: Prevent Admin from deleting their own active account
    if (isset($_SESSION['user_id']) && $user_id === (int)$_SESSION['user_id']) {
        header("Location: admin.php?error=" . urlencode("❌ You cannot delete your own account while logged in!"));
        exit;
    }

    try {
        // Anzisha Transaction ili kufuta data zote zinazomtegemea user huyu kwanza
        $pdo->beginTransaction();

        // A. Kama ni mwalimu, ondoa usajili wake kwenye masomo/madarasa
        $stmt1 = $pdo->prepare("DELETE FROM teacher_assignments WHERE teacher_id = ?");
        $stmt1->execute([$user_id]);

        // B. Kama ni mzazi, tenga uhusiano wake na wanafunzi bila kuwafuta wanafunzi
        $stmt2 = $pdo->prepare("UPDATE students SET parent_id = NULL WHERE parent_id = ?");
        $stmt2->execute([$user_id]);

        // C. Mfute mtumiaji kutoka kwenye jedwali la 'users' (herufi ndogo pekee)
        $stmt3 = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt3->execute([$user_id]);

        // Thibitisha mabadiliko kwenye database
        $pdo->commit();

        // Redirect na ujumbe wa mafanikio
        header("Location: admin.php?success=" . urlencode("✔️ User deleted successfully!"));
        exit;

    } catch (PDOException $e) {
        // Ikitokea kosa lolote, batilisha hatua zote zilizofanyika
        $pdo->rollBack();
        header("Location: admin.php?error=" . urlencode("An error occurred while deleting: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: admin.php");
    exit;
}
?>