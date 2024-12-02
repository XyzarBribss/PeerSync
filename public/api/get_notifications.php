<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-red-500'>Please log in to view notifications</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10;

try {
    // First, delete notifications for non-existent posts
    $delete_sql = "DELETE n FROM notifications n 
                   LEFT JOIN bubble_posts bp ON n.post_id = bp.id 
                   WHERE bp.id IS NULL AND n.post_id IS NOT NULL";
    $conn->query($delete_sql);

    // Now fetch remaining valid notifications
    $sql = "SELECT n.*, 
            u.username, 
            bp.title as post_title, 
            bp.id as post_id,
            n.type
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.id
            LEFT JOIN bubble_posts bp ON n.post_id = bp.id
            WHERE n.to_user_id = ? AND (bp.id IS NOT NULL OR n.post_id IS NULL)
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<div class='text-gray-500 text-center p-4'>No notifications</div>";
    } else {
        while ($row = $result->fetch_assoc()) {
            $username = htmlspecialchars($row['username'] ?? 'Someone');
            $postTitle = htmlspecialchars($row['post_title'] ?? 'a post');
            $bgColor = $row['is_read'] ? 'bg-white' : 'bg-blue-50';
            $postId = (int)$row['post_id'];
            $type = $row['type'] ?? 'interaction';

            // Only create clickable link if we have a valid post ID
            if ($postId > 0) {
                echo "<a href='postDetails.php?post_id={$postId}' class='block'>
                        <div class='notification-item p-3 {$bgColor} border-b hover:bg-gray-50'>
                            <div class='text-sm'>
                                <b>{$username}</b> {$type} your post: {$postTitle}
                            </div>
                        </div>
                      </a>";
            } else {
                echo "<div class='notification-item p-3 {$bgColor} border-b'>
                        <div class='text-sm'>
                            <b>{$username}</b> {$type} a post (no longer available)
                        </div>
                      </div>";
            }
        }
    }
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    echo "<div class='text-red-500 text-center p-4'>Error loading notifications</div>";
}

$conn->close();
?>
