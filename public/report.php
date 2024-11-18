<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id']) || !isset($_POST['reportReason'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$reason = $_POST['reportReason'];
$current_time = date('Y-m-d H:i:s');

// Insert the report into the database
$query = "INSERT INTO reports (user_id, post_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('iiss', $user_id, $post_id, $reason, $current_time);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error submitting report']);
}

$stmt->close();
$conn->close();
?>
