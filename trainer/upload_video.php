<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'trainer') {
    header("location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = "";
$current_datetime = "2025-03-14 12:41:03";

// Create upload directories if they don't exist
$video_upload_dir = "../assets/uploads/videos";
$material_upload_dir = "../assets/uploads/materials";

if (!file_exists($video_upload_dir)) {
    mkdir($video_upload_dir, 0777, true);
}
if (!file_exists($material_upload_dir)) {
    mkdir($material_upload_dir, 0777, true);
}

// Get trainer's qualified languages (where they passed the test)
$qualified_languages_query = mysqli_query($conn, "
    SELECT DISTINCT language 
    FROM quiz_results 
    WHERE user_id = $user_id 
    AND status = 'pass' 
    AND percentage >= 70
");

$qualified_languages = [];
while($lang = mysqli_fetch_assoc($qualified_languages_query)) {
    $qualified_languages[] = $lang['language'];
}

// Handle video upload
if(isset($_POST['title'])) {
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $language = cleanInput($_POST['language']);
    
    // Check if trainer is qualified for this language
    if(!in_array($language, $qualified_languages)) {
        $error = "You must pass the skill test for $language before uploading videos!";
    } else {
        // Video file upload handling
        $video_file = $_FILES['video'];
        $allowed_video_types = ['video/mp4', 'video/webm'];
        $max_video_size = 500 * 1024 * 1024; // 500MB
        
        if(in_array($video_file['type'], $allowed_video_types) && $video_file['size'] <= $max_video_size) {
            $video_name = time() . '_' . $user_id . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $video_file['name']);
            $video_path = $video_upload_dir . '/' . $video_name;
            
            // Create directory if it doesn't exist
            if (!is_dir(dirname($video_path))) {
                mkdir(dirname($video_path), 0777, true);
            }
            
            if(move_uploaded_file($video_file['tmp_name'], $video_path)) {
                $material_name = "";
                
                // Handle optional material upload
                if(isset($_FILES['material']) && $_FILES['material']['size'] > 0) {
                    $material_file = $_FILES['material'];
                    $allowed_material_types = ['application/pdf', 'application/msword', 
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $max_material_size = 10 * 1024 * 1024; // 10MB
                    
                    if(in_array($material_file['type'], $allowed_material_types) && 
                       $material_file['size'] <= $max_material_size) {
                        $material_name = time() . '_' . $user_id . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $material_file['name']);
                        $material_path = $material_upload_dir . '/' . $material_name;
                        
                        // Create directory if it doesn't exist
                        if (!is_dir(dirname($material_path))) {
                            mkdir(dirname($material_path), 0777, true);
                        }
                        
                        if(!move_uploaded_file($material_file['tmp_name'], $material_path)) {
                            $error = "Error uploading material file.";
                            // Delete uploaded video if material upload fails
                            if(file_exists($video_path)) {
                                unlink($video_path);
                            }
                            $material_name = "";
                        }
                    }
                }
                
                if(!$error) {
                    // Insert video record
                    $query = "INSERT INTO videos (user_id, title, description, language, video_path, material_path) 
                             VALUES ($user_id, '$title', '$description', '$language', '$video_name', '$material_name')";
                    
                    if(mysqli_query($conn, $query)) {
                        $success = "Video uploaded successfully!";
                        header("location: dashboard.php?upload=success");
                        exit();
                    } else {
                        $error = "Database error. Please try again.";
                        // Delete uploaded files if database insert fails
                        if(file_exists($video_path)) {
                            unlink($video_path);
                        }
                        if($material_name && file_exists($material_upload_dir . '/' . $material_name)) {
                            unlink($material_upload_dir . '/' . $material_name);
                        }
                    }
                }
            } else {
                $error = "Error uploading video file. Please check directory permissions.";
            }
        } else {
            $error = "Invalid video file. Please upload MP4 or WebM files under 500MB.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - LearnHub Trainer</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .upload-preview {
            max-width: 100%;
            max-height: 300px;
            display: none;
        }
        .qualified-badge {
            font-size: 0.8em;
            cursor: help;
        }
        .upload-progress {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include('includes/trainer_navbar.php'); ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload New Video</h5>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if(empty($qualified_languages)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You haven't qualified for any programming language yet. 
                            <a href="take_test.php" class="alert-link">Take a skill test</a> to start uploading videos!
                        </div>
                        <?php else: ?>
                        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" required 
                                       maxlength="255" pattern="[A-Za-z0-9\s\-_\.]{3,255}">
                                <small class="text-muted">Use only letters, numbers, spaces, and basic punctuation</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required 
                                          maxlength="1000"></textarea>
                                <small class="text-muted">Maximum 1000 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Programming Language</label>
                                <select class="form-select" name="language" required>
                                    <option value="">Select Language</option>
                                    <?php foreach($qualified_languages as $lang): ?>
                                    <option value="<?php echo $lang; ?>">
                                        <?php echo $lang; ?>
                                        <?php 
                                        $score = mysqli_fetch_assoc(mysqli_query($conn, 
                                            "SELECT percentage FROM quiz_results 
                                             WHERE user_id = $user_id AND language = '$lang' 
                                             AND status = 'pass' 
                                             ORDER BY percentage DESC LIMIT 1"))['percentage'];
                                        ?>
                                        (Qualified - <?php echo $score; ?>%)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Video File</label>
                                <input type="file" class="form-control" name="video" 
                                       accept="video/mp4,video/webm" required onChange="previewVideo(this)">
                                <small class="text-muted">Max size: 500MB. Supported formats: MP4, WebM</small>
                                <video id="videoPreview" class="upload-preview mt-3" controls></video>
                                <div class="progress upload-progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Training Material (Optional)</label>
                                <input type="file" class="form-control" name="material" 
                                       accept=".pdf,.doc,.docx">
                                <small class="text-muted">Max size: 10MB. Supported formats: PDF, DOC, DOCX</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="upload" class="btn btn-primary" id="uploadBtn">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload Video
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewVideo(input) {
            const preview = document.getElementById('videoPreview');
            const file = input.files[0];
            
            if(file) {
                preview.style.display = 'block';
                preview.src = URL.createObjectURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        }

        // Form submission handling
        document.getElementById('uploadForm').onsubmit = function() {
            const videoFile = document.querySelector('input[name="video"]').files[0];
            if(videoFile && videoFile.size > 500 * 1024 * 1024) {
                alert('Video file size must be less than 500MB');
                return false;
            }

            const materialFile = document.querySelector('input[name="material"]').files[0];
            if(materialFile && materialFile.size > 10 * 1024 * 1024) {
                alert('Material file size must be less than 10MB');
                return false;
            }

            document.getElementById('uploadBtn').disabled = true;
            document.querySelector('.upload-progress').style.display = 'block';
            
            // Simulate upload progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                document.querySelector('.progress-bar').style.width = progress + '%';
                if(progress >= 100) clearInterval(interval);
            }, 500);

            return true;
        };
    </script>
</body>
</html>