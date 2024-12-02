-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    to_user_id INT NOT NULL,
    from_user_id INT,
    post_id INT,
    comment_id INT,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (post_id) REFERENCES bubble_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES bubble_comments(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_notifications_to_user ON notifications(to_user_id);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_post_id ON notifications(post_id);
CREATE INDEX idx_notifications_comment_id ON notifications(comment_id);

-- Sample notifications for testing
INSERT INTO notifications (to_user_id, from_user_id, post_id, message, type) 
SELECT 
    bp.user_id as to_user_id,
    1 as from_user_id,
    bp.id as post_id,
    'liked your post' as message,
    'like' as type
FROM bubble_posts bp
LIMIT 1;

INSERT INTO notifications (to_user_id, from_user_id, post_id, comment_id, message, type)
SELECT 
    bp.user_id as to_user_id,
    2 as from_user_id,
    bp.id as post_id,
    bc.id as comment_id,
    'commented on your post' as message,
    'comment' as type
FROM bubble_posts bp
JOIN bubble_comments bc ON bp.id = bc.post_id
LIMIT 1;

INSERT INTO notifications (to_user_id, from_user_id, message, type)
VALUES (1, 3, 'started following you', 'follow');

INSERT INTO notifications (to_user_id, from_user_id, post_id, comment_id, message, type)
SELECT 
    bp.user_id as to_user_id,
    4 as from_user_id,
    bp.id as post_id,
    bc.id as comment_id,
    'mentioned you in a comment' as message,
    'mention' as type
FROM bubble_posts bp
JOIN bubble_comments bc ON bp.id = bc.post_id
LIMIT 1;
