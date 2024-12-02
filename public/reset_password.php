<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

// Check if the email exists in the database
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No account found with this email address']);
    exit;
}

$user = $result->fetch_assoc();

// Generate a unique reset token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store the reset token in the database
$stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user['id'], $token, $expires);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to generate reset token']);
    exit;
}

// Send reset email
$resetLink = "http://localhost/PeerSync/public/reset_password_form.php?token=" . $token;
$to = $email;
$subject = "Password Reset Request - PeerSync";
$message = "Hello " . htmlspecialchars($user['username']) . ",\n\n";
$message .= "We received a request to reset your password. Click the link below to reset your password:\n\n";
$message .= $resetLink . "\n\n";
$message .= "This link will expire in 1 hour.\n\n";
$message .= "If you didn't request this, please ignore this email.\n\n";
$message .= "Best regards,\nPeerSync Team";
$headers = "From: noreply@peersync.com";

if (mail($to, $subject, $message, $headers)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send reset email']);
}
