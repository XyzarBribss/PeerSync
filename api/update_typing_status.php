<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recipientId']) || !isset($data['isTyping'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$senderId = $_SESSION['admin_id'];
$recipientId = $data['recipientId'];
$isTyping = $data['isTyping'];

try {
    // Update the typing status in a temporary table or cache
    // For simplicity, we'll just return success
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to update typing status']);
}
?>
