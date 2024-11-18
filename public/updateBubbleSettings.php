<?php
$response = ['success' => false, 'new_image' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php'; // Adjust to your database connection file

    $bubbleId = $_POST['bubble_id'];
    $bubbleName = $_POST['bubble_name'];
    $newImage = null;

    // Check if an image file was uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['profile_image']['tmp_name']);
        $newImage = base64_encode($imageData);

        // Update bubble name and image
        $stmt = $conn->prepare("UPDATE bubbles SET bubble_name = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param('sbi', $bubbleName, $imageData, $bubbleId);
        $stmt->send_long_data(1, $imageData);
    } else {
        // Update only bubble name if no image uploaded
        $stmt = $conn->prepare("UPDATE bubbles SET bubble_name = ? WHERE id = ?");
        $stmt->bind_param('si', $bubbleName, $bubbleId);
    }

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        if ($newImage) {
            $response['new_image'] = $newImage; // Send back the new image to update dynamically
        }
    }
    $stmt->close();
}

echo json_encode($response);
?>
