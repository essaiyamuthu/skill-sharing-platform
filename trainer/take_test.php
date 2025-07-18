<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

// Check login status
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'trainer') {
    header("location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = "";
$current_datetime = "2025-03-14 12:36:45";

// Get trainer's assigned skills
$skills_query = mysqli_query($conn, "SELECT language FROM trainer_skills WHERE user_id = $user_id");
$trainer_skills = [];
while($skill = mysqli_fetch_assoc($skills_query)) {
    $trainer_skills[] = $skill['language'];
}

// Get available questions only for trainer's skills
if(!empty($trainer_skills)) {
    $languages_query = mysqli_query($conn, "SELECT DISTINCT language 
                                          FROM quiz_questions 
                                          WHERE language IN ('" . implode("','", $trainer_skills) . "')");
    $available_languages = [];
    while($lang = mysqli_fetch_assoc($languages_query)) {
        $available_languages[] = $lang['language'];
    }
} else {
    $available_languages = [];
}

// Get trainer's test results
$results_query = mysqli_query($conn, "
    SELECT language, MAX(percentage) as best_score, status
    FROM quiz_results 
    WHERE user_id = $user_id
    GROUP BY language
");

$test_results = [];
while($result = mysqli_fetch_assoc($results_query)) {
    $test_results[$result['language']] = [
        'score' => $result['best_score'],
        'status' => $result['status']
    ];
}

// Handle test submission
if(isset($_POST['submit_test'])) {
    $language = cleanInput($_POST['language']);
    
    // Verify language is in trainer's skills
    if(!in_array($language, $trainer_skills)) {
        $error = "Unauthorized test attempt.";
    } else {
        $answers = isset($_POST['answers']) ? $_POST['answers'] : [];
        
        // Get questions for the selected language
        $questions_query = mysqli_query($conn, "SELECT * FROM quiz_questions WHERE language = '$language'");
        $total_questions = mysqli_num_rows($questions_query);
        
        if($total_questions > 0) {
            $correct_answers = 0;
            
            while($question = mysqli_fetch_assoc($questions_query)) {
                if(isset($answers[$question['question_id']]) && 
                   $answers[$question['question_id']] == $question['correct_answer']) {
                    $correct_answers++;
                }
            }
            
            $percentage = ($correct_answers / $total_questions) * 100;
            $status = ($percentage >= 70) ? 'pass' : 'fail';
            
            // Record test result
            $query = "INSERT INTO quiz_results (user_id, language, score, percentage, status) 
                     VALUES ($user_id, '$language', $correct_answers, $percentage, '$status')";
            
            if(mysqli_query($conn, $query)) {
                if($status == 'pass') {
                    // Add or update trainer skill if passed
                    $skill_check = mysqli_query($conn, "SELECT skill_id FROM trainer_skills 
                                                      WHERE user_id = $user_id AND language = '$language'");
                    
                    if(mysqli_num_rows($skill_check) == 0) {
                        mysqli_query($conn, "INSERT INTO trainer_skills (user_id, language) 
                                          VALUES ($user_id, '$language')");
                    }
                    
                    $success = "Congratulations! You passed the $language test with $percentage%!";
                } else {
                    $error = "You scored $percentage%. Minimum 70% required to pass. Try again!";
                }
            } else {
                $error = "Error recording test results.";
            }
        } else {
            $error = "No questions available for this test.";
        }
    }
}

// Get questions if language is selected
$questions = [];
if(isset($_GET['language'])) {
    $selected_language = cleanInput($_GET['language']);
    
    // Verify selected language is in trainer's skills
    if(in_array($selected_language, $trainer_skills)) {
        $questions_query = mysqli_query($conn, "SELECT * FROM quiz_questions 
                                              WHERE language = '$selected_language' 
                                              ORDER BY RAND() 
                                              LIMIT 20");
        while($question = mysqli_fetch_assoc($questions_query)) {
            $questions[] = $question;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Skill Test - LearnHub Trainer</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .language-card {
            transition: transform 0.3s;
        }
        .language-card:hover {
            transform: translateY(-5px);
        }
        .question-card {
            margin-bottom: 1rem;
        }
        #timerDisplay {
            z-index: 1050;
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-timer {
            position: sticky;
            top: 1rem;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <?php include('includes/trainer_navbar.php'); ?>

    <div class="container py-4">
        <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if(!isset($_GET['language'])): ?>
        <!-- Language Selection -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Select Programming Language</h2>
                <p class="text-muted">Choose a language to take the skill test. You need to score at least 70% to qualify.</p>
                
                <?php if(empty($trainer_skills)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    You don't have any assigned programming languages.
                </div>
                <?php elseif(empty($available_languages)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No test questions are currently available for your skills (<?php echo implode(', ', $trainer_skills); ?>). 
                    Please check back later.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($trainer_skills as $language): ?>
            <div class="col">
                <div class="card language-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo $language; ?>
                            <?php if(isset($test_results[$language])): ?>
                                <span class="badge bg-<?php echo ($test_results[$language]['status'] == 'pass') ? 'success' : 'danger'; ?>">
                                    <?php echo $test_results[$language]['score']; ?>%
                                </span>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text">
                            <?php if(!in_array($language, $available_languages)): ?>
                                <i class="fas fa-clock text-warning"></i> 
                                Waiting for questions to be added
                            <?php else: ?>
                                <?php if(isset($test_results[$language])): ?>
                                    <?php if($test_results[$language]['status'] == 'pass'): ?>
                                        <i class="fas fa-check-circle text-success"></i> Qualified
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger"></i> Not Qualified
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-question-circle text-warning"></i> Not Attempted
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                        <?php if(in_array($language, $available_languages)): ?>
                            <a href="?language=<?php echo urlencode($language); ?>" class="btn btn-primary">
                                <?php if(isset($test_results[$language]) && $test_results[$language]['status'] == 'pass'): ?>
                                    <i class="fas fa-redo me-2"></i>Retake Test
                                <?php else: ?>
                                    <i class="fas fa-play me-2"></i>Take Test
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-clock me-2"></i>Not Available Yet
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Quiz Section -->
        <?php if(in_array($_GET['language'], $trainer_skills)): ?>
            <div class="test-timer card mb-3" id="timerDisplay">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Time Remaining</h5>
                        <h3 id="timer" class="mb-0">20:00</h3>
                    </div>
                    <div class="ms-auto">
                        <small class="text-muted">Test will auto-submit when timer ends</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($_GET['language']); ?> Skill Test</h5>
                    <a href="take_test.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Languages
                    </a>
                </div>
                <div class="card-body">
                    <?php if(empty($questions)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No questions available for this language at the moment.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="quizForm">
                            <input type="hidden" name="language" value="<?php echo htmlspecialchars($_GET['language']); ?>">
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You need to score at least 70% to qualify. Take your time and answer carefully.
                            </div>

                            <?php foreach($questions as $index => $question): ?>
                            <div class="card question-card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        Question <?php echo $index + 1; ?> of <?php echo count($questions); ?>
                                    </h6>
                                    <p class="card-text">
                                        <?php echo htmlspecialchars($question['question_text']); ?>
                                    </p>
                                    <div class="list-group">
                                        <?php for($i = 1; $i <= 4; $i++): ?>
                                        <label class="list-group-item">
                                            <input type="radio" 
                                                   name="answers[<?php echo $question['question_id']; ?>]" 
                                                   value="<?php echo $i; ?>" 
                                                   required 
                                                   class="form-check-input me-2">
                                            <?php echo htmlspecialchars($question["option$i"]); ?>
                                        </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" name="submit_test" class="btn btn-primary" onClick="return confirmSubmit()">
                                    <i class="fas fa-check-circle me-2"></i>Submit Test
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Invalid language selection.
                <a href="take_test.php" class="alert-link">Return to language selection</a>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if(isset($_GET['language']) && !empty($questions)): ?>
        // Timer functionality
        let timeLeft = 1200; // 20 minutes in seconds
        const timerDisplay = document.getElementById('timer');
        
        const timerInterval = setInterval(function() {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            if(timeLeft <= 300) { // Last 5 minutes
                timerDisplay.style.color = 'red';
            }

            if(timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('quizForm').submit();
            }
        }, 1000);

        // Prevent form resubmission
        if(window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Confirm submission
        function confirmSubmit() {
            return confirm('Are you sure you want to submit your test? Make sure you have answered all questions.');
        }

        // Warn before leaving page
        window.onbeforeunload = function() {
            return "If you leave this page, your test progress will be lost. Are you sure?";
        };
        <?php endif; ?>
    </script>
	
	</body>
	</html>