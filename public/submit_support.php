<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get user info from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous';

// Get form data
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate input
if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

if (empty($subject)) {
    echo json_encode(['success' => false, 'error' => 'Subject is required']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Valid email is required']);
    exit;
}

try {
    // Create support_tickets table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $conn->query($sql);

    // Insert the support ticket
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, username, email, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $username, $email, $subject, $message);
    
    if ($stmt->execute()) {
        // Send email notification to admin (you can configure this with your admin email)
        $admin_email = "admin@peersync.com"; // Change this to your admin email
        $email_subject = "New Support Ticket: " . $subject;
        $email_message = "A new support ticket has been submitted:\n\n";
        $email_message .= "From: $username ($email)\n";
        $email_message .= "Subject: $subject\n";
        $email_message .= "Message: $message\n";
        
        // Uncomment this when you have configured your email settings
        // mail($admin_email, $email_subject, $email_message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Your support ticket has been submitted successfully. We will respond to your email shortly.'
        ]);
    } else {
        throw new Exception("Failed to submit support ticket");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while submitting your support ticket. Please try again later.'
    ]);
}
