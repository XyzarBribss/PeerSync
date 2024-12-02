<?php
session_start();
require 'config.php';

$logged_in_user_id = $_SESSION['user_id'];
// Fetch posts from bubbles the user has joined
$query = "
    SELECT bp.*, u.username, u.profile_image, b.bubble_name AS bubble_name
    FROM bubble_posts bp
    JOIN user_bubble ub ON bp.bubble_id = ub.bubble_id
    JOIN users u ON bp.user_id = u.id
    JOIN bubbles b ON bp.bubble_id = b.id
    WHERE ub.user_id = ?
    ORDER BY bp.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch joined bubbles
$sql = "
    SELECT b.id, b.bubble_name 
    FROM bubbles b
    JOIN user_bubble ub ON b.id = ub.bubble_id
    WHERE ub.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$joined_bubbles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user data from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline Thread</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .content { margin-top: 64px; margin-left: 80px; transition: margin-left 0.3s; }
        .right-sidebar { position: fixed; right: 0; height: calc(100% - 64px); overflow-y: auto; z-index: 100; margin-top: 80px; }

        /* Feedback Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .feedback-modal {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2b547e;
        }
        .close-button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .next-button {
            background-color: #4682b4;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .next-button:hover {
            background-color: #3a6d96;
        }
        
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
</head>
<body class="bg-blue-50">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white  flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;">
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

    <!-- Leftmost Sidebar -->
    <div id="sidebar" class="fixed top-0 left-0 h-full mt-10 text-white z-50 flex flex-col items-center sidebar transition-all duration-300 shadow-lg border-r border-gray-300" style="width: 64px; background-color: rgb(70 130 180 / 50%) /* #4682b4 */;">
    <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
    </div>

    <!-- Main Container -->
    <div id="main-content" class="content pt-8">
        <div class="flex justify-center mr-80 mt-4">
            <div class="p-4 mx-auto w-full max-w-4xl">
                <!-- Search Bar -->
                <div class="mb-4 flex items-center">
                    <input type="text" id="searchBar" class="w-full p-2 border border-gray-300 rounded-l-full" placeholder="Search posts...">
                    <button id="searchButton" class="text-white px-4 py-2 rounded-r-full hover:bg-blue-600" style="background-color: rgb(70 130 180 / var(--tw-bg-opacity)); /* #4682b4 */">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php
                // Search functionality
                if (isset($_GET['search'])) {
                    $search_term = '%' . $_GET['search'] . '%';
                    $search_query = "
                        SELECT bp.*, u.username, u.profile_image, b.bubble_name AS bubble_name
                        FROM bubble_posts bp
                        JOIN user_bubble ub ON bp.bubble_id = ub.bubble_id
                        JOIN users u ON bp.user_id = u.id
                        JOIN bubbles b ON bp.bubble_id = b.id
                        WHERE ub.user_id = ? AND (bp.title LIKE ? OR bp.message LIKE ?)
                    ";
                    $stmt = $conn->prepare($search_query);
                    $stmt->bind_param('iss', $logged_in_user_id, $search_term, $search_term);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $posts = $result->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                }
                ?>
                <script>
                document.getElementById('searchButton').addEventListener('click', function() {
                    const searchBar = document.getElementById('searchBar');
                    const searchTerm = searchBar.value;
                    window.location.href = `indexTimeline.php?search=${encodeURIComponent(searchTerm)}`;
                });
                </script>
                <!-- Display posts here -->
                <?php foreach ($posts as $post): ?>
                    <?php
                    // Fetch the number of comments for each post
                    $comment_query = "SELECT COUNT(*) AS comment_count FROM bubble_comments WHERE post_id = ?";
                    $comment_stmt = $conn->prepare($comment_query);
                    $comment_stmt->bind_param('i', $post['id']);
                    $comment_stmt->execute();
                    $comment_result = $comment_stmt->get_result();
                    $comment_count = $comment_result->fetch_assoc()['comment_count'];
                    ?>
                    <?php
                    $like_query = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
                    $like_stmt = $conn->prepare($like_query);
                    $like_stmt->bind_param('i', $post['id']);
                    $like_stmt->execute();
                    $like_result = $like_stmt->get_result();
                    $like_count = $like_result->fetch_assoc()['like_count'];
                    $like_stmt->close();                   
                    ?>
                    <?php
                    // Check if user has liked this post
                    $like_query = "SELECT COUNT(*) as liked FROM post_likes WHERE post_id = ? AND user_id = ?";
                    $like_stmt = $conn->prepare($like_query);
                    $like_stmt->bind_param("ii", $post['id'], $logged_in_user_id);
                    $like_stmt->execute();
                    $like_result = $like_stmt->get_result();
                    $user_like = $like_result->fetch_assoc()['liked'] > 0;
                    $like_stmt->close();

                    // Get total like count
                    $count_query = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?";
                    $count_stmt = $conn->prepare($count_query);
                    $count_stmt->bind_param("i", $post['id']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $like_count = $count_result->fetch_assoc()['count'];
                    $count_stmt->close();
                    ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden mb-8" data-post-id="<?= $post['id'] ?>">
                        <!-- Post Header -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <img src="<?= htmlspecialchars($post['profile_image']) ?>" 
                                        alt="Profile Image" 
                                        class="w-12 h-12 rounded-full border-2 border-gray-100 shadow-sm">
                                    <div>
                                        <div class="font-semibold text-gray-800"><?= htmlspecialchars($post['username']) ?></div>
                                        <div class="flex items-center text-sm text-gray-500 space-x-2">
                                            <span class="bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full text-xs font-medium">
                                                <?= htmlspecialchars($post['bubble_name']) ?>
                                            </span>
                                            <span>•</span>
                                            <span><?= date('M j, Y \a\t g:i a', strtotime($post['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors" onclick="togglePostMenu(<?= $post['id'] ?>)">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div id="postMenu-<?= $post['id'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                                        <?php if ($post['user_id'] == $logged_in_user_id): ?>
                                            <!-- Edit and Delete options for post owner -->
                                            <a href="editPost.php?post_id=<?= $post['id'] ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-edit mr-2"></i>Edit Post
                                            </a>
                                            <button onclick="deletePost(<?= $post['id'] ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                <i class="fas fa-trash-alt mr-2"></i>Delete Post
                                            </button>
                                        <?php else: ?>
                                            <!-- Report option for other users' posts -->
                                            <button onclick="openReportModal(<?= $post['id'] ?>, '<?= htmlspecialchars($post['message'], ENT_QUOTES) ?>', '<?= htmlspecialchars($post['bubble_name'], ENT_QUOTES) ?>', <?= $post['user_id'] ?>)" 
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-flag mr-2"></i>Report Post
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <a href="postDetails.php?post_id=<?= $post['id'] ?>" class="block group">
                                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-sky-600 transition-colors">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h3>
                                <p class="text-gray-600 leading-relaxed mb-4">
                                    <?= htmlspecialchars($post['message']) ?>
                                </p>
                                <?php if (!empty($post['image'])): ?>
                                    <div class="relative -mx-6 mb-4 bg-gray-100">
                                        <img src="data:image/jpeg;base64,<?= base64_encode($post['image']) ?>" 
                                            alt="Post Image" 
                                            class="w-full h-auto max-h-96 object-cover transform group-hover:scale-[1.02] transition-transform duration-300">
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Post Actions -->
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
                                    <a href="postDetails.php?post_id=<?= $post['id'] ?>#comments" 
                                       class="flex items-center space-x-2 text-gray-500 hover:text-sky-500 transition-colors">
                                        <i class="fas fa-comment"></i>
                                        <span class="text-sm">
                                            <?= $comment_count ?> <?= $comment_count === 1 ? 'Comment' : 'Comments' ?>
                                        </span>
                                    </a>

                                    <!-- Share Button -->
                                    <button class="flex items-center space-x-2 text-gray-500 hover:text-sky-500 transition-colors">
                                        <i class="fas fa-share"></i>
                                        <span class="text-sm">Share</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>

    <!-- Right Sidebar -->
    <aside class="w-64 bg-white p-4 overflow-auto fixed right-0 top-16 h-full shadow-lg">
        <div class="space-y-6">
        <!-- Home Section -->
        <div>
            <h2 class="text-xl font-semibold mb-2">Home</h2>
            <p class="text-sm text-gray-600 mb-4">
            Your home PeerSync frontpage. Come here to check in with your favorite bubble community!
            </p>
            <button class="w-full mb-2 py-2 px-4 text-white rounded hover:bg-blue-600 transition duration-300" id="createPostButton" style="background-color: rgb(70 130 180 / var(--tw-bg-opacity)); /* #4682b4 */">Create Post</button>
            <button class="w-full py-2 px-4 text-white rounded hover:bg-blue-600 transition duration-300" id="createBubbleButton" style="background-color: rgb(70 130 180 / var(--tw-bg-opacity)); /* #4682b4 */">Create Bubble</button>
        </div>

        <!-- Notebook Section -->
        <div>
            <h2 class="text-xl font-semibold mb-2">Notebook</h2>
            <p class="text-sm text-gray-600">
            Your home PeerSync frontpage. Come here to check in with your favorite bubble community!
            </p>
        </div>

        <!-- PeerSync Premium Section -->
        <div>
            <h2 class="text-xl font-semibold mb-2">PeerSync Premium</h2>
            <p class="text-sm text-gray-600 mb-4">Unlock exclusive features and content with PeerSync Premium.</p>
            <a href="sub.html" target="_blank" class="block w-full py-2 bg-yellow-500 text-white text-center rounded hover:bg-yellow-600 transition duration-300">Learn More</a>
        </div>
        </div>
    </aside>

    <!-- Modal for creating a new post -->
    <div id="createPostModal" class="modal">
        <div class="modal-content">
            <span id="closePostModal" class="close">&times;</span>
            <h2 class="text-xl font-bold mb-4">Create a New Post</h2>
            <form action="addBubblePostTimeline.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="bubble_id" class="block text-gray-700">Select Bubble</label>
                    <select name="bubble_id" id="bubble_id" class="w-full border border-gray-300 p-2 rounded">
                        <?php foreach ($joined_bubbles as $bubble): ?>
                            <option value="<?= $bubble['id'] ?>"><?= htmlspecialchars($bubble['bubble_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="title" class="block text-gray-700">Title</label>
                    <input type="text" name="title" id="title" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-gray-700">Message</label>
                    <textarea name="message" id="message" class="w-full border border-gray-300 p-2 rounded" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-700">Image File</label>
                    <input type="file" name="image" id="image" class="w-full border border-gray-300 p-2 rounded">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Post</button>
            </form>
        </div>
    </div>

    <!-- Modal for creating a new bubble -->
    <div id="createBubbleModal" class="modal">
        <div class="modal-content">
            <span id="closeBubbleModal" class="close">&times;</span>
            <h2 class="text-xl font-bold mb-4">Create a New Bubble</h2>
            <form action="createBubble.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="bubble_name" class="block text-gray-700">Bubble Name</label>
                    <input type="text" name="bubble_name" id="bubble_name" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700">Description</label>
                    <textarea name="description" id="description" class="w-full border border-gray-300 p-2 rounded" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="profile_image" class="block text-gray-700">Profile Image</label>
                    <input type="file" name="profile_image" id="profile_image" class="w-full border border-gray-300 p-2 rounded">
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-gray-700">Status</label>
                    <select name="status" id="status" class="w-full border border-gray-300 p-2 rounded">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Create Bubble</button>
            </form>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div id="notificationsModal" class="modal">
        <div class="modal-content max-w-lg mx-auto">
            <div class="flex justify-between items-center mb-4 border-b pb-4">
                <h2 class="text-xl font-bold">Notifications</h2>
                <div class="flex items-center gap-4">
                    <button id="markAllReadBtn" class="text-sm text-blue-600 hover:text-blue-800">Mark all as read</button>
                    <span id="closeNotificationsModal" class="close cursor-pointer">&times;</span>
                </div>
            </div>
            <div id="notificationsList" class="space-y-4 max-h-[70vh] overflow-y-auto">
                <!-- Notifications will be dynamically loaded here -->
            </div>
            <div id="notificationsLoader" class="text-center py-4 hidden">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div id="reportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 md:mx-auto">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-xl font-semibold text-gray-900">
                    Report Content
                </h3>
                <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Modal body -->
            <div class="p-6">
                <p class="text-gray-600 mb-6">
                    Please select a reason for reporting this content. Your report will help us maintain a safe community.
                </p>
                <form id="reportForm" onsubmit="handleReport(event)" data-post-id="" data-post-content="" data-bubble-name="" data-post-owner-id="">
                    <div class="space-y-3">
                        <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200">
                            <input type="radio" name="reportReason" value="inappropriate" class="h-4 w-4 text-blue-600 focus:ring-blue-500" required>
                            <span class="text-gray-700">Inappropriate content</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200">
                            <input type="radio" name="reportReason" value="spam" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700">Spam or misleading</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200">
                            <input type="radio" name="reportReason" value="harassment" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700">Harassment or bullying</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200">
                            <input type="radio" name="reportReason" value="violence" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700">Violence or threats</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200">
                            <input type="radio" name="reportReason" value="other" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700">Other</span>
                        </label>
                    </div>
                    
                    <!-- Additional details textarea, shown when "Other" is selected -->
                    <div id="otherDetailsContainer" class="hidden">
                        <textarea name="otherDetails" rows="3" class="mt-4 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Please provide additional details..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeReportModal()" class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium rounded-lg text-sm">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        document.getElementById('profileImage').addEventListener('click', function() {
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('hidden');
        });

        // Modal functionality for creating a post
        const postModal = document.getElementById('createPostModal');
        const postBtn = document.getElementById('createPostButton');
        const closePostModal = document.getElementById('closePostModal');

        postBtn.onclick = function() {
            postModal.style.display = 'block';
        }

        closePostModal.onclick = function() {
            postModal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == postModal) {
                postModal.style.display = 'none';
            }
        }

        // Report modal functionality
        function openReportModal(postId, postContent, bubbleName, postOwnerId) {
            const reportForm = document.getElementById('reportForm');
            reportForm.setAttribute('data-post-id', postId);
            reportForm.setAttribute('data-post-content', postContent);
            reportForm.setAttribute('data-bubble-name', bubbleName);
            reportForm.setAttribute('data-post-owner-id', postOwnerId);
            document.getElementById('reportModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeReportModal() {
            document.getElementById('reportModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('reportForm').reset();
            document.getElementById('otherDetailsContainer').classList.add('hidden');
        }

        // Handle form submission
        function handleReport(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const reason = formData.get('reportReason');
            const details = formData.get('otherDetails');
            const postId = document.getElementById('reportForm').getAttribute('data-post-id');
            const postContent = document.getElementById('reportForm').getAttribute('data-post-content');
            const bubbleName = document.getElementById('reportForm').getAttribute('data-bubble-name');
            const postOwnerId = document.getElementById('reportForm').getAttribute('data-post-owner-id');
            
            // Prepare the report data
            const reportFormData = new FormData();
            reportFormData.append('post_id', postId);
            reportFormData.append('post_owner_id', postOwnerId);
            reportFormData.append('report_reason', reason === 'other' ? details : reason);
            reportFormData.append('post_content', postContent);
            reportFormData.append('bubble_name', bubbleName);

            // Send the report to the server
            fetch('Admin_Page/insert_report.php', {
                method: 'POST',
                body: reportFormData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your report. We will review it shortly.');
                } else {
                    alert(data.error || 'Failed to submit report. Please try again.');
                }
                closeReportModal();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the report. Please try again.');
                closeReportModal();
            });
        }

        // Handle showing/hiding the "Other" details textarea
        document.querySelectorAll('input[name="reportReason"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const otherDetails = document.getElementById('otherDetailsContainer');
                if (this.value === 'other') {
                    otherDetails.classList.remove('hidden');
                } else {
                    otherDetails.classList.add('hidden');
                }
            });
        });

        // Close modal when clicking outside
        document.getElementById('reportModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeReportModal();
            }
        });
    </script>

    <script>
        // Modal functionality for creating a bubble
        const bubbleModal = document.getElementById('createBubbleModal');
        const bubbleBtn = document.getElementById('createBubbleButton');
        const closeBubbleModal = document.getElementById('closeBubbleModal');

        bubbleBtn.onclick = function() {
            bubbleModal.style.display = 'block';
        }

        closeBubbleModal.onclick = function() {
            bubbleModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == bubbleModal) {
                bubbleModal.style.display = 'none';
            }
        }

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
    </script>
    <script>
    $(document).ready(function() {
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

        // Load more notifications when scrolling to bottom
        $('#notificationsList').on('scroll', function() {
            if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 20) {
                loadNotifications();
            }
        });

        // Event Listeners
        $('#notificationsButton').on('click', function() {
            notificationsOffset = 0;
            loadNotifications();
        });

        $('#markAllReadBtn').on('click', function() {
            fetch('api/mark_notifications_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('.notification-item').removeClass('bg-blue-50').addClass('bg-white');
                    updateNotificationBadge(0);
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
        });

        $('#closeNotificationsModal').on('click', function() {
            $('#notificationsModal').hide();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is('#notificationsModal')) {
                $('#notificationsModal').hide();
            }
        });

        // Like button functionality
        $('.like-button').on('click', function() {
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
                            button.toggleClass('liked');
                            const likeCountText = data.likes === 1 ? '1 Like' : data.likes + ' Likes';
                            button.find('span').text(likeCountText);
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

        async function openPostModal(postId) {
            try {
                const response = await fetch(`api/get_post.php?post_id=${postId}`);
                const post = await response.json();
                
                if (post) {
                    // Close notifications modal
                    $('#notificationsModal').hide();
                    
                    // Fill and show post modal
                    $('#postModalTitle').text(post.title || '');
                    $('#postModalContent').text(post.message || '');
                    if (post.image) {
                        $('#postModalImage').attr('src', `data:image/jpeg;base64,${post.image}`).show();
                    } else {
                        $('#postModalImage').hide();
                    }
                    $('#postModal').show();
                    
                    // Mark notification as read
                    fetch('api/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ post_id: postId })
                    });
                }
            } catch (error) {
                console.error('Error opening post:', error);
            }
        }
    });
    </script>

    <script>
        // Toggle post menu dropdown
        function togglePostMenu(postId) {
            const menu = document.getElementById(`postMenu-${postId}`);
            // Close all other open menus first
            document.querySelectorAll('[id^="postMenu-"]').forEach(m => {
                if (m.id !== `postMenu-${postId}`) {
                    m.classList.add('hidden');
                }
            });
            menu.classList.toggle('hidden');
        }

        // Close post menus when clicking outside
        document.addEventListener('click', function(event) {
            const menuButton = event.target.closest('.relative');
            if (!menuButton) {
                document.querySelectorAll('[id^="postMenu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Function to handle post deletion
        function deletePost(postId) {
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                fetch(`deletePost.php?post_id=${postId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the post element from DOM and show success message
                        const postElement = document.querySelector(`[data-post-id="${postId}"]`).closest('.bg-white');
                        postElement.remove();
                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
                        successMessage.innerHTML = 'Post deleted successfully';
                        document.querySelector('.p-4.mx-auto').insertBefore(successMessage, document.querySelector('.mb-4'));
                        // Remove success message after 3 seconds
                        setTimeout(() => successMessage.remove(), 3000);
                    } else {
                        alert(data.error || 'Failed to delete post. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the post.');
                });
            }
        }
    </script>

    <!-- Dismissed Report Notification Modal -->
<div id="dismissedReportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header flex justify-between items-center mb-4">
            <h5 class="modal-title text-xl font-bold text-gray-800">Report Status</h5>
            <span class="close-button cursor-pointer text-2xl" id="closeDismissedReportModal">&times;</span>
        </div>
        <div class="modal-body p-4">
            <p class="text-gray-700">The report you have submitted does not contain any issues, thank you for making our community clean.</p>
        </div>
    </div>
</div>

<script>
    // Check for dismissed reports on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Get the dismissed report status from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const reportStatus = urlParams.get('report_status');
        
        if (reportStatus === 'dismissed') {
            const dismissedReportModal = document.getElementById('dismissedReportModal');
            dismissedReportModal.style.display = 'block';
            
            // Close modal after 3 seconds
            setTimeout(() => {
                dismissedReportModal.style.display = 'none';
                // Remove the parameter from URL
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 3000);
        }

        // Close modal when clicking the close button
        document.getElementById('closeDismissedReportModal').onclick = function() {
            document.getElementById('dismissedReportModal').style.display = 'none';
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('dismissedReportModal');
            if (event.target == modal) {
                modal.style.display = 'none';
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    });
</script>

<script>
    // Notifications Modal functionality
    const notificationsModal = document.getElementById('notificationsModal');
    const notificationsBtn = document.getElementById('notificationsButton');
    const closeNotificationsModal = document.getElementById('closeNotificationsModal');

    notificationsBtn.onclick = function() {
        notificationsModal.style.display = "block";
    }

    closeNotificationsModal.onclick = function() {
        notificationsModal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == notificationsModal) {
            notificationsModal.style.display = "none";
        }
    }
</script>
</body>
</html>
