<?php
require '../config.php';

// Query to get count of active users
$query = "SELECT COUNT(*) as active_users FROM users WHERE status = 'active'";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['count' => $row['active_users']]);
} else {
    echo json_encode(['error' => 'Failed to fetch user count']);
}

$conn->close();
?>
