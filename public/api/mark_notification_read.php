<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

try {
    $sql = "UPDATE notifications 
            SET is_read = TRUE 
            WHERE to_user_id = ? AND post_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['user_id'], $post_id);
    $success = $stmt->execute();
    
    echo json_encode(['success' => $success]);
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>
