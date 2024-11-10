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
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);

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
    </style>
</head>
<body class="bg-blue-50">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white  flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;}">
        <div class="flex items-center">
            <a href="indexTimeline.php"><img src="../public/ps.png" alt="Peerync Logo" class="h-18 w-16"></a>
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
                    <div class="bg-white p-4 shadow rounded mb-4">
                        <div class="flex items-center mb-4">
                            <img src="<?= htmlspecialchars($post['profile_image']) ?>" alt="Profile Image" class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <div class="text-gray-700 font-bold"><?= htmlspecialchars($post['username']) ?></div>
                                <div class="text-gray-500 text-sm"><?= htmlspecialchars($post['bubble_name']) ?> • <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?></div>
                            </div>
                        </div>
                        <a href="postDetails.php?post_id=<?= $post['id'] ?>" class="block">
                            <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                            <p class="text-gray-700 mb-4"><?= htmlspecialchars($post['message']) ?></p>
                            <?php if (!empty($post['image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($post['image']) ?>" alt="Post Image" class="w-full h-auto rounded mb-4">
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
            <a href="premium.php" class="block w-full py-2 bg-yellow-500 text-white text-center rounded hover:bg-yellow-600 transition duration-300">Learn More</a>
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

        window.onclick = function(event) {
            if (event.target == postModal) {
                postModal.style.display = 'none';
            }
        }

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
</body>
</html>
