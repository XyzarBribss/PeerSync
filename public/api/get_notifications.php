<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Please log in to view notifications']);
    exit;
}

$user_id = $_SESSION['user_id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10;

try {
    // Get total unread count for this user
    $unread_sql = "SELECT COUNT(*) as unread_count 
                   FROM notifications 
                   WHERE to_user_id = ? 
                   AND is_read = 0";
    $unread_stmt = $conn->prepare($unread_sql);
    $unread_stmt->bind_param("i", $user_id);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result();
    $unread_count = $unread_result->fetch_assoc()['unread_count'];

    // Get notifications with user and post details
    $sql = "SELECT 
            n.*,
            bp.title as post_title,
            bp.message as post_message,
            bp.image as post_image,
            bp.bubble_id,
            u.username as from_username
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.id
            LEFT JOIN bubble_posts bp ON n.post_id = bp.id
            WHERE n.to_user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Format the notification data
        $notification = [
            'id' => $row['id'],
            'type' => $row['type'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'from_user' => [
                'username' => $row['from_username']
            ],
            'post' => null
        ];

        // Add post details if present
        if ($row['post_id']) {
            $notification['post'] = [
                'id' => $row['post_id'],
                'title' => $row['post_title'],
                'message' => $row['post_message'],
                'image' => $row['post_image'],
                'bubble_id' => $row['bubble_id']
            ];
        }

        $notifications[] = $notification;
    }

    $has_more = count($notifications) === $limit;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'has_more' => $has_more
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
