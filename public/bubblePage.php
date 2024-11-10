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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubble Posts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    .dropdown:hover .dropdown-menu { display: block; }
    .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: hidden; }
    .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
    .dropdown-menu {z-index: 50; /* Ensure this value is higher than other elements */}
    </style>
</head>
<body class="bg-white h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;
}">
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
                <p>This is the notebook section.</p>
                <main class="flex-1 bg-gray-50">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <button class="gap-2 bg-blue-500 text-white p-2 rounded">
                                <i class="fas fa-plus h-4 w-4"></i>
                                Create Notebook
                            </button>
                            <div class="relative max-w-sm">
                                <i class="fas fa-search absolute left-2 top-2.5 h-4 w-4 text-gray-400"></i>
                                <input type="text" placeholder="Search notebooks..." class="pl-8 p-2 border rounded w-full">
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($notebooks as $notebook): ?>
                                <div class="group relative overflow-hidden transition-colors hover:bg-gray-50 p-4 bg-white rounded shadow">
                                    <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($notebook['title']); ?></h3>
                                        <div class="relative">
                                            <button class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100" aria-label="Open menu">
                                                <i class="fas fa-ellipsis-h h-4 w-4"></i>
                                            </button>
                                            <div class="dropdown-menu absolute right-0 mt-2 w-32 bg-white border border-gray-300 rounded shadow-lg hidden">
                                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Edit</a>
                                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Share</a>
                                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($notebook['description']); ?></p>
                                    </div>
                                    <div>
                                        <a href="notebook.php?id=<?php echo $notebook['id']; ?>" class="text-sm text-blue-500 hover:underline">
                                            View notebook
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </main>
            </div>

            <div id="settings" class="hidden flex-grow flex flex-col">
                <div class="tabs">
                    <div class="tabs-content">
                        <div id="general" class="tab-content">
                            <div class="card bg-white p-4 rounded shadow">
                                <div class="card-header mb-4">
                                    <h2 class="card-title text-xl font-bold">General Settings</h2>
                                    <p class="card-description text-gray-500">Manage your server's general settings</p>
                                </div>
                                <div class="card-content space-y-4">
                                    <div class="space-y-2">
                                        <label for="server-name" class="block text-sm font-medium text-gray-700">Server Name</label>
                                        <input type="text" id="server-name" class="input w-full p-2 border rounded" value="<?php echo htmlspecialchars($bubble['bubble_name']); ?>">
                                    </div>
                                    <div class="space-y-2">
                                        <label for="server-image" class="block text-sm font-medium text-gray-700">Server Image</label>
                                        <div class="flex items-center space-x-4">
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="Server" class="h-20 w-20 rounded-full">
                                            <input type="file" id="server-image" class="input w-full max-w-xs p-2 border rounded">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4 mt-4">
                            <button class="btn btn-primary p-2 rounded bg-blue-500 text-white" onclick="handleChangeServerDetails()">Save Changes</button>
                        </div>
                        <div id="users" class="tab-content mt-6">
                            <div class="card bg-white p-4 rounded shadow">
                                <div class="card-header mb-4">
                                    <h2 class="card-title text-xl font-bold">Manage Users</h2>
                                    <p class="card-description text-gray-500">Add, remove, or edit user permissions</p>
                                </div>
                                <div class="card-content space-y-4">
                                    <?php foreach ($members as $member): ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="<?php echo htmlspecialchars($member['username']); ?>" class="h-10 w-10 rounded-full">
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($member['username']); ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['id']); ?></p>
                                                </div>
                                            </div>
                                            <button class="btn btn-destructive p-2 rounded bg-red-500 text-white" onclick="handleRemoveUser(<?php echo $member['id']; ?>)">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
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

        // Fetch joined bubbles and populate the sidebar
        function fetchJoinedBubbles() {
            fetch("joinedBubble.php")
            .then(response => response.json())
            .then(data => {
                const bubbleList = document.getElementById("bubble-list");
                bubbleList.innerHTML = "";
                data.bubbles.forEach(bubble => {
                    const bubbleItem = document.createElement("li");
                    bubbleItem.className = "bubble-container";
                    bubbleItem.innerHTML = `
                        <a href="bubblePage.php?bubble_id=${bubble.id}" class="block p-2 text-center hover:bg-gray-700">
                            <img src="data:image/jpeg;base64,${bubble.profile_image}" alt="${bubble.bubble_name}" class="w-10 h-10 rounded-full mx-auto">
                        </a>
                    `;
                    bubbleList.appendChild(bubbleItem);
                });
            })
            .catch(error => console.error("Error fetching joined bubbles:", error));
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
    </script>

    
</body>
</html>
