<?php
session_start();
require_once 'config.php'; // Include your database configuration file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username and password are not empty
    if (!empty($username) && !empty($password)) {
        // Prepare SQL to retrieve user credentials and status
        $sql = "SELECT * FROM users WHERE username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if the account is suspended
            if ($user['status'] === 'suspended') {
                echo "<script>alert('Your account is suspended. Please contact support.'); window.location.href = 'indexLogin.html';</script>";
            } else {
                // Validate password or check if user is logging in with Google
                if (password_verify($password, $user['password']) || $user['password'] === null) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Update user status to active
                    $updateStatusSql = "UPDATE users SET status='active' WHERE id=?";
                    $updateStmt = $conn->prepare($updateStatusSql);
                    $updateStmt->bind_param("i", $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    // Redirect to dashboard.html if username is 'admin', else redirect to indexTimeline.php
                    if ($username === 'admin') {
                        header("Location: ../Admin_Page/dashboard.html");
                    } else {
                        header("Location: ../public/indexTimeline.php");
                    }
                    exit();
                } else {
                    echo "<script>alert('Invalid username or password'); window.location.href = 'indexLogin.html';</script>";
                }
            }
        } else {
            echo "<script>alert('Invalid username or password'); window.location.href = 'indexLogin.html';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.'); window.location.href = 'indexLogin.html';</script>";
    }
}

$conn->close();
?>