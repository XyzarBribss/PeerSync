<?php
session_start();
require_once 'vendor/autoload.php';
include 'config.php';




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
            z-index: 100;
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
            <a href="Support" class="text-white hover:text-gray-200">Support</a>
            <a href="About_Us.html" class="text-white hover:text-gray-200">About Us</a>
            <a href="Login.html" class="text-white hover:text-gray-200">Sign-in</a>
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
                            <a href="#" class="text-blue-500 hover:underline" id="forgot-password-link">Forgot password?</a>
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

<script src="index.js"></script>
</body>
</html>