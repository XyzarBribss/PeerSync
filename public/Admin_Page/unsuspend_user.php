<?php
require_once 'config.php';
header('Content-Type: application/json');

// Retrieve the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Check if username is provided
if (isset($data['username'])) {
    $username = $data['username'];

    // Prepare and execute the SQL query to update the user's status based on username
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE username = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            // Success response
            echo json_encode(['success' => true, 'message' => 'User unsuspended successfully']);
        } else {
            // Error response if no rows were affected (user not found)
            echo json_encode(['success' => false, 'message' => 'User not found or already active']);
        }
    } else {
        // Error response
        echo json_encode(['success' => false, 'message' => 'Failed to unsuspend user']);
    }
    
    $stmt->close();
} else {
    // Error response if username is not provided
    echo json_encode(['success' => false, 'message' => 'Username not provided']);
}

$conn->close();
?>
