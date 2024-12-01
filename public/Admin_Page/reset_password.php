<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Check if username and password are provided
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

// Validate password length
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update the user's password in the database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $hashedPassword, $username);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update password: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("User not found");
    }

    // Return success response
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
