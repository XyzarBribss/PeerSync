<?php
session_start();
include 'config.php';

// Fetch user data from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <!-- Leftmost Sidebar -->
    <div class="fixed top-0 left-0 h-full w-20 bg-gray-800 text-white z-50 flex flex-col items-center">
        <a href="indexTimeline.php"><img src="../login/ps.png" alt="Peerync Logo" class="h-20 w-20 mb-4"></a>
        <a href="exploreBubble.php" class="w-full py-2 text-center hover:bg-blue-400">
            <i class="fas fa-globe fa-2x"></i>
        </a>
        <a href="indexBubble.php" class="w-full py-2 text-center hover:bg-blue-400">
            <i class="fas fa-comments fa-2x"></i>
        </a>
        <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
        <div class="relative mt-auto mb-4">
            <img src="path/to/profile_image.jpg" alt="Profile Image" class="w-8 h-8 rounded-full cursor-pointer" id="profileImage">
            <div class="dropdown-menu absolute right-0 mt-1 w-48 bg-white border border-gray-300 rounded shadow-lg hidden">
                <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-20 flex-grow p-5">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-4">Profile Information</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
            <p><strong>Profile Image:</strong></p>
            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="w-32 h-32 rounded-full">
            <!-- Add more profile fields as needed -->
        </div>
    </div>

    <script>
        // JavaScript for handling dropdown and other interactions
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
                    // Populate the bubble list
                });
        }

        fetchJoinedBubbles();
    </script>
</body>
</html>