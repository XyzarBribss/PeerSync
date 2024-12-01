<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Emp_id']) || !isset($_GET['last_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$currentAdminId = $_SESSION['Emp_id'];
$lastId = $_GET['last_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            a.username as sender_name
        FROM admin_messages m
        JOIN admin a ON a.Emp_id = m.sender_id
        WHERE m.id > ? 
        AND (m.recipient_id = ? OR m.sender_id = ?)
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bind_param("iii", $lastId, $currentAdminId, $currentAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode($messages);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch new messages']);
}
?>
