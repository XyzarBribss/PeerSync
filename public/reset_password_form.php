<?php
session_start();
include 'config.php';

// Check if token is provided and valid
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Invalid or missing reset token');
}

$token = $_GET['token'];

// Verify token and check if it's expired
$stmt = $conn->prepare("SELECT pr.*, u.username, u.email 
                       FROM password_resets pr 
                       JOIN users u ON pr.user_id = u.id 
                       WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invalid or expired reset token');
}

$reset = $result->fetch_assoc();
$username = $reset['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PeerSync</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 bg-gradient-to-r from-blue-700 to-blue-500 shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="peersync-logo.png" alt="PeerSync" class="h-12">
                    <span class="text-white text-xl font-bold">PeerSync</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 mt-16">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <svg class="mx-auto h-16 w-16 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                    </path>
                </svg>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Reset Your Password</h2>
                <p class="mt-2 text-lg text-gray-600">
                    For account: <span class="font-medium text-blue-600"><?php echo htmlspecialchars($username); ?></span>
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    Please enter your new password below
                </p>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
                    <form id="resetForm" class="space-y-6">
                        <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div>
                            <label for="newPassword" class="block text-sm font-medium text-gray-700">New Password</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="password" id="newPassword" name="newPassword" required
                                       class="pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2.5"
                                       placeholder="Enter your new password">
                            </div>
                        </div>
                        
                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="password" id="confirmPassword" name="confirmPassword" required
                                       class="pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2.5"
                                       placeholder="Confirm your new password">
                            </div>
                        </div>

                        <div id="errorMessage" class="rounded-md bg-red-50 p-4 hidden">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"></p>
                                </div>
                            </div>
                        </div>

                        <div id="successMessage" class="rounded-md bg-green-50 p-4 hidden">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700"></p>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span>Reset Password</span>
                            <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const token = document.getElementById('token').value;
            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            const submitButton = this.querySelector('button[type="submit"]');

            // Reset messages
            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');

            // Validate passwords
            if (newPassword !== confirmPassword) {
                errorDiv.querySelector('p').textContent = 'Passwords do not match';
                errorDiv.classList.remove('hidden');
                return;
            }

            if (newPassword.length < 8) {
                errorDiv.querySelector('p').textContent = 'Password must be at least 8 characters long';
                errorDiv.classList.remove('hidden');
                return;
            }

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating Password...
            `;

            // Submit the form
            fetch('update_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `token=${encodeURIComponent(token)}&password=${encodeURIComponent(newPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successDiv.querySelector('p').textContent = 'Password has been reset successfully. Redirecting to login...';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => {
                        window.location.href = 'indexLogin.php';
                    }, 3000);
                } else {
                    errorDiv.querySelector('p').textContent = data.error || 'Failed to reset password';
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(error => {
                errorDiv.querySelector('p').textContent = 'An error occurred. Please try again.';
                errorDiv.classList.remove('hidden');
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = `
                    <span>Reset Password</span>
                    <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                `;
            });
        });
    </script>
</body>
</html>
