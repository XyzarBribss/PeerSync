<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 10;
$offset = ($page - 1) * $posts_per_page;
$logged_in_user_id = $_SESSION['user_id'];

// Fetch posts with pagination
$query = "
    SELECT bp.*, u.username, u.profile_image, b.bubble_name AS bubble_name,
           (SELECT COUNT(*) FROM bubble_comments WHERE post_id = bp.id) as comment_count,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = bp.id) as like_count
    FROM bubble_posts bp
    JOIN user_bubble ub ON bp.bubble_id = ub.bubble_id
    JOIN users u ON bp.user_id = u.id
    JOIN bubbles b ON bp.bubble_id = b.id
    WHERE ub.user_id = ?
    ORDER BY bp.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $logged_in_user_id, $posts_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['posts' => $posts]);
?>
