<?php
header('Content-Type: application/json');
require '../config.php';

try {
    // Query to get total count of users
    $query = "SELECT COUNT(*) as total_users FROM users";
    $result = $conn->query($query);

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();
    echo json_encode(['count' => intval($row['total_users'])]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user count: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
