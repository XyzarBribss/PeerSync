<?php
// Start session
session_start();

// Include database connection
include 'config.php';

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare SQL query to prevent SQL injection
    $sql = "SELECT * FROM Admin WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        // User found, redirect to dashboard
        $_SESSION['username'] = $username;

        // Send success response for your JavaScript to handle redirection
        echo "success";
    } else {
        // Invalid credentials
        echo "Invalid username or password.";
    }

    // Clean up
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
