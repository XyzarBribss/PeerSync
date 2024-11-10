<?php
session_start();
$user_id = $_SESSION['user_id']; // Assuming user ID is stored in session
include 'config.php';

$sql = "SELECT id, username FROM users WHERE id != ? ORDER BY RAND() LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['users' => $users]);
?>