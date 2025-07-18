<?php
session_start();
include("includes/dbconnect.php");
include("includes/functions.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            min-height: 500px;
            color: white;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="./admin/index.php">LearnHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['user_type']; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section d-flex align-items-center">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Welcome to LearnHub</h1>
            <p class="lead mb-4">Join our community of learners and trainers. Share knowledge, grow together.</p>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <div class="d-grid gap-2 d-sm-block">
                    <a href="register.php" class="btn btn-primary btn-lg me-sm-3">Get Started</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Sign In</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container py-5">
        <h2 class="text-center mb-5">Why Choose LearnHub?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Expert Trainers</h5>
                        <p class="card-text">Learn from qualified trainers who pass rigorous testing.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-video fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Quality Content</h5>
                        <p class="card-text">Access high-quality video content and learning materials.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-comments fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Interactive Learning</h5>
                        <p class="card-text">Engage with trainers and other learners through comments.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Videos Section -->
    <?php
    $latest_videos = mysqli_query($conn, "SELECT v.*, u.name as trainer_name 
                                        FROM videos v 
                                        JOIN users u ON v.user_id = u.user_id 
                                        WHERE v.status='active' 
                                        ORDER BY v.upload_date DESC 
                                        LIMIT 3");
    if(mysqli_num_rows($latest_videos) > 0):
    ?>
    <div class="container py-5">
        <h2 class="text-center mb-5">Latest Videos</h2>
        <div class="row g-4">
            <?php while($video = mysqli_fetch_assoc($latest_videos)): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <video class="card-img-top" controls>
                        <source src="assets/uploads/videos/<?php echo $video['video_path']; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($video['description']); ?></p>
                        <p class="card-text"><small class="text-muted">By <?php echo htmlspecialchars($video['trainer_name']); ?></small></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>LearnHub</h5>
                    <p>Empowering learning through technology</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">About Us</a></li>
                        <li><a href="#" class="text-white">Contact</a></li>
                        <li><a href="#" class="text-white">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2025 LearnHub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>