<?php
require_once '../config.php';

// Set JSON header before any output
header('Content-Type: application/json');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Query to fetch user data without ID
    $sql = "SELECT username, email, status, created_at FROM users";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $users = array();
    while ($row = $result->fetch_assoc()) {
        // Convert any null values to empty strings to avoid JSON encoding issues
        foreach ($row as $key => $value) {
            $row[$key] = $value === null ? "" : $value;
        }
        $users[] = $row;
    }

    echo json_encode($users);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
