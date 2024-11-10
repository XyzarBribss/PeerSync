<?php
session_start();
include 'config.php';

$bubble_id = $_GET['bubble_id'];

$sql = "SELECT bm.message, bm.timestamp, u.username, u.profile_image 
    FROM bubble_message bm
    JOIN users u ON bm.user_id = u.id
    WHERE bm.bubble_id = ?
    ORDER BY bm.timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bubble_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['messages' => $messages]);
?>