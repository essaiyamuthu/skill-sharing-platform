<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(isset($_SESSION['admin_id'])) {
    header("location: dashboard.php");
    exit();
}

$errors = [];

if(isset($_POST['login'])) {
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);
    
    $query = mysqli_query($conn, "SELECT * FROM admin WHERE name='$username' && psw='$password'");
    
    if(mysqli_num_rows($query) == 1) {
        $admin = mysqli_fetch_assoc($query);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['name'];
        header("location: dashboard.php");
        exit();
    } else {
        $errors[] = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-shield fa-3x text-primary"></i>
                        <h2 class="mt-3">Admin Login</h2>
                    </div>
                    
                    <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach($errors as $error): ?>
                            <p class="mb-0"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>