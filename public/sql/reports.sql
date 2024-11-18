-- Create reports table
CREATE TABLE IF NOT EXISTS reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    reporter_id INT NOT NULL,
    post_owner_id INT NOT NULL,
    report_reason VARCHAR(255) NOT NULL,
    post_content TEXT NOT NULL,
    bubble_name VARCHAR(255) NOT NULL,
    report_status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    report_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES bubble_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id),
    FOREIGN KEY (post_owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_post_id ON reports(post_id);
CREATE INDEX idx_reporter_id ON reports(reporter_id);
CREATE INDEX idx_post_owner_id ON reports(post_owner_id);
CREATE INDEX idx_report_status ON reports(report_status);
CREATE INDEX idx_report_date ON reports(report_date);
