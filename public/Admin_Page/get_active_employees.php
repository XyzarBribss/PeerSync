<?php
require '../config.php';

// Query to get count of active employees from Admin table
$query = "SELECT COUNT(*) as active_employees FROM Admin WHERE status = 'active'";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['count' => $row['active_employees']]);
} else {
    echo json_encode(['error' => 'Failed to fetch employee count']);
}

$conn->close();
?>
