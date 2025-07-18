<?php
if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}
?>
<div class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-header p-3">
        <h3 class="text-center">LearnHub Admin</h3>
    </div>
    <hr class="bg-light">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" 
               href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_quiz.php') ? 'active' : ''; ?>" 
               href="manage_quiz.php">
                <i class="fas fa-question-circle me-2"></i> Manage Quiz
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_users.php">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_videos.php">
                <i class="fas fa-video me-2"></i> Manage Videos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link text-white" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>