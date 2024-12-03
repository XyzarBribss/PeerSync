<?php
session_start();
include 'config.php'; // Ensure this file correctly sets up the $conn variable

// Check if the post_id is provided
if (!isset($_GET['post_id'])) {
    echo json_encode(['error' => 'Post ID not provided.']);
    exit();
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id']; // Fetch user data from the database
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
$comment_count = $comment_result->fetch_assoc()['comment_count']; // Close the statement
$stmt->close();

// Fetch bubble membership status
$is_member = false;
$membership_query = "SELECT user_id FROM user_bubble WHERE user_id = ? AND bubble_id = ?";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param('ii', $user_id, $post['bubble_id']);
$stmt->execute();
$result = $stmt->get_result();
$is_member = $result->num_rows > 0;
$stmt->close();

// Fetch bubble statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM bubble_posts WHERE bubble_id = ?) as total_posts,
        (SELECT COUNT(*) FROM user_bubble WHERE bubble_id = ?) as total_members
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param('ii', $post['bubble_id'], $post['bubble_id']);
$stmt->execute();
$result = $stmt->get_result();
$bubble_stats = $result->fetch_assoc();
$stmt->close();

// Handle post deletion
if (isset($_POST['delete_post_id'])) {
    $delete_post_id = $_POST['delete_post_id']; // Check if the user is the owner of the post
    $query = "SELECT user_id FROM bubble_posts WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $delete_post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = $result->fetch_assoc();
    $stmt->close();

    if ($post_owner && $post_owner['user_id'] == $user_id) {
        // Delete the post
        $query = "DELETE FROM bubble_posts WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $delete_post_id);
        $stmt->execute();
        $stmt->close();

        header('Location: indexTimeline.php');
    } else {
        echo json_encode(['error' => 'You do not have permission to delete this post.']);
    }
    exit();
}

// Handle post update
if (isset($_POST['update_post_id'])) {
    $update_post_id = $_POST['update_post_id'];
    $updated_title = $_POST['title'];
    $updated_message = $_POST['message'];
    $updated_image = $_FILES['image']['tmp_name']; // Check if the user is the owner of the post
    $query = "SELECT user_id FROM bubble_posts WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $update_post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = $result->fetch_assoc();
    $stmt->close();

    if ($post_owner && $post_owner['user_id'] == $user_id) {
        // Update the post
        if (!empty($updated_image)) {
            $image_data = file_get_contents($updated_image);
            $query = "UPDATE bubble_posts SET title = ?, message = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssbi', $updated_title, $updated_message, $null, $update_post_id);
            $stmt->send_long_data(2, $image_data);
        } else {
            $query = "UPDATE bubble_posts SET title = ?, message = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssi', $updated_title, $updated_message, $update_post_id);
        }
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'You do not have permission to update this post.']);
    }
    exit();
}

// Fetch like count
$like_query = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
$like_stmt = $conn->prepare($like_query);
$like_stmt->bind_param('i', $post_id);
$like_stmt->execute();
$like_result = $like_stmt->get_result();
$like_count = $like_result->fetch_assoc()['like_count'];
$like_stmt->close();

// Add JavaScript functions for bubble actions
$js_functions = "
<script>
function createPost() {
    window.location.href = 'createPost.php?bubble_id=" . $post['bubble_id'] . "';
}

function joinBubble() {
    fetch('joinBubble.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            bubble_id: " . $post['bubble_id'] . "
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
";

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
    <script src="js/notifications.js" defer></script>
    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .content { margin-top: 64px; margin-left: 80px; transition: margin-left 0.3s; }
        .right-sidebar { position: fixed; right: 0; height: calc(100% - 64px); overflow-y: auto; z-index: 100; margin-top: 80px; }
    </style>
    <?= $js_functions ?>
</head>
<body class="bg-blue-50">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;">
        <div class="flex items-center">
            <a href="indexTimeline.php"><img src="../public/ps.png" alt="Peerync Logo" class="h-18 w-16"></a>
            <span class="text-2xl font-bold">PeerSync</span>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Notifications Button -->
            <button id="notificationsButton" class="text-white hover:text-gray-200 relative">
                <i class="fas fa-bell text-xl"></i>
                <span class="notification-badge hidden absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">0</span>
            </button>
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

    <!-- Notifications Modal -->
    <div id="notificationsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white w-96 max-w-lg mx-auto mt-20 rounded-lg shadow-lg">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Notifications</h3>
                <button id="closeNotificationsModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="notificationsList" class="max-h-96 overflow-y-auto">
                <div id="notificationsLoader" class="text-center py-4 hidden">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <img src="<?= htmlspecialchars($post['user_profile_image']) ?>" alt="<?= htmlspecialchars($post['username']) ?>" class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <div class="text-gray-700 font-bold"><?= htmlspecialchars($post['username']) ?></div>
                                <div class="text-gray-500 text-sm"><?= htmlspecialchars($post['bubble_name']) ?> • <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="relative">
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors" onclick="togglePostMenu()">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div id="postMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                                <?php if ($post['user_id'] == $user_id): ?>
                                    <!-- Edit and Delete options for post owner -->
                                    <button onclick="openEditModal()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-edit mr-2"></i>Edit Post
                                    </button>
                                    <button onclick="deletePost(<?= $post['id'] ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        <i class="fas fa-trash-alt mr-2"></i>Delete Post
                                    </button>
                                <?php else: ?>
                                    <!-- Report option for other users' posts -->
                                    <button onclick="openReportModal(<?= $post['id'] ?>, '<?= htmlspecialchars($post['message']) ?>', '<?= htmlspecialchars($post['bubble_name']) ?>', <?= $post['user_id'] ?>)" 
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-flag mr-2"></i>Report Post
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <a href="postDetails.php?post_id=<?= $post['id'] ?>" class="block">
                        <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="text-gray-700 mb-4"><?= htmlspecialchars($post['message']) ?></p>
                        <?php if (!empty($image_base64)): ?>
                            <img src="<?= $image_base64 ?>" alt="Post Image" class="w-full h-auto rounded mb-4">
                        <?php endif; ?>
                    </a>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="flex items-center space-x-6">
                            <!-- Like Button -->
                            <button type="button" 
                                    id="like-button-<?= $post['id'] ?>" 
                                    class="like-button <?= $user_like ? 'liked' : '' ?>" 
                                    data-post-id="<?= $post['id'] ?>">
                                <i class="fas fa-thumbs-up"></i>
                                <span class="text-sm">
                                    <?= $like_count ?> <?= $like_count === 1 ? 'Like' : 'Likes' ?>
                                </span>
                            </button>

                            <!-- Comment Button -->
                            <button class="flex items-center space-x-2 text-gray-500 hover:text-sky-500 transition-colors">
                                <i class="fas fa-comment"></i>
                                <span class="text-sm">
                                    <?= $comment_count ?> <?= $comment_count === 1 ? 'Comment' : 'Comments' ?>
                                </span>
                            </button>

                            <!-- Share Button -->
                            <button class="flex items-center space-x-2 text-gray-500 hover:text-sky-500 transition-colors">
                                <i class="fas fa-share"></i>
                                <span class="text-sm">Share</span>
                            </button>
                        </div>

                        <!-- Report Button -->
                        <button onclick="openReportModal()" 
                                class="flex items-center space-x-2 text-gray-400 hover:text-red-500 transition-colors text-sm">
                            <i class="fas fa-flag"></i>
                            <span>Report</span>
                        </button>
                    </div>
                    <style>
                        /* Like button styles */
                        .like-button {
                            transition: all 0.3s ease;
                            padding: 0.5rem 1rem;
                            border-radius: 0.375rem;
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                        }
                        
                        .like-button.liked {
                            color: rgb(43, 84, 126);
                            font-weight: 600;
                            background-color: rgba(43, 84, 126, 0.1);
                        }
                        
                        .like-button:hover {
                            color: rgb(70, 130, 180);
                            background-color: rgba(70, 130, 180, 0.1);
                        }
                        
                        .like-button i {
                            transition: transform 0.2s ease;
                        }
                        
                        .like-button.liked i {
                            transform: scale(1.1);
                            color: rgb(43, 84, 126);
                        }

                        .like-button:not(.liked) {
                            color: #6B7280;
                        }
                    </style>

                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                    $(document).ready(function() {
                        $('.like-button').click(function() {
                            const button = $(this);
                            const postId = button.data('post-id');
                            
                            $.ajax({
                                url: 'toggle_like.php',
                                type: 'POST',
                                data: { post_id: postId },
                                success: function(response) {
                                    try {
                                        const data = JSON.parse(response);
                                        if (data.success) {
                                            // Toggle the liked state
                                            button.toggleClass('liked');
                                            
                                            // Update the like count text
                                            const likeCountText = data.likes === 1 ? '1 Like' : data.likes + ' Likes';
                                            button.find('span').text(likeCountText);
                                            
                                            // Add animation effect
                                            button.find('i').addClass('animate-bounce');
                                            setTimeout(() => {
                                                button.find('i').removeClass('animate-bounce');
                                            }, 1000);
                                        } else {
                                            console.error('Like toggle failed:', data.message);
                                        }
                                    } catch (e) {
                                        console.error('Error parsing response:', e);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Ajax request failed:', error);
                                }
                            });
                        });
                    });
                    </script>
                </div>


                <!-- Display comments -->
                <div class="bg-white p-4 shadow rounded mb-4">
                    <h3 class="font-bold mb-2">Comments</h3>
                    <div class="mb-4">
                        <form id="comment-form" class="space-y-2">
                            <textarea name="comment" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Write a comment..."></textarea>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                                Post Comment
                            </button>
                        </form>
                    </div>
                    <div id="comments-container">
                        <?php foreach ($comments as $comment): ?>
                            <?php if (empty($comment['parent_comment_id'])): ?>
                                <div class="comment-item mb-4 bg-gray-50 p-3 rounded" data-comment-id="<?= $comment['id'] ?>">
                                    <div class="flex items-start space-x-3">
                                        <img src="<?= $comment['profile_image'] ?>" alt="Profile" class="w-8 h-8 rounded-full">
                                        <div class="flex-grow">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold"><?= htmlspecialchars($comment['username']) ?></span>
                                                <div class="text-sm text-gray-500">
                                                    <?= date('M d, Y H:i', strtotime($comment['created_at'])) ?>
                                                </div>
                                            </div>
                                            <p class="mt-1 comment-text"><?= htmlspecialchars($comment['comment']) ?></p>
                                            <div class="mt-2 space-x-2">
                                                <button onclick="showReplyForm(<?= $comment['id'] ?>)" class="text-sm text-blue-500 hover:text-blue-600">
                                                    Reply
                                                </button>
                                                <?php if ($comment['user_id'] == $user_id): ?>
                                                    <button onclick="editComment(<?= $comment['id'] ?>)" class="text-sm text-gray-500 hover:text-gray-600">
                                                        Edit
                                                    </button>
                                                    <button onclick="deleteComment(<?= $comment['id'] ?>)" class="text-sm text-red-500 hover:text-red-600">
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Reply form container -->
                                            <div id="reply-form-<?= $comment['id'] ?>" class="hidden mt-2">
                                                <form onsubmit="submitReply(event, <?= $comment['id'] ?>)" class="space-y-2">
                                                    <textarea name="reply" class="w-full p-2 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Write a reply..."></textarea>
                                                    <div class="flex space-x-2">
                                                        <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                                            Reply
                                                        </button>
                                                        <button type="button" onclick="hideReplyForm(<?= $comment['id'] ?>)" class="text-gray-500 hover:text-gray-600 text-sm">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <!-- Replies container -->
                                            <div class="replies-container ml-8 mt-2">
                                                <?php
                                                foreach ($comments as $reply):
                                                    if ($reply['parent_comment_id'] == $comment['id']):
                                                ?>
                                                    <div class="reply-item bg-white p-2 rounded mt-2" data-reply-id="<?= $reply['id'] ?>">
                                                        <div class="flex items-start space-x-2">
                                                            <img src="<?= $reply['profile_image'] ?>" alt="Profile" class="w-6 h-6 rounded-full">
                                                            <div class="flex-grow">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="font-semibold text-sm"><?= htmlspecialchars($reply['username']) ?></span>
                                                                    <div class="text-xs text-gray-500">
                                                                        <?= date('M d, Y H:i', strtotime($reply['created_at'])) ?>
                                                                    </div>
                                                                </div>
                                                                <p class="text-sm mt-1 reply-text"><?= htmlspecialchars($reply['comment']) ?></p>
                                                                <?php if ($reply['user_id'] == $user_id): ?>
                                                                    <div class="mt-1 space-x-2">
                                                                        <button onclick="editComment(<?= $reply['id'] ?>)" class="text-xs text-gray-500 hover:text-gray-600">
                                                                            Edit
                                                                        </button>
                                                                        <button onclick="deleteComment(<?= $reply['id'] ?>)" class="text-xs text-red-500 hover:text-red-600">
                                                                            Delete
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <script>
                // Comment functionality
                document.getElementById('comment-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const commentText = this.querySelector('textarea[name="comment"]').value;
                    if (!commentText.trim()) return;

                    submitComment(commentText);
                    this.reset();
                });

                function submitComment(commentText, parentId = null) {
                    fetch('handleComment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add',
                            post_id: <?= $post_id ?>,
                            comment: commentText,
                            parent_id: parentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (parentId) {
                                // Add reply to the specific comment's replies container
                                const repliesContainer = document.querySelector(`[data-comment-id="${parentId}"] .replies-container`);
                                const replyHTML = createReplyHTML(data.comment);
                                repliesContainer.insertAdjacentHTML('beforeend', replyHTML);
                                hideReplyForm(parentId);
                            } else {
                                // Add new comment to the comments container
                                const commentsContainer = document.getElementById('comments-container');
                                const commentHTML = createCommentHTML(data.comment);
                                commentsContainer.insertAdjacentHTML('afterbegin', commentHTML);
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }

                function createCommentHTML(comment) {
                    const currentUserId = <?= $user_id ?>;
                    const userActions = comment.user_id == currentUserId ? `
                        <button onclick="editComment(${comment.id})" class="text-sm text-gray-500 hover:text-gray-600">Edit</button>
                        <button onclick="deleteComment(${comment.id})" class="text-sm text-red-500 hover:text-red-600">Delete</button>
                    ` : '';

                    return `
                        <div class="comment-item mb-4 bg-gray-50 p-3 rounded" data-comment-id="${comment.id}">
                            <div class="flex items-start space-x-3">
                                <img src="${comment.profile_image}" alt="Profile" class="w-8 h-8 rounded-full">
                                <div class="flex-grow">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold">${comment.username}</span>
                                        <div class="text-sm text-gray-500">
                                            ${new Date(comment.created_at).toLocaleString()}
                                        </div>
                                    </div>
                                    <p class="mt-1 comment-text">${comment.comment}</p>
                                    <div class="mt-2 space-x-2">
                                        <button onclick="showReplyForm(${comment.id})" class="text-sm text-blue-500 hover:text-blue-600">Reply</button>
                                        ${userActions}
                                    </div>
                                    <div id="reply-form-${comment.id}" class="hidden mt-2">
                                        <form onsubmit="submitReply(event, ${comment.id})" class="space-y-2">
                                            <textarea name="reply" class="w-full p-2 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Write a reply..."></textarea>
                                            <div class="flex space-x-2">
                                                <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Reply</button>
                                                <button type="button" onclick="hideReplyForm(${comment.id})" class="text-gray-500 hover:text-gray-600 text-sm">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="replies-container ml-8 mt-2"></div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                function createReplyHTML(reply) {
                    const currentUserId = <?= $user_id ?>;
                    const userActions = reply.user_id == currentUserId ? `
                        <div class="mt-1 space-x-2">
                            <button onclick="editComment(${reply.id})" class="text-xs text-gray-500 hover:text-gray-600">Edit</button>
                            <button onclick="deleteComment(${reply.id})" class="text-xs text-red-500 hover:text-red-600">Delete</button>
                        </div>
                    ` : '';

                    return `
                        <div class="reply-item bg-white p-2 rounded mt-2" data-reply-id="${reply.id}">
                            <div class="flex items-start space-x-2">
                                <img src="${reply.profile_image}" alt="Profile" class="w-6 h-6 rounded-full">
                                <div class="flex-grow">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-sm">${reply.username}</span>
                                        <div class="text-xs text-gray-500">
                                            ${new Date(reply.created_at).toLocaleString()}
                                        </div>
                                    </div>
                                    <p class="text-sm mt-1 reply-text">${reply.comment}</p>
                                    ${userActions}
                                </div>
                            </div>
                        </div>
                    `;
                }

                function showReplyForm(commentId) {
                    document.getElementById(`reply-form-${commentId}`).classList.remove('hidden');
                }

                function hideReplyForm(commentId) {
                    const form = document.getElementById(`reply-form-${commentId}`);
                    form.classList.add('hidden');
                    form.querySelector('textarea').value = '';
                }

                function submitReply(event, commentId) {
                    event.preventDefault();
                    const replyText = event.target.querySelector('textarea[name="reply"]').value;
                    if (!replyText.trim()) return;

                    submitComment(replyText, commentId);
                }

                function editComment(commentId) {
                    const commentElement = document.querySelector(`[data-comment-id="${commentId}"] .comment-text`) || 
                                          document.querySelector(`[data-reply-id="${commentId}"] .reply-text`);
                    const currentText = commentElement.textContent;
                    
                    const textArea = document.createElement('textarea');
                    textArea.className = 'w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400';
                    textArea.value = currentText;
                    
                    const saveButton = document.createElement('button');
                    saveButton.textContent = 'Save';
                    saveButton.className = 'mt-2 bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 mr-2';
                    
                    const cancelButton = document.createElement('button');
                    cancelButton.textContent = 'Cancel';
                    cancelButton.className = 'mt-2 text-gray-500 hover:text-gray-600 text-sm';
                    
                    const container = document.createElement('div');
                    container.appendChild(textArea);
                    container.appendChild(saveButton);
                    container.appendChild(cancelButton);
                    
                    commentElement.replaceWith(container);
                    
                    saveButton.onclick = () => {
                        const newText = textArea.value;
                        if (!newText.trim()) return;
                        
                        fetch('handleComment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'edit',
                                comment_id: commentId,
                                comment: newText
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const newElement = document.createElement('p');
                                newElement.className = commentElement.className;
                                newElement.textContent = newText;
                                container.replaceWith(newElement);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    };
                    
                    cancelButton.onclick = () => {
                        container.replaceWith(commentElement);
                    };
                }

                function deleteComment(commentId) {
                    if (!confirm('Are you sure you want to delete this comment?')) return;

                    fetch('handleComment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            comment_id: commentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                            const replyElement = document.querySelector(`[data-reply-id="${commentId}"]`);
                            if (commentElement) {
                                commentElement.remove();
                            } else if (replyElement) {
                                replyElement.remove();
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
                </script>
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
            <aside class="w-64 bg-white p-6 overflow-auto fixed right-0 top-16 h-full shadow-lg border-l border-gray-100">
                <div class="space-y-6">
                    <!-- Bubble Header -->
                    <div class="border-b border-gray-100 pb-4">
                        <h2 class="text-xl font-semibold mb-4 text-[rgb(43,84,126)]">About Bubble</h2>
                        <div class="flex items-center mb-4">
                            <?php if (!empty($profile_image_base64)): ?>
                                <img src="<?= $profile_image_base64 ?>" alt="<?= htmlspecialchars($post['bubble_name']) ?>" 
                                     class="w-12 h-12 rounded-full mx-auto">
                            <?php else: ?>
                                <img src="default-profile.png" alt="Default Profile Image" 
                                     class="w-12 h-12 rounded-full mx-auto border-2 border-[rgb(70,130,180)]">
                            <?php endif; ?>
                            <div>
                                <h3 class="font-bold text-gray-800"><?= htmlspecialchars($post['bubble_name']) ?></h3>
                                <p class="text-xs text-gray-500">Created <?= date('M d, Y', strtotime($post['bubble_created_at'])) ?></p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed mb-4"><?= htmlspecialchars($post['bubble_description']) ?></p>
                    </div>

                    <!-- Bubble Stats -->
                    <div class="py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Bubble Stats</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <span class="block text-lg font-bold text-[rgb(43,84,126)]">
                                    <?= number_format($bubble_stats['total_posts'] ?? 0) ?>
                                </span>
                                <span class="text-xs text-gray-500">Posts</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-lg font-bold text-[rgb(43,84,126)]">
                                    <?= number_format($bubble_stats['total_members'] ?? 0) ?>
                                </span>
                                <span class="text-xs text-gray-500">Members</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button onclick="createPost()" 
                                class="w-full bg-[rgb(70,130,180)] text-white px-4 py-2.5 rounded-lg hover:bg-[rgb(43,84,126)] transition-colors duration-300 flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            Create Post
                        </button>
                        <?php if (!$is_member): ?>
                            <button onclick="joinBubble()" 
                                    class="w-full border-2 border-[rgb(70,130,180)] text-[rgb(70,130,180)] px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors duration-300 flex items-center justify-center gap-2">
                                <i class="fas fa-user-plus"></i>
                                Join Bubble
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Bubble Rules -->
                    <div class="py-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Bubble Rules</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-[rgb(70,130,180)]"></i>
                                Be respectful and kind
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-[rgb(70,130,180)]"></i>
                                Stay on topic
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-[rgb(70,130,180)]"></i>
                                No spam or self-promotion
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div id="editPostModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Post</h2>
            <form action="postDetails.php?post_id=<?= htmlspecialchars($post_id) ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="update_post_id" value="<?= htmlspecialchars($post_id) ?>">
                <div class="mb-4">
                    <label class="block">
                        <span class="text-gray-700">Title</span>
                        <input type="text" name="title" id="editPostTitle" class="w-full p-2 border rounded mt-1" value="<?= htmlspecialchars($post['title']) ?>" required>
                    </label>
                </div>
                <div class="mb-4">
                    <label class="block">
                        <span class="text-gray-700">Message</span>
                        <textarea name="message" id="editPostMessage" class="w-full p-2 border rounded mt-1" rows="4" required><?= htmlspecialchars($post['message']) ?></textarea>
                    </label>
                </div>
                <div class="mb-4">
                    <label class="block">
                        <span class="text-gray-700">Image</span>
                        <input type="file" name="image" id="editPostImage" class="w-full p-2 border rounded mt-1">
                    </label>
                    <?php if (!empty($image_base64)): ?>
                        <img src="<?= $image_base64 ?>" alt="Post Image" class="w-full h-auto rounded mt-2">
                    <?php endif; ?>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
            </form>
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

function openEditModal(postId) {
    document.getElementById('editPostModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editPostModal').style.display = 'none';
}

// Close the modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('editPostModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
    </script>

    <script>
    // Notifications functionality
    let notificationsOffset = 0;
    let isLoadingNotifications = false;

    async function loadNotifications() {
        if (isLoadingNotifications) return;
        
        isLoadingNotifications = true;
        $('#notificationsLoader').removeClass('hidden');

        try {
            const response = await fetch(`api/get_notifications.php?offset=${notificationsOffset}`);
            const html = await response.text();
            
            if (notificationsOffset === 0) {
                $('#notificationsList').empty();
            }
            
            $('#notificationsList').append(html);
            notificationsOffset += 10;
            
        } catch (error) {
            $('#notificationsList').html("<div class='text-red-500 text-center p-4'>Failed to load notifications</div>");
        }
        
        isLoadingNotifications = false;
        $('#notificationsLoader').addClass('hidden');
    }

    // Event Listeners
    $('#notificationsButton').on('click', function() {
        notificationsOffset = 0;
        loadNotifications();
        $('#notificationsModal').removeClass('hidden');
    });

    $('#closeNotificationsModal').on('click', function() {
        $('#notificationsModal').addClass('hidden');
    });

    // Load more notifications when scrolling to bottom
    $('#notificationsList').on('scroll', function() {
        if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 20) {
            loadNotifications();
        }
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#notificationsModal')) {
            $('#notificationsModal').addClass('hidden');
        }
    });
</script>
</body>
</html>