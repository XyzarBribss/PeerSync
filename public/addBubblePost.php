<?php
// Start the session
session_start();

// Include the database configuration file
include 'config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $bubble_id = $_POST['bubble_id'];
    $user_id = $_POST['user_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $image = null;

    // Check if an image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Prepare the SQL statement to insert the post
    $sql = "INSERT INTO bubble_posts (bubble_id, user_id, title, message, image, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $bubble_id, $user_id, $title, $message, $image);

    // Execute the statement
    if ($stmt->execute()) {
        // If the post was added successfully, return a success response
        header("Location: bubblePage.php?bubble_id=" . $bubble_id);
        exit();
    } else {
        // If there was an error adding the post, return an error response
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    // If the request method is not POST, return an error response
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Close the database connection
$conn->close();
?>