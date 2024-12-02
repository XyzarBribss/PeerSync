<?php
session_start();
require_once 'vendor/autoload.php';
include 'config.php';

$clientID = '273557119438-65dm34o7l62joqp3uvminivbkd1u6cjp.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/PeerSync/public/indexLogin.php';

// Create Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
 
// Authenticate code from Google OAuth Flow
if (isset($_GET['code'])) {
  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  $client->setAccessToken($token['access_token']);

  // Get profile info
  $google_oauth = new Google_Service_Oauth2($client);
  $google_account_info = $google_oauth->userinfo->get();
  $email = $google_account_info->email;
  $name = $google_account_info->name;
  $profile_image = $google_account_info->picture;

  // Check if user exists in the database
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // User exists, log them in
    $user = $result->fetch_assoc();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
  } else {
    // User does not exist, insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, profile_image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $profile_image);
    $stmt->execute();
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['username'] = $name;
  }

  // Redirect to the dashboard or home page
  header("Location: indexTimeline.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign-In to PeerSync</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: "Roboto", sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
        }
        .navbar-gradient {
            background: linear-gradient(to right, #285f97, #629eda);
        }
        .container {
            position: relative;
            width: 100%;
            max-width: 800px;
            min-height: 500px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }
        .register-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }
        .login-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }
        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 10;
        }
        .overlay {
            background: #629eda;
            background: linear-gradient(to right, #629eda, #629eda);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }
        .overlay-panel {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }
        .overlay-left {
            transform: translateX(-20%);
        }
        .overlay-right {
            right: 0;
            transform: translateX(0);
        }
        .container.right-panel-active .register-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
        }
        .container.right-panel-active .login-container {
            transform: translateX(100%);
            opacity: 0;
        }
        .container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }
        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }
        .container.right-panel-active .overlay-left {
            transform: translateX(0);
        }
        .container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }
    </style>
</head>
<body class="bg-gray-100">

<!-- Navigation Bar -->
<header class="navbar-gradient shadow-md">
    <div class="navcontainer mx-auto p-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <img src="peersync-logo.png" alt="" class="h-12" />
            <span class="text-white text-xl font-bold">PeerSync</span>
        </div>
        <nav class="flex-1 flex justify-end space-x-8">
            <a href="dashboard" class="text-white hover:text-gray-200">Dashboard</a>
            <a href="#" onclick="openSupportModal()" class="text-white hover:text-gray-200">Support</a>
            <a href="About_Us.html" class="text-white hover:text-gray-200">About Us</a>
            <a href="Login.html" class="text-white hover:text-gray-200">Sign-in</a>
            <a href="AdminLogin.html" class="text-white hover:text-gray-200">Admin Log</a>
            </nav>
        </nav>
    </div>
</header>

<main>
    <section class="container mx-auto mt-10">
        <div class="container" id="container">
            <div class="form-container register-container p-8">
                <form action="register.php" method="POST">
                    <h1 class="text-2xl font-bold mb-4">Register</h1>
                    <input type="text" placeholder="username" id="username" name="username" class="w-full p-2 mb-4 border border-gray-300 rounded" />
                    <input type="email" placeholder="Email" id="email" name="email" class="w-full p-2 mb-4 border border-gray-300 rounded" />
                    <input type="password" placeholder="Password" id="password" name="password" class="w-full p-2 mb-4 border border-gray-300 rounded" />
                    <button class="w-full bg-blue-500 text-white p-2 rounded">Register</button>
                    <span class="block mt-4">or use your account</span>
                    <a href="<?php echo $client->createAuthUrl(); ?>" class="social text-red-600">
                        <i class="lni lni-google"></i>
                    </a>
                    <div class="social-container flex justify-center mt-4 space-x-4">
                        <a href="#" class="social text-blue-600"><i class="lni lni-facebook-fill"></i></a>
                        <a href="#" class="social text-red-600"><i class="lni lni-google"></i></a>
                        <a href="#" class="social text-blue-700"><i class="lni lni-linkedin-original"></i></a>
                    </div>
                </form>
            </div>

            <div class="form-container login-container p-8">
                <form action="login.php" method="POST">
                    <h1 class="text-2xl font-bold mb-4">Login</h1>
                    <input type="text" id="username" name="username" placeholder="Username" class="w-full p-2 mb-4 border border-gray-300 rounded" />
                    <input type="password" id="password" name="password" placeholder="Password" class="w-full p-2 mb-4 border border-gray-300 rounded" />
                    <div class="content flex justify-between items-center mb-4">
                        <div class="checkbox flex items-center">
                            <input type="checkbox" name="checkbox" id="checkbox" class="mr-2" />
                            <label for="checkbox" class="text-gray-700">Remember me</label>
                        </div>
                        <div class="pass-link">
                            <button type="button" onclick="openResetPasswordForm()" class="text-blue-500 hover:underline">Reset password</button>
                        </div>
                    </div>
                    <button class="w-full bg-blue-500 text-white p-2 rounded">Login</button>
                    <div class="flex flex-col items-center mt-4">
                        <span class="block text-gray-500">or login with google</span>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="social text-red-600 mt-2">
                            <img src="googleimg.png" alt="Google Sign-In" width="30" height="30" />
                        </a>
                    </div>
                    <div class="social-container flex justify-center mt-4 space-x-4">
                        <a href="#" class="social text-blue-600"><i class="lni lni-facebook-fill"></i></a>
                        <a href="#" class="social text-red-600"><i class="lni lni-google"></i></a>
                        <a href="#" class="social text-blue-700"><i class="lni lni-linkedin-original"></i></a>
                    </div>
                </form>
            </div>

            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left p-8">
                        <h1 class="text-2xl font-bold mb-4">Welcome to PeerSync!</h1>
                        <p class="mb-4">If you have an account, login here and connect with us!</p>
                        <button class="ghost bg-transparent border border-white text-white p-2 rounded" id="login">
                            Login <i class="lni lni-arrow-left login ml-2"></i>
                        </button>
                    </div>
                    <div class="overlay-panel overlay-right p-8">
                        <h1 class="text-2xl font-bold mb-4">Collaborate with Peers now!</h1>
                        <p class="mb-4">If you don't have an account yet, join us and start your journey.</p>
                        <button class="ghost bg-transparent border border-white text-white p-2 rounded" id="register">
                            Register <i class="lni lni-arrow-right register ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="bg-white shadow-md mt-8">
    <div class="footcontainer mx-auto p-4 text-center">
        <p class="text-gray-700">&copy; 2024 PeerSync. All rights reserved.</p>
    </div>
</footer>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999;">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full mx-4 relative">
        <button onclick="closeResetPasswordModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <div class="text-center mb-6">
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                </path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900">Reset Password</h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        <form id="resetPasswordForm" class="space-y-4">
            <div>
                <label for="resetEmail" class="block text-sm font-medium text-gray-700">Email Address</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                            </path>
                        </svg>
                    </div>
                    <input type="email" id="resetEmail" name="email" required 
                           class="pl-10 block w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2.5"
                           placeholder="Enter your email">
                </div>
            </div>

            <div id="resetMessage" class="hidden rounded-md p-4">
                <p class="text-sm"></p>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeResetPasswordModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center">
                    <span>Send Reset Link</span>
                    <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Support Modal -->
<div id="supportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999;">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl w-full mx-4 relative">
        <!-- Close Button -->
        <button onclick="closeSupportModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Modal Header -->
        <div class="text-center mb-6">
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                </path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900">Contact Support</h2>
            <p class="mt-2 text-sm text-gray-600">
                Need help? Send us a message and we'll get back to you as soon as possible.
            </p>
        </div>

        <!-- Support Form -->
        <form id="supportForm" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <!-- Email Field -->
                <div>
                    <label for="supportEmail" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <div class="mt-1">
                        <input type="email" id="supportEmail" name="email" required 
                               class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5"
                               value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>"
                               placeholder="Enter your email">
                    </div>
                </div>
            </div>

            <!-- Subject Field -->
            <div>
                <label for="supportSubject" class="block text-sm font-medium text-gray-700">Subject</label>
                <div class="mt-1">
                    <input type="text" id="supportSubject" name="subject" required
                           class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5"
                           placeholder="Brief description of your issue">
                </div>
            </div>

            <!-- Message Field -->
            <div>
                <label for="supportMessage" class="block text-sm font-medium text-gray-700">Message</label>
                <div class="mt-1">
                    <textarea id="supportMessage" name="message" rows="4" required
                              class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2.5"
                              placeholder="Describe your issue in detail"></textarea>
                </div>
            </div>

            <!-- Message Display -->
            <div id="supportMessageDisplay" class="hidden rounded-md p-4">
                <p class="text-sm"></p>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeSupportModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center">
                    <span>Submit</span>
                    <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResetPasswordForm() {
        document.getElementById('resetPasswordModal').classList.remove('hidden');
        document.getElementById('resetEmail').focus();
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
        document.getElementById('resetMessage').classList.add('hidden');
        document.getElementById('resetPasswordForm').reset();
    }

    // Close modal when clicking outside
    document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeResetPasswordModal();
        }
    });

    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        const messageDiv = document.getElementById('resetMessage');
        const messagePara = messageDiv.querySelector('p');
        const submitButton = this.querySelector('button[type="submit"]');
        
        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
        `;

        // Send password reset request
        fetch('reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.classList.remove('hidden');
            if (data.success) {
                messageDiv.className = 'rounded-md bg-green-50 p-4';
                messagePara.className = 'text-sm text-green-700';
                messagePara.textContent = 'Password reset instructions have been sent to your email.';
                setTimeout(closeResetPasswordModal, 3000);
            } else {
                messageDiv.className = 'rounded-md bg-red-50 p-4';
                messagePara.className = 'text-sm text-red-700';
                messagePara.textContent = data.error || 'Failed to send reset instructions.';
            }
        })
        .catch(error => {
            messageDiv.classList.remove('hidden');
            messageDiv.className = 'rounded-md bg-red-50 p-4';
            messagePara.className = 'text-sm text-red-700';
            messagePara.textContent = 'An error occurred. Please try again.';
        })
        .finally(() => {
            // Reset submit button state
            submitButton.disabled = false;
            submitButton.innerHTML = `
                <span>Send Reset Link</span>
                <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            `;
        });
    });

    // Support Modal Functions
    function openSupportModal() {
        document.getElementById('supportModal').classList.remove('hidden');
        document.getElementById('supportEmail').focus();
    }

    function closeSupportModal() {
        document.getElementById('supportModal').classList.add('hidden');
        document.getElementById('supportMessageDisplay').classList.add('hidden');
        document.getElementById('supportForm').reset();
    }

    // Close modal when clicking outside
    document.getElementById('supportModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSupportModal();
        }
    });

    // Handle support form submission
    document.getElementById('supportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const messageDiv = document.getElementById('supportMessageDisplay');
        const messagePara = messageDiv.querySelector('p');
        const submitButton = this.querySelector('button[type="submit"]');

        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Submitting...
        `;

        // Submit support ticket
        fetch('submit_support.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.classList.remove('hidden');
            if (data.success) {
                messageDiv.className = 'rounded-md bg-green-50 p-4';
                messagePara.className = 'text-sm text-green-700';
                messagePara.textContent = data.message;
                setTimeout(closeSupportModal, 3000);
            } else {
                messageDiv.className = 'rounded-md bg-red-50 p-4';
                messagePara.className = 'text-sm text-red-700';
                messagePara.textContent = data.error;
            }
        })
        .catch(error => {
            messageDiv.classList.remove('hidden');
            messageDiv.className = 'rounded-md bg-red-50 p-4';
            messagePara.className = 'text-sm text-red-700';
            messagePara.textContent = 'An error occurred. Please try again.';
        })
        .finally(() => {
            // Reset submit button state
            submitButton.disabled = false;
            submitButton.innerHTML = `
                <span>Submit</span>
                <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            `;
        });
    });
</script>

<script src="index.js"></script>
</body>
</html>