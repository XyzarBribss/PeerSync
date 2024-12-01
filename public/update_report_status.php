<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $reporter_id = '';

    // First, get the reporter_id from the report
    $query = "SELECT reporter_id FROM reports WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reporter_id = $row['reporter_id'];
    }
    $stmt->close();

    // Update the report status
    $query = "UPDATE reports SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $report_id);
    
    $response = ['success' => false];
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Report status updated successfully';
        
        // If the status is "Dismissed" and we have a reporter_id, 
        // we'll redirect the reporter to their timeline with the dismissed status
        if ($status === 'Dismissed' && $reporter_id) {
            // Get the reporter's username
            $user_query = "SELECT username FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $reporter_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_row = $user_result->fetch_assoc()) {
                $response['redirect_url'] = "indexTimeline.php?report_status=dismissed";
            }
            $user_stmt->close();
        }
    } else {
        $response['error'] = 'Failed to update report status';
    }
    
    $stmt->close();
    echo json_encode($response);
    exit;
}

// If not a POST request, redirect to the reports page
header('Location: Admin_Page/index_timeline_reports.html');
exit;
?>
