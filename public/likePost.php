<?php
session_start();
require 'config.php';

if (isset($_POST['like_post_id'])) {
    $like_post_id = $_POST['like_post_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user has already liked the post
    $query = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $like_post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $like = $result->fetch_assoc();
    $stmt->close();

    if ($like) {
        // Unlike the post
        $query = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
    } else {
        // Like the post
        $query = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $like_post_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Fetch the updated like count
    $query = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $like_post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $like_count = $result->fetch_assoc()['like_count'];
    $stmt->close();

    // Return the new like count as JSON
    header('Content-Type: application/json');
    echo json_encode(['new_like_count' => $like_count]);
}
?>
