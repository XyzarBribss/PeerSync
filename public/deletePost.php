<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if (!isset($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'No post ID provided']);
    exit;
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id'];

// First, verify that the user owns this post
$check_query = "SELECT user_id FROM bubble_posts WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized to delete this post']);
    exit;
}

// Delete associated likes first (foreign key constraint)
$delete_likes = "DELETE FROM post_likes WHERE post_id = ?";
$likes_stmt = $conn->prepare($delete_likes);
$likes_stmt->bind_param('i', $post_id);
$likes_stmt->execute();

// Delete associated comments
$delete_comments = "DELETE FROM bubble_comments WHERE post_id = ?";
$comments_stmt = $conn->prepare($delete_comments);
$comments_stmt->bind_param('i', $post_id);
$comments_stmt->execute();

// Finally, delete the post
$delete_query = "DELETE FROM bubble_posts WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('ii', $post_id, $user_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete post']);
}

$delete_stmt->close();
$conn->close();
?>
