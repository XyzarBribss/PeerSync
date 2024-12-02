<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Verify token and get user information
$stmt = $conn->prepare("SELECT pr.user_id, pr.id as reset_id
                       FROM password_resets pr 
                       WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired reset token']);
    exit;
}

$reset = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Update password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $reset['user_id']);
    $stmt->execute();

    // Mark reset token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->bind_param("i", $reset['reset_id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
}
