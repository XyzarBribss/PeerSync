<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        // Add new comment
        if (!isset($input['post_id']) || !isset($input['comment'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $post_id = intval($input['post_id']);
        $comment = trim($input['comment']);
        $parent_id = isset($input['parent_id']) ? intval($input['parent_id']) : null;

        $sql = "INSERT INTO bubble_comments (post_id, user_id, comment, parent_comment_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $post_id, $user_id, $comment, $parent_id);

        if ($stmt->execute()) {
            // Fetch the newly created comment with user details
            $comment_id = $stmt->insert_id;
            $query = "SELECT c.*, u.username, u.profile_image 
                     FROM bubble_comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $new_comment = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true, 
                'comment' => $new_comment,
                'message' => 'Comment added successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding comment']);
        }
        break;

    case 'edit':
        // Edit existing comment
        if (!isset($input['comment_id']) || !isset($input['comment'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $comment_id = intval($input['comment_id']);
        $comment = trim($input['comment']);

        // Verify ownership
        $sql = "UPDATE bubble_comments SET comment = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $comment, $comment_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating comment']);
        }
        break;

    case 'delete':
        // Delete comment
        if (!isset($input['comment_id'])) {
            echo json_encode(['success' => false, 'message' => 'Comment ID not provided']);
            exit;
        }

        $comment_id = intval($input['comment_id']);

        // First delete all replies
        $sql = "DELETE FROM bubble_comments WHERE parent_comment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();

        // Then delete the comment itself
        $sql = "DELETE FROM bubble_comments WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $comment_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting comment']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>
