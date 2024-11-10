<?php
session_start();
include 'config.php';

$bubble_id = $_GET['bubble_id'];

// Fetch posts from the bubble_posts table
$sql = "SELECT bp.id as post_id, bp.title, bp.message, bp.image, bp.created_at, u.username 
    FROM bubble_posts bp
    JOIN users u ON bp.user_id = u.id
    WHERE bp.bubble_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bubble_id);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    if (!is_null($row['image'])) {
        $row['image'] = base64_encode($row['image']);
    } else {
        $row['image'] = 'default_image_path.jpg'; // Set your default image path here
    }
    $posts[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['posts' => $posts]);
?>