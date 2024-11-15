<?php
// Example: Delete a bubble group
$bubble_id = 1; // Example bubble ID to delete

$sql = "DELETE FROM bubbles WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bubble_id);

if ($stmt->execute()) {
    echo "Bubble group deleted successfully.";
} else {
    echo "Error deleting bubble: " . $conn->error;
}

$stmt->close();
?>