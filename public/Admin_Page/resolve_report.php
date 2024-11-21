<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

// Get POST data
$report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

if (!$report_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get report details
    $stmt = $conn->prepare("SELECT post_id, post_owner_id FROM reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    
    if (!$report) {
        throw new Exception('Report not found');
    }

    // 1. Update user status to suspended
    $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
    $stmt->bind_param("i", $report['post_owner_id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update user status');
    }

    // 2. First delete related likes
    $stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ?");
    $stmt->bind_param("i", $report['post_id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete post likes');
    }

    // 3. Delete the bubble post
    $stmt = $conn->prepare("DELETE FROM bubble_posts WHERE id = ?");
    $stmt->bind_param("i", $report['post_id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete post');
    }

    // 4. Delete the report from reports table
    $stmt = $conn->prepare("DELETE FROM reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete report');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
