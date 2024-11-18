<?php
// This file will be included in bubblePage.php
?>
<div id="notebook" class="hidden flex-grow flex flex-col">
    <h2 class="text-2xl font-bold mb-4">Notebook</h2>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Bubble Notebooks</h1>
        <button onclick="openShareModal()" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
            <i class="fas fa-share-alt mr-2"></i>Share Your Notebook
        </button>
    </div>

    <!-- Notebook Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        // Fetch shared notebooks for this bubble
        $notebooks_query = "SELECT n.*, u.username, u.profile_image, np.permission_level 
                          FROM notebooks n 
                          JOIN users u ON n.user_id = u.id 
                          JOIN notebook_permissions np ON n.id = np.notebook_id 
                          WHERE np.bubble_id = ?
                          ORDER BY n.created_at DESC";
        $stmt = $conn->prepare($notebooks_query);
        $stmt->bind_param("i", $bubble_id);
        $stmt->execute();
        $shared_notebooks = $stmt->get_result();
        
        while ($notebook = $shared_notebooks->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo htmlspecialchars($notebook['profile_image']); ?>" 
                             alt="Profile" 
                             class="w-10 h-10 rounded-full">
                        <div>
                            <h3 class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($notebook['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                by <?php echo htmlspecialchars($notebook['username']); ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($notebook['user_id'] == $user_id): ?>
                        <div class="relative">
                            <button onclick="toggleMenu(<?php echo $notebook['id']; ?>)" 
                                    class="p-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div id="menu-<?php echo $notebook['id']; ?>" 
                                 class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10">
                                <a href="#" onclick="editPermissions(<?php echo $notebook['id']; ?>)" 
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-lock mr-2"></i>Edit Permissions
                                </a>
                                <a href="#" onclick="removeShare(<?php echo $notebook['id']; ?>)" 
                                   class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                    <i class="fas fa-trash mr-2"></i>Remove Share
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>
                        <i class="fas <?php echo $notebook['permission_level'] === 'edit' ? 'fa-edit' : 'fa-eye'; ?> mr-2"></i>
                        <?php echo $notebook['permission_level'] === 'edit' ? 'Can Edit' : 'View Only'; ?>
                    </span>
                    <span>
                        <i class="far fa-clock mr-2"></i>
                        <?php echo date('M d, Y', strtotime($notebook['created_at'])); ?>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    // Modal Functions
    function openShareModal() {
        document.getElementById('shareModal').classList.remove('hidden');
    }

    function closeShareModal() {
        document.getElementById('shareModal').classList.add('hidden');
    }

    function toggleMenu(notebookId) {
        const menu = document.getElementById(`menu-${notebookId}`);
        document.querySelectorAll('[id^="menu-"]').forEach(m => {
            if (m.id !== `menu-${notebookId}`) m.classList.add('hidden');
        });
        menu.classList.toggle('hidden');
    }

    // Share Notebook
    function handleShare(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        fetch('shareNotebook.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeShareModal();
                location.reload();
            } else {
                alert(data.message || 'Error sharing notebook');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sharing notebook');
        });
    }

    // Remove Share
    function removeShare(notebookId) {
        if (confirm('Are you sure you want to remove this notebook share?')) {
            fetch('removeNotebookShare.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notebook_id: notebookId,
                    bubble_id: <?php echo $bubble_id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error removing share');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing share');
            });
        }
    }

    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('[id^="menu-"]') && 
            !event.target.closest('button')) {
            document.querySelectorAll('[id^="menu-"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
</script>
