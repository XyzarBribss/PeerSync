<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Emp_id']) || !isset($_GET['contact_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$currentAdminId = $_SESSION['Emp_id'];
$contactId = $_GET['contact_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            a.username as sender_name
        FROM admin_messages m
        JOIN admin a ON a.Emp_id = m.sender_id
        WHERE (m.sender_id = ? AND m.recipient_id = ?)
        OR (m.sender_id = ? AND m.recipient_id = ?)
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bind_param("iiii", $currentAdminId, $contactId, $contactId, $currentAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Mark messages as read
    $updateStmt = $conn->prepare("
        UPDATE admin_messages 
        SET read_at = NOW() 
        WHERE recipient_id = ? 
        AND sender_id = ? 
        AND read_at IS NULL
    ");
    $updateStmt->bind_param("ii", $currentAdminId, $contactId);
    $updateStmt->execute();
    
    echo json_encode($messages);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch chat history']);
}
?>
