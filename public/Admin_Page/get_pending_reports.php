<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    // Query to count pending reports (where report_status is NULL or empty)
    $query = "SELECT COUNT(*) as count FROM reports WHERE report_status IS NULL OR report_status = '' OR report_status = 'pending'";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    echo json_encode(['count' => (int)$row['count']]);
} catch (Exception $e) {
    error_log('Error in get_pending_reports.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
