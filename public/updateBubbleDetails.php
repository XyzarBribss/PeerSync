<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bubble_id = $_POST['bubble_id'];
    $bubble_name = $_POST['bubble_name'];
    $profile_image = $_FILES['profile_image'] ?? null;

    $sql = "UPDATE bubbles SET bubble_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $bubble_name, $bubble_id);
    $stmt->execute();

    if ($profile_image) {
        $image_data = file_get_contents($profile_image['tmp_name']);
        $sql = "UPDATE bubbles SET profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $image_data, $bubble_id);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
}
?>
