<?php
// Start the session
session_start();

// Include the database configuration file
require_once 'config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the input data
        $bubble_id = $_POST['bubble_id'];
        $title = $_POST['title'];
        $message = $_POST['message'];
        $image = null;

        // Get the user ID from the session
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }
        $user_id = $_SESSION['user_id'];

        // Check if an image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Check file size (limit to 15MB)
            if ($_FILES['image']['size'] > 15728640) { // 15MB in bytes
                throw new Exception('Image file is too large. Maximum size is 15MB.');
            }
            
            // Verify it's actually an image
            $image_info = getimagesize($_FILES['image']['tmp_name']);
            if ($image_info === false) {
                throw new Exception('Uploaded file is not a valid image.');
            }
            
            $image = file_get_contents($_FILES['image']['tmp_name']);
        }

        // Prepare the SQL statement to insert the post
        $sql = "INSERT INTO bubble_posts (bubble_id, user_id, title, message, image, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        // Reconnect if connection was lost
        if (!$conn->ping()) {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iisss", $bubble_id, $user_id, $title, $message, $image);
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Close the statement
        $stmt->close();
        
        // Redirect on success
        header("Location: indexTimeline.php");
        exit();
        
    } catch (Exception $e) {
        // Log the error
        error_log("Error in addBubblePostTimeline.php: " . $e->getMessage());
        
        // If it's an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            // Otherwise, redirect with error
            header("Location: indexTimeline.php?error=" . urlencode($e->getMessage()));
        }
        exit();
    }
} else {
    // If the request method is not POST, return an error response
    header("Location: indexTimeline.php?error=" . urlencode('Invalid request method'));
    exit();
}

// Close the database connection
$conn->close();
?>