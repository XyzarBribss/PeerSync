<?php
session_start();
$user_id = $_SESSION['user_id']; // Assuming user ID is stored in session
include 'config.php';

// Fetch joined bubbles
$sql = "SELECT b.id, b.bubble_name, b.description, b.creator_id, b.profile_image, b.created_at 
        FROM bubbles b
        JOIN user_bubble ub ON b.id = ub.bubble_id
        WHERE ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bubbles = [];
while ($row = $result->fetch_assoc()) {
    $row['profile_image'] = base64_encode($row['profile_image']); // Encode profile_image as base64
    $bubbles[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['bubbles' => $bubbles]);
?>