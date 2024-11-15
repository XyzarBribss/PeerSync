<?php
// Include your existing database connection
require_once 'config.php';  // This will use your config.php for DB connection

// Fetch the latest revenue and sales from dashboard_metrics
$sql_metrics = "SELECT revenue, sales, last_updated FROM dashboard_metrics ORDER BY last_updated DESC LIMIT 1";
$result_metrics = $conn->query($sql_metrics);

// Fetch the total number of users
$sql_users = "SELECT COUNT(*) as total_users FROM users";
$result_users = $conn->query($sql_users);

// Fetch the number of employees, assuming 'role' is used to identify employees
$sql_employees = "SELECT COUNT(*) as total_employees FROM users WHERE role = 'employee'";
$result_employees = $conn->query($sql_employees);

$data = array();

// Check if metrics data exists
if ($result_metrics->num_rows > 0) {
    $row_metrics = $result_metrics->fetch_assoc();
    $data['revenue'] = $row_metrics['revenue'];
    $data['sales'] = $row_metrics['sales'];
    $data['last_updated'] = $row_metrics['last_updated'];
} else {
    // Default values if no metrics are found
    $data['revenue'] = 0.00;
    $data['sales'] = 0;
    $data['last_updated'] = date("Y-m-d H:i:s");
}

// Check if users data exists
if ($result_users->num_rows > 0) {
    $row_users = $result_users->fetch_assoc();
    $data['total_users'] = $row_users['total_users'];
} else {
    $data['total_users'] = 0;
}

// Check if employees data exists
if ($result_employees->num_rows > 0) {
    $row_employees = $result_employees->fetch_assoc();
    $data['total_employees'] = $row_employees['total_employees'];
} else {
    $data['total_employees'] = 0;
}

// Return all the data as JSON
echo json_encode($data);

// Close connection
$conn->close();
?>
