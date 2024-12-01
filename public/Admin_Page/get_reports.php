<?php
session_start();
require '../config.php';

// Fetch all reports with related information
$query = "
    SELECT 
        r.report_id,
        r.post_id,
        r.report_reason,
        r.post_content,
        r.bubble_name,
        r.report_status,
        r.report_date,
        reporter.username as reporter_username,
        post_owner.username as post_owner_username
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN users post_owner ON r.post_owner_id = post_owner.id
    ORDER BY r.report_date DESC
";

try {
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception($conn->error);
    }
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($reports);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>