<?php
session_start();
include 'config.php';

// Handle notebook creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['notebook_name'])) {
    $notebook_name = $conn->real_escape_string($_POST['notebook_name']);
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

    // Insert the new notebook into the notebooks table
    $sql = "INSERT INTO notebooks (user_id, name, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $notebook_name);

    if ($stmt->execute()) {
        header("Location: notebook.php?message=Notebook added successfully");
        exit();
    } else {
        header("Location: notebook.php?message=Error: " . urlencode($stmt->error));
        exit();
    }

    $stmt->close();
}

// Handle notebook deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_notebook_id'])) {
    $notebook_id = intval($_POST['delete_notebook_id']);
    $conn->query("DELETE FROM notebooks WHERE id = $notebook_id");
    $sql = "DELETE FROM notebooks WHERE id = $notebook_id";
    if ($conn->query($sql) === TRUE) {
        header("Location: notebook.php?message=Notebook deleted successfully");
        exit();
    } else {
        header("Location: notebook.php?message=Error deleting notebook: " . urlencode($conn->error));
        exit();
    }
}

// Fetch notebooks for the logged-in user
$notebooks = [];
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session
$sql = "SELECT * FROM notebooks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notebooks[] = $row;
    }
}
$stmt->close();

// Fetch user data from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
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
    <title>NotebookPS</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/9ai4ffzegkn572ycqvbetrnlp87ikc35prqpzfnpafymqzfe/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .sticky-note {
            background-color: #ffeb3b;
            padding: 20px;
            margin: 10px;
            width: 200px;
            height: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .sticky-note:hover {
            transform: scale(1.05);
        }
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .content { margin-top: 64px; margin-left: 80px; transition: margin-left 0.3s; }
        .right-sidebar { position: fixed; right: 0; height: calc(100% - 64px); overflow-y: auto; z-index: 100; margin-top: 80px; }
        .notebook {
            background-color: #fdfd96;
            border: 2px solid #f4f4f4;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            width: 250px;
            height: 300px;
        }
        .notebook:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }
        .notebook-header {
            background-color: #ffeb3b;
            border-bottom: 2px solid #f4f4f4;
            padding: 15px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .notebook-content {
            padding: 15px;
        }
    </style> 
</head>
<body class="bg-gray-100 p-6">
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
    <div id="sidebar" class="fixed top-0 left-0 h-full mt-10 text-white z-50 flex flex-col items-center sidebar transition-all duration-300 shadow-lg border-r border-gray-300" style="width: 64px; background-color: rgb(70 130 180 / 50%) /* #4682b4 */;">
        <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
    </div>

    <!-- Main Container -->
    <div id="main-content" class="content pt-8">
        <div class="container mx-auto">
            <div id="notebooks-container" class="mb-6">
                <div class="">
                    <div class="container mx-auto px-4 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-semibold">Your Notebooks</h2>
                            <button class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded shadow flex items-center" onclick="showAddNotebookModal()">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add Notebook</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Add Notebook Modal -->
                <div id="add-notebook-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-semibold mb-4">Add Notebook</h2>
                        <form action="notebook.php" method="POST">
                            <input type="text" name="notebook_name" class="border p-2 w-full mb-4" placeholder="Notebook Name" required>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Add</button>
                            <button type="button" class="bg-red-500 text-white px-4 py-2 rounded" onclick="hideAddNotebookModal()">Cancel</button>
                        </form>
                    </div>
                </div>

                <div id="notebooks-list" class="flex flex-wrap mt-10">
    <div class="grid grid-cols-3 gap-6">
        <?php if (!empty($notebooks)): ?>
            <?php foreach ($notebooks as $notebook): ?>
                <a href="notes.php?notebook_id=<?php echo $notebook['id']; ?>" class="notebook group relative hover:shadow-lg transition-shadow block">
                    <div class="notebook-header">
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($notebook['name']); ?></h3>
                    </div>
                    <div class="notebook-content">
                        <p class="text-sm text-gray-500">Click to open notebook</p>
                        <div class="flex justify-between mt-4 absolute bottom-0 left-0 right-0 p-4">
                            <form action="notebook.php" method="POST">
                                <input type="hidden" name="delete_notebook_id" value="<?php echo $notebook['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="sr-only">Delete notebook</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notebooks found.</p>
        <?php endif; ?>
    </div>
</div>
            </div>
        </div>
    </div>

    <script>
        function showAddNotebookModal() {
            document.getElementById('add-notebook-modal').classList.remove('hidden');
        }

        function hideAddNotebookModal() {
            document.getElementById('add-notebook-modal').classList.add('hidden');
        }

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
    </script>
</body>
</html>