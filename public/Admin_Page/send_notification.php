<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_POST['message'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$userId = $_POST['user_id'];
$message = $_POST['message'];
$currentTime = date('Y-m-d H:i:s');

try {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("iss", $userId, $message, $currentTime);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to insert notification']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
