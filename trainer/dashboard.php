<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

// Check if user is logged in and is a trainer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'trainer') {
    header("location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_datetime = "2025-03-15 06:21:59"; // Current UTC time

// Get trainer's videos with comment counts
$videos_query = mysqli_query($conn, "
    SELECT v.*, 
           (SELECT COUNT(*) FROM comments WHERE video_id = v.video_id) as comment_count
    FROM videos v 
    WHERE v.user_id = $user_id 
    ORDER BY v.upload_date DESC");

// Get trainer's skills
$skills_query = mysqli_query($conn, "SELECT language FROM trainer_skills WHERE user_id = $user_id");
$skills = [];
while($skill = mysqli_fetch_assoc($skills_query)) {
    $skills[] = $skill['language'];
}

// Get statistics
$total_videos = mysqli_num_rows($videos_query);
$active_videos = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM videos 
    WHERE user_id = $user_id AND status = 'active'"))['count'];

$total_comments = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM comments 
    WHERE video_id IN (SELECT video_id FROM videos WHERE user_id = $user_id)"))['count'];

// Get recent comments on trainer's videos with user information
$comments_query = mysqli_query($conn, "
    SELECT c.*, 
           v.title as video_title, 
           u.name as user_name,
           v.video_id,
           v.video_path
    FROM comments c
    JOIN videos v ON c.video_id = v.video_id
    JOIN users u ON c.user_id = u.user_id
    WHERE v.user_id = $user_id
    ORDER BY c.created_at DESC
    LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            width: 120px;
            height: 68px;
            object-fit: cover;
            cursor: pointer;
            background: #000;
        }
        #previewPlayer {
            max-height: 70vh;
            background: #000;
            width: 100%;
        }
        .modal-body {
            background: #000;
            padding: 0;
        }
        .comment-section {
            max-height: 400px;
            overflow-y: auto;
        }
        @media (min-width: 992px) {
            .modal-lg {
                max-width: 80%;
            }
        }
        .skills-badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include('includes/trainer_navbar.php'); ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p class="text-muted">
                    Your Skills: 
                    <?php if(!empty($skills)): ?>
                        <?php foreach($skills as $skill): ?>
                            <span class="badge bg-primary skills-badge">
                                <?php echo htmlspecialchars($skill); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-warning">
                            <i class="fas fa-exclamation-circle"></i>
                            No skills qualified yet. 
                            <a href="take_test.php">Take a skill test</a>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stats-card bg-primary text-white h-100">
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
            
            <div class="col-md-4">
                <div class="card stats-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Active Videos</h6>
                                <h2 class="mt-2 mb-0"><?php echo $active_videos; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stats-card bg-info text-white h-100">
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
        </div>

        <!-- Videos Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Your Videos</h5>
                <a href="upload_video.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-upload me-2"></i>Upload New Video
                </a>
            </div>
            <div class="card-body">
                <?php if($total_videos > 0): ?>
                <div class="table-responsive">
                    <table id="videosTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Language</th>
                                <th>Upload Date</th>
                                <th>Comments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($videos_query, 0); ?>
                            <?php while($video = mysqli_fetch_assoc($videos_query)): ?>
                            <tr>
                                <td>
                                    <video class="video-thumbnail" 
                                           data-video-id="<?php echo $video['video_id']; ?>"
                                           muted>
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
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($video['language']); ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo $video['upload_date']; ?>">
                                    <?php 
                                    $upload_date = new DateTime($video['upload_date']);
                                    $current_date = new DateTime($current_datetime);
                                    $interval = $current_date->diff($upload_date);
                                    
                                    if($interval->days == 0) {
                                        if($interval->h == 0) {
                                            echo $interval->i . " minutes ago";
                                        } else {
                                            echo $interval->h . " hours ago";
                                        }
                                    } else {
                                        echo date('M d, Y H:i', strtotime($video['upload_date']));
                                    }
                                    ?>
                                </td>
                                <td><?php echo $video['comment_count']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($video['status'] == 'active') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($video['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="previewVideo(<?php echo $video['video_id']; ?>)">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php if($video['material_path']): ?>
                                    <a href="../assets/uploads/materials/<?php echo $video['material_path']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       target="_blank">
                                        <i class="fas fa-file-download"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteVideo(<?php echo $video['video_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <p class="mb-0">You haven't uploaded any videos yet.</p>
                    <a href="upload_video.php" class="btn btn-primary mt-3">
                        <i class="fas fa-upload me-2"></i>Upload Your First Video
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Comments -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Comments</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($comments_query) > 0): ?>
                    <div class="list-group comment-section">
                        <?php while($comment = mysqli_fetch_assoc($comments_query)): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($comment['user_name']); ?></h6>
                                <small class="text-muted" data-timestamp="<?php echo $comment['created_at']; ?>">
                                    <?php 
                                    $comment_date = new DateTime($comment['created_at']);
                                    $interval = $current_date->diff($comment_date);
                                    
                                    if($interval->days == 0) {
                                        if($interval->h == 0) {
                                            echo $interval->i . " minutes ago";
                                        } else {
                                            echo $interval->h . " hours ago";
                                        }
                                    } elseif($interval->days == 1) {
                                        echo "Yesterday";
                                    } else {
                                        echo date('M d, Y', strtotime($comment['created_at']));
                                    }
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            <small class="text-muted">
                                On video: 
                                <a href="#" onclick="previewVideo(<?php echo $comment['video_id']; ?>); return false;">
                                    <?php echo htmlspecialchars($comment['video_title']); ?>
                                </a>
                            </small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No comments yet on your videos.</p>
                    </div>
                <?php endif; ?>
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
                    <video id="previewPlayer" controls controlsList="nodownload">
                        Your browser does not support video playback.
                    </video>
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

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#videosTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']], // Sort by upload date by default
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search videos..."
                },
                columnDefs: [
                    { orderable: false, targets: [0, 6] } // Disable sorting for preview and actions columns
                ]
            });

            // Initialize video thumbnails
            document.querySelectorAll('.video-thumbnail').forEach(video => {
                video.addEventListener('mouseover', function() {
                    this.play().catch(function(e) {
                        console.log("Preview play failed:", e);
                    });
                });
                
                video.addEventListener('mouseout', function() {
                    this.pause();
                    this.currentTime = 0;
                });
                
                video.addEventListener('click', function() {
                    const videoId = this.dataset.videoId;
                    previewVideo(videoId);
                });
            });
        });

        // Function to preview video
        function previewVideo(videoId) {
            const modal = $('#videoPreviewModal');
            const player = document.getElementById('previewPlayer');
            
            // Find the video element with matching data-video-id
            const videoElement = document.querySelector(`video[data-video-id="${videoId}"]`);
            if (videoElement) {
                const videoSource = videoElement.querySelector('source').src;
                
                // Set the source and load the video
                player.src = videoSource;
                player.load();
                
                // Show modal
                modal.modal('show');
                
                // Auto play when modal is shown
                modal.on('shown.bs.modal', function() {
                    player.play().catch(function(error) {
                        console.log("Video play failed:", error);
                    });
                });
            }
        }

        // Clean up video when modal is closed
        $('#videoPreviewModal').on('hidden.bs.modal', function() {
            const player = document.getElementById('previewPlayer');
            player.pause();
            player.src = '';
            player.load();
        });

        // Function to delete video
        function deleteVideo(videoId) {
            if(confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
                $.ajax({
                    url: 'ajax/delete_video.php',
                    type: 'POST',
                    data: { video_id: videoId },
                    beforeSend: function() {
                        // Disable delete button and show loading state
                        $(`button[onclick="deleteVideo(${videoId})"]`)
                            .prop('disabled', true)
                            .html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if(response === 'success') {
                            // Fade out and remove the table row
                            $(`video[data-video-id="${videoId}"]`)
                                .closest('tr')
                                .fadeOut(400, function() {
                                    $(this).remove();
                                    // Reload page to update statistics
                                    location.reload();
                                });
                        } else {
                            alert('Error deleting video. Please try again.');
                            // Reset delete button
                            $(`button[onclick="deleteVideo(${videoId})"]`)
                                .prop('disabled', false)
                                .html('<i class="fas fa-trash"></i>');
                        }
                    },
                    error: function() {
                        alert('Error connecting to server. Please try again.');
                        // Reset delete button
                        $(`button[onclick="deleteVideo(${videoId})"]`)
                            .prop('disabled', false)
                            .html('<i class="fas fa-trash"></i>');
                    }
                });
            }
        }

        // Check for video upload success message
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('upload') === 'success') {
            // Show success message
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Video uploaded successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);
            
            // Remove success parameter from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Update relative timestamps every minute
        setInterval(function() {
            const now = new Date('<?php echo $current_datetime; ?>');
            
            document.querySelectorAll('[data-timestamp]').forEach(element => {
                const timestamp = new Date(element.dataset.timestamp);
                const diffMinutes = Math.floor((now - timestamp) / 60000);
                
                if(diffMinutes < 60) {
                    element.textContent = `${diffMinutes} minutes ago`;
                } else if(diffMinutes < 1440) {
                    const hours = Math.floor(diffMinutes / 60);
                    element.textContent = `${hours} hours ago`;
                }
            });
        }, 60000);

        // Animate statistics cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.addEventListener('mouseover', function() {
                const icon = this.querySelector('i');
                icon.style.transform = 'scale(1.2)';
                icon.style.transition = 'transform 0.3s';
            });
            
            card.addEventListener('mouseout', function() {
                const icon = this.querySelector('i');
                icon.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>