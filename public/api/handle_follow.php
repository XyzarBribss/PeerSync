<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$target_user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if (!$target_user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

if ($target_user_id === $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot follow yourself']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if already following
    $check_sql = "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $_SESSION['user_id'], $target_user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Already following, unfollow
        $delete_sql = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $_SESSION['user_id'], $target_user_id);
        $delete_stmt->execute();
        $action = 'unfollowed';
    } else {
        // Not following, create follow
        $insert_sql = "INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $_SESSION['user_id'], $target_user_id);
        $insert_stmt->execute();
        $action = 'followed';

        // Create notification for the target user
        $notification_data = json_encode([
            'type' => 'follow',
            'to_user_id' => $target_user_id
        ]);

        // Make API call to create notification
        $ch = curl_init('http://localhost/PeerSync/public/api/create_notification.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notification_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($notification_data)
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // Get updated follower count
    $count_sql = "SELECT COUNT(*) as follower_count FROM follows WHERE following_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $target_user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $follower_count = $count_result->fetch_assoc()['follower_count'];

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'follower_count' => $follower_count
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Follow handling error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>
