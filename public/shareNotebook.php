<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST data
$notebook_id = $_POST['notebook_id'] ?? null;
$bubble_id = $_POST['bubble_id'] ?? null;
$permission = $_POST['permission'] ?? 'view';

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
    echo json_encode(['success' => false, 'message' => 'Not authorized to share this notebook']);
    exit;
}

// Check if share already exists
$check_share = $conn->prepare("SELECT id FROM notebook_permissions WHERE notebook_id = ? AND bubble_id = ?");
$check_share->bind_param("ii", $notebook_id, $bubble_id);
$check_share->execute();

if ($check_share->get_result()->num_rows > 0) {
    // Update existing share
    $update = $conn->prepare("UPDATE notebook_permissions SET permission_level = ? WHERE notebook_id = ? AND bubble_id = ?");
    $update->bind_param("sii", $permission, $notebook_id, $bubble_id);
    $success = $update->execute();
} else {
    // Create new share
    $insert = $conn->prepare("INSERT INTO notebook_permissions (notebook_id, bubble_id, permission_level) VALUES (?, ?, ?)");
    $insert->bind_param("iis", $notebook_id, $bubble_id, $permission);
    $success = $insert->execute();
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Notebook shared successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error sharing notebook']);
}
