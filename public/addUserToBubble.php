<?php
session_start();
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$bubble_id = $data['bubble_id'];

// Check if the user exists
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Add user to the bubble
    $insert_sql = "INSERT INTO user_bubble (user_id, bubble_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ii", $user_id, $bubble_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>
