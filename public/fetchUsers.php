<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peersync";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch users
$sql = "SELECT id, username FROM users";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<div class="bg-gray-700 p-4 w-64 fixed h-full overflow-auto">
    <h2 class="text-white text-center text-xl mb-4">Users</h2>
    <ul class="list-none p-0">
        <?php foreach ($users as $user): ?>
            <li class="text-white py-2 border-b border-gray-600"><?php echo htmlspecialchars($user['username']); ?></li>
        <?php endforeach; ?>
    </ul>
</div>