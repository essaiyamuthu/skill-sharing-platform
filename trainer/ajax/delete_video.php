<?php
session_start();
include("../../includes/dbconnect.php");
include("../../includes/functions.php");

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'trainer' || !isset($_POST['video_id'])) {
    exit('unauthorized');
}

$video_id = cleanInput($_POST['video_id']);
$user_id = $_SESSION['user_id'];

// Verify video belongs to trainer
$video_query = mysqli_query($conn, "SELECT video_path, material_path FROM videos 
                                   WHERE video_id = $video_id AND user_id = $user_id");

if(mysqli_num_rows($video_query) == 1) {
    $video = mysqli_fetch_assoc($video_query);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Delete physical files
        if($video['video_path'] && file_exists("../../assets/uploads/videos/" . $video['video_path'])) {
            unlink("../../assets/uploads/videos/" . $video['video_path']);
        }
        if($video['material_path'] && file_exists("../../assets/uploads/materials/" . $video['material_path'])) {
            unlink("../../assets/uploads/materials/" . $video['material_path']);
        }
        
        // Delete comments
        mysqli_query($conn, "DELETE FROM comments WHERE video_id = $video_id");
        
        // Delete video record
        mysqli_query($conn, "DELETE FROM videos WHERE video_id = $video_id");
        
        mysqli_commit($conn);
        echo 'success';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo 'error';
    }
} else {
    echo 'unauthorized';
}
?>