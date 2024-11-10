<?php
require 'config.php';

// Get the POST data
$post_id = $_GET['post_id'];

// Fetch comments from the database
$sql = "SELECT comment, created_at FROM bubble_comment WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = array();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($comments);
?>