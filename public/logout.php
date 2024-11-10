<?php
session_start();
include 'config.php'; // Include your database configuration file

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Update user status to offline
    $updateStatusSql = "UPDATE users SET status='Inactive' WHERE id=?";
    $updateStmt = $conn->prepare($updateStatusSql);
    $updateStmt->bind_param("i", $userId);
    $updateStmt->execute();
    $updateStmt->close();

    // Destroy the session and redirect to login page
    session_unset();
    session_destroy();
    header("Location: indexLogin.php");
    exit();
} else {
    header("Location: indexLogin.php");
    exit();
}
?>