<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['title']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$title = trim($_POST['title']);
$message = trim($_POST['message']);
$remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

// Verify that the user owns this post
$check_query = "SELECT user_id, image FROM bubble_posts WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized to edit this post']);
    exit;
}

// Handle image upload
$image_data = null;
$image_updated = false;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // New image uploaded
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG and GIF are allowed.']);
        exit;
    }
    
    $image_data = file_get_contents($_FILES['image']['tmp_name']);
    $image_updated = true;
} elseif ($remove_image) {
    // Remove existing image
    $image_updated = true;
}

// Update the post
if ($image_updated) {
    $update_query = "UPDATE bubble_posts SET title = ?, message = ?, image = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('sssii', $title, $message, $image_data, $post_id, $user_id);
} else {
    $update_query = "UPDATE bubble_posts SET title = ?, message = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ssii', $title, $message, $post_id, $user_id);
}

if ($update_stmt->execute()) {
    $response = [
        'success' => true,
        'post' => [
            'id' => $post_id,
            'title' => $title,
            'message' => $message
        ]
    ];
    
    // If image was updated, include the new image URL
    if ($image_updated) {
        if ($image_data) {
            $response['post']['image_url'] = 'data:image/jpeg;base64,' . base64_encode($image_data);
        } else {
            $response['post']['image_url'] = null;
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update post']);
}

$update_stmt->close();
$conn->close();
?>
