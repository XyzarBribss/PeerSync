<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);

  $sender_id = $_SESSION['user_id'];
  $receiver_id = $data['receiver_id'];
  $message = $data['content'];

  if (!empty($sender_id) && !empty($receiver_id) && !empty($message)) {
    $query = "INSERT INTO direct_messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);

    if ($stmt->execute()) {
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }

    $stmt->close();
  } else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
  }
} else {
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
