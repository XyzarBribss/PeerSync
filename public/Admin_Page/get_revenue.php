<?php
require 'config.php';

header('Content-Type: application/json'); // Set the content type to JSON

// Initialize an empty array to hold revenue values
$revenues = [];

// Create a SQL query to select the Revenue column
$sql = "SELECT Revenue FROM Revenue";

// Execute the query
$result = $conn->query($sql);

// Check if there are results
if ($result->num_rows > 0) {
    // Fetch all revenue values into the array
    while ($row = $result->fetch_assoc()) {
        $revenues[] = $row['Revenue'];
    }
} else {
    echo json_encode(['error' => 'No results found.']);
    exit;
}

// Close the database connection
$conn->close();

// Output the revenue values as JSON
echo json_encode(['revenue' => $revenues]);
?>
