<?php
// Example: Update a bubble group (e.g., description)
$bubble_id = 1; // Example bubble ID
$new_description = "Updated description for the bubble.";

$sql = "UPDATE bubbles SET description = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_description, $bubble_id);

if ($stmt->execute()) {
    echo "Bubble group updated successfully.";
} else {
    echo "Error updating bubble: " . $conn->error;
}

$stmt->close();
?>
