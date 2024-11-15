<?php
require_once 'config.php'; // Include your database connection configuration

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch bubble data
$sql = "SELECT id, bubble_name, description, creator_id, created_at FROM bubbles";
$result = $conn->query($sql);

$bubbles = array(); // Initialize an array to hold bubbles data

if ($result->num_rows > 0) {
    // Fetch each row and add it to the bubbles array
    while ($row = $result->fetch_assoc()) {
        $bubbles[] = $row; // Add each bubble to the array
    }
}

// Output the bubbles array as JSON
header('Content-Type: application/json'); // Set content type to JSON
echo json_encode($bubbles); // Return the JSON-encoded data

$conn->close(); // Close the database connection
?>
