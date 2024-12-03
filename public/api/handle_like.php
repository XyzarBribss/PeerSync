<?php
session_start();
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message) {
    error_log("[Like Handler Debug] " . $message);
}

if (!isset($_SESSION['user_id'])) {
    logError("No user session found");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
logError("Received data: " . print_r($data, true));

if (!isset($data['post_id'])) {
    logError("Missing post_id");
    http_response_code(400);
    echo json_encode(['error' => 'Missing post_id']);
    exit;
}

$post_id = (int)$data['post_id'];
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if user already liked the post
    $check_sql = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $post_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike the post
        logError("Unliking post $post_id by user $user_id");
        $unlike_sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
        $unlike_stmt = $conn->prepare($unlike_sql);
        $unlike_stmt->bind_param("ii", $user_id, $post_id);
        $unlike_stmt->execute();
        $action = 'unliked';
    } else {
        // Like the post
        logError("Liking post $post_id by user $user_id");
        $like_sql = "INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, NOW())";
        $like_stmt = $conn->prepare($like_sql);
        $like_stmt->bind_param("ii", $user_id, $post_id);
        $like_stmt->execute();
        $action = 'liked';

        // Get post author
        $author_sql = "SELECT user_id FROM bubble_posts WHERE id = ?";
        $author_stmt = $conn->prepare($author_sql);
        $author_stmt->bind_param("i", $post_id);
        $author_stmt->execute();
        $author_result = $author_stmt->get_result();
        $post_author = $author_result->fetch_assoc();

        if ($post_author && $post_author['user_id'] != $user_id) {
            logError("Creating notification for post author {$post_author['user_id']}");
            // Create notification for post author
            $notification_data = json_encode([
                'type' => 'like',
                'to_user_id' => $post_author['user_id'],
                'post_id' => $post_id
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
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            logError("Notification API Response: $response (HTTP Code: $http_code)");
            curl_close($ch);
        }
    }

    // Get updated like count
    $count_sql = "SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $post_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $like_count = $count_result->fetch_assoc()['like_count'];

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $like_count
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    logError("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>
