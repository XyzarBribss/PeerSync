<?php
session_start();
include 'config.php';

$receiver_id = $_GET['receiver_id'];
$sender_id = $_SESSION['user_id']; // Assuming user ID is stored in session

// Fetch receiver's name
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$result = $stmt->get_result();
$receiver = $result->fetch_assoc();
$receiver_name = $receiver['username'];
$stmt->close();

// Fetch messages
$sql = "SELECT dm.message, dm.timestamp, u.username, u.profile_image
        FROM direct_messages dm
        JOIN users u ON dm.sender_id = u.id
        WHERE (dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?)
        ORDER BY dm.timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['messages' => $messages, 'receiver_name' => $receiver_name]);
?>