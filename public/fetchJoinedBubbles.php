<?php
session_start();
include 'config.php';

$user_id = $_SESSION['user_id']; // Assuming user ID is stored in session

$sql = "SELECT b.id, b.bubble_name FROM bubbles b
        JOIN user_bubble ub ON b.id = ub.bubble_id
        WHERE ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bubbles = [];
while ($row = $result->fetch_assoc()) {
    $bubbles[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['bubbles' => $bubbles]);
?>