<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}

$success = $error = "";

// Add new question
if(isset($_POST['add_question'])) {
    $language = cleanInput($_POST['language']);
    $question = cleanInput($_POST['question']);
    $option1 = cleanInput($_POST['option1']);
    $option2 = cleanInput($_POST['option2']);
    $option3 = cleanInput($_POST['option3']);
    $option4 = cleanInput($_POST['option4']);
    $correct_answer = cleanInput($_POST['correct_answer']);
    
    $query = "INSERT INTO quiz_questions (language, question_text, option1, option2, option3, option4, correct_answer) 
              VALUES ('$language', '$question', '$option1', '$option2', '$option3', '$option4', $correct_answer)";
    
    if(mysqli_query($conn, $query)) {
        $success = "Question added successfully!";
    } else {
        $error = "Error adding question: " . mysqli_error($conn);
    }
}

// Delete question
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = cleanInput($_GET['delete']);
    if(mysqli_query($conn, "DELETE FROM quiz_questions WHERE question_id = $id")) {
        $success = "Question deleted successfully!";
    } else {
        $error = "Error deleting question: " . mysqli_error($conn);
    }
}

// Get all questions
$questions = mysqli_query($conn, "SELECT * FROM quiz_questions ORDER BY language, question_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz - LearnHub Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                <h2>Manage Quiz Questions</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    <i class="fas fa-plus me-2"></i>Add New Question
                </button>
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
            
            <!-- Questions Table -->
            <div class="card">
                <div class="card-body">
                    <table id="questionsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Language</th>
                                <th>Question</th>
                                <th>Options</th>
                                <th>Correct Answer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($questions)): ?>
                            <tr>
                                <td><?php echo $row['question_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['language']); ?></td>
                                <td><?php echo htmlspecialchars($row['question_text']); ?></td>
                                <td>
                                    1. <?php echo htmlspecialchars($row['option1']); ?><br>
                                    2. <?php echo htmlspecialchars($row['option2']); ?><br>
                                    3. <?php echo htmlspecialchars($row['option3']); ?><br>
                                    4. <?php echo htmlspecialchars($row['option4']); ?>
                                </td>
                                <td><?php echo $row['correct_answer']; ?></td>
                                <td>
                                   
                                    <a href="?delete=<?php echo $row['question_id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this question?')">
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

    <!-- Add Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Programming Language</label>
                            <select class="form-select" name="language" required>
                                <option value="">Select Language</option>
                                <option value="PHP">PHP</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="JavaScript">JavaScript</option>
                                <option value="C++">C++</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Question</label>
                            <textarea class="form-control" name="question" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Option 1</label>
                                <input type="text" class="form-control" name="option1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Option 2</label>
                                <input type="text" class="form-control" name="option2" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Option 3</label>
                                <input type="text" class="form-control" name="option3" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Option 4</label>
                                <input type="text" class="form-control" name="option4" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Correct Answer (1-4)</label>
                            <select class="form-select" name="correct_answer" required>
                                <option value="">Select Correct Answer</option>
                                <option value="1">Option 1</option>
                                <option value="2">Option 2</option>
                                <option value="3">Option 3</option>
                                <option value="4">Option 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                    </div>
                </form>
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
            $('#questionsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        });
    </script>
</body>
</html>