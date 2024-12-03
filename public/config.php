<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peersync";

// Create connection with adjusted settings
$conn = new mysqli($servername, $username, $password, $dbname);

// Set wait_timeout and max_allowed_packet
$conn->query("SET session wait_timeout=300"); // 5 minutes timeout
$conn->query("SET GLOBAL max_allowed_packet=16777216"); // 16MB packet size

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to handle special characters properly
$conn->set_charset("utf8mb4");
?>