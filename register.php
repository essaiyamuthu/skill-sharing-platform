<?php
session_start();
include("includes/dbconnect.php");
include("includes/functions.php");

$errors = [];
$success = false;

if(isset($_POST['register'])) {
    // Validate inputs
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $password = cleanInput($_POST['password']);
    $confirm_password = cleanInput($_POST['confirm_password']);
    $gender = cleanInput($_POST['gender']);
    $qualification = cleanInput($_POST['qualification']);
    $user_type = cleanInput($_POST['user_type']);
    
    // Validation
    if(empty($name)) {
        $errors[] = "Name is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif(checkEmailExists($email)) {
        $errors[] = "Email already exists";
    }
    
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($gender)) {
        $errors[] = "Gender is required";
    }
    
    if(empty($qualification)) {
        $errors[] = "Qualification is required";
    } elseif (!preg_match("/^[a-zA-Z\s\.\-]+$/", $qualification)) {
        $errors[]="Qualification must contain only letters and spaces";
    } elseif (strlen($Qualification) < 2 || strlen($qualification) > 10) {
        $errors[]="Qualification must be  between 2 and 100 characters long"; 
    }
    
    if(empty($user_type)) {
        $errors[] = "User type is required";
    }
    
    // If trainer, validate languages
    if($user_type == 'trainer' && empty($_POST['languages'])) {
        $errors[] = "Please select at least one programming language";
    }
    
    // If no errors, proceed with registration
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (name, email, password, gender, qualification, user_type) 
                  VALUES ('$name', '$email', '$hashed_password', '$gender', '$qualification', '$user_type')";
        
        if(mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            
            // If trainer, insert programming languages
            if($user_type == 'trainer' && isset($_POST['languages'])) {
                foreach($_POST['languages'] as $language) {
                    $language = cleanInput($language);
                    mysqli_query($conn, "INSERT INTO trainer_skills (user_id, language) VALUES ($user_id, '$language')");
                }
            }
            
            $success = true;
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LearnHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .trainer-fields {
            display: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Register</h2>
                        
                        <?php if($success): ?>
                        <div class="alert alert-success">
                            Registration successful! Redirecting to login page...
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control" name="qualification" value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Register as</label>
                                <select class="form-select" name="user_type" id="user_type" required>
                                    <option value="">Select Type</option>
                                    <option value="trainer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'trainer') ? 'selected' : ''; ?>>Trainer</option>
                                    <option value="learner" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'learner') ? 'selected' : ''; ?>>Learner</option>
                                </select>
                            </div>
                            
                              <div class="mb-3 trainer-fields">
                                <label class="form-label">Programming Languages</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="languages[]" value="PHP" id="lang_php" 
                                                <?php echo (isset($_POST['languages']) && in_array('PHP', $_POST['languages'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="lang_php">PHP</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="languages[]" value="Java" id="lang_java"
                                                <?php echo (isset($_POST['languages']) && in_array('Java', $_POST['languages'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="lang_java">Java</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="languages[]" value="Python" id="lang_python"
                                                <?php echo (isset($_POST['languages']) && in_array('Python', $_POST['languages'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="lang_python">Python</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="languages[]" value="JavaScript" id="lang_js"
                                                <?php echo (isset($_POST['languages']) && in_array('JavaScript', $_POST['languages'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="lang_js">JavaScript</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="languages[]" value="C++" id="lang_cpp"
                                                <?php echo (isset($_POST['languages']) && in_array('C++', $_POST['languages'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="lang_cpp">C++</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="register" class="btn btn-primary">Register</button>
                                <a href="login.php" class="btn btn-link text-center">Already have an account? Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide trainer fields based on user type selection
        document.getElementById('user_type').addEventListener('change', function() {
            const trainerFields = document.querySelectorAll('.trainer-fields');
            trainerFields.forEach(field => {
                if(this.value === 'trainer') {
                    field.style.display = 'block';
                } else {
                    field.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>