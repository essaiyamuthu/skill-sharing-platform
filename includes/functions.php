<?php
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function checkEmailExists($email) {
    global $conn;
    $email = cleanInput($email);
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    return mysqli_num_rows($query) > 0;
}

function calculateQuizPercentage($correct, $total) {
    return ($correct / $total) * 100;
}

function checkQuizEligibility($user_id, $language) {
    global $conn;
    $query = mysqli_query($conn, "SELECT * FROM quiz_results WHERE user_id=$user_id AND language='$language' AND status='pass'");
    return mysqli_num_rows($query) > 0;
}

function uploadFile($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if file already exists
    if (file_exists($target_file)) {
        $filename = pathinfo($file["name"], PATHINFO_FILENAME);
        $target_file = $target_dir . $filename . "_" . time() . "." . $imageFileType;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return basename($target_file);
    } else {
        return false;
    }
}
?>