<?php
session_start();
include("../../includes/dbconnect.php");
include("../../includes/functions.php");

// Verify admin is logged in
if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if(!isset($_POST['video_id'])) {
    echo json_encode(['error' => 'Video ID required']);
    exit();
}

$video_id = cleanInput($_POST['video_id']);

// Get comments with user information
$query = "SELECT c.*, u.name as user_name, 
          CASE 
              WHEN TIMESTAMPDIFF(MINUTE, c.created_at, NOW()) < 60 
                  THEN CONCAT(TIMESTAMPDIFF(MINUTE, c.created_at, NOW()), ' minutes ago')
              WHEN TIMESTAMPDIFF(HOUR, c.created_at, NOW()) < 24 
                  THEN CONCAT(TIMESTAMPDIFF(HOUR, c.created_at, NOW()), ' hours ago')
              ELSE DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i')
          END as created_at_formatted
          FROM comments c
          JOIN users u ON c.user_id = u.user_id
          WHERE c.video_id = $video_id
          ORDER BY c.created_at DESC";

$result = mysqli_query($conn, $query);

$comments = [];
while($row = mysqli_fetch_assoc($result)) {
    $comments[] = [
        'comment_id' => $row['comment_id'],
        'user_name' => htmlspecialchars($row['user_name']),
        'comment_text' => htmlspecialchars($row['comment_text']),
        'created_at' => $row['created_at_formatted']
    ];
}

echo json_encode($comments);
?>