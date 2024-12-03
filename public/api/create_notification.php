<?php
session_start();
require_once '../config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function debugResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'debug_info' => $data
    ];
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    debugResponse(false, 'Unauthorized', ['error' => 'No user session found']);
}

// First, verify the notifications table exists
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check->num_rows == 0) {
    // Create the notifications table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        post_id INT,
        comment_id INT,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(id),
        FOREIGN KEY (to_user_id) REFERENCES users(id),
        FOREIGN KEY (post_id) REFERENCES bubble_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (comment_id) REFERENCES bubble_comments(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($create_table_sql)) {
        debugResponse(false, 'Failed to create notifications table', ['sql_error' => $conn->error]);
    }
}

function createNotification($conn, $from_user_id, $to_user_id, $type, $post_id = null, $comment_id = null) {
    try {
        // Generate appropriate message based on type
        switch ($type) {
            case 'like':
                $message = 'liked your post';
                break;
            case 'comment':
                $message = 'commented on your post';
                break;
            case 'follow':
                $message = 'started following you';
                break;
            case 'mention':
                $message = 'mentioned you in a comment';
                break;
            default:
                $message = 'interacted with your post';
        }

        // Build SQL based on available fields
        if ($comment_id) {
            $sql = "INSERT INTO notifications (from_user_id, to_user_id, type, post_id, comment_id, message, is_read, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'error' => 'SQL Prepare Error (comment): ' . $conn->error];
            }
            $stmt->bind_param("iisiis", $from_user_id, $to_user_id, $type, $post_id, $comment_id, $message);
        } else if ($post_id) {
            $sql = "INSERT INTO notifications (from_user_id, to_user_id, type, post_id, message, is_read, created_at) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'error' => 'SQL Prepare Error (post): ' . $conn->error];
            }
            $stmt->bind_param("iisis", $from_user_id, $to_user_id, $type, $post_id, $message);
        } else {
            $sql = "INSERT INTO notifications (from_user_id, to_user_id, type, message, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 0, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'error' => 'SQL Prepare Error (basic): ' . $conn->error];
            }
            $stmt->bind_param("iiss", $from_user_id, $to_user_id, $type, $message);
        }

        $result = $stmt->execute();
        if (!$result) {
            return ['success' => false, 'error' => 'SQL Execute Error: ' . $stmt->error];
        }
        
        return [
            'success' => true,
            'notification_id' => $conn->insert_id,
            'debug_info' => [
                'sql' => $sql,
                'params' => [
                    'from_user_id' => $from_user_id,
                    'to_user_id' => $to_user_id,
                    'type' => $type,
                    'post_id' => $post_id,
                    'comment_id' => $comment_id,
                    'message' => $message
                ]
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}

// Get POST data
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!isset($data['type']) || !isset($data['to_user_id'])) {
    debugResponse(false, 'Missing required fields', ['received_data' => $data]);
}

$from_user_id = $_SESSION['user_id'];
$to_user_id = $data['to_user_id'];
$type = $data['type'];
$post_id = isset($data['post_id']) ? $data['post_id'] : null;
$comment_id = isset($data['comment_id']) ? $data['comment_id'] : null;

try {
    // Don't create notification if user is notifying themselves
    if ($from_user_id === $to_user_id) {
        debugResponse(true, 'Self notification skipped', [
            'from_user_id' => $from_user_id,
            'to_user_id' => $to_user_id
        ]);
    }

    $result = createNotification($conn, $from_user_id, $to_user_id, $type, $post_id, $comment_id);
    
    if ($result['success']) {
        debugResponse(true, 'Notification created successfully', $result['debug_info']);
    } else {
        debugResponse(false, 'Failed to create notification', ['error' => $result['error']]);
    }
} catch (Exception $e) {
    debugResponse(false, 'Server error', ['error' => $e->getMessage()]);
}

$conn->close();
?>
