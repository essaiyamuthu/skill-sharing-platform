<?php
session_start();
include("includes/dbconnect.php");
include("includes/functions.php");

$errors = [];

if(isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = cleanInput($_POST['password']);
    
    if(empty($email) || empty($password)) {
        $errors[] = "All fields are required";
    } else {
        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND status='active'");
        
        if(mysqli_num_rows($query) == 1) {
            $user = mysqli_fetch_assoc($query);
            
            if(password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect based on user type
                if($user['user_type'] == 'trainer') {
                    header("location: trainer/dashboard.php");
                } else {
                    header("location: learner/dashboard.php");
                }
                exit();
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "Email not found";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 0 0.3rem 0.3rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <div class="card-body p-5">
                                <h2 class="text-center mb-4">Login</h2>
                                
                                <?php if(!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    
                                    
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                                        <a href="register.php" class="btn btn-link text-center">Don't have an account? Register</a>
                                    </div>
                                </form>
								
								 <div class="text-center mt-3">
                        <a href="./index.php" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                    </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-none d-md-block">
                            <div class="login-banner h-100">
                                <div class="text-center">
                                    <h3 class="mb-4">Welcome Back!</h3>
                                    <p>Access your account to continue your learning journey or start sharing your knowledge.</p>
                                    <img src="https://yt3.googleusercontent.com/lcOyYdAPTqecuhLdt5D0zbOgQ16U6gFVCmlIoS-NEEyKHMM__dyUf3JU5R8B1AQhuqfpw7uNVQ=s900-c-k-c0x00ffffff-no-rj" alt="Learning" class="img-fluid mt-4" 
                                         style="max-width: 80%;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>