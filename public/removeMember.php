<?php
include 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $bubble_id = $data['bubble_id'];
    $user_id = $data['user_id'];


    // Delete member from bubble
    $stmt = $conn->prepare('DELETE FROM user_bubble WHERE bubble_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $bubble_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove user']);
    }

    $stmt->close();
    $conn->close();
}
?>
