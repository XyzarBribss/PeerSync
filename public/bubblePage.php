<?php
session_start();
include 'config.php';

$bubble_id = $_GET['bubble_id'];
$user_id = $_SESSION['user_id'];

// Fetch bubble data
$sql = "SELECT * FROM bubbles WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bubble_id);
$stmt->execute();
$bubble = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch bubble members
$sql = "SELECT users.id, users.username, users.profile_image 
    FROM user_bubble 
    JOIN users ON user_bubble.user_id = users.id 
    WHERE user_bubble.bubble_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bubble_id);
$stmt->execute();
$members = $stmt->get_result();

// Fetch posts
$post_query = "SELECT bubble_posts.*, users.username, users.profile_image 
           FROM bubble_posts 
           JOIN users ON bubble_posts.user_id = users.id 
           WHERE bubble_posts.bubble_id = ?";
$post_stmt = $conn->prepare($post_query);
$post_stmt->bind_param("i", $bubble_id);
$post_stmt->execute();
$posts = $post_stmt->get_result();

// Fetch bubble messages
$message_query = "SELECT bubble_message.*, users.username, users.profile_image 
          FROM bubble_message 
          JOIN users ON bubble_message.user_id = users.id 
          WHERE bubble_message.bubble_id = ?";
$message_stmt = $conn->prepare($message_query);
$message_stmt->bind_param("i", $bubble_id);
$message_stmt->execute();
$messages = $message_stmt->get_result();
$message_stmt->close();

// Handle deletion of bubble messages
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_message_id'])) {
    $message_id = intval($_POST['delete_message_id']);
    $sql = "DELETE FROM bubble_message WHERE id = $message_id";
    if ($conn->query($sql) === TRUE) {
        header("Location: bubblePage.php?bubble_id=" . $bubble_id . "&message=Message deleted successfully");
        exit();
    } else {
        header("Location: bubblePage.php?bubble_id=" . $bubble_id . "&message=Error deleting message: " . urlencode($conn->error));
        exit();
    }
}
// Handle editing of bubble messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_message_id'])) {
    $edit_message_id = $_POST['edit_message_id'];
    $new_message = $_POST['new_message'];

    // Update the message in the database
    $update_query = "UPDATE bubble_message SET message = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('sii', $new_message, $edit_message_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Redirect to the same page to refresh the list of messages
    header("Location: bubblePage.php?bubble_id=" . $bubble_id);
    exit();
}

// Fetch notebooks
$notebook_query = "SELECT notebooks.*, users.username, users.profile_image 
                   FROM notebooks 
                   JOIN users ON notebooks.user_id = users.id";
$notebook_stmt = $conn->prepare($notebook_query);
$notebook_stmt->execute();
$notebooks = $notebook_stmt->get_result();
$notebook_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubble Posts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
    .dropdown:hover .dropdown-menu { display: block; }
    .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
    .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
    .dropdown-menu {z-index: 50; /* Ensure this value is higher than other elements */}
    </style>
</head>
<body class="bg-white h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;}">
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
            <div class="relative ml-4  p-4">
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

    <div class="main-content flex-grow flex overflow-hidden mt-16">
        <div class="sidebarb w-64 bg-blue-50 text-sky-700 p-5 overflow-y-auto flex-shrink-0 ml-20 shadow-lg transition-transform transform" style="margin-left: 64px;">
            <?php if ($bubble): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" class="w-full h-40 rounded-lg object-cover border-2 border-gray-300 mb-4">
                <h2 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($bubble['bubble_name']); ?></h2>
            <?php else: ?>
                <p class="text-red-500">Bubble not found.</p>
            <?php endif; ?>
            <ul class="space-y-2">
                <li>
                    <a href="#" class="block p-2 rounded text-sky-700 hover:bg-sky-200 transition duration-300" onclick="showContent('chat')">
                        <i class="fas fa-comments mr-2"></i> Chat
                    </a>
                </li>
                <li>
                    <a href="#" class="block p-2 rounded text-sky-700 hover:bg-sky-200 transition duration-300" onclick="showContent('forum')">
                        <i class="fas fa-list mr-2"></i> Thread
                    </a>
                </li>
                <li>
                    <a href="#" class="block p-2 rounded hover:bg-sky-200 transition duration-300" onclick="showContent('notebook')">
                        <i class="fas fa-book mr-2"></i> Notebook
                    </a>
                </li>
                <?php if ($bubble['creator_id'] == $user_id): ?>
                    <li>
                        <a href="#" class="block p-2 rounded hover:bg-sky-200 transition duration-300" onclick="showContent('settings')">
                            <i class="fas fa-gear mr-2"></i> Settings
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <h3 class="font-semibold mt-6">Bubble Members</h3>
            <ul class="space-y-2 mt-4" id="member-list">
                <?php while ($member = $members->fetch_assoc()): ?>
                    <li class="flex items-center space-x-2 cursor-pointer" data-member-id="<?php echo $member['id']; ?>">
                        <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="Profile Image" class="w-8 h-8 rounded-full">
                        <span><?php echo htmlspecialchars($member['username']); ?></span>
                    </li>
                <?php endwhile; ?>
                <?php $stmt->close(); ?>
            </ul>
        </div>

        <div class="content-container flex-grow flex flex-col h-full p-5 overflow-y-auto bg-white">
            <div id="chat" class="hidden flex flex-col h-full">
            <h2 class="text-2xl font-bold mb-4">Chat</h2>
                <div id="chat-messages" class="flex-grow space-y-4 p-2 bg-white overflow-y-auto">
                    <!-- Chat messages will be populated by JavaScript -->
                    <?php foreach ($messages as $message): ?>
                        <div class="p-2 bg-white rounded shadow-sm relative flex items-start space-x-2 hover:bg-gray-100 transition-colors duration-200">
                            <img src="<?php echo htmlspecialchars($message['profile_image']); ?>" alt="Profile Image" class="w-8 h-8 rounded-full">
                            <div class="flex-grow">
                                <p class="font-bold text-sm"><?php echo htmlspecialchars($message['username']); ?></p>
                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($message['timestamp']); ?></p>
                                <p class="text-sm"><?php echo htmlspecialchars($message['message']); ?></p>
                            </div>
                            <div class="relative">
                                <button class="text-gray-500 hover:text-gray-700 focus:outline-none" onclick="toggleDropdown(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu absolute right-0 mt-2 w-32 bg-white border border-gray-300 rounded shadow-lg hidden">
                                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Report</a>
                                    <?php if ($message['user_id'] == $user_id): ?>
                                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100" onclick="showEditModal(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars($message['message']); ?>')">Edit</a>
                                        <form method="post" action="" class="inline">
                                            <input type="hidden" name="delete_message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- Edit Message Modal -->
                    <div id="edit-message-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
                        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
                            <h2 class="text-2xl font-semibold mb-4">Edit Message</h2>
                            <form method="post" action="">
                                <input type="hidden" name="edit_message_id" id="edit_message_id">
                                <textarea name="new_message" id="new_message" class="border p-2 w-full mb-4" placeholder="Enter new message" required></textarea>
                                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Save</button>
                                <button type="button" class="bg-red-500 text-white px-4 py-2 rounded" onclick="hideEditModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <form id="message-form" class="mt-4 flex-shrink-0 flex">
                    <input type="text" id="message-input" class="flex-grow p-2 border rounded" placeholder="Type your message here..." required>
                    <button type="submit" class="ml-2 p-2 bg-blue-500 text-white rounded">Send</button>
                </form>
            </div>

            <div id="forum" class="hidden flex flex-col h-full">
            <h2 class="text-2xl font-bold mb-4">Thread</h2>
                <form id="forum-form" class="space-y-2 mb-2 flex-shrink-0 bg-white p-2 rounded shadow-md border border-gray-300" enctype="multipart/form-data" method="post" action="addBubblePost.php">
                    <input type="hidden" name="bubble_id" value="<?php echo $bubble_id; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="text" name="title" id="forum-title" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Title" required>
                    <textarea name="message" id="forum-message" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Message" required></textarea>
                    <input type="file" name="image" id="forum-image" class="flex-grow p-2 border rounded focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button type="submit" class="ml-2 p-2 bg-sky-500 text-white rounded hover:bg-sky-600 transition duration-300">Post</button>
                </form>
                <div id="forum-posts" class="flex-grow space-y-4 overflow-y-auto mt-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="p-4 bg-white rounded shadow-lg flex justify-between items-start transition transform hover:shadow-2xl h-64 hover:-translate-y-1 border border-gray-200 cursor-pointer relative" data-post-id="<?= $post['id'] ?>">
                            <div class="flex-grow">
                                <a href="postDetails.php?post_id=<?= $post['id'] ?>" class="block">
                                    <div class="flex items-center mb-2">
                                        <img src="<?= htmlspecialchars($post['profile_image']) ?>" alt="Profile Image" class="w-8 h-8 rounded-full mr-3">
                                        <div>
                                            <h3 class="font-semibold text-sky-900"><?= htmlspecialchars($post['username']) ?></h3>
                                            <h4 class="text-gray-500 text-xs"><?= htmlspecialchars($post['created_at']) ?></h4>
                                        </div>
                                    </div>
                                    <h3 class="font-semibold text-sky-900 mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                                    <p class="text-gray-500 text-sm"><?= htmlspecialchars($post['message']) ?></p>
                                </a>
                            </div>
                            <?php if (!empty($post['image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($post['image']) ?>" alt=" " class="ml-4 rounded object-cover" style="width: 300px; height: 150px;">
                            <?php endif; ?>
                            <div class="relative">
                                <button class="dropdown-toggle p-2 rounded-full hover:bg-gray-300 focus:outline-none">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white border border-gray-300 rounded shadow-lg hidden">
                                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Report</a>
                                    <?php if ($post['user_id'] == $user_id): ?>
                                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Edit</a>
                                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <div id="notebook" class="hidden flex-grow flex flex-col">
    <h2 class="text-2xl font-bold mb-4">Notebook</h2>
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Bubble Notebooks</h1>
        <button class="bg-blue-500 text-white p-2 rounded">Share Your Notebook</button>
    </div>

    <!-- Search and Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-4 mb-8">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
            <input type="text" placeholder="Search notebooks..." class="pl-10 w-full p-2 border rounded">
        </div>
        <select class="w-full sm:w-[180px] p-2 border rounded">
            <option value="recent">Most Recent</option>
            <option value="popular">Most Popular</option>
            <option value="discussed">Most Discussed</option>
        </select>
    </div>

    <!-- Bubble Stats -->
    <div class="bg-white p-4 rounded shadow mb-8">
        <div class="mb-4">
            <h3 class="text-xl font-bold">Bubble Insights</h3>
            <p class="text-gray-500">Explore our community's notebook activity</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="flex flex-col items-center">
                <i class="fas fa-book-open h-8 w-8 text-blue-500 mb-2"></i>
                <span class="text-2xl font-bold">156</span>
                <span class="text-sm text-gray-500">Shared Notebooks</span>
            </div>
            <div class="flex flex-col items-center">
                <i class="fas fa-star h-8 w-8 text-blue-500 mb-2"></i>
                <span class="text-2xl font-bold">42</span>
                <span class="text-sm text-gray-500">Featured Notes</span>
            </div>
            <div class="flex flex-col items-center">
                <i class="fas fa-bookmark h-8 w-8 text-blue-500 mb-2"></i>
                <span class="text-2xl font-bold">328</span>
                <span class="text-sm text-gray-500">Bookmarks</span>
            </div>
            <div class="flex flex-col items-center">
                <i class="fas fa-users h-8 w-8 text-blue-500 mb-2"></i>
                <span class="text-2xl font-bold">89%</span>
                <span class="text-sm text-gray-500">Active Members</span>
            </div>
        </div>
    </div>

    <!-- Notebook Grid -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Recent Notebooks</h2>
        <div class="flex gap-2">
            <button class="border p-2 rounded" onclick="setView('grid')">Grid</button>
            <button class="border p-2 rounded" onclick="setView('list')">List</button>
        </div>
    </div>

    <div id="notebook-grid" class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($notebooks as $notebook): ?>
            <div class="bg-white p-4 rounded shadow group transition-all hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($notebook['profile_image']); ?>" alt="Profile Image" class="w-10 h-10 rounded-full">
                    <div>
                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($notebook['name']); ?></h3>
                        <p class="text-gray-500">by <?php echo htmlspecialchars($notebook['username']); ?> • Updated <?php echo htmlspecialchars($notebook['updated_at']); ?></p>
                    </div>
                </div>
                <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($notebook['description']); ?></p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach ($notebook['tags'] as $tag): ?>
                        <span class="bg-gray-200 text-gray-700 p-1 rounded"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-book-open h-4 w-4"></i>
                            <?php echo htmlspecialchars($notebook['views']); ?> views
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-comments h-4 w-4"></i>
                            <?php echo htmlspecialchars($notebook['comments']); ?> comments
                        </span>
                    </div>
                    <button class="text-blue-500 flex items-center gap-1">
                        <i class="fas fa-share h-4 w-4"></i>
                        Share
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function setView(view) {
        const notebookGrid = document.getElementById('notebook-grid');
        if (view === 'grid') {
            notebookGrid.classList.remove('grid-cols-1');
            notebookGrid.classList.add('md:grid-cols-2', 'lg:grid-cols-3');
        } else {
            notebookGrid.classList.add('grid-cols-1');
            notebookGrid.classList.remove('md:grid-cols-2', 'lg:grid-cols-3');
        }
    }
</script>

<div id="settings" class="hidden flex-grow flex flex-col">
    <!-- General Settings Tab -->
    <div class="tabs">
        <div class="tabs-content">
            <div id="general" class="tab-content">
                <div class="card bg-white p-4 rounded shadow">
                    <div class="card-header mb-4">
                        <h2 class="card-title text-xl font-bold">General Settings</h2>
                        <p class="card-description text-gray-500">Manage your bubble's general settings</p>
                    </div>
                    <div class="card-content space-y-4">
                        <div class="space-y-2">
                            <label for="bubble-name" class="block text-sm font-medium text-gray-700">Bubble Name</label>
                            <input type="text" id="bubble-name" name="bubble_name" class="input w-full p-2 border rounded" value="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" required>
                        </div>
                        <div class="space-y-2">
                        <label for="bubble-image" class="block text-sm font-medium">Bubble Image</label>
                        <div class="flex items-center space-x-4">
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="Bubble" class="h-20 w-20 rounded-full">
                            <input type="file" id="bubble-image" name="profile_image" class="input p-2 border rounded">
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary p-2 rounded bg-blue-500 text-white" onclick="updateBubbleDetails()">Save Changes</button>
                </div>
                </div>
            </div>

            <!-- Manage Users Tab -->
            <div id="users" class="tab-content mt-6">
                <div class="card bg-white p-4 rounded shadow">
                    <div class="card-header mb-4">
                        <h2 class="card-title text-xl font-bold">Manage Users</h2>
                        
                    </div>
                    <div class="card-content">
                        <div class="space-y-4">
                            <label for="add-user" class="block text-sm font-medium text-gray-700">Add User to Bubble</label>
                            <input type="text" id="add-user" class="w-full p-2 border rounded" placeholder="Enter username to add">
                            <button class="mt-2 p-2 bg-green-500 text-white rounded" onclick="addUserToBubble()">Add User</button>
                        </div>

                        <h3 class="mt-6 font-semibold text-lg">Current Members</h3>
                        <ul id="user-list" class="space-y-2 mt-4">
                           
                        <?php
                            $members->data_seek(0); // Reset the result set pointer
                            while ($member = $members->fetch_assoc()): ?>
                            <li class="flex items-center justify-between space-x-4">
                                <div class="flex items-center space-x-2">
                                    <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="Profile Image" class="w-8 h-8 rounded-full">
                                    <span><?php echo htmlspecialchars($member['username']); ?></span>
                                </div>
                                <button class="bg-red-500 text-white p-2 rounded" onclick="removeMember(<?php echo $member['id']; ?>)">Remove</button>
                            </li>
                        <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Toggle profile dropdown menu
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

        // Fetch joined bubbles on page load
        document.addEventListener("DOMContentLoaded", fetchJoinedBubbles);

        // Show the selected content section
        function showContent(section) {
            document.getElementById('chat').classList.add('hidden');
            document.getElementById('forum').classList.add('hidden');
            document.getElementById('notebook').classList.add('hidden');
            document.getElementById('settings').classList.add('hidden');
            document.getElementById(section).classList.remove('hidden');

            if (section === 'chat') fetchMessages();
        }

        // Handle message form submission
        document.getElementById('message-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value;
            const bubbleId = <?php echo $bubble_id; ?>;
            const userId = <?php echo $_SESSION['user_id']; ?>;

            fetch('sendBubbleMessage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bubble_id: bubbleId, user_id: userId, message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    window.location.href = `bubblePage.php?bubble_id=${bubbleId}`;
                } else {
                    console.error('Error sending message');
                }
            })
            .catch(error => console.error('Error sending message:', error));
        });

        // Redirect to member's bubble page on member list click
        document.getElementById("member-list").addEventListener("click", function(event) {
            const memberElement = event.target.closest("li[data-member-id]");
            if (memberElement) {
                const memberId = memberElement.getAttribute("data-member-id");
                window.location.href = `indexBubble.php?receiver_id=${memberId}`;               
            }
        });

        // Show chat content on page load
        document.addEventListener("DOMContentLoaded", function() {
            showContent('chat');
        });

        // Toggle dropdown menu visibility
        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('hidden');
            });
        });

        // Toggle dropdown menu visibility
        function toggleDropdown(button) {
            const dropdownMenu = button.nextElementSibling;
            dropdownMenu.classList.toggle('hidden');
        }

        // Show edit message modal
        function showEditModal(messageId, messageContent) {
            document.getElementById('edit_message_id').value = messageId;
            document.getElementById('new_message').value = messageContent;
            document.getElementById('edit-message-modal').classList.remove('hidden');
        }

        // Hide edit message modal
        function hideEditModal() {
            document.getElementById('edit-message-modal').classList.add('hidden');
        }


        document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.remove-button').forEach(button => {
        button.addEventListener('click', async (e) => {
            const memberId = button.dataset.memberId;
            const bubbleId = "<?php echo $bubble_id; ?>"; // Bubble ID passed from PHP

            if (confirm("Are you sure you want to remove this member?")) {
                try {
                    const response = await fetch('removeMember.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ bubble_id: bubbleId, user_id: memberId })
                    });

                    const result = await response.json();
                    if (result.success) {
                        button.closest('li').remove(); // Remove from UI
                        alert('Member removed successfully.');
                    } else {
                        alert('Failed to remove member.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error removing member.');
                }
            }
        });
    });
});



function removeMember(memberId) {
    if (confirm("Are you sure you want to remove this member?")) {
        fetch('removeMember.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bubble_id: <?php echo $bubble_id; ?>, user_id: memberId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Member removed successfully");
                location.reload(); // Refresh the page to update the member list
            } else {
                alert("Error: " + data.message);
            }
        });
    }
}


    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById(tab).classList.remove('hidden');
    }

    function handleRemoveUser(userId) {
        // Implement user removal logic here
        console.log('Removing user with ID:', userId);
    }

    function updateBubbleDetails() {
    const bubbleName = document.getElementById('bubble-name').value;
    const bubbleImage = document.getElementById('bubble-image').files[0];

    const formData = new FormData();
    formData.append('bubble_name', bubbleName);
    formData.append('bubble_id', "<?php echo $bubble_id; ?>");
    if (bubbleImage) formData.append('profile_image', bubbleImage);

    fetch('updateBubbleSettings.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bubble settings updated successfully.');

            // Update the displayed profile picture dynamically
            const profileImageElement = document.querySelector('.card-content img');
            if (profileImageElement && data.new_image) {
                profileImageElement.src = `data:image/jpeg;base64,${data.new_image}`;
            }
        } else {
            alert('Error updating bubble settings.');
        }
    })
    .catch(error => console.error('Error:', error));
}



// Add a user to the bubble
function addUserToBubble() {
    const username = document.getElementById('add-user').value;
    
    fetch('addUserToBubble.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: username, bubble_id: <?php echo $bubble_id; ?> })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User added to bubble.');
            location.reload(); // Reload to reflect changes
        } else {
            alert('Error adding user.');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Remove a user from the bubble
function removeUserFromBubble(userId) {
    fetch('removeUserFromBubble.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, bubble_id: <?php echo $bubble_id; ?> })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User removed from bubble.');
            location.reload(); // Reload to reflect changes
        } else {
            alert('Error removing user.');
        }
    })
    .catch(error => console.error('Error:', error));
}


    </script>
</body>
</html>
