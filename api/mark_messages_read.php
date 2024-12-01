<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Emp_id']) || !isset($_POST['sender_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$recipient_id = $_SESSION['Emp_id'];
$sender_id = $_POST['sender_id'];

try {
    $stmt = $conn->prepare("
        UPDATE admin_messages 
        SET read_at = NOW() 
        WHERE recipient_id = ? 
        AND sender_id = ? 
        AND read_at IS NULL
    ");
    
    $stmt->bind_param("ii", $recipient_id, $sender_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to mark messages as read']);
}
?>
