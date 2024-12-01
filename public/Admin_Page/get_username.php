<?php
require_once '../config.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    
    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['username' => $row['username']]);
    } else {
        echo json_encode(['username' => 'Unknown User']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'No user ID provided']);
}

$conn->close();
?>
