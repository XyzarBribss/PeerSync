<?php
session_start();
include 'config.php';

if (isset($_GET['bubble_id'])) {
    $bubble_id = $_GET['bubble_id'];
    $user_id = $_SESSION['user_id'];
    $joined_at = date('Y-m-d H:i:s');

    // Insert the user into the user_bubble table
    $sql = "INSERT INTO user_bubble (user_id, bubble_id, joined_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $bubble_id, $joined_at);

    if ($stmt->execute()) {
        // Redirect to the exploreBubble.php page
        header("Location: exploreBubble.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>