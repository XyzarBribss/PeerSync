<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Emp_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            Emp_id,
            username,
            email
        FROM admin 
        WHERE Emp_id = ?
    ");
    
    $stmt->bind_param("i", $_SESSION['Emp_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    echo json_encode($admin);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch admin details']);
}
?>
