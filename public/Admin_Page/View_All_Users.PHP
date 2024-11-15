<?php
require_once 'config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch user data
$sql = "SELECT id, username, email, status, created_at FROM users";
$result = $conn->query($sql);

$users = array();

if ($result->num_rows > 0) {
    // Fetch each row and add it to the users array
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Output the users array as JSON
echo json_encode($users);
$conn->close();
?>
