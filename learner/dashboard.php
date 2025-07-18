<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'learner') {
    header("location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_datetime = "2025-03-15 05:19:47";

// Handle comment submission
if(isset($_POST['add_comment'])) {
    $video_id = cleanInput($_POST['video_id']);
    $comment_text = cleanInput($_POST['comment_text']);
    
    $comment_query = "INSERT INTO comments (video_id, user_id, comment_text) 
                     VALUES ($video_id, $user_id, '$comment_text')";
    
    if(mysqli_query($conn, $comment_query)) {
        header("Location: dashboard.php?success=comment_added#video_" . $video_id);
        exit();
    }
}

// Get available programming languages
$languages_query = mysqli_query($conn, "SELECT DISTINCT language FROM videos WHERE status = 'active'");
$languages = [];
while($lang = mysqli_fetch_assoc($languages_query)) {
    $languages[] = $lang['language'];
}

// Filter videos by language if selected
$selected_language = isset($_GET['language']) ? cleanInput($_GET['language']) : '';
$language_filter = $selected_language ? "AND language = '$selected_language'" : "";

// Get videos with trainer information and comment counts
$videos_query = mysqli_query($conn, "
    SELECT v.*, 
           u.name as trainer_name, 
           u.qualification as trainer_qualification,
           COUNT(DISTINCT c.comment_id) as comment_count,
           COUNT(DISTINCT qr.result_id) as quiz_attempts,
           MAX(CASE WHEN qr.user_id = $user_id THEN qr.status END) as quiz_status
    FROM videos v
    JOIN users u ON v.user_id = u.user_id
    LEFT JOIN comments c ON v.video_id = c.video_id
    LEFT JOIN quiz_results qr ON qr.language = v.language AND qr.user_id = $user_id
    WHERE v.status = 'active' " . 
    ($selected_language ? "AND v.language = '$selected_language'" : "") . "
    GROUP BY v.video_id
    ORDER BY v.upload_date DESC"
);

if(!$videos_query) {
    die("Query error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .language-filter {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #f8f9fa;
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .trainer-info {
            font-size: 0.9rem;
        }
        .comment-section {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include('includes/learner_navbar.php'); ?>
<br><br>
    <div class="container-fluid py-4">
        <!-- Language Filter -->
        <div class="language-filter mb-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0">Available Learning Materials</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <div class="btn-group">
                                <a href="dashboard.php" class="btn btn-<?php echo empty($selected_language) ? 'primary' : 'outline-primary'; ?>">
                                    All Languages
                                </a>
                                <?php foreach($languages as $lang): ?>
                                <a href="?language=<?php echo urlencode($lang); ?>" 
                                   class="btn btn-<?php echo $selected_language == $lang ? 'primary' : 'outline-primary'; ?>">
                                    <?php echo $lang; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <?php if(mysqli_num_rows($videos_query) > 0): ?>
                    <?php while($video = mysqli_fetch_assoc($videos_query)): ?>
                    <div class="col-md-6 mb-4" id="video_<?php echo $video['video_id']; ?>">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($video['title']); ?></h5>
                            </div>
                            <div class="video-container">
                                <video controls>
                                    <source src="../assets/uploads/videos/<?php echo $video['video_path']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="card-body">
                                <div class="trainer-info mb-3">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <strong>Trainer:</strong> <?php echo htmlspecialchars($video['trainer_name']); ?>
                                    <br>
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    <strong>Qualification:</strong> <?php echo htmlspecialchars($video['trainer_qualification']); ?>
                                </div>

                                <p class="card-text"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary"><?php echo $video['language']; ?></span>
                                    <small class="text-muted">
                                        Uploaded: <?php echo date('M d, Y', strtotime($video['upload_date'])); ?>
                                    </small>
                                </div>

                                <?php if($video['material_path']): ?>
                                <a href="../assets/uploads/materials/<?php echo $video['material_path']; ?>" 
                                   class="btn btn-outline-primary btn-sm mb-3" target="_blank">
                                    <i class="fas fa-file-download me-2"></i>Download Materials
                                </a>
                                <?php endif; ?>

                                <!-- Comments Section -->
                                <h6 class="mb-3">
                                    <i class="fas fa-comments me-2"></i>
                                    Comments (<?php echo $video['comment_count']; ?>)
                                </h6>
                                
                                <div class="comment-section mb-3">
                                    <?php 
                                    $comments_query = mysqli_query($conn, "
                                        SELECT c.*, u.name 
                                        FROM comments c
                                        JOIN users u ON c.user_id = u.user_id
                                        WHERE c.video_id = {$video['video_id']}
                                        ORDER BY c.created_at DESC
                                    ");
                                    while($comment = mysqli_fetch_assoc($comments_query)):
                                    ?>
                                    <div class="card mb-2">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                <small class="text-muted">
                                                    <?php 
                                                    $comment_date = new DateTime($comment['created_at']);
                                                    $current_date = new DateTime($current_datetime);
                                                    $interval = $current_date->diff($comment_date);
                                                    
                                                    if($interval->days == 0) {
                                                        if($interval->h == 0) {
                                                            echo $interval->i . " minutes ago";
                                                        } else {
                                                            echo $interval->h . " hours ago";
                                                        }
                                                    } else {
                                                        echo date('M d, Y', strtotime($comment['created_at']));
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                            <p class="card-text mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>

                                <!-- Add Comment Form -->
                                <form method="POST" action="" class="mt-3">
                                    <input type="hidden" name="video_id" value="<?php echo $video['video_id']; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="comment_text" 
                                               placeholder="Add a comment..." required>
                                        <button type="submit" name="add_comment" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No videos available for the selected language.
                            <?php if($selected_language): ?>
                                <a href="dashboard.php" class="alert-link">View all videos</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to video if comment was just added
        <?php if(isset($_GET['success']) && isset($_GET['video_id'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const videoElement = document.getElementById('video_<?php echo $_GET['video_id']; ?>');
            if(videoElement) {
                videoElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
        <?php endif; ?>

        // Handle video playback
        document.addEventListener('DOMContentLoaded', function() {
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                video.addEventListener('play', function() {
                    videos.forEach(v => {
                        if(v !== video) v.pause();
                    });
                });
            });
        });
    </script>
</body>
</html>