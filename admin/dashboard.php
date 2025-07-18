<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}

// Get statistics
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_trainers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_type='trainer'"))['count'];
$total_learners = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_type='learner'"))['count'];
$total_videos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM videos"))['count'];

// Get recent activities
$recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_videos = mysqli_query($conn, "SELECT v.*, u.name as trainer_name 
                                    FROM videos v 
                                    JOIN users u ON v.user_id = u.user_id 
                                    ORDER BY upload_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card-stats {
            transition: transform 0.3s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard</h2>
                
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-stats bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Users</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_users; ?></h2>
                                </div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-stats bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Trainers</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_trainers; ?></h2>
                                </div>
                                <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-stats bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Learners</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_learners; ?></h2>
                                </div>
                                <i class="fas fa-user-graduate fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-stats bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Videos</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_videos; ?></h2>
                                </div>
                                <i class="fas fa-video fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">User Distribution</h5>
                            <canvas id="userDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Activities</h5>
                            <div class="list-group">
                                <?php while($user = mysqli_fetch_assoc($recent_users)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                        <small><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">New <?php echo $user['user_type']; ?> registered</p>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Videos -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Videos</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Trainer</th>
                                    <th>Language</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($video = mysqli_fetch_assoc($recent_videos)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo htmlspecialchars($video['trainer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($video['language']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($video['upload_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $video['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($video['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
							
							                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js Implementation -->
    <script>
        // User Distribution Chart
        const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userDistributionCtx, {
            type: 'pie',
            data: {
                labels: ['Trainers', 'Learners'],
                datasets: [{
                    data: [<?php echo $total_trainers; ?>, <?php echo $total_learners; ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.createElement('button');
            toggleBtn.classList.add('btn', 'btn-primary', 'position-fixed');
            toggleBtn.style.cssText = 'top: 10px; left: 10px; z-index: 1001; display: none;';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            
            document.body.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
            
            // Show/hide toggle button based on screen size
            function handleResize() {
                if(window.innerWidth <= 768) {
                    toggleBtn.style.display = 'block';
                } else {
                    toggleBtn.style.display = 'none';
                    document.querySelector('.sidebar').classList.remove('active');
                }
            }
            
            window.addEventListener('resize', handleResize);
            handleResize();
        });
    </script>
</body>
</html>
                        