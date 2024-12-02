<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Check if user has already liked the post
    $check_query = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // User has already liked the post, so unlike it
        $delete_query = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $post_id, $user_id);
        $success = $delete_stmt->execute();
        $liked = false;
    } else {
        // User hasn't liked the post, so add the like
        $insert_query = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $post_id, $user_id);
        $success = $insert_stmt->execute();
        $liked = true;
    }

    // Get updated like count
    $count_query = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $post_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $like_count = $count_result->fetch_assoc()['count'];

    if ($success) {
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likeCount' => $like_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update like status']);
    }
}
?>
