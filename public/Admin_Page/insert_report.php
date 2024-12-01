<?php
session_start();
require '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get POST data
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$post_owner_id = isset($_POST['post_owner_id']) ? intval($_POST['post_owner_id']) : 0;
$report_reason = isset($_POST['report_reason']) ? $_POST['report_reason'] : '';
$post_content = isset($_POST['post_content']) ? $_POST['post_content'] : '';
$bubble_name = isset($_POST['bubble_name']) ? $_POST['bubble_name'] : '';

// Validate required fields
if (!$post_id || !$post_owner_id || !$report_reason || !$post_content || !$bubble_name) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Insert the report
    $stmt = $conn->prepare("INSERT INTO reports (post_id, reporter_id, post_owner_id, report_reason, post_content, bubble_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $post_id, $_SESSION['user_id'], $post_owner_id, $report_reason, $post_content, $bubble_name);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();