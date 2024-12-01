<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if the request is POST and user is authenticated
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['Emp_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['recipient_id']) || !isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$sender_id = $_SESSION['Emp_id'];
$recipient_id = $data['recipient_id'];
$message = trim($data['message']);

try {
    // Insert the message
    $stmt = $conn->prepare("
        INSERT INTO admin_messages (sender_id, recipient_id, message)
        VALUES (?, ?, ?)
    ");
    
    $stmt->bind_param("iis", $sender_id, $recipient_id, $message);
    $stmt->execute();
    
    // Get the inserted message details
    $messageId = $conn->insert_id;
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            a.username as sender_name
        FROM admin_messages m
        JOIN admin a ON a.Emp_id = m.sender_id
        WHERE m.id = ?
    ");
    
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to send message']);
}
?>
