<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// Check if user has already liked the post
$check_query = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $post_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$existing_like = $result->fetch_assoc();
$check_stmt->close();

if ($existing_like) {
    // Unlike: Remove the like
    $delete_query = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $post_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
} else {
    // Like: Add new like
    $insert_query = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $post_id, $user_id);
    $insert_stmt->execute();
    $insert_stmt->close();
}

// Get updated like count
$count_query = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $post_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$like_count = $count_result->fetch_assoc()['count'];
$count_stmt->close();

echo json_encode([
    'success' => true,
    'likes' => $like_count
]);