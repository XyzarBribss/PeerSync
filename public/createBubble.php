<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Handle the case where the user is not logged in
    // For example, redirect to the login page or show an error message
    header("Location: login.php");
    exit();
}

include 'config.php'; // Include the database configuration file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bubble_name = $_POST['bubble_name'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $creator_id = $_SESSION['user_id'];

    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $profile_image = file_get_contents($_FILES['profile_image']['tmp_name']);
    }

    // Prepare the SQL statement to insert the bubble
    $sql = "INSERT INTO bubbles (bubble_name, description, creator_id, profile_image, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $response = ['success' => false, 'error' => $conn->error];
        echo json_encode($response);
        exit;
    }

    // Bind parameters
    $stmt->bind_param('ssiss', $bubble_name, $description, $creator_id, $profile_image, $status);

    // Execute the statement
    if ($stmt->execute()) {
        // Get the ID of the newly created bubble
        $bubble_id = $stmt->insert_id;

        // Insert the user as a member of the new bubble
        $sql_user_bubble = "INSERT INTO user_bubble (user_id, bubble_id) VALUES (?, ?)";
        $stmt_user_bubble = $conn->prepare($sql_user_bubble);
        if ($stmt_user_bubble === false) {
            $response = ['success' => false, 'error' => $conn->error];
            echo json_encode($response);
            exit;
        }

        // Bind parameters for user_bubble
        $stmt_user_bubble->bind_param('ii', $creator_id, $bubble_id);

        // Execute the statement
        if ($stmt_user_bubble->execute()) {
            $response = ['success' => true];
        } else {
            $response = ['success' => false, 'error' => $stmt_user_bubble->error];
        }

        // Close the user_bubble statement
        $stmt_user_bubble->close();
    } else {
        $response = ['success' => false, 'error' => $stmt->error];
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Return the response as JSON
    echo json_encode($response);
}
?>