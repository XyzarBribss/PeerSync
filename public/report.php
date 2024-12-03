<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Check if it's a POST request with required data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check required fields
    $required_fields = ['post_id', 'post_content', 'post_owner_id', 'bubble_name', 'report_reason'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $reporter_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $post_owner_id = $_POST['post_owner_id'];
    $post_content = $_POST['post_content'];
    $bubble_name = $_POST['bubble_name'];
    $report_reason = $_POST['report_reason'];
    $report_status = 'Pending';
    $report_date = date('Y-m-d H:i:s');

    // Insert the report
    $query = "INSERT INTO reports (post_id, reporter_id, post_owner_id, report_reason, post_content, bubble_name, report_status, report_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('iiissss', $post_id, $reporter_id, $post_owner_id, $report_reason, $post_content, $bubble_name, $report_status, $report_date);
    
    if (!$stmt->execute()) {
        throw new Exception('Error submitting report: ' . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
