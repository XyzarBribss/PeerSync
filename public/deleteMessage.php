<?php
session_start();
include 'config.php';

// Check if the request is JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($input['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'Message ID not provided']);
    exit;
}

$message_id = intval($input['message_id']);
$user_id = $_SESSION['user_id'];

// Only allow deletion if the user is the sender
$sql = "DELETE FROM direct_messages WHERE id = ? AND sender_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $message_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting message']);
}

$stmt->close();
$conn->close();
?>
