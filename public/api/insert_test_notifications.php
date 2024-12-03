<?php
require_once '../config.php';

try {
    // Start transaction
    $conn->begin_transaction();

    // 1. Insert like notification
    $like_sql = "INSERT INTO notifications (to_user_id, from_user_id, post_id, type, is_read, created_at) 
                 SELECT 
                     bp.user_id as to_user_id,
                     1 as from_user_id,
                     bp.id as post_id,
                     'like' as type,
                     0 as is_read,
                     NOW() as created_at
                 FROM bubble_posts bp
                 WHERE bp.user_id != 1
                 LIMIT 1";
    $conn->query($like_sql);

    // 2. Insert comment notification
    $comment_sql = "INSERT INTO notifications (to_user_id, from_user_id, post_id, type, is_read, created_at)
                    SELECT 
                        bp.user_id as to_user_id,
                        2 as from_user_id,
                        bp.id as post_id,
                        'comment' as type,
                        0 as is_read,
                        NOW() as created_at
                    FROM bubble_posts bp
                    JOIN bubble_comments bc ON bp.id = bc.post_id
                    WHERE bp.user_id != 2
                    LIMIT 1";
    $conn->query($comment_sql);

    // 3. Insert follow notification
    $follow_sql = "INSERT INTO notifications (to_user_id, from_user_id, type, is_read, created_at)
                   VALUES (1, 3, 'follow', 0, NOW())";
    $conn->query($follow_sql);

    // 4. Insert mention notification
    $mention_sql = "INSERT INTO notifications (to_user_id, from_user_id, post_id, type, is_read, created_at)
                    SELECT 
                        bp.user_id as to_user_id,
                        4 as from_user_id,
                        bp.id as post_id,
                        'mention' as type,
                        0 as is_read,
                        NOW() as created_at
                    FROM bubble_posts bp
                    JOIN bubble_comments bc ON bp.id = bc.post_id
                    WHERE bp.user_id != 4
                    LIMIT 1";
    $conn->query($mention_sql);

    // Commit transaction
    $conn->commit();
    echo "Test notifications inserted successfully!";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error inserting test notifications: " . $e->getMessage();
}

$conn->close();
?>
