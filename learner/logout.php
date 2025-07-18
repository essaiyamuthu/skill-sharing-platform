<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

// Get learner details before destroying session
$learner_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$learner_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'testt453';
$current_datetime = "2025-03-15 05:28:03";

// Log the logout action if user is logged in
if($learner_id) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Create learner_logs table if it doesn't exist
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS learner_logs (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        learner_id INT,
        action_type VARCHAR(50),
        action_details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME,
        FOREIGN KEY (learner_id) REFERENCES users(user_id) ON DELETE SET NULL
    )");
    
    // Log the logout action
    $log_query = "INSERT INTO learner_logs (
                    learner_id, 
                    action_type, 
                    action_details, 
                    ip_address, 
                    user_agent, 
                    created_at
                  ) VALUES (
                    $learner_id,
                    'logout',
                    'User $learner_name logged out successfully',
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
setcookie('learner_remember', '', time()-3600, '/');
setcookie('learner_token', '', time()-3600, '/');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - LearnHub</title>
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
        .progress {
            height: 0.5rem;
            margin: 1rem 0;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="mb-4">
            <i class="fas fa-graduation-cap fa-4x text-primary logo"></i>
        </div>
        <h4 class="mb-3">Logging Out...</h4>
        <p class="text-muted mb-4">
            Thank you for learning with us, <?php echo htmlspecialchars($learner_name); ?>!
        </p>
        <div class="progress mb-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: 0%">
            </div>
        </div>
        <div class="d-flex justify-content-center align-items-center mb-3">
            <div class="spinner-border text-primary spinner me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-muted">
                Securing your session...
            </div>
        </div>
        <small class="text-muted">
            <i class="fas fa-shield-alt me-1"></i>
            Clearing session data and cookies
        </small>
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
                    // Add a small delay before redirect for smoother transition
                    setTimeout(() => {
                        window.location.href = '../index.php?logout=success&user=learner';
                    }, 500);
                }
            }, 100);

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

            // Prevent going back
            history.pushState(null, null, document.URL);
            window.addEventListener('popstate', function () {
                history.pushState(null, null, document.URL);
            });

            // Clear iframe content if any
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