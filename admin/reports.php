<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}

// Set current datetime
$current_datetime = "2025-03-14 12:08:44";

// Get overall statistics
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_videos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM videos"))['count'];
$total_comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM comments"))['count'];
$total_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM quiz_results"))['count'];

// Get monthly statistics for the past 6 months
$months_query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as user_count
                FROM users 
                WHERE created_at >= DATE_SUB('$current_datetime', INTERVAL 6 MONTH)
                GROUP BY month
                ORDER BY month DESC";
$monthly_users = mysqli_query($conn, $months_query);

// Get trainer performance
$trainer_query = "SELECT 
                    u.name,
                    COUNT(v.video_id) as video_count,
                    COUNT(DISTINCT c.user_id) as unique_viewers,
                    COUNT(c.comment_id) as comment_count,
                    ROUND(AVG(qr.percentage), 2) as avg_quiz_score
                FROM users u
                LEFT JOIN videos v ON u.user_id = v.user_id
                LEFT JOIN comments c ON v.video_id = c.video_id
                LEFT JOIN quiz_results qr ON u.user_id = qr.user_id
                WHERE u.user_type = 'trainer'
                GROUP BY u.user_id
                ORDER BY video_count DESC";
$trainer_stats = mysqli_query($conn, $trainer_query);

// Get language popularity
$language_query = "SELECT 
                    v.language,
                    COUNT(v.video_id) as video_count,
                    COUNT(DISTINCT c.user_id) as unique_viewers,
                    COUNT(c.comment_id) as total_comments
                  FROM videos v
                  LEFT JOIN comments c ON v.video_id = c.video_id
                  GROUP BY v.language
                  ORDER BY video_count DESC";
$language_stats = mysqli_query($conn, $language_query);

// Get recent activities
$activity_query = "SELECT 
                    'video' as type,
                    v.title as description,
                    u.name as user_name,
                    v.upload_date as activity_date
                  FROM videos v
                  JOIN users u ON v.user_id = u.user_id
                  UNION ALL
                  SELECT 
                    'comment' as type,
                    SUBSTRING(c.comment_text, 1, 50) as description,
                    u.name as user_name,
                    c.created_at as activity_date
                  FROM comments c
                  JOIN users u ON c.user_id = u.user_id
                  ORDER BY activity_date DESC
                  LIMIT 10";
$recent_activities = mysqli_query($conn, $activity_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - LearnHub Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
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
                <h2>Reports & Analytics</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onClick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                   
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white">
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
                    <div class="card stat-card bg-success text-white">
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
                
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Comments</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_comments; ?></h2>
                                </div>
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Quiz Attempts</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $total_quizzes; ?></h2>
                                </div>
                                <i class="fas fa-tasks fa-2x"></i>
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
                            <h5 class="card-title">User Growth</h5>
                            <div class="chart-container">
                                <canvas id="userGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Language Popularity</h5>
                            <div class="chart-container">
                                <canvas id="languageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trainer Performance Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Trainer Performance</h5>
                    <div class="table-responsive">
                        <table id="trainerTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Trainer Name</th>
                                    <th>Videos</th>
                                    <th>Unique Viewers</th>
                                    <th>Comments</th>
                                    <th>Avg Quiz Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($trainer = mysqli_fetch_assoc($trainer_stats)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                    <td><?php echo $trainer['video_count']; ?></td>
                                    <td><?php echo $trainer['unique_viewers']; ?></td>
                                    <td><?php echo $trainer['comment_count']; ?></td>
                                    <td>
                                        <?php if($trainer['avg_quiz_score']): ?>
                                            <?php echo $trainer['avg_quiz_score']; ?>%
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Activities</h5>
                    <div class="list-group">
                        <?php while($activity = mysqli_fetch_assoc($recent_activities)): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <i class="fas fa-<?php echo ($activity['type'] == 'video') ? 'video' : 'comment'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($activity['user_name']); ?>
                                </h6>
                                <small>
                                    <?php
                                    $activity_date = new DateTime($activity['activity_date']);
                                    $current_date = new DateTime($current_datetime);
                                    $interval = $current_date->diff($activity_date);
                                    
                                    if($interval->days == 0) {
                                        if($interval->h == 0) {
                                            echo $interval->i . " minutes ago";
                                        } else {
                                            echo $interval->h . " hours ago";
                                        }
                                    } elseif($interval->days == 1) {
                                        echo "Yesterday";
                                    } else {
                                        echo $activity_date->format('M d, Y');
                                    }
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- html2pdf -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#trainerTable').DataTable({
                pageLength: 10,
                order: [[1, 'desc']]
            });

            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        $labels = [];
                        $data = [];
                        mysqli_data_seek($monthly_users, 0);
                        while($month = mysqli_fetch_assoc($monthly_users)) {
                            $labels[] = "'" . date('M Y', strtotime($month['month'] . '-01')) . "'";
                            $data[] = $month['user_count'];
                        }
                        echo implode(',', array_reverse($labels));
                        ?>
                    ],
                    datasets: [{
                        label: 'New Users',
                        data: [<?php echo implode(',', array_reverse($data)); ?>],
                        fill: false,
                        borderColor: '#0d6efd',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'User Growth Over Time'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Language Popularity Chart
            const languageCtx = document.getElementById('languageChart').getContext('2d');
			
			            new Chart(languageCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        $labels = [];
                        $video_data = [];
                        $comment_data = [];
                        mysqli_data_seek($language_stats, 0);
                        while($lang = mysqli_fetch_assoc($language_stats)) {
                            $labels[] = "'" . $lang['language'] . "'";
                            $video_data[] = $lang['video_count'];
                            $comment_data[] = $lang['total_comments'];
                        }
                        echo implode(',', $labels);
                        ?>
                    ],
                    datasets: [{
                        label: 'Videos',
                        data: [<?php echo implode(',', $video_data); ?>],
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    },
                    {
                        label: 'Comments',
                        data: [<?php echo implode(',', $comment_data); ?>],
                        backgroundColor: '#20c997',
                        borderColor: '#20c997',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Content Distribution by Language'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });

        // Function to generate PDF report
        function generatePDF() {
            const element = document.querySelector('.main-content');
            const opt = {
                margin: 1,
                filename: 'LearnHub_Report_<?php echo date("Y-m-d"); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            // Remove print buttons for PDF
            const buttonsToHide = document.querySelectorAll('.btn-outline-primary, .btn-primary');
            buttonsToHide.forEach(btn => btn.style.display = 'none');

            // Generate PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Restore buttons after PDF generation
                buttonsToHide.forEach(btn => btn.style.display = 'inline-block');
            });
        }

        // Handle mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.createElement('button');
            toggleBtn.classList.add('btn', 'btn-primary', 'position-fixed');
            toggleBtn.style.cssText = 'top: 10px; left: 10px; z-index: 1001; display: none;';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            
            document.body.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
            
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

            // Add custom print styles
            const style = document.createElement('style');
            style.textContent = `
                @media print {
                    .sidebar, .btn, .no-print { display: none !important; }
                    .main-content { margin-left: 0 !important; }
                    .card { break-inside: avoid; }
                    .chart-container { height: 400px !important; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>