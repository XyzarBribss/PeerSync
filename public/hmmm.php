<?php
session_start();
require 'db_connection.php'; // Make sure to include your database connection

$logged_in_user_id = $_SESSION['user_id']; // Assuming you store the logged-in user's ID in the session

// Fetch posts from bubbles the user has joined
$query = "
    SELECT bp.*
    FROM bubble_posts bp
    JOIN user_bubbles ub ON bp.bubble_id = ub.bubble_id
    WHERE ub.user_id = :logged_in_user_id
";
$stmt = $pdo->prepare($query);
$stmt->execute(['logged_in_user_id' => $logged_in_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubble Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Bubble Posts</h1>
        <div class="list-group">
            <?php foreach ($posts as $post): ?>
                <div class="list-group-item">
                    <h5 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h5>
                    <p class="mb-1"><?php echo htmlspecialchars($post['message']); ?></p>
                    <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="img-fluid">
                    <?php endif; ?>
                    <small>Posted on <?php echo htmlspecialchars($post['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>