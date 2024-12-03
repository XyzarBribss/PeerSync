<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['post_id']) || !isset($data['comment'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$post_id = (int)$data['post_id'];
$comment_text = trim($data['comment']);

if (empty($comment_text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment cannot be empty']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert the comment
    $insert_sql = "INSERT INTO comments (user_id, post_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iis", $_SESSION['user_id'], $post_id, $comment_text);
    $insert_stmt->execute();
    $comment_id = $insert_stmt->insert_id;

    // Get comment details including user info
    $select_sql = "SELECT c.*, u.username, u.profile_image 
                   FROM comments c 
                   JOIN users u ON c.user_id = u.id 
                   WHERE c.id = ?";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("i", $comment_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $comment = $result->fetch_assoc();

    // Get post author ID
    $author_sql = "SELECT user_id FROM bubble_posts WHERE id = ?";
    $author_stmt = $conn->prepare($author_sql);
    $author_stmt->bind_param("i", $post_id);
    $author_stmt->execute();
    $author_result = $author_stmt->get_result();
    $post_author = $author_result->fetch_assoc();

    if ($post_author && $post_author['user_id'] != $_SESSION['user_id']) {
        // Create notification for the post author
        $notification_data = json_encode([
            'type' => 'comment',
            'to_user_id' => $post_author['user_id'],
            'post_id' => $post_id,
            'comment_id' => $comment_id
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

    // Commit transaction
    $conn->commit();

    // Format the timestamp
    $comment['created_at_formatted'] = date('M j, Y g:i A', strtotime($comment['created_at']));

    echo json_encode([
        'success' => true,
        'comment' => $comment
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Comment handling error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>
