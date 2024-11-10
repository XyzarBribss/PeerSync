<?php
session_start();
include 'config.php'; // Ensure this file correctly sets up the $conn variable

// Check if the post_id is provided
if (!isset($_GET['post_id'])) {
    echo "Post ID not provided.";
    exit();
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id'];

// Fetch user data from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch the post details
$query = "
    SELECT bp.*, u.username, u.profile_image AS user_profile_image, b.bubble_name AS bubble_name, b.profile_image AS bubble_profile_image, b.description AS bubble_description, b.created_at AS bubble_created_at
    FROM bubble_posts bp
    JOIN users u ON bp.user_id = u.id
    JOIN bubbles b ON bp.bubble_id = b.id
    WHERE bp.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

// Check if the post exists
if (!$post) {
    echo "Post not found.";
    exit();
}

// Convert the image to base64 if it exists
$image_base64 = '';
if (!empty($post['image'])) {
    $image_base64 = 'data:image/jpeg;base64,' . base64_encode($post['image']);
}

// Convert the bubble profile image to base64 if it exists
$profile_image_base64 = '';
if (!empty($post['bubble_profile_image'])) {
    $profile_image_base64 = 'data:image/jpeg;base64,' . base64_encode($post['bubble_profile_image']);
}

// Fetch the comments for the post
$query = "
    SELECT c.*, u.username, u.profile_image
    FROM bubble_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);

// Fetch the number of comments for the post
$comment_query = "SELECT COUNT(*) AS comment_count FROM bubble_comments WHERE post_id = ?";
$comment_stmt = $conn->prepare($comment_query);
$comment_stmt->bind_param('i', $post['id']);
$comment_stmt->execute();
$comment_result = $comment_stmt->get_result();
$comment_count = $comment_result->fetch_assoc()['comment_count'];

// Close the statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubble Posts</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .content { margin-top: 64px; margin-left: 80px; transition: margin-left 0.3s; }
        .right-sidebar { position: fixed; right: 0; height: calc(100% - 64px); overflow-y: auto; z-index: 100; margin-top: 80px; }
    </style>
</head>
<body class="bg-blue-50">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white  flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;}">
        <div class="flex items-center">
            <a href="indexTimeline.php"><img src="../public/ps.png" alt="Peerync Logo" class="h-16 w-16"></a>
            <span class="text-2xl font-bold">PeerSync</span>
        </div>
        <div class="flex items-center">
            <a href="exploreBubble.php" class="ml-4 hover:bg-blue-400 p-2 rounded">
                <i class="fas fa-globe fa-lg"></i>
            </a>
            <a href="indexBubble.php" class="ml-4 hover:bg-blue-400 p-2 rounded">
                <i class="fas fa-comments fa-lg"></i>
            </a>
            <a href="notebook.php" class="ml-4 hover:bg-blue-400 p-2 rounded">
                <i class="fas fa-book fa-lg"></i>
            </a>
            <div class="relative ml-4 p-4">
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="w-10 h-10 rounded-full cursor-pointer" id="profileImage">
                <div class="dropdown-menu absolute right-0 mt-1 w-48 bg-white border border-gray-300 rounded shadow-lg hidden">
                    <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                    <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Leftmost Sidebar -->
    <div id="sidebar" class="fixed top-0 left-0 h-full mt-10 text-white z-50 flex flex-col items-center sidebar transition-all duration-300 shadow-lg border-r border-gray-300" style="width: 64px; background-color: rgb(70 130 180 / 50%);">
        <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
    </div>

<!-- Main Container -->
<div id="main-content" class="content pt-8 ">
    <div class="flex justify-center mr-80 mt-4">
        <div class="p-4 mx-auto w-full max-w-4xl">
            <div class="space-y-4">
                <div class="bg-white p-4 shadow rounded mb-4">
                    <div class="flex items-center mb-4">
                        <img src="<?= htmlspecialchars($post['user_profile_image']) ?>" alt="<?= htmlspecialchars($post['username']) ?>" class="w-10 h-10 rounded-full mr-3">
                        <div>
                            <div class="text-gray-700 font-bold"><?= htmlspecialchars($post['username']) ?></div>
                            <div class="text-gray-500 text-sm"><?= htmlspecialchars($post['bubble_name']) ?> • <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?></div>
                        </div>
                        <div class="relative ml-auto">
                            <?php if ($post['user_id'] == $user_id): ?>
                                <button type="button" class="inline-flex justify-center shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none" id="menu-button-<?= $post['id'] ?>" aria-expanded="false" aria-haspopup="menu">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="menu-button-<?= $post['id'] ?>" tabindex="-1" id="menu-<?= $post['id'] ?>">
                                    <div class="py-1" role="none">
                                        <a href="editPost.php?post_id=<?= htmlspecialchars($post_id) ?>" class="text-gray-700 block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-1">Edit</a>
                                        <a href="deletePost.php?post_id=<?= htmlspecialchars($post_id) ?>" class="text-gray-700 block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-2">Delete</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="postDetails.php?post_id=<?= $post['id'] ?>" class="block">
                        <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="text-gray-700 mb-4"><?= htmlspecialchars($post['message']) ?></p>
                        <?php if (!empty($image_base64)): ?>
                            <img src="<?= $image_base64 ?>" alt="Post Image" class="w-full h-auto rounded mb-4">
                        <?php endif; ?>
                    </a>
                    <div class="flex items-center justify-between text-gray-500 text-sm">
                        <div class="flex justify-between w-full">
                            <button class="flex items-center space-x-1">
                                <i class="fas fa-thumbs-up"></i>
                                <span>Like</span>
                            </button>
                            <button class="flex items-center space-x-1">
                                <i class="fas fa-comment"></i>
                                <span>Comment (<?= $comment_count ?>)</span>
                            </button>
                            <button class="flex items-center space-x-1">
                                <i class="fas fa-flag"></i>
                                <span>Report</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comment form -->
                <div class="bg-white rounded-lg shadow-md mb-4">
                    <div class="p-4">
                        <h3 class="font-bold mb-2">Comment right here</h3>
                        <form action="postComment.php" method="post">
                            <textarea name="comment" class="w-full p-2 border rounded" rows="4" placeholder="What are your thoughts?"></textarea>
                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post_id) ?>">
                            <input type="hidden" name="bubble_id" value="<?= htmlspecialchars($post['bubble_id']) ?>">
                            <button type="submit" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Comment</button>
                        </form>
                    </div>
                </div>

                <!-- Display comments -->
                <div class="bg-white p-4 shadow rounded mb-4">
                    <h3 class="font-bold mb-2">Comments</h3>
                    <?php foreach ($comments as $comment): ?>
                        <?php if (empty($comment['parent_comment_id'])): ?>
                            <div class="mb-4 bg-gray-100 p-2 rounded">
                                <div class="flex items-center mb-2">
                                    <img src="<?= htmlspecialchars($comment['profile_image']) ?>" alt="<?= htmlspecialchars($comment['username']) ?>" class="w-8 h-8 rounded-full mr-2">
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($comment['username']) ?>:</p>
                                </div>
                                <p class="mt-1"><?= htmlspecialchars($comment['comment']) ?></p>
                                <button class="text-gray-500 text-sm" onclick="toggleReplies(<?= $comment['id'] ?>)">Like </button>
                                <button class="text-gray-500 text-sm" onclick="toggleReplies(<?= $comment['id'] ?>)">Replies</button>
                                <div id="replies-<?= $comment['id'] ?>" class="ml-4 hidden">
                                    <?php
                                    $reply_query = "
                                        SELECT c.*, u.username, u.profile_image
                                        FROM bubble_comments c
                                        JOIN users u ON c.user_id = u.id
                                        WHERE c.parent_comment_id = ?
                                        ORDER BY c.created_at ASC
                                    ";
                                    $reply_stmt = $conn->prepare($reply_query);
                                    $reply_stmt->bind_param('i', $comment['id']);
                                    $reply_stmt->execute();
                                    $reply_result = $reply_stmt->get_result();
                                    $replies = $reply_result->fetch_all(MYSQLI_ASSOC);
                                    foreach ($replies as $reply): ?>
                                        <div class="mb-4 bg-gray-200 p-2 rounded">
                                            <div class="flex items-center mb-2">
                                                <img src="<?= htmlspecialchars($reply['profile_image']) ?>" alt="<?= htmlspecialchars($reply['username']) ?>" class="w-8 h-8 rounded-full mr-2">
                                                <p class="text-sm text-gray-500"><?= htmlspecialchars($reply['username']) ?>:</p>
                                            </div>
                                            <p class="mt-1"><?= htmlspecialchars($reply['comment']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                    <!-- Reply form -->
                                    <form action="postComment.php" method="post" class="mt-2">
                                        <textarea name="comment" class="w-full p-2 border rounded" rows="2" placeholder="Reply to this comment"></textarea>
                                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post_id) ?>">
                                        <input type="hidden" name="bubble_id" value="<?= htmlspecialchars($post['bubble_id']) ?>">
                                        <input type="hidden" name="parent_comment_id" value="<?= htmlspecialchars($comment['id']) ?>">
                                        <button type="submit" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Reply</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleReplies(commentId) {
    const repliesDiv = document.getElementById(`replies-${commentId}`);
    repliesDiv.classList.toggle('hidden');
}
</script>

                </div>
            </div>

            <!-- About Bubble Section -->
            <aside class="w-64 bg-white p-4 overflow-auto fixed right-0 top-16 h-full shadow-lg">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold mb-2">About Bubble</h2>
                        <div class="flex items-center mb-4">
                            <?php if (!empty($profile_image_base64)): ?>
                                <img src="<?= $profile_image_base64 ?>" alt="<?= htmlspecialchars($post['bubble_name']) ?>" class="w-10 h-10 rounded-full mr-2">
                            <?php else: ?>
                                <img src="default-profile.png" alt="Default Profile Image" class="w-10 h-10 rounded-full mr-2">
                            <?php endif; ?>
                            <span class="font-bold"><?= htmlspecialchars($post['bubble_name']) ?></span>
                        </div>
                        <p class="text-sm mb-4"><?= htmlspecialchars($post['bubble_description']) ?></p>
                        <p class="text-xs text-gray-500">Created: <?= htmlspecialchars($post['bubble_created_at']) ?></p>
                        <button class="mt-4 w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Post</button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        document.getElementById('profileImage').addEventListener('click', function() {
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('hidden');
        });

        // Fetch the list of bubbles the user has joined
function fetchJoinedBubbles() {
    fetch("joinedBubble.php")
    .then(response => response.json())
    .then(data => {
        const bubbleList = document.getElementById("bubble-list");
        bubbleList.innerHTML = "";
        data.bubbles.forEach(bubble => {
            const bubbleItem = document.createElement("li");
            bubbleItem.className = "bubble-container relative";
            bubbleItem.innerHTML = `
                <a href="bubblePage.php?bubble_id=${bubble.id}" class="block p-2 text-center transform hover:scale-105 transition-transform duration-200 relative">
                    <img src="data:image/jpeg;base64,${bubble.profile_image}" alt="${bubble.bubble_name}" class="w-10 h-10 rounded-full mx-auto">
                    <div class="bubble-name-modal absolute left-full top-1/2 transform -translate-y-1/2 ml-2 bg-gray-800 text-white text-xs rounded px-2 py-1 opacity-0 transition-opacity duration-200">${bubble.bubble_name}</div>
                </a>
            `;
            bubbleList.appendChild(bubbleItem);
        });

        // Add event listeners to show/hide the modal on hover
        document.querySelectorAll('.bubble-container a').forEach(anchor => {
            anchor.addEventListener('mouseenter', function() {
                const modal = this.querySelector('.bubble-name-modal');
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
            });
            anchor.addEventListener('mouseleave', function() {
                const modal = this.querySelector('.bubble-name-modal');
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0');
            });
        });
    })
    .catch(error => {
        console.error("Error fetching joined bubbles:", error);
    });
}

        document.addEventListener("DOMContentLoaded", fetchJoinedBubbles);

        document.querySelectorAll('[id^="menu-button-"]').forEach(button => {
    button.addEventListener('click', function() {
        const dropdownMenu = document.getElementById(`menu-${this.id.split('-')[2]}`);
        dropdownMenu.classList.toggle('hidden');
    });
});
    </script>
</body>
</html>