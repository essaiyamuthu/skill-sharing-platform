<?php
session_start();
include("../includes/dbconnect.php");
include("../includes/functions.php");

if(!isset($_SESSION['admin_id'])) {
    header("location: index.php");
    exit();
}

$success = $error = "";

// Handle user status toggle
if(isset($_GET['toggle_status']) && isset($_GET['user_id'])) {
    $user_id = cleanInput($_GET['user_id']);
    $new_status = ($_GET['toggle_status'] == 'active') ? 'inactive' : 'active';
    
    if(mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE user_id=$user_id")) {
        $success = "User status updated successfully!";
    } else {
        $error = "Error updating user status: " . mysqli_error($conn);
    }
}

// Handle user deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = cleanInput($_GET['delete']);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Delete user's videos
        mysqli_query($conn, "DELETE FROM videos WHERE user_id = $user_id");
        
        // Delete user's comments
        mysqli_query($conn, "DELETE FROM comments WHERE user_id = $user_id");
        
        // Delete user's quiz results
        mysqli_query($conn, "DELETE FROM quiz_results WHERE user_id = $user_id");
        
        // Delete user's trainer skills
        mysqli_query($conn, "DELETE FROM trainer_skills WHERE user_id = $user_id");
        
        // Finally, delete the user
        mysqli_query($conn, "DELETE FROM users WHERE user_id = $user_id");
        
        mysqli_commit($conn);
        $success = "User and all related data deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Handle user update
if(isset($_POST['update_user'])) {
    $user_id = cleanInput($_POST['user_id']);
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $qualification = cleanInput($_POST['qualification']);
    
    // Check if email exists for other users
    $check_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id != $user_id");
    if(mysqli_num_rows($check_email) > 0) {
        $error = "Email already exists for another user!";
    } else {
        $query = "UPDATE users SET name='$name', email='$email', qualification='$qualification' WHERE user_id=$user_id";
        if(mysqli_query($conn, $query)) {
            // Update trainer skills if applicable
            if(isset($_POST['languages']) && is_array($_POST['languages'])) {
                mysqli_query($conn, "DELETE FROM trainer_skills WHERE user_id=$user_id");
                foreach($_POST['languages'] as $language) {
                    $language = cleanInput($language);
                    mysqli_query($conn, "INSERT INTO trainer_skills (user_id, language) VALUES ($user_id, '$language')");
                }
            }
            $success = "User updated successfully!";
        } else {
            $error = "Error updating user: " . mysqli_error($conn);
        }
    }
}

// Get all users with their skills
$query = "SELECT u.*, 
          GROUP_CONCAT(ts.language) as languages,
          COUNT(v.video_id) as video_count,
          COUNT(DISTINCT qr.result_id) as quiz_attempts
          FROM users u 
          LEFT JOIN trainer_skills ts ON u.user_id = ts.user_id
          LEFT JOIN videos v ON u.user_id = v.user_id
          LEFT JOIN quiz_results qr ON u.user_id = qr.user_id
          GROUP BY u.user_id
          ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - LearnHub Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
        .user-badge {
            font-size: 0.8em;
            padding: 3px 8px;
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
                <h2>Manage Users</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onClick="exportUserData()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userStatsModal">
                        <i class="fas fa-chart-bar me-2"></i>User Statistics
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
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <table id="usersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Qualification</th>
                                <th>Languages</th>
                                <th>Stats</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($user['user_type'] == 'trainer') ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['qualification']); ?></td>
                                <td>
                                    <?php 
                                    if($user['languages']) {
                                        $languages = explode(',', $user['languages']);
                                        foreach($languages as $lang) {
                                            echo "<span class='badge bg-secondary me-1'>$lang</span>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <small>
                                        Videos: <?php echo $user['video_count']; ?><br>
                                        Quiz Attempts: <?php echo $user['quiz_attempts']; ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="?toggle_status=<?php echo $user['status']; ?>&user_id=<?php echo $user['user_id']; ?>" 
                                       class="badge text-decoration-none bg-<?php echo ($user['status'] == 'active') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </a>
                                </td>
                                <td>
                                   
                                    <a href="?delete=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure? This will delete all user data including videos and comments.')">
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Qualification</label>
                            <input type="text" class="form-control" name="qualification" id="edit_qualification" required>
                        </div>
                        
                        <div class="mb-3 trainer-fields">
                            <label class="form-label">Programming Languages</label>
                            <select class="form-select" name="languages[]" id="edit_languages" multiple>
                                <option value="PHP">PHP</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="JavaScript">JavaScript</option>
                                <option value="C++">C++</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Statistics Modal -->
    <div class="modal fade" id="userStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="userTypeChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="userActivityChart"></canvas>
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
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#usersTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                                                className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: [0,1,2,3,4,5,6,7]
                        }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: [0,1,2,3,4,5,6,7]
                        }
                    }
                ]
            });

            // Initialize Select2 for multiple select
            $('#edit_languages').select2({
                width: '100%',
                placeholder: 'Select languages',
                allowClear: true
            });

            // Handle edit user modal
            $('.edit-user').click(function() {
                const userData = $(this).data('user');
                $('#edit_user_id').val(userData.user_id);
                $('#edit_name').val(userData.name);
                $('#edit_email').val(userData.email);
                $('#edit_qualification').val(userData.qualification);
                
                // Show/hide trainer fields based on user type
                if(userData.user_type === 'trainer') {
                    $('.trainer-fields').show();
                    if(userData.languages) {
                        const languages = userData.languages.split(',');
                        $('#edit_languages').val(languages).trigger('change');
                    }
                } else {
                    $('.trainer-fields').hide();
                }
            });

            // Initialize Charts
            const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
            const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');

            // Calculate statistics from table data
            let trainers = 0;
            let learners = 0;
            let activeUsers = 0;
            let inactiveUsers = 0;

            $('#usersTable tbody tr').each(function() {
                const role = $(this).find('td:eq(3)').text().trim();
                const status = $(this).find('td:eq(7)').text().trim();
                
                if(role === 'Trainer') trainers++;
                if(role === 'Learner') learners++;
                if(status === 'Active') activeUsers++;
                if(status === 'Inactive') inactiveUsers++;
            });

            // User Type Distribution Chart
            new Chart(userTypeCtx, {
                type: 'pie',
                data: {
                    labels: ['Trainers', 'Learners'],
                    datasets: [{
                        data: [trainers, learners],
                        backgroundColor: ['#0d6efd', '#0dcaf0']
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
                            text: 'User Type Distribution'
                        }
                    }
                }
            });

            // User Activity Status Chart
            new Chart(userActivityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active Users', 'Inactive Users'],
                    datasets: [{
                        data: [activeUsers, inactiveUsers],
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
                            text: 'User Activity Status'
                        }
                    }
                }
            });
        });

        // Function to handle user data export
        function exportUserData() {
            const buttons = $.fn.dataTable.Buttons.getInstance('usersTable');
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