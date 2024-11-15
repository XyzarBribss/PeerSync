<?php
// Example: Change a bubble's status (suspend/activate)
$bubble_id = 1; // Example bubble ID
$new_status = 'suspended'; // Status could be 'active' or 'suspended'

$sql = "UPDATE bubbles SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_status, $bubble_id);

if ($stmt->execute()) {
    echo "Bubble group status updated to " . $new_status;
} else {
    echo "Error updating status: " . $conn->error;
}

$stmt->close();
?>
