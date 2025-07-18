<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}

$success = $error = "";
$current_datetime = "2025-03-14 12:05:33"; // Current UTC time

// Handle video status toggle
if(isset($_GET['toggle_status']) && isset($_GET['video_id'])) {
    $video_id = cleanInput($_GET['video_id']);
    $new_status = ($_GET['toggle_status'] == 'active') ? 'inactive' : 'active';
    
    if(mysqli_query($conn, "UPDATE videos SET status='$new_status' WHERE video_id=$video_id")) {
        $success = "Video status updated successfully!";
    } else {
        $error = "Error updating video status: " . mysqli_error($conn);
    }
}

// Handle video deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $video_id = cleanInput($_GET['delete']);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Get video details first
        $video_query = mysqli_query($conn, "SELECT video_path, material_path FROM videos WHERE video_id = $video_id");
        $video_data = mysqli_fetch_assoc($video_query);
        
        // Delete physical files if they exist
        if($video_data['video_path'] && file_exists("../assets/uploads/videos/" . $video_data['video_path'])) {
            unlink("../assets/uploads/videos/" . $video_data['video_path']);
        }
        if($video_data['material_path'] && file_exists("../assets/uploads/materials/" . $video_data['material_path'])) {
            unlink("../assets/uploads/materials/" . $video_data['material_path']);
        }
        
        // Delete comments first
        mysqli_query($conn, "DELETE FROM comments WHERE video_id = $video_id");
        
        // Delete video record
        mysqli_query($conn, "DELETE FROM videos WHERE video_id = $video_id");
        
        mysqli_commit($conn);
        $success = "Video and associated data deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error deleting video: " . $e->getMessage();
    }
}

// Get all videos with trainer details and comment counts
$query = "SELECT v.*, 
          u.name as trainer_name, 
          u.email as trainer_email,
          COUNT(c.comment_id) as comment_count,
          (SELECT GROUP_CONCAT(DISTINCT language) 
           FROM trainer_skills 
           WHERE user_id = v.user_id) as trainer_languages
          FROM videos v 
          LEFT JOIN users u ON v.user_id = u.user_id
          LEFT JOIN comments c ON v.video_id = c.video_id
          GROUP BY v.video_id
          ORDER BY v.upload_date DESC";
$videos = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - LearnHub Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Video.js -->
    <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet">
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
        .video-preview {
            max-width: 200px;
            max-height: 120px;
            cursor: pointer;
        }
        .video-badge {
            font-size: 0.8em;
            padding: 3px 8px;
        }
        .comment-count {
            font-size: 0.9em;
            color: #6c757d;
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
                <h2>Manage Videos</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onClick="exportVideoData()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#videoStatsModal">
                        <i class="fas fa-chart-bar me-2"></i>Video Statistics
                    </button>
                </div>
            </div>
            
            <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Videos Table -->
            <div class="card">
                <div class="card-body">
                    <table id="videosTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Trainer</th>
                                <th>Language</th>
                                <th>Upload Date</th>
                                <th>Comments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($video = mysqli_fetch_assoc($videos)): ?>
                            <tr>
                                <td><?php echo $video['video_id']; ?></td>
                                <td>
                                    <video class="video-preview" 
                                           poster="../assets/uploads/videos/thumbnails/<?php echo $video['video_id']; ?>.jpg"
                                           data-video-src="../assets/uploads/videos/<?php echo $video['video_path']; ?>">
                                        <source src="../assets/uploads/videos/<?php echo $video['video_path']; ?>" type="video/mp4">
                                    </video>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($video['title']); ?>
                                    <?php if($video['material_path']): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-file-alt"></i> Has materials
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($video['trainer_name']); ?>
                                    <br><small class="text-muted"><?php echo $video['trainer_email']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $video['language']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $upload_date = new DateTime($video['upload_date']);
                                    $current_date = new DateTime($current_datetime);
                                    $interval = $current_date->diff($upload_date);
                                    
                                    if($interval->days == 0) {
                                        echo "Today " . $upload_date->format('H:i');
                                    } elseif($interval->days == 1) {
                                        echo "Yesterday " . $upload_date->format('H:i');
                                    } else {
                                        echo $upload_date->format('Y-m-d H:i');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="comment-count">
                                        <i class="fas fa-comments"></i> 
                                        <?php echo $video['comment_count']; ?>
                                    </span>
                                    <button class="btn btn-sm btn-link" 
                                            onclick="viewComments(<?php echo $video['video_id']; ?>)">
                                        View
                                    </button>
                                </td>
                                <td>
                                    <a href="?toggle_status=<?php echo $video['status']; ?>&video_id=<?php echo $video['video_id']; ?>" 
                                       class="badge text-decoration-none bg-<?php echo ($video['status'] == 'active') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($video['status']); ?>
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="previewVideo('<?php echo htmlspecialchars($video['title']); ?>', 
                                                               '../assets/uploads/videos/<?php echo $video['video_path']; ?>')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php if($video['material_path']): ?>
                                    <a href="../assets/uploads/materials/<?php echo $video['material_path']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       target="_blank">
                                        <i class="fas fa-file-download"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $video['video_id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure? This will delete the video and all associated comments.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Preview Modal -->
    <div class="modal fade" id="videoPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Video Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <video id="previewVideo" class="video-js vjs-default-skin" controls>
                        Your browser does not support video playback.
                    </video>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Modal -->
    <div class="modal fade" id="commentsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Video Comments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="commentsList" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Statistics Modal -->
    <div class="modal fade" id="videoStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Video Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="videoLanguageChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="videoStatusChart"></canvas>
                        </div>
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
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <!-- Video.js -->
    <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#videosTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                                        exportOptions: {
                            columns: [0,2,3,4,5,6,7]
                        }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: [0,2,3,4,5,6,7]
                        }
                    }
                ],
                order: [[5, 'desc']] // Sort by upload date by default
            });

            // Initialize video preview thumbnails
            $('.video-preview').on('mouseover', function() {
                this.play();
            }).on('mouseout', function() {
                this.pause();
                this.currentTime = 0;
            });

            // Initialize Charts
            const languageCtx = document.getElementById('videoLanguageChart').getContext('2d');
            const statusCtx = document.getElementById('videoStatusChart').getContext('2d');

            // Calculate statistics from table data
            let languageStats = {};
            let statusStats = {active: 0, inactive: 0};

            $('#videosTable tbody tr').each(function() {
                const language = $(this).find('td:eq(4)').text().trim();
                const status = $(this).find('td:eq(7)').text().trim().toLowerCase();
                
                languageStats[language] = (languageStats[language] || 0) + 1;
                statusStats[status]++;
            });

            // Language Distribution Chart
            new Chart(languageCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(languageStats),
                    datasets: [{
                        label: 'Videos per Language',
                        data: Object.values(languageStats),
                        backgroundColor: [
                            '#0d6efd', '#6610f2', '#6f42c1', 
                            '#d63384', '#dc3545', '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Videos by Programming Language'
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

            // Video Status Chart
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [statusStats.active, statusStats.inactive],
                        backgroundColor: ['#198754', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Video Status Distribution'
                        }
                    }
                }
            });
        });

        // Function to preview video
        let videoPlayer = null;
        function previewVideo(title, videoUrl) {
            const modal = $('#videoPreviewModal');
            modal.find('.modal-title').text(title);
            
            if(videoPlayer) {
                videoPlayer.dispose();
            }
            
            videoPlayer = videojs('previewVideo', {
                controls: true,
                autoplay: false,
                preload: 'auto',
                width: 640,
                height: 360
            });
            
            videoPlayer.src({
                type: 'video/mp4',
                src: videoUrl
            });
            
            modal.modal('show');
            
            modal.on('hidden.bs.modal', function() {
                if(videoPlayer) {
                    videoPlayer.pause();
                }
            });
        }

        // Function to view comments
        function viewComments(videoId) {
            $.ajax({
                url: 'ajax/get_comments.php',
                type: 'POST',
                data: {video_id: videoId},
                success: function(response) {
                    const comments = JSON.parse(response);
                    let html = '';
                    
                    if(comments.length > 0) {
                        comments.forEach(comment => {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${comment.user_name}</h6>
                                        <small>${comment.created_at}</small>
                                    </div>
                                    <p class="mb-1">${comment.comment_text}</p>
                                   
                                </div>
                            `;
                        });
                    } else {
                        html = '<div class="text-center p-3">No comments yet</div>';
                    }
                    
                    $('#commentsList').html(html);
                    $('#commentsModal').modal('show');
                },
                error: function() {
                    alert('Error loading comments');
                }
            });
        }

        // Function to delete comment
        function deleteComment(commentId) {
            if(confirm('Are you sure you want to delete this comment?')) {
                $.ajax({
                    url: 'ajax/delete_comment.php',
                    type: 'POST',
                    data: {comment_id: commentId},
                    success: function(response) {
                        if(response === 'success') {
                            $(`#comment-${commentId}`).remove();
                            location.reload(); // Refresh to update comment counts
                        } else {
                            alert('Error deleting comment');
                        }
                    },
                    error: function() {
                        alert('Error deleting comment');
                    }
                });
            }
        }

        // Function to handle video data export
        function exportVideoData() {
            const buttons = $.fn.dataTable.Buttons.getInstance('videosTable');
            buttons.trigger('exportData', {
                format: 'excel'
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
        });
    </script>
</body>
</html>