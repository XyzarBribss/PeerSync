<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Please log in to mark notifications as read']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Update all unread notifications for the user
    $sql = "UPDATE notifications SET is_read = 1 WHERE to_user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();

    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to mark notifications as read']);
    }
} catch (Exception $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error marking notifications as read']);
}

$conn->close();
?>
