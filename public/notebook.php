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
    if ($conn->affected_rows > 0) {
        header("Location: notebook.php?message=Notebook deleted successfully");
        exit();
    } else {
        header("Location: notebook.php?message=Error deleting notebook: " . urlencode($conn->error));
        exit();
    }
}

$notebooks = [];
$user_id = $_SESSION['user_id'];
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string($_GET['search_query']) : '';

if ($search_query) {
    $sql = "SELECT * FROM notebooks WHERE user_id = ? AND name LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_query%";
    $stmt->bind_param("is", $user_id, $search_param);
} else {
    $sql = "SELECT * FROM notebooks WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notebooks[] = $row;
    }
}
$stmt->close();

// Handle notebook name update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_notebook') {
    $notebook_id = intval($_POST['notebook_id']);
    $new_name = str_replace("\'", "'", $_POST['new_name']);
    $user_id = $_SESSION['user_id'];

    $sql = "UPDATE notebooks SET name = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_name, $notebook_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
    exit();
}

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
<body class="bg-gray-100 h-screen">
    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white  flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;">
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
                            <input type="text" id="searchBox" placeholder="Search notebooks..." class="border-2 border-solid p-2 rounded-md float-right mr-6 ml-6 mt-4" oninput="searchNotebooks()">
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

                <div id="edit-notebook-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center z-50">
                    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3 text-center">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Notebook Name</h3>
                            <form id="updateNotebookForm" class="mt-4">
                                <input type="hidden" id="notebook_id" name="notebook_id">
                                <input type="text" id="notebookName" name="new_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <div class="mt-4 flex justify-end space-x-3">
                                    <button type="button" onclick="closeModal()" class="inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                                    <button type="submit" id="saveButton" class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


    <!--Notebook List-->
    <div id="notebooks-list" class="flex flex-wrap mt-10">
    <div class="grid grid-cols-3 gap-6">
        <?php if (!empty($notebooks)): ?>
            <?php foreach ($notebooks as $notebook): ?>
                <div id="notebook-<?php echo $notebook['id']; ?>" class="notebook group relative bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 p-4">
                    <div class="flex flex-col h-full">
                        <div class="flex justify-between items-center mb-3">
                            <h3 id="title-<?php echo $notebook['id']; ?>" class="text-lg font-semibold notebook-title">
                                <?= htmlspecialchars(str_replace("\'", "'", $notebook['name'])) ?>
                            </h3>
                        </div>

                        <div class="text-xs text-gray-400 mt-2">
                            Created: <?= date('M d, Y', strtotime($notebook['created_at'])) ?>
                        </div>

                        <a href="notes.php?notebook_id=<?php echo $notebook['id']; ?>" class="flex-grow">
                            <div class="text-sm text-gray-500">Click to view notes</div>
                        </a>

                        <div class=" flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <button onclick="event.stopPropagation(); editNotebook(<?php echo $notebook['id']; ?>)" 
                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form action="export_to_pdf.php" method="POST" class="inline-block" onclick="event.stopPropagation();">
                                <input type="hidden" name="notebook_id" value="<?php echo $notebook['id']; ?>">
                                <button type="submit" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-full transition-colors">
                                    <i class="fas fa-file-export"></i>
                                </button>
                            </form>

                            <form action="notebook.php" method="POST" class="inline-block" onclick="event.stopPropagation();">
                                <input type="hidden" name="delete_notebook_id" value="<?php echo $notebook['id']; ?>">
                                <button type="submit" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notebooks found.</p>
        <?php endif; ?>
    </div>
</div>
    <script>
        function showAddNotebookModal() {
            document.getElementById('add-notebook-modal').classList.remove('hidden');
        }

        function hideAddNotebookModal() {
            document.getElementById('add-notebook-modal').classList.add('hidden');
        }

        document.getElementById('profileImage').addEventListener('click', function() {
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('hidden');
        });

        function searchNotebooks() {
        const searchQuery = document.getElementById('searchBox').value.toLowerCase();
        const notebooksContainer = document.querySelector('.grid');
        const notebooks = document.querySelectorAll('.notebook');

        notebooks.forEach(notebook => {
            const notebookTitle = notebook.querySelector('.notebook-title').textContent.trim().toLowerCase();
            
            if (notebookTitle.includes(searchQuery)) {
                notebook.classList.remove('hidden');
                notebook.style.display = 'block';
            } else {
                notebook.classList.add('hidden');
                notebook.style.display = 'none';
            }
        });

        // Show "No notebooks found" message if no results
        const visibleNotebooks = document.querySelectorAll('.notebook:not(.hidden)');
        const noResultsMessage = document.querySelector('.no-results');
        
        if (visibleNotebooks.length === 0 && searchQuery !== '') {
            if (!noResultsMessage) {
                const message = document.createElement('p');
                message.className = 'no-results col-span-3 text-center text-gray-500 py-4';
                message.textContent = 'No notebooks found matching your search.';
                notebooksContainer.appendChild(message);
            }
        } else {
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }
    }

    // Add event listener for real-time search
    document.getElementById('searchBox').addEventListener('input', searchNotebooks);

function closeModal() {
    document.getElementById('edit-notebook-modal').classList.add('hidden');
}

function editNotebook(notebookId) {
    const notebookNameElement = document.querySelector(`#notebook-${notebookId} .notebook-title`);
    const currentName = notebookNameElement.textContent.trim();
    
    document.getElementById('notebook_id').value = notebookId;
    document.getElementById('notebookName').value = currentName;

    document.getElementById('edit-notebook-modal').classList.remove('hidden');
}

document.getElementById('updateNotebookForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const button = document.getElementById('saveButton');
    const input = document.getElementById('notebookName');
    const notebookId = document.getElementById('notebook_id').value;
    
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
        const formData = new FormData();
        formData.append('notebook_id', notebookId);
        formData.append('new_name', input.value);
        formData.append('action', 'update_notebook');

        const response = await fetch('notebook.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Failed to update notebook');
        }

        const notebookNameElement = document.querySelector(`#notebook-${notebookId} .notebook-title`);
        if (notebookNameElement) {
            notebookNameElement.textContent = input.value;
        }

        document.getElementById('edit-notebook-modal').classList.add('hidden');
        
    } catch (error) {
        console.error('Error updating notebook:', error);
        alert('Failed to update notebook name');
    } finally {
        button.disabled = false;
        button.textContent = 'Save Changes';
    }
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