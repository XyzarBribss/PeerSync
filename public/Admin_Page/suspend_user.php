<?php
// suspend_user.php

// Include the database connection
include 'config.php';

// Get the posted data
$data = json_decode(file_get_contents("php://input"), true);
$identifier = $data['identifier']; // Changed to 'identifier' to match JavaScript

// Check if the identifier is provided
if (empty($identifier)) {
    echo json_encode(["success" => false, "message" => "No identifier provided."]);
    exit();
}

// Prepare SQL to suspend user by username or email
$sql = "UPDATE users SET status = 'suspended' WHERE username = ? OR email = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();

    // Check if any rows were updated
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "User suspended successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
}

$conn->close();
?>
