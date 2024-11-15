<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

$userId = $_SESSION['user_id'];

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $imageName = basename($_FILES['profile_image']['name']);
    $targetFilePath = $targetDir . $imageName;

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFilePath)) {
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $targetFilePath, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch user data and statistics
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM user_bubble WHERE user_id = u.id) as bubble_count,
        (SELECT COUNT(*) FROM bubble_posts WHERE user_id = u.id) as post_count
        FROM users u WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?php echo htmlspecialchars($userData['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css" rel="stylesheet">
    <style>
        /* Background design */
        body {
            position: relative;
            background-color: #ffffff;
        }
        
        body::before {
            content: '';
            position: fixed;
            display: block;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background-color: #e4f7f7;
            margin: -50px 50px;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            display: block;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background-color: #97ebdb;
            bottom: -50px;
            right: -50px;
            z-index: -1;
            opacity: 0.8;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card {
            background: linear-gradient(135deg, #00c2c7, #0086ad);
            border-radius: 15px;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
        }

        .profile-image-container::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border: 3px solid #00c2c7;
            border-radius: 50%;
        }

        .tab-active {
            color: #00c2c7;
            border-bottom: 3px solid #00c2c7;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            margin: 2rem auto;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-secondary-100 text-white flex justify-between items-center fixed w-full top-0 z-50 px-4 py-2" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity));">
        <div class="flex items-center">
            <a href="indexTimeline.php"><img src="../public/ps.png" alt="Peerync Logo" class="h-12 w-12"></a>
            <span class="text-2xl font-bold ml-2">PeerSync</span>
        </div>
        <div class="flex items-center space-x-4">
            <a href="exploreBubble.php" class="text-gray-600">
                <i class="uil uil-compass"></i> Explore
            </a>
            <a href="indexBubble.php" class="text-gray-600">
                <i class="uil uil-comments"></i> Bubbles
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 pt-20">
        <!-- Profile Header -->
        <div class="profile-card p-8 mb-8">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                <div class="profile-image-container">
                    <img id="profile-picture" 
                         src="<?php echo !empty($userData['profile_image']) ? htmlspecialchars($userData['profile_image']) : 'profile_page/default_profile.png'; ?>" 
                         alt="Profile Picture" 
                         class="w-40 h-40 rounded-full object-cover">
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($userData['username']); ?></h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($userData['email']); ?></p>
                        </div>
                        <button id="edit-profile-picture" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            Edit Profile
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div class="stat-card p-4 text-white text-center">
                            <div class="text-3xl font-bold"><?php echo $userData['bubble_count']; ?></div>
                            <div class="text-sm">Bubbles Joined</div>
                        </div>
                        <div class="stat-card p-4 text-white text-center">
                            <div class="text-3xl font-bold"><?php echo $userData['post_count']; ?></div>
                            <div class="text-sm">Posts Created</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="profile-card mb-8">
            <div class="flex justify-around p-4">
                <button class="tab-button tab-active px-4 py-2" data-tab="posts">
                    <i class="uil uil-postcard mr-1"></i> Posts
                </button>
                <button class="tab-button px-4 py-2" data-tab="bubbles">
                    <i class="uil uil-circle mr-1"></i> Bubbles
                </button>
                <button class="tab-button px-4 py-2" data-tab="settings">
                    <i class="uil uil-setting mr-1"></i> Settings
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="posts-content">
            <div class="grid gap-6">
                <!-- Loading placeholder -->
                <div class="animate-pulse profile-card p-6">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        </div>

        <div class="tab-content hidden" id="bubbles-content">
            <div class="grid md:grid-cols-2 gap-6" id="bubble-list">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <div class="tab-content hidden" id="settings-content">
            <div class="profile-card p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Profile Settings</h3>
                <form class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Username</label>
                        <input type="text" class="w-full p-3 border rounded-lg" value="<?php echo htmlspecialchars($userData['username']); ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" class="w-full p-3 border rounded-lg" value="<?php echo htmlspecialchars($userData['email']); ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Bio</label>
                        <textarea class="w-full p-3 border rounded-lg" rows="4"></textarea>
                    </div>
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div id="edit-picture-modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Update Profile Picture</h2>
                <button id="close-modal" class="text-gray-500">
                    <i class="uil uil-times text-xl"></i>
                </button>
            </div>
            <form id="edit-picture-form" method="post" enctype="multipart/form-data" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <i class="uil uil-image-upload text-4xl text-gray-400"></i>
                    <p class="mt-2 text-sm text-gray-500">Click to upload or drag and drop</p>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 text-gray-600" onclick="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                        Upload Picture
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        function showTab(tabName) {
            tabContents.forEach(content => {
                content.style.display = content.id === `${tabName}-content` ? 'block' : 'none';
            });

            tabButtons.forEach(btn => {
                btn.classList.toggle('tab-active', btn.getAttribute('data-tab') === tabName);
            });
        }

        // Set initial active tab
        showTab('posts');

        // Add click handlers to tabs
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                showTab(button.getAttribute('data-tab'));
            });
        });

        // Profile picture upload functionality
        const modal = document.getElementById('edit-picture-modal');
        const editButton = document.getElementById('edit-profile-picture');
        const closeButton = document.getElementById('close-modal');
        const fileInput = document.getElementById('profile_image');
        const dropZone = document.querySelector('.border-dashed');
        const form = document.getElementById('edit-picture-form');

        function openModal() {
            modal.style.display = 'flex';
            showTab('settings'); // Switch to settings tab when editing profile
        }

        function closeModal() {
            modal.style.display = 'none';
            fileInput.value = ''; // Reset file input
        }

        // Event listeners for modal
        editButton.addEventListener('click', openModal);
        closeButton.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // File upload handling
        dropZone.addEventListener('click', () => fileInput.click());
        
        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    form.submit();
                } else {
                    alert('Please upload an image file');
                }
            }
        }

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        // Settings form handling
        const settingsForm = document.querySelector('#settings-content form');
        settingsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Add your settings update logic here
            alert('Settings updated successfully');
        });
    </script>
</body>
</html>
