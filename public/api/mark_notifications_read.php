<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $notification_ids = isset($_POST['notification_ids']) ? json_decode($_POST['notification_ids']) : null;

    if ($notification_ids) {
        // Mark specific notifications as read
        $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id IN ($placeholders) AND to_user_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $types = str_repeat('i', count($notification_ids)) . 'i';
        $params = array_merge($notification_ids, [$user_id]);
        $stmt->bind_param($types, ...$params);
    } else {
        // Mark all notifications as read
        $sql = "UPDATE notifications SET is_read = TRUE WHERE to_user_id = ? AND is_read = FALSE";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $user_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $success = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => $success]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
