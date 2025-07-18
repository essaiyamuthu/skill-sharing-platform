<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

// Get trainer details before destroying session
$trainer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$trainer_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Unknown';
$current_datetime = "2025-03-14 12:45:04";

// Log the logout action if user is logged in
if($trainer_id) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Create trainer_logs table if it doesn't exist
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS trainer_logs (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        trainer_id INT,
        action_type VARCHAR(50),
        action_details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME,
        FOREIGN KEY (trainer_id) REFERENCES users(user_id) ON DELETE SET NULL
    )");
    
    // Log the logout action
    $log_query = "INSERT INTO trainer_logs (
                    trainer_id, 
                    action_type, 
                    action_details, 
                    ip_address, 
                    user_agent, 
                    created_at
                  ) VALUES (
                    $trainer_id,
                    'logout',
                    'Trainer logged out successfully',
                    '" . mysqli_real_escape_string($conn, $ip_address) . "',
                    '" . mysqli_real_escape_string($conn, $user_agent) . "',
                    '$current_datetime'
                  )";
    
    mysqli_query($conn, $log_query);
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
setcookie('trainer_remember', '', time()-3600, '/');
setcookie('trainer_token', '', time()-3600, '/');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - LearnHub Trainer</title>
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
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="logout-card">
         <i class="fas fa-graduation-cap fa-4x text-primary logo"></i>
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
        <p class="small text-muted mb-0">
            <i class="fas fa-shield-alt me-1"></i>
            Securing your session
        </p>
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
                    window.location.href = '../index.php?logout=success';
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

            // Clear any iframe content
            try {
                const iframes = document.getElementsByTagName('iframe');
                for(let i = 0; i < iframes.length; i++) {
                    iframes[i].src = 'about:blank';
                }
            } catch (e) {
                console.error('Error clearing iframes:', e);
            }
        });

        // Prevent form resubmission
        if(window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Block browser back button
        window.onbeforeunload = function() {
            void(0);
        };
    </script>
</body>
</html>