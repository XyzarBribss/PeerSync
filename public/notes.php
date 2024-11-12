<?php
session_start();
include 'config.php';
$user_id = $_SESSION['user_id'];

// Fetch user data from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch notebooks data from the database
$notebooks_query = "SELECT * FROM notebooks WHERE user_id = ?";
$notebooks_stmt = $conn->prepare($notebooks_query);
$notebooks_stmt->bind_param("i", $user_id);
$notebooks_stmt->execute();
$notebooks_result = $notebooks_stmt->get_result();
$notebooks = [];
while ($row = $notebooks_result->fetch_assoc()) {
    $notebooks[] = $row;
}

// Handle form submission for adding a new note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && !isset($_POST['update_note_id'])) {
    $new_note_title = $_POST['title'];
    $new_note_content = $_POST['content'];
    $new_notebook_id = $_POST['notebook_id'] ?? null;

    // Insert the new note into the database
    $insert_note_query = "INSERT INTO notes (Title, ContentType, Content, NotebookID, CreatedAt) VALUES (?, 'text', ?, ?, NOW())";
    $insert_note_stmt = $conn->prepare($insert_note_query);
    $insert_note_stmt->bind_param("ssi", $new_note_title, $new_note_content, $new_notebook_id);

    if ($insert_note_stmt->execute()) {
        // Redirect to the same page to show the new note
        header("Location: notes.php?notebook_id=" . $new_notebook_id);
        exit();
    } else {
        echo "Error: " . $insert_note_stmt->error;
    }
    $insert_note_stmt->close();
}

// Handle note update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note_id'])) {
    $edit_note_id = $_POST['update_note_id'];
    $edit_note_title = $_POST['title'];
    $edit_note_content = $_POST['content'];
    $edit_notebook_id = $_POST['notebook_id'] ?? null;

    // Update the note in the database
    $update_note_query = "UPDATE notes SET Title = ?, Content = ? WHERE NoteID = ?";
    $update_note_stmt = $conn->prepare($update_note_query);
    $update_note_stmt->bind_param("ssi", $edit_note_title, $edit_note_content, $edit_note_id);

    if ($update_note_stmt->execute()) {
        // Redirect to the same page to show the updated note
        header("Location: notes.php?notebook_id=" . $edit_notebook_id);
        exit();
    } else {
        echo "Error: " . $update_note_stmt->error;
    }
    $update_note_stmt->close();
}

// Handle note deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    $delete_note_id = $_POST['delete_note_id'];
    $notebook_id = $_POST['notebook_id'] ?? null;

    // Delete the note from the database
    $delete_note_query = "DELETE FROM notes WHERE NoteID = ?";
    $delete_note_stmt = $conn->prepare($delete_note_query);
    $delete_note_stmt->bind_param("i", $delete_note_id);

    if ($delete_note_stmt->execute()) {
        // Redirect to the same page to show the updated list of notes
        header("Location: notes.php?notebook_id=" . $notebook_id);
        exit();
    } else {
        echo "Error: " . $delete_note_stmt->error;
    }

    $delete_note_stmt->close();
}



// Fetch notes data from the database
$notebook_id = $_GET['notebook_id'] ?? null;
if ($notebook_id) {
    $notes_query = "SELECT * FROM notes WHERE NotebookID = ?";
    $notes_stmt = $conn->prepare($notes_query);
    $notes_stmt->bind_param("i", $notebook_id);
    $notes_stmt->execute();
    $notes_result = $notes_stmt->get_result();
    $notes = [];
    while ($row = $notes_result->fetch_assoc()) {
        $notes[] = $row;
    }
    $notes_stmt->close();
} else {
    $notes = [];
}

// Handle search for a specific note
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_term'])) {
    $search_term = '%' . $_GET['search_term'] . '%';
    $search_query = "SELECT * FROM notes WHERE NotebookID = ? AND Title LIKE ?";
    $search_stmt = $conn->prepare($search_query);
    $search_stmt->bind_param("is", $notebook_id, $search_term);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();
    $notes = [];
    while ($row = $search_result->fetch_assoc()) {
        $notes[] = $row;
    }
    $search_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/9ai4ffzegkn572ycqvbetrnlp87ikc35prqpzfnpafymqzfe/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<style>
    .dropdown:hover .dropdown-menu { display: block; }
    .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: hidden; }
    .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 999; }
    .content { margin-top: 64px; margin-left: 80px; transition: margin-left 0.3s; }
</style>
<body class="bg-gray-100 p-6">

    <!-- Navbar -->
    <nav class="navbar bg-secondary-100 text-white flex justify-between items-center" style="background-color: rgb(43 84 126 / var(--tw-bg-opacity)) /* #2b547e */;">
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

    <!-- Main Content -->
    <div class="content pt-8">
        <div class="">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center gap-4">
                <button class="bg-transparent hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 border border-gray-400 rounded shadow" aria-label="Go back" onclick="window.location.href='notebook.php'">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold"></h1>
                    <?php
                    $notebook_id = $_GET['notebook_id'] ?? null;
                    $notebook_title = "Notebook Title";

                    if ($notebook_id) {
                        foreach ($notebooks as $notebook) {
                            if ($notebook['id'] == $notebook_id) {
                                $notebook_title = htmlspecialchars($notebook['name']);
                                break;
                            }
                        }
                    }
                    ?>
                    <h1 class="text-xl font-semibold"><?php echo $notebook_title; ?></h1>
                    <div class="relative ml-4">
                        <form method="GET" action="notes.php">
                            <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
                            <input type="text" name="search_term" id="searchNotes" placeholder="Search notes..." class="border-2 h-10 w-full p-2 rounded" oninput="searchNotes()">
                            <button type="submit" class="absolute right-0 top-0 mt-2 mr-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="ml-auto">
                        <button class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded shadow flex items-center" onclick="showNoteModal()">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Note</span>
                        </button>
                    </div>
                </div>           
            </div>
        </div>
    </div>
<div class="container mx-auto px-4 py-4">
    <div class="rounded p-4">
        <h2 class="text-lg font-semibold mb-4">Notes</h2>
        <?php if (empty($notes)): ?>
    <p class="text-gray-600">No notes available.</p>
<?php else: ?>
    <ul id="notesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($notes as $note): ?>
            <li class="bg-yellow-200 p-6 rounded-lg shadow-lg transform transition duration-500 hover:scale-105 cursor-pointer overflow-hidden" style="height: 250px;" onclick="showNoteDetails(<?php echo htmlspecialchars(json_encode($note)); ?>)">
                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($note['Title']); ?></h3>
                
                <small class="text-gray-500">Created at: <?php echo htmlspecialchars($note['CreatedAt']); ?></small>
                <form method="POST" action="notes.php" class="absolute top-2 right-2" onsubmit="return confirm('Are you sure you want to delete this note?');">
                    <input type="hidden" name="delete_note_id" value="<?php echo $note['NoteID']; ?>">
                    <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
    </div>
</div>

<script>
    function showNoteDetails(note) {
        document.getElementById('noteDetailsTitle').innerText = note.Title;
        document.getElementById('noteDetailsContent').innerHTML = note.Content;
        document.getElementById('noteDetailsModal').style.display = 'block';
    }

    function hideNoteDetails() {
        document.getElementById('noteDetailsModal').style.display = 'none';
    }
</script>

    <!-- Note Details Modal -->
    <div id="noteDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;">
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 40px; border-radius: 10px; border: 2px solid #49759E; width: 60%; height: 90%; overflow-y: auto;">
            <form method="POST" action="notes.php">
                <input type="hidden" name="update_note_id" id="update_note_id">
                <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
                <input type="text" name="title" id="noteDetailsTitle" class="border-2 h-10 w-full p-2 mt-2" required>
                <textarea id="noteDetailsContent" name="content" class="border-2 h-40 w-full p-2 mt-2" required></textarea>
                <button type="submit" class="mt-3 p-2 bg-blue-500 text-white rounded" onclick="saveNotes()">Save Changes</button>
                <button type="button" class="mt-3 p-2 bg-red-500 text-white rounded" onclick="hideNoteDetails()">Cancel</button>
            </form>
        </div>
    </div>

  

    <!-- Modal Backdrop -->
    <div id="modalBackdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;">
        <!-- Modal Form for Add Note -->
        <form id="noteForm" action="notes.php" method="POST" enctype="multipart/form-data" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 10px; border: 2px solid #49759E; width: 80%;">
    <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
    <input type="text" name="title" placeholder="Title" class="border-2 h-10 w-full p-2 mt-2" required>
    <textarea id="noteContent" name="content" placeholder="Content" class="border-2 h-40 w-full p-2 mt-2" required></textarea>
    <button type="submit" class="mt-3 p-2 bg-blue-500 text-white rounded" onclick="saveNotes()">Save Notes</button>
    <button type="button" class="mt-3 p-2 bg-red-500 text-white rounded" onclick="hideNoteModal()">Cancel</button>
</form>
    </div>

    <script>
    // Initialize TinyMCE
    tinymce.init({
        selector: '#noteContent, #noteDetailsContent',
        plugins: [
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
            'checklist', 'mediaembed', 'casechange', 'export', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown',
            'importword', 'exportword', 'exportpdf'
        ],
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        tinycomments_mode: 'embedded',
        tinycomments_author: 'Author name',
        mergetags_list: [
            { value: 'First.Name', title: 'First Name' },
            { value: 'Email', title: 'Email' },
        ],
        ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
        exportpdf_converter_options: { 'format': 'Letter', 'margin_top': '1in', 'margin_right': '1in', 'margin_bottom': '1in', 'margin_left': '1in' },
        exportword_converter_options: { 'document': { 'size': 'Letter' } },
        importword_converter_options: { 'formatting': { 'styles': 'inline', 'resets': 'inline', 'defaults': 'inline', } },
    });

    function showNoteDetails(note) {
        document.getElementById('update_note_id').value = note.NoteID;
        document.getElementById('noteDetailsTitle').value = note.Title;
        tinymce.get('noteDetailsContent').setContent(note.Content);
        document.getElementById('noteDetailsModal').style.display = 'block';
    }

    function hideNoteDetails() {
        document.getElementById('noteDetailsModal').style.display = 'none';
    }

    function saveNotes() {
        tinymce.triggerSave();
    }

    function showNoteModal() {
        document.getElementById('modalBackdrop').style.display = 'block';
        document.getElementById('noteForm').style.display = 'block';
    }

    function hideNoteModal() {
        document.getElementById('modalBackdrop').style.display = 'none';
        document.getElementById('noteForm').style.display = 'none';
    }

    // Toggle dropdown menu
    document.getElementById('profileImage').addEventListener('click', function() {
        const dropdownMenu = this.nextElementSibling;
        dropdownMenu.classList.toggle('hidden');
    });

    function searchNotes() {
    let input = document.getElementById("searchNotes").value.toLowerCase();
    let notes = document.querySelectorAll(".note-item"); // Assuming .note-item is the class for each note.

    notes.forEach(note => {
        let noteContent = note.textContent.toLowerCase();
        if (noteContent.includes(input)) {
            note.style.display = ""; // Show matching notes.
        } else {
            note.style.display = "none"; // Hide non-matching notes.
        }
    });
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

        document.addEventListener("DOMContentLoaded", fetchJoinedBubbles);
    </script>
</body>
</html>