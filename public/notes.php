<?php
session_start();
include 'config.php';
$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$notebooks_query = "SELECT * FROM notebooks WHERE user_id = ?";
$notebooks_stmt = $conn->prepare($notebooks_query);
$notebooks_stmt->bind_param("i", $user_id);
$notebooks_stmt->execute();
$notebooks_result = $notebooks_stmt->get_result();
$notebooks = [];
while ($row = $notebooks_result->fetch_assoc()) {
    $notebooks[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && !isset($_POST['update_note_id'])) {
    $new_note_title = $_POST['title'];
    $new_note_content = $_POST['content'];
    $new_notebook_id = $_POST['notebook_id'] ?? null;

    $insert_note_query = "INSERT INTO notes (Title, ContentType, Content, NotebookID, CreatedAt) VALUES (?, 'text', ?, ?, NOW())";
    $insert_note_stmt = $conn->prepare($insert_note_query);
    $insert_note_stmt->bind_param("ssi", $new_note_title, $new_note_content, $new_notebook_id);

    if ($insert_note_stmt->execute()) {
        header("Location: notes.php?notebook_id=" . $new_notebook_id);
        exit();
    } else {
        echo "Error: " . $insert_note_stmt->error;
    }
    $insert_note_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note_id'])) {
    $edit_note_id = $_POST['update_note_id'];
    $edit_note_title = $_POST['title'];
    $edit_note_content = $_POST['content'];
    $edit_notebook_id = $_POST['notebook_id'] ?? null;

    $update_note_query = "UPDATE notes SET Title = ?, Content = ? WHERE NoteID = ?";
    $update_note_stmt = $conn->prepare($update_note_query);
    $update_note_stmt->bind_param("ssi", $edit_note_title, $edit_note_content, $edit_note_id);

    if ($update_note_stmt->execute()) {
        header("Location: notes.php?notebook_id=" . $edit_notebook_id);
        exit();
    } else {
        echo "Error: " . $update_note_stmt->error;
    }
    $update_note_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    $delete_note_id = $_POST['delete_note_id'];
    $notebook_id = $_POST['notebook_id'] ?? null;

    $delete_note_query = "DELETE FROM notes WHERE NoteID = ?";
    $delete_note_stmt = $conn->prepare($delete_note_query);
    $delete_note_stmt->bind_param("i", $delete_note_id);

    if ($delete_note_stmt->execute()) {
        header("Location: notes.php?notebook_id=" . $notebook_id);
        exit();
    } else {
        echo "Error: " . $delete_note_stmt->error;
    }

    $delete_note_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename') {
    $rename_note_id = $_POST['note_id'];
    $new_title = $_POST['new_title'];

    $rename_note_query = "UPDATE notes SET Title = ? WHERE NoteID = ?";
    $rename_note_stmt = $conn->prepare($rename_note_query);
    $rename_note_stmt->bind_param("si", $new_title, $rename_note_id);

    if ($rename_note_stmt->execute()) {
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false]);
        exit();
    }
    $rename_note_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_note_id = $_POST['note_id'];

    $delete_note_query = "DELETE FROM notes WHERE NoteID = ?";
    $delete_note_stmt = $conn->prepare($delete_note_query);
    $delete_note_stmt->bind_param("i", $delete_note_id);

    if ($delete_note_stmt->execute()) {
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false]);
        exit();
    }
    $delete_note_stmt->close();
}

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/9ai4ffzegkn572ycqvbetrnlp87ikc35prqpzfnpafymqzfe/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
</head>

<style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
    .main-content {
        margin-top: 72px; 
        margin-left: 64px; 
        height: calc(100vh - 72px);
        width: calc(100% - 64px);
        position: relative;
        z-index: 30;
        overflow: hidden;
    }
</style>

<body class="bg-gray-100">
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
    <div id="sidebar" class="fixed top-0 left-0 h-full mt-10 text-white z-50 flex flex-col items-center sidebar transition-all duration-300 shadow-lg border-r border-gray-300" style="width: 64px; background-color: rgb(70 130 180 / 50%);">
        <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
    </div>
    
    

    <!-- Main Content -->
    <div class="main-content">
        <div class="flex h-full overflow-hidden -ml-[1px]">
            <!-- Left Column - Notes List -->
            <div class="flex w-80 flex-col border-r bg-white">
                <!-- Header -->
                <header class="flex h-14 items-center gap-4 border-b bg-primary px-6 text-black">
                    <button class="shrink-0 p-2 text-black hover:bg-gray-100 hover:text-gray-700 rounded-lg transition-colors" onclick="window.location.href='notebook.php'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
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
                    <h1 class="text-lg font-semibold truncate"><?php echo $notebook_title; ?></h1>
                </header>

                <!-- Fixed Search Bar -->
                <div class="flex flex-col gap-3 p-4 border-b">
                    <div class="relative w-full">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4 opacity-50"></i>
                        <input type="text" id="searchNotes" placeholder="Search notes..." 
                            class="h-9 w-full pl-10 pr-4 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            oninput="searchNotes()">
                    </div>
                    <button onclick="showNoteModal()" 
                        class="flex h-9 w-full items-center justify-center gap-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Note
                    </button>
                </div>

                <!-- Scrollable Notes List -->
                <div class="h-[calc(100vh-244px)] overflow-y-auto">
                    <?php if (empty($notes)): ?>
                        <p class="text-gray-600 text-center py-8">No notes available.</p>
                    <?php else: ?>
                        <div class="grid gap-2 p-2">
                            <?php foreach ($notes as $note): ?>
                                <div class="bg-white rounded-lg border shadow-sm cursor-pointer transition-colors hover:bg-gray-50 relative group"
                                    onclick="showNoteDetails(<?php echo htmlspecialchars(json_encode($note)); ?>)">
                                    <div class="p-4">
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-base font-semibold"><?php echo htmlspecialchars($note['Title']); ?></h3>
                                            <div class="relative" onclick="event.stopPropagation()">
                                                <button class="p-1 rounded-full hover:bg-gray-200 transition-colors opacity-0 group-hover:opacity-100" 
                                                    onclick="toggleNoteMenu(<?php echo $note['NoteID']; ?>)">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                                    </svg>
                                                </button>
                                                <div id="noteMenu_<?php echo $note['NoteID']; ?>" 
                                                    class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border hidden z-50">
                                                    <div class="py-1">
                                                        <button onclick="renameNote(<?php echo $note['NoteID']; ?>, '<?php echo htmlspecialchars(addslashes($note['Title'])); ?>')" 
                                                            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 transition-colors flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                            Rename
                                                        </button>
                                                        <button onclick="deleteNote(<?php echo $note['NoteID']; ?>)" 
                                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <span><?php echo date('M d, Y', strtotime($note['CreatedAt'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                    // Close all note menus when clicking outside
                    document.addEventListener('click', function(event) {
                        const menus = document.querySelectorAll('[id^="noteMenu_"]');
                        menus.forEach(menu => {
                            if (!menu.contains(event.target) && !event.target.closest('button[onclick^="toggleNoteMenu"]')) {
                                menu.classList.add('hidden');
                            }
                        });
                    });

                    function toggleNoteMenu(noteId) {
                        const menu = document.getElementById(`noteMenu_${noteId}`);
                        const allMenus = document.querySelectorAll('[id^="noteMenu_"]');
                        
                        // Hide all other menus
                        allMenus.forEach(m => {
                            if (m !== menu) {
                                m.classList.add('hidden');
                            }
                        });
                        
                        // Toggle current menu
                        menu.classList.toggle('hidden');
                    }

                    function renameNote(noteId, currentTitle) {
                        const newTitle = prompt('Enter new title:', currentTitle);
                        if (newTitle && newTitle.trim() !== '' && newTitle !== currentTitle) {
                            // Make an AJAX call to update the note title
                            fetch('notes.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=rename&note_id=${noteId}&new_title=${encodeURIComponent(newTitle)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Failed to rename note. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while renaming the note.');
                            });
                        }
                    }

                    function deleteNote(noteId) {
                        if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
                            // Make an AJAX call to delete the note
                            fetch('notes.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=delete&note_id=${noteId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Failed to delete note. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while deleting the note.');
                            });
                        }
                    }
                </script>
            </div>

            <div class="flex-1 overflow-hidden bg-white">
                <!-- Empty State -->
                <div id="emptyStateMessage" class="flex flex-col items-center justify-center h-full text-gray-500">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <p class="text-lg">Select a note to view or edit</p>
                </div>

                <!-- Note Form -->
                <form id="noteDetailsForm" method="POST" action="notes.php" class="hidden h-full">
                    <input type="hidden" name="update_note_id" id="update_note_id">
                    <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
                    <input type="hidden" name="title" id="noteDetailsTitle">
                    <div class="prose prose-sm h-full max-w-none">
                        <textarea id="noteDetailsContent" name="content" 
                            class="w-full h-full border-0 focus:ring-0 rounded-none" required></textarea>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div id="modalBackdrop" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl">
            <form id="noteForm" action="notes.php" method="POST" enctype="multipart/form-data" 
                class="bg-white rounded-xl shadow-2xl p-6 m-4">
                <input type="hidden" name="notebook_id" value="<?php echo htmlspecialchars($notebook_id); ?>">
                <input type="text" name="title" placeholder="Note Title" 
                    class="w-full px-4 py-2 text-xl font-semibold border-2 border-gray-200 rounded-lg mb-4" required>
                <textarea id="noteContent" name="content" placeholder="Start writing your note..." 
                    class="w-full border-2 border-gray-200 rounded-lg mb-4" required></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600" 
                        onclick="hideNoteModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" 
                        onclick="saveNotes()">Save Note</button>
                </div>
            </form>
        </div>
    </div>

<script>
    // Initialize TinyMCE
    tinymce.init({
        selector: '#noteContent, #noteDetailsContent',
        plugins: [
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 
            'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'save'
        ],
        toolbar: 'save | undo redo | blocks fontfamily fontsize | bold italic underline strikethrough backcolor forecolor | link image media table | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 'calc(100vh - 200px)',
        autoresize_overflow_padding: 0,
        autoresize_bottom_margin: 0,
        resize: true,
        overflow_y: 'auto',
        menubar: true,
        branding: false,
        save_enablewhendirty: false,
        save_onsavecallback: function() {
            saveNoteChanges(new Event('save'));
            return false;
        },
        setup: function(editor) {
            editor.on('init', function() {
                // This ensures TinyMCE is fully loaded before we try to set content
                if (typeof initialNoteContent !== 'undefined') {
                    editor.setContent(initialNoteContent);
                }
            });
        }
    });

    let currentNoteId = null;

    function showNoteDetails(note) {
        document.getElementById('emptyStateMessage').classList.add('hidden');
        document.getElementById('noteDetailsForm').classList.remove('hidden');
        document.getElementById('update_note_id').value = note.NoteID;
        document.getElementById('noteDetailsTitle').value = note.Title;
        currentNoteId = note.NoteID;
        
        if (tinymce.get('noteDetailsContent')) {
            tinymce.get('noteDetailsContent').setContent(note.Content || '');
        } else {
            setTimeout(function() {
                if (tinymce.get('noteDetailsContent')) {
                    tinymce.get('noteDetailsContent').setContent(note.Content || '');
                }
            }, 500);
        }
    }

    function saveNoteChanges(event) {
        event.preventDefault();
        tinymce.triggerSave();
        
        const form = document.getElementById('noteDetailsForm');
        const formData = new FormData(form);
        
        fetch('notes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                // Refresh the page to show updated content
                window.location.reload();
            } else {
                console.error('Failed to save changes');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function hideNoteDetails() {
        document.getElementById('noteDetailsForm').classList.add('hidden');
        document.getElementById('emptyStateMessage').classList.remove('hidden');
        currentNoteId = null;
    }

    function showNoteModal() {
        document.getElementById('modalBackdrop').classList.remove('hidden');
        document.getElementById('noteForm').classList.remove('hidden');
    }

    function hideNoteModal() {
        document.getElementById('modalBackdrop').classList.add('hidden');
        document.getElementById('noteForm').classList.add('hidden');
    }

    function saveNotes() {
        tinymce.triggerSave();
    }

    document.getElementById('profileImage').addEventListener('click', function() {
        const dropdownMenu = this.nextElementSibling;
        dropdownMenu.classList.toggle('hidden');
    });

    function searchNotes() {
    const searchTerm = document.getElementById('searchNotes').value.toLowerCase();
    const notesList = document.getElementById('notesList');
    const notes = notesList.getElementsByTagName('li');
    let hasVisibleNotes = false;

    for (let note of notes) {
        const title = note.querySelector('h3').textContent.toLowerCase();
        const content = note.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || content.includes(searchTerm)) {
            note.style.display = '';
            hasVisibleNotes = true;
        } else {
            note.style.display = 'none';
        }
    }

    // Show/hide "no results" message
    let noResultsMsg = notesList.querySelector('.no-results-message');
    if (!hasVisibleNotes) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('p');
            noResultsMsg.className = 'no-results-message text-gray-600 text-center py-8';
            noResultsMsg.textContent = 'No notes found matching your search.';
            notesList.appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Add event listener for real-time search
document.getElementById('searchNotes').addEventListener('input', searchNotes);
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