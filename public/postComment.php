<?php
session_start();
require 'config.php';

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];
$comment = $_POST['comment'];

if (empty($post_id) || empty($user_id) || empty($comment)) {
    echo "All fields are required.";
    exit();
}

if (isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id'])) {
    $parent_comment_id = $_POST['parent_comment_id'];
    $sql = "INSERT INTO bubble_comments (post_id, user_id, comment, parent_comment_id, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        exit();
    }
    $stmt->bind_param('iisi', $post_id, $user_id, $comment, $parent_comment_id);
} else {
    $sql = "INSERT INTO bubble_comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        exit();
    }
    $stmt->bind_param('iis', $post_id, $user_id, $comment);
}

if ($stmt->execute()) {
    header("Location: postDetails.php?post_id=" . $post_id);
    exit();
} else {
    echo "Error executing statement: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
