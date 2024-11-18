<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Function to check if user is already a member
function isUserMember($conn, $user_id, $bubble_id) {
    $check_query = "SELECT id FROM bubble_members WHERE user_id = ? AND bubble_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $bubble_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_member = $result->num_rows > 0;
    $stmt->close();
    return $is_member;
}

// Handle both GET and POST requests
$input = json_decode(file_get_contents('php://input'), true);
$bubble_id = isset($_GET['bubble_id']) ? $_GET['bubble_id'] : ($input['bubble_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!$bubble_id) {
    echo json_encode(['success' => false, 'message' => 'Bubble ID not provided']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is already a member
if (isUserMember($conn, $user_id, $bubble_id)) {
    echo json_encode(['success' => false, 'message' => 'Already a member of this bubble']);
    exit();
}

// Insert the user into the bubble_members table
$insert_query = "INSERT INTO bubble_members (user_id, bubble_id, joined_date) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ii", $user_id, $bubble_id);

if ($stmt->execute()) {
    // If it's a GET request, redirect to exploreBubble.php
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: exploreBubble.php");
        exit();
    }
    // For POST requests, return JSON response
    echo json_encode(['success' => true, 'message' => 'Successfully joined the bubble']);
} else {
    $error_message = $stmt->error;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "Error: " . $error_message;
    } else {
        echo json_encode(['success' => false, 'message' => $error_message]);
    }
}

$stmt->close();
$conn->close();
?>