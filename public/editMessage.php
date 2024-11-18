<?php
session_start();
include 'config.php';

// Check if the request is JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($input['message_id']) || !isset($input['new_message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$message_id = intval($input['message_id']);
$new_message = trim($input['new_message']);
$user_id = $_SESSION['user_id'];

if (empty($new_message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

// Only allow editing if the user is the sender
$sql = "UPDATE direct_messages SET message = ? WHERE id = ? AND sender_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $new_message, $message_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating message']);
}

$stmt->close();
$conn->close();
?>
