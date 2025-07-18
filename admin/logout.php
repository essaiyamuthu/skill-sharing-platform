<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

// Get admin details before destroying session
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Unknown';

// Log the logout action
if($admin_id) {
    $current_datetime = "2025-03-14 12:12:51"; // Using the provided UTC time
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
   
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if(isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies set by the application
setcookie('admin_remember', '', time()-3600, '/');
setcookie('admin_token', '', time()-3600, '/');

// JavaScript to clear localStorage and sessionStorage
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - LearnHub Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .logout-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .spinner {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="mb-4">
            <div class="spinner-border text-primary spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <h4 class="mb-3">Logging Out...</h4>
        <p class="text-muted mb-4">Please wait while we securely log you out.</p>
        <div class="progress mb-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: 0%">
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear localStorage and sessionStorage
            try {
                localStorage.clear();
                sessionStorage.clear();
            } catch (e) {
                console.error('Error clearing storage:', e);
            }

            // Animate progress bar
            const progressBar = document.querySelector('.progress-bar');
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                progressBar.style.width = progress + '%';
                
                if(progress >= 100) {
                    clearInterval(interval);
                    // Redirect to login page after completion
                    window.location.href = 'index.php?logout=success';
                }
            }, 50);

            // Clear browser cache for security
            try {
                if (window.caches) {
                    caches.keys().then(function(names) {
                        names.forEach(function(name) {
                            caches.delete(name);
                        });
                    });
                }
            } catch (e) {
                console.error('Error clearing cache:', e);
            }

            // Prevent going back to previous page
            history.pushState(null, null, document.URL);
            window.addEventListener('popstate', function () {
                history.pushState(null, null, document.URL);
            });
        });
    </script>
</body>
</html>