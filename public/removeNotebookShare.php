<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$notebook_id = $data['notebook_id'] ?? null;
$bubble_id = $data['bubble_id'] ?? null;

// Validate inputs
if (!$notebook_id || !$bubble_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify notebook ownership
$check_ownership = $conn->prepare("SELECT user_id FROM notebooks WHERE id = ?");
$check_ownership->bind_param("i", $notebook_id);
$check_ownership->execute();
$result = $check_ownership->get_result()->fetch_assoc();

if (!$result || $result['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to remove this share']);
    exit;
}

// Remove the share
$delete = $conn->prepare("DELETE FROM notebook_permissions WHERE notebook_id = ? AND bubble_id = ?");
$delete->bind_param("ii", $notebook_id, $bubble_id);
$success = $delete->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Share removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error removing share']);
}
