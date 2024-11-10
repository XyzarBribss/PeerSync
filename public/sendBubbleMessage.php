<?php
session_start();
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$bubble_id = $data['bubble_id'];
$user_id = $data['user_id'];
$message = $data['message'];

$sql = "INSERT INTO bubble_message (bubble_id, user_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $bubble_id, $user_id, $message);
$success = $stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?>