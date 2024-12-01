<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['post_id']) ? $_GET['post_id'] : null;

if (!$post_id) {
    header('Location: indexTimeline.php');
    exit;
}

// Fetch post details
$query = "SELECT bp.*, b.bubble_name FROM bubble_posts bp 
          JOIN bubbles b ON bp.bubble_id = b.id 
          WHERE bp.id = ? AND bp.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header('Location: indexTimeline.php');
    exit;
}

// Handle post update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    if (!empty($title) && !empty($message)) {
        $update_query = "UPDATE bubble_posts SET title = ?, message = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('ssii', $title, $message, $post_id, $user_id);
        
        if ($update_stmt->execute()) {
            header('Location: postDetails.php?post_id=' . $post_id);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - PeerSync</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Post</h1>
            <form action="" method="POST">
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 font-medium mb-2">Title</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?= htmlspecialchars($post['title']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required>
                </div>
                <div class="mb-6">
                    <label for="message" class="block text-gray-700 font-medium mb-2">Message</label>
                    <textarea id="message" 
                              name="message" 
                              rows="6" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              required><?= htmlspecialchars($post['message']) ?></textarea>
                </div>
                <div class="flex justify-between items-center">
                    <a href="postDetails.php?post_id=<?= $post_id ?>" 
                       class="text-gray-600 hover:text-gray-800">Cancel</a>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
