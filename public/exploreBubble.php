<?php
session_start();
include 'config.php';

// Fetch search for explore
$bubbles = [];
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session


// Fetch user ID from session
$user_id = $_SESSION['user_id']; // Fetch user data
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .dropdown:hover .dropdown-menu { display: block; }
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .sidebar { width: 80px; transition: width 0.3s; position: fixed; top: 0; left: 0; height: 100%; overflow: visible; }

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
     
      <!-- Success Message Container -->
      <div id="success-message" class="hidden fixed top-[70px] left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md mx-auto pt-3">
        <div class="bg-white bg-opacity-80 text-white px-6 py-3 rounded-lg shadow-lg text-center backdrop-blur-sm">
          <span class="text-black text-lg font-medium">Bubble created successfully!</span>
        </div>
      </div>
    <!-- Leftmost Sidebar -->
    <div id="sidebar" class="fixed top-0 left-0 h-full mt-10 text-white z-50 flex flex-col items-center sidebar transition-all duration-300 shadow-lg border-r border-gray-300" style="width: 64px; background-color: rgb(70 130 180 / 50%) /* #4682b4 */;">
    



    <ul id="bubble-list" class="space-y-4 mt-10">
            <!-- Bubble list will be populated by JavaScript -->
        </ul>
    </div>
  </div>

  
  <!-- Main Container -->
  <div class="content pt-20 py-20 pl-20 pr-20">
    <div class="container mx-auto">
      <!-- Slider Placeholder -->
      <!--Image Slider-->
    <div id="default-carousel" class="relative bg-blue-900 justify-center rounded-3xl" data-carousel="static">
        <div class="flex h-56 sm:h-64 xl:h-80 2xl:h-96 relative items-center justify-center">
            <div class="absolute inset-0 duration-700 ease-in-out flex flex-col items-center justify-center hidden" data-carousel-item>
                <img src="image5 (2).png" class="h-64" alt="...">
                <div class="p-5 flex flex-col justify-center items-center text-white">
                    <h3>Don't let the journey be a solo journey.</h3>
                </div>
            </div>
            <div class="absolute inset-0 duration-700 ease-in-out flex flex-col items-center justify-center hidden" data-carousel-item>
                <img src="image6 (1).png" class="h-64" alt="...">
                <div class="p-5 flex flex-col justify-center items-center text-white">
                    <h3>This is the second image caption.</h3>
                </div>
            </div>
            <div class="absolute inset-0 duration-700 ease-in-out flex flex-col items-center justify-center hidden" data-carousel-item>
                <img src="image7 (1).png" class="h-64" alt="...">
                <div class="p-5 flex flex-col justify-center items-center text-white">
                    <h3>This is the third image caption.</h3>
                </div>
            </div>
            <div class="absolute inset-0 duration-700 ease-in-out flex flex-col items-center justify-center hidden" data-carousel-item>
                <img src="image8 (2).png" class="h-64" alt="...">
                <div class="p-5 flex flex-col justify-center items-center text-white">
                    <h3>This is the fourth image caption.</h3>
                </div>
            </div>
        </div>

        <!-- Slider indicators -->
        <div class="absolute bottom-5 left-1/2 transform -translate-x-1/2 flex space-x-3 z-30">
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white" data-slide-to="0"></button>
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white" data-slide-to="1"></button>
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white" data-slide-to="2"></button>
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white" data-slide-to="3"></button>
        </div>
    </div>

    <!-- JavaScript for Auto-Slider -->
    <script>
        const carouselItems = document.querySelectorAll('[data-carousel-item]');
        const indicators = document.querySelectorAll('[data-slide-to]');
        let currentIndex = 0;
        const intervalTime = 3000;

        function showSlide(index) {
            carouselItems.forEach((item, i) => {
                if (i === index) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
            indicators.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === index);
                dot.classList.toggle('bg-white/50', i !== index);
            });
        }

        function nextSlide() {
            currentIndex = (currentIndex + 1) % carouselItems.length;
            showSlide(currentIndex);
        }

        setInterval(nextSlide, intervalTime);

        indicators.forEach((indicator, i) => {
            indicator.addEventListener('click', () => {
                currentIndex = i;
                showSlide(currentIndex);
            });
        });

        showSlide(currentIndex);
    </script>
      
      <!-- Success Message Container -->
      <div id="success-message" class="hidden fixed top-16 left-1/2 transform -translate-x-1/2 z-50">
        <div class="bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg">
          <span class="text-lg font-medium">Bubble created successfully!</span>
        </div>
      </div>
      <div class="float-right flex items-center space-x-4 mt-4">
        <div class="flex items-center">
          <input
            type="text"
            id="search-bubbles"
            placeholder="Search bubbles..."
            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
          />
        </div>
        <button id="create-bubble-button" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
          Create Bubble
        </button>
      </div>

     

      <div id="your-bubbles" class="tab-content">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 mb-8 mt-20 mx-auto max-w-[1920px] px-4">
          <?php while ($bubble = $your_bubbles_result->fetch_assoc()): ?>
            <div class="bubble-card relative bg-gray-200 rounded-lg overflow-hidden shadow-lg transition-transform transform hover:-translate-y-1 flex flex-col justify-between h-80 w-full p-4">
              <div class="flex-grow">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" class="w-full h-40 object-cover mb-4">
                <div class="text-lg font-bold mb-2 truncate" title="<?php echo htmlspecialchars($bubble['bubble_name']); ?>">
                  <?php echo (strlen($bubble['bubble_name']) > 10) ? substr(htmlspecialchars($bubble['bubble_name']), 0, 10) . '...' : htmlspecialchars($bubble['bubble_name']); ?>
                </div>
                <div class="text-sm text-gray-600 mb-2">
                  <div class="description-container">
                    <div class="description-text line-clamp-2">
                      <?php echo (strlen($bubble['description']) > 10) ? substr(htmlspecialchars($bubble['description']), 0, 10) . '...' : htmlspecialchars($bubble['description']); ?>
                    </div>
                    <?php if (strlen($bubble['description']) > 10): ?>
                      <button class="see-more-btn text-blue-500 hover:text-blue-600 text-sm">See more</button>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="flex justify-between items-center mt-2">
                  <div>
                    <div class="text-sm text-gray-500 truncate" title="Created by: <?php echo htmlspecialchars($bubble['creator']); ?>">
                      Created by: <?php echo (strlen($bubble['creator']) > 10) ? substr(htmlspecialchars($bubble['creator']), 0, 10) . '...' : htmlspecialchars($bubble['creator']); ?>
                    </div>
                    <div class="text-sm text-gray-500">Members: <?php echo $bubble['member_count']; ?></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div id="available-bubbles" class="tab-content active">
        <div id="bubbles-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 mb-8 mt-20 mx-auto max-w-[1920px] px-4">
          <?php 
          if ($available_bubbles_result && $available_bubbles_result->num_rows > 0):
            while ($bubble = $available_bubbles_result->fetch_assoc()): 
          ?>
            <div class="bubble-card relative bg-gray-200 rounded-lg overflow-hidden shadow-lg transition-transform transform hover:-translate-y-1 flex flex-col justify-between h-80 w-full p-4">
              <div class="flex-grow">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($bubble['profile_image']); ?>" alt="<?php echo htmlspecialchars($bubble['bubble_name']); ?>" class="w-full h-40 object-cover mb-4">
                <div class="text-lg font-bold mb-2 truncate" title="<?php echo htmlspecialchars($bubble['bubble_name']); ?>">
                  <?php echo (strlen($bubble['bubble_name']) > 10) ? substr(htmlspecialchars($bubble['bubble_name']), 0, 10) . '...' : htmlspecialchars($bubble['bubble_name']); ?>
                </div>
                <div class="text-sm text-gray-600 mb-2">
                  <div class="description-container">
                    <div class="description-text line-clamp-2">
                      <?php echo (strlen($bubble['description']) > 10) ? substr(htmlspecialchars($bubble['description']), 0, 10) . '...' : htmlspecialchars($bubble['description']); ?>
                    </div>
                    <?php if (strlen($bubble['description']) > 10): ?>
                      <button class="see-more-btn text-blue-500 hover:text-blue-600 text-sm">See more</button>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="flex justify-between items-center mt-2">
                  <div>
                    <div class="text-sm text-gray-500 truncate" title="Created by: <?php echo htmlspecialchars($bubble['creator']); ?>">
                      Created by: <?php echo (strlen($bubble['creator']) > 10) ? substr(htmlspecialchars($bubble['creator']), 0, 10) . '...' : htmlspecialchars($bubble['creator']); ?>
                    </div>
                    <div class="text-sm text-gray-500">Members: <?php echo $bubble['member_count']; ?></div>
                  </div>
                  <button onclick="window.location.href='joinBubble.php?bubble_id=<?php echo $bubble['id']; ?>'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full transition-colors">Join</button>
                </div>
              </div>
            </div>
          <?php 
            endwhile; 
          else: 
          ?>
            <div class="col-span-full text-center text-gray-600 py-8">
              No available bubbles found.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <style>
        .description-container {
          position: relative;
        }
        .description-text {
          overflow: hidden;
          display: -webkit-box;
          -webkit-box-orient: vertical;
          -webkit-line-clamp: 2;
        }
        .description-text.expanded {
          -webkit-line-clamp: unset;
        }
        .see-more-btn {
          cursor: pointer;
          display: inline-block;
        }
      </style>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
          // Handle see more buttons
          document.querySelectorAll('.see-more-btn').forEach(button => {
            button.addEventListener('click', function(e) {
              const descriptionText = this.previousElementSibling;
              const isExpanded = descriptionText.classList.contains('expanded');
              
              if (isExpanded) {
                descriptionText.classList.remove('expanded');
                this.textContent = 'See more';
                // Scroll back to the top of the description if needed
                descriptionText.parentElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              } else {
                descriptionText.classList.add('expanded');
                this.textContent = 'See less';
              }
            });
          });
        });
      </script>
     </div>
      </div>
    </div>
  </div>

    <!-- Modal for Create Bubble Form -->
    <div id="create-bubble-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg">
            <span id="closeBubbleModal" class="cursor-pointer text-red-500 float-right">&times;</span>
            <h2 class="text-xl font-bold mb-4">Create a New Bubble</h2>
            <form id="create-bubble-form" action="createBubble.php" method="POST" enctype="multipart/form-data">
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

    <style>
        #toast-success {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 16px 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        #toast-success.show {
            opacity: 1;
        }

        .toast-icon {
            width: 20px;
            height: 20px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-icon::before {
            content: "✓";
            color: white;
            font-size: 14px;
        }

        .toast-message {
            font-size: 14px;
            font-weight: 500;
        }
    </style>

    <!-- Toast Notification -->
    <div id="toast-success" class="hidden">
        <div class="toast-icon"></div>
        <div class="toast-message">Bubble created successfully</div>
    </div>

    <script>
        function showNotification(message) {
            const toast = document.getElementById('toast-success');
            toast.classList.remove('hidden');
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
            
            // Hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 300);
            }, 3000);
        }
    </script>

    <script>
        document.getElementById('create-bubble-button').addEventListener('click', () => {
            document.getElementById('create-bubble-modal').classList.remove('hidden');
        });

        document.getElementById('closeBubbleModal').addEventListener('click', () => {
            document.getElementById('create-bubble-modal').classList.add('hidden');
        });

        function showNotification(message) {
            const notification = document.getElementById('success-message');
            notification.classList.remove('hidden');
            
            // Hide the notification after 3 seconds
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }

        // Handle form submission
        document.getElementById('create-bubble-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('createBubble.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    document.getElementById('create-bubble-modal').classList.add('hidden');
                    
                    // Clear the form
                    this.reset();
                    
                    // Show success notification
                    showNotification('Bubble created successfully! ');
                    
                    // Refresh the bubbles grid without reloading the page
                    fetchAvailableBubbles();
                    fetchJoinedBubbles();
                } else {
                    alert('Error creating bubble: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating bubble. Please try again.');
            });
        });

        // Function to fetch available bubbles
        function fetchAvailableBubbles() {
            fetch('getAvailableBubbles.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const bubblesGrid = document.getElementById('bubbles-grid');
                    bubblesGrid.innerHTML = data.bubbles.map(bubble => createBubbleCard(bubble)).join('');
                    initializeSeeMoreButtons();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Function to create bubble card HTML
        function createBubbleCard(bubble) {
            const bubbleName = bubble.bubble_name.length > 10 ? bubble.bubble_name.substring(0, 10) + '...' : bubble.bubble_name;
            const description = bubble.description.length > 10 ? bubble.description.substring(0, 10) + '...' : bubble.description;
            const creator = bubble.creator.length > 10 ? bubble.creator.substring(0, 10) + '...' : bubble.creator;
            
            return `
                <div class="bubble-card relative bg-gray-200 rounded-lg overflow-hidden shadow-lg transition-transform transform hover:-translate-y-1 flex flex-col justify-between h-80 w-full p-4">
                  <div class="flex-grow">
                    <img src="data:image/jpeg;base64,${bubble.profile_image}" alt="${bubble.bubble_name}" class="w-full h-40 object-cover mb-4">
                    <div class="text-lg font-bold mb-2 truncate" title="${bubble.bubble_name}">${bubbleName}</div>
                    <div class="text-sm text-gray-600 mb-2">
                      <div class="description-container">
                        <div class="description-text line-clamp-2" title="${bubble.description}">${description}</div>
                        ${bubble.description.length > 10 ? `<button class="see-more-btn text-blue-500 hover:text-blue-600 text-sm">See more</button>` : ''}
                      </div>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                      <div>
                        <div class="text-sm text-gray-500 truncate" title="Created by: ${bubble.creator}">Created by: ${creator}</div>
                        <div class="text-sm text-gray-500">Members: ${bubble.member_count}</div>
                      </div>
                      <button onclick="window.location.href='joinBubble.php?bubble_id=${bubble.id}'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full transition-colors">Join</button>
                    </div>
                  </div>
                </div>
              `;
        }

        // Add event listener for dynamically added see more buttons
        function initializeSeeMoreButtons() {
            document.querySelectorAll('.see-more-btn').forEach(button => {
                if (!button.hasListener) {
                    button.hasListener = true;
                    button.addEventListener('click', function(e) {
                        const descriptionText = this.previousElementSibling;
                        const isExpanded = descriptionText.classList.contains('expanded');
                        
                        if (isExpanded) {
                            descriptionText.classList.remove('expanded');
                            this.textContent = 'See more';
                            descriptionText.parentElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        } else {
                            descriptionText.classList.add('expanded');
                            this.textContent = 'See less';
                        }
                    });
                }
            });
        }
    </script>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('search-bubbles');
        const bubblesGrid = document.getElementById('bubbles-grid');

        // Show loading state
        function showLoading() {
          bubblesGrid.innerHTML = `
            <div class="col-span-full text-center text-gray-600 py-8">
              Searching...
            </div>
          `;
        }

        // Show error message
        function showError(message) {
          bubblesGrid.innerHTML = `
            <div class="col-span-full text-center text-gray-600 py-8">
              ${message}
            </div>
          `;
        }

        // Handle search input
        searchInput.addEventListener('input', function(e) {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            const searchTerm = e.target.value.trim();
            if (searchTerm === '') {
              location.reload(); // Reload to show all bubbles
              return;
            }
            searchBubbles(searchTerm);
          }, 300);
        });

        // Perform search
        function searchBubbles(searchTerm) {
          showLoading();
          
          fetch(`searchBubbles.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
              if (!data.success) {
                throw new Error(data.error || 'Failed to search bubbles');
              }

              const bubbles = data.bubbles;
              bubblesGrid.innerHTML = '';
              
              if (bubbles.length === 0) {
                showError('No bubbles found matching your search.');
                return;
              }

              bubbles.forEach(bubble => {
                bubblesGrid.innerHTML += createBubbleCard(bubble);
              });
              
              // Initialize see more buttons for search results
              initializeSeeMoreButtons();
            })
            .catch(error => {
              console.error('Error searching bubbles:', error);
              showError('An error occurred while searching. Please try again.');
            });
        }
    </script>

    <script>
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

        function searchBubble() {
          const searchQuery = document.getElementById('searchBox').value.toLowerCase();
          const bubbles = document.querySelectorAll('.bubbles');

            notebooks.forEach(bubbles => {
             const bubblesName = bubbles.querySelector('.bubbles-header h3').innerText.toLowerCase();
             if (bubbleName.includes(searchQuery)) {
                bubbles.style.display = 'block';
            } else {
                bubbles.style.display = 'none';
            }
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
