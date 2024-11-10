<?php
include 'config.php';

$sql = "SELECT bubbles.bubble_name, bubbles.description, bubbles.profile_image, users.username AS creator_name FROM bubbles JOIN users ON bubbles.creator_id = users.id";
$result = $conn->query($sql);

$bubbles = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Encode the BLOB data as a base64 string
        $row['profile_image'] = 'data:image/jpeg;base64,' . base64_encode($row['profile_image']);
        $bubbles[] = $row;
    }
}

echo json_encode(['bubbles' => $bubbles]);

$conn->close();
?>