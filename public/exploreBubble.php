<?php
session_start();
include 'config.php';

// Fetch user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch bubbles the user is a member of
$sql = "SELECT b.id, b.bubble_name, b.profile_image, b.description, u.username AS creator, 
  (SELECT COUNT(*) FROM user_bubble ub WHERE ub.bubble_id = b.id) AS member_count 
  FROM bubbles b 
  JOIN users u ON b.creator_id = u.id
  JOIN user_bubble ub ON b.id = ub.bubble_id
  WHERE ub.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$your_bubbles_result = $stmt->get_result();
$stmt->close();

// Fetch bubbles the user is not a member of
$sql = "SELECT b.id, b.bubble_name, b.profile_image, b.description, u.username AS creator, 
  (SELECT COUNT(*) FROM user_bubble ub WHERE ub.bubble_id = b.id) AS member_count 
  FROM bubbles b 
  JOIN users u ON b.creator_id = u.id
  WHERE b.id NOT IN (SELECT bubble_id FROM user_bubble WHERE user_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_bubbles_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bubble Posts</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar {
      width: 80px;
      transition: width 0.3s;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      overflow: hidden;
    }
    .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }

    .content {
      margin-top: 64px; /* Adjust based on navbar height */
      margin-left: 80px; /* Adjust based on sidebar width */
      transition: margin-left 0.3s;
    }
    .right-sidebar {
      position: fixed;
      right: 0;
      height: calc(100% - 64px); /* Adjust based on navbar height */
      overflow-y: auto;
      z-index: 100;
      margin-top: 80px;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
  </style>
</head>
<body class="bg-blue-50">
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

  <!-- Main Container -->
  <div class="content pt-16 pl-20 pr-20">
    <div class="container mx-auto">
      <!-- Slider Placeholder -->
      <div class="w-full h-72 bg-sky-800 rounded-lg mb-4"></div>
      
      <div class="flex justify-between items-center mb-4">
        <div>
            <div class="tab cursor-pointer p-2 mr-2 bg-sky-800 text-white rounded inline-block active" data-tab="your-bubbles">Your Bubbles</div>
            <div class="tab cursor-pointer p-2 bg-sky-800 text-white rounded inline-block" data-tab="available-bubbles">Available Bubbles</div>
        </div>
        <button id="create-bubble-button" class="bg-blue-500 text-white px-4 py-2 rounded">Create Bubble</button>
      </div>

      <div id="your-bubbles" class="tab-content active">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <?php while ($bubble = $your_bubbles_result->fetch_assoc()): ?>
            <div class="relative bg-gray-200 rounded-lg overflow-hidden shadow-lg transition-transform transform hover:-translate-y-1 flex flex-col justify-between h-64">
              <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" class="w-full h-24 object-cover">
              <div class="p-2 flex-grow">
                <div class="text-lg font-bold mb-1"><?php echo htmlspecialchars($bubble['bubble_name']); ?></div>
                <!-- Additional content here -->
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div id="available-bubbles" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <?php while ($bubble = $available_bubbles_result->fetch_assoc()): ?>
            <div class="relative bg-gray-200 rounded-lg overflow-hidden shadow-lg transition-transform transform hover:-translate-y-1 flex flex-col justify-between h-64">
              <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" class="w-full h-24 object-cover">
              <div class="p-2 flex-grow">
                <div class="text-lg font-bold mb-1"><?php echo htmlspecialchars($bubble['bubble_name']); ?></div>
              </div>
              <div class="flex justify-between items-center p-2 bg-gray-300 border-t border-gray-400">
                <div class="text-sm text-gray-600">
                  Created by: <?php echo htmlspecialchars($bubble['creator']); ?>
                  <span class="ml-1">(<?php echo $bubble['member_count']; ?> members)</span>
                </div>
                <button class="bg-blue-500 text-white px-2 py-1 rounded text-sm" onclick="window.location.href='joinBubble.php?bubble_id=<?php echo $bubble['id']; ?>'">Join</button>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>

    <!-- Modal for Create Bubble Form -->
    <div id="create-bubble-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg">
            <span id="closeBubbleModal" class="cursor-pointer text-red-500 float-right">&times;</span>
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
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create Bubble</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('create-bubble-button').addEventListener('click', () => {
            document.getElementById('create-bubble-modal').classList.remove('hidden');
        });

        document.getElementById('closeBubbleModal').addEventListener('click', () => {
            document.getElementById('create-bubble-modal').classList.add('hidden');
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
                        bubbleItem.className = "bubble-container";
                        bubbleItem.innerHTML = `
                            <a href="bubblePage.php?bubble_id=${bubble.id}" class="block p-2 text-center hover:bg-gray-700">
                                <img src="data:image/jpeg;base64,${bubble.profile_image}" alt="${bubble.bubble_name}" class="w-10 h-10 rounded-full mx-auto">
                            </a>
                        `;
                        bubbleList.appendChild(bubbleItem);
                    });
                })
                .catch(error => {
                    console.error("Error fetching joined bubbles:", error);
                });
        }

        // Fetch joined bubbles on page load
        document.addEventListener("DOMContentLoaded", fetchJoinedBubbles);

            // Tab switching functionality
            document.querySelectorAll('.tab').forEach(tab => {
              tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => {
                  t.classList.remove('active');
                  t.classList.remove('bg-sky-800');
                });
                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
                this.classList.add('active');
                this.classList.add('bg-sky-800');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
              });
            });
    </script>
</body>
</html>
