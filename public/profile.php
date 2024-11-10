<?php
session_start();

require 'config.php'; // Ensure this file correctly sets up the $conn variable

// Check if the user is logged in
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

// Fetch user data
$sql = "SELECT username, profile_image FROM users WHERE id = ?";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css" rel="stylesheet">
  <title>Enhanced Profile Page</title>
  <style>
    .comment-box, .section { display: none; }
    .active { border-bottom: 4px solid blue; color: blue; }
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }
  </style>
</head>
<body class="bg-gray-100 font-sans">
  <div class="max-w-4xl mx-auto mt-10 bg-white rounded-lg shadow-md overflow-hidden">
    <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600"></div>
    <div class="flex justify-center -mt-16 relative">
      <img 
        id="profile-picture" 
        class="w-32 h-32 rounded-full border-4 border-white object-cover cursor-pointer" 
        src="<?php echo !empty($userData['profile_image']) ? htmlspecialchars($userData['profile_image']) : 'profile_page/default_profile.png'; ?>" 
        alt="Profile Picture" 
      />
      <div class="absolute bottom-0 right-0 bg-gray-800 p-2 rounded-full text-white cursor-pointer" id="edit-profile-picture">
        <i class="uil uil-camera"></i>
      </div>
    </div>
    <div class="text-center mt-2">
      <h2 id="profile-name" class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($userData['username']); ?></h2>
      <p id="profile-bio" class="text-gray-600">Web Developer & Kupal</p>
    </div>
    <div class="flex justify-around bg-gray-50 border-t mt-4"> 
      <a href="#" class="py-3 hover:text-gray-700 flex items-center" data-target="profile">
        <i class="uil uil-user"></i>
        <span class="ml-1">Profile</span>
      </a>
      <a href="#" class="py-3 hover:text-gray-700 flex items-center" data-target="posts">
        <i class="uil uil-postcard"></i>
        <span class="ml-1">Posts</span>
      </a>
      <a href="#" class="py-3 hover:text-gray-700 flex items-center" data-target="settings">
        <i class="uil uil-cog"></i>
        <span class="ml-1">Settings</span>
      </a>
    </div>
    <div id="profile" class="section p-6"></div>
    <div id="posts" class="section p-6"></div>
    <div id="settings" class="section p-6">
      <h3 class="text-lg font-semibold text-gray-800">Settings</h3>
      <div class="mt-4">
        <label for="name-input" class="block text-gray-700">Profile Name:</label>
        <input type="text" id="name-input" class="border border-gray-300 rounded-md p-2 w-full" placeholder="Enter your name" />
        <button id="save-name-button" class="mt-2 bg-green-500 text-white rounded-md px-4 py-2 hover:bg-green-600">Save Name</button>
      </div>
      <div class="mt-4">
        <label for="bio-input" class="block text-gray-700">Bio:</label>
        <textarea id="bio-input" class="border border-gray-300 rounded-md p-2 w-full" rows="4" placeholder="Enter your bio"></textarea>
        <button id="save-bio-button" class="mt-2 bg-green-500 text-white rounded-md px-4 py-2 hover:bg-green-600">Save Bio</button>
      </div>
    </div>
  </div>

  <!-- Modal for Edit Profile Picture -->
  <div id="edit-picture-modal" class="modal">
    <div class="modal-content">
      <h2 class="text-xl font-semibold">Edit Profile Picture</h2>
      <form id="edit-picture-form" method="post" enctype="multipart/form-data">
        <label for="profile_image" class="bg-blue-500 text-white rounded-md px-4 py-2 cursor-pointer mt-4 inline-block">
          Change Profile Picture
        </label>
        <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" required />
        <button type="submit" class="bg-green-500 text-white rounded-md px-4 py-2 mt-4">Upload</button>
        <button type="button" id="close-modal" class="bg-red-500 text-white rounded-md px-4 py-2 mt-4">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    // Function to show the selected section and hide others
    const showSection = (sectionId) => {
      document.querySelectorAll('.section').forEach((section) => {
        section.style.display = section.id === sectionId ? 'block' : 'none';
      });

      document.querySelectorAll('a[data-target]').forEach(link => {
        link.classList.toggle('active', link.getAttribute('data-target') === sectionId);
      });
    };

    // Initially show the profile section
    showSection('profile');

    // Add click event listeners to navigation links
    document.querySelectorAll('a[data-target]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('data-target');
        showSection(targetId);
      });
    });

    // Get elements related to editing profile picture
    const editProfilePicture = document.getElementById('edit-profile-picture');
    const editPictureModal = document.getElementById('edit-picture-modal');
    const closeModal = document.getElementById('close-modal');
    const profileImageInput = document.getElementById('profile_image');

    // Show the modal when edit profile picture is clicked
    editProfilePicture.addEventListener('click', () => {
      editPictureModal.style.display = 'flex';
    });

    // Hide the modal when close button is clicked
    closeModal.addEventListener('click', () => {
      editPictureModal.style.display = 'none';
    });

    // Trigger file input click when label is clicked
    document.querySelector('label[for="profile_image"]').addEventListener('click', () => {
      profileImageInput.click();
    });
  </script>
</body>
</html>
