<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Emp_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$currentAdminId = $_SESSION['Emp_id'];

try {
    // Get all admins except current one
    $stmt = $conn->prepare("
        SELECT 
            a.Emp_id,
            a.username as name,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE, a.last_active, NOW()) <= 5 THEN 1 
                ELSE 0 
            END as online,
            (
                SELECT COUNT(*) 
                FROM admin_messages 
                WHERE recipient_id = ? 
                AND sender_id = a.Emp_id 
                AND read_at IS NULL
            ) as unread_count,
            (
                SELECT message 
                FROM admin_messages 
                WHERE (sender_id = a.Emp_id AND recipient_id = ?) 
                OR (sender_id = ? AND recipient_id = a.Emp_id)
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message
        FROM admin a
        WHERE a.Emp_id != ?
        ORDER BY online DESC, name ASC
    ");
    
    $stmt->bind_param("iiii", $currentAdminId, $currentAdminId, $currentAdminId, $currentAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        // Update the admin's last_active timestamp
        if ($row['Emp_id'] === $currentAdminId) {
            $updateStmt = $conn->prepare("UPDATE admin SET last_active = NOW() WHERE Emp_id = ?");
            $updateStmt->bind_param("i", $currentAdminId);
            $updateStmt->execute();
        }
        $admins[] = $row;
    }
    
    echo json_encode($admins);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch admin contacts']);
}
?>
