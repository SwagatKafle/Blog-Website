<?php
// admin_users.php - User Management Page (Admin Only)
session_start();
include 'config.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to database
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle user actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            
            // Check if email already exists
            $check_email = "SELECT id FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($result) > 0) {
                $message = "Email already exists!";
                $message_type = "error";
            } else {
                $insert_user = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
                if (mysqli_query($conn, $insert_user)) {
                    $message = "User added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding user: " . mysqli_error($conn);
                    $message_type = "error";
                }
            }
            break;
            
        case 'update_user':
            $user_id = (int)$_POST['user_id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $role = $_POST['role'];
            
            // Check if email exists for other users
            $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
            $result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($result) > 0) {
                $message = "Email already exists for another user!";
                $message_type = "error";
            } else {
                $update_query = "UPDATE users SET name = '$name', email = '$email', role = '$role' WHERE id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $message = "User updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating user: " . mysqli_error($conn);
                    $message_type = "error";
                }
            }
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            
            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                $message = "You cannot delete your own account!";
                $message_type = "error";
            } else {
                $delete_query = "DELETE FROM users WHERE id = $user_id";
                if (mysqli_query($conn, $delete_query)) {
                    $message = "User deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting user: " . mysqli_error($conn);
                    $message_type = "error";
                }
            }
            break;
            
        case 'reset_password':
            $user_id = (int)$_POST['user_id'];
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            $update_password = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
            if (mysqli_query($conn, $update_password)) {
                $message = "Password reset successfully!";
                $message_type = "success";
            } else {
                $message = "Error resetting password: " . mysqli_error($conn);
                $message_type = "error";
            }
            break;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build query with filters
$where_conditions = [];
if (!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(name LIKE '%$search_term%' OR email LIKE '%$search_term%')";
}
if (!empty($role_filter)) {
    $where_conditions[] = "role = '$role_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users for current page
$users_query = "SELECT * FROM users $where_clause ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
$users_result = mysqli_query($conn, $users_query);

// Get user statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_registrations,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_registrations
    FROM users
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
        }

        .stat-card.total { border-left-color: #007bff; }
        .stat-card.admin { border-left-color: #dc3545; }
        .stat-card.user { border-left-color: #28a745; }
        .stat-card.today { border-left-color: #ffc107; }
        .stat-card.week { border-left-color: #17a2b8; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .controls-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        th:hover {
            background: #e9ecef;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: #dc3545;
            color: white;
        }

        .role-user {
            background: #28a745;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .page-btn {
            padding: 8px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
        }

        .page-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        @media (max-width: 768px) {
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div>Total Users</div>
            </div>
            <div class="stat-card admin">
                <div class="stat-number"><?php echo $stats['admin_count']; ?></div>
                <div>Administrators</div>
            </div>
            <div class="stat-card user">
                <div class="stat-number"><?php echo $stats['user_count']; ?></div>
                <div>Regular Users</div>
            </div>
            <div class="stat-card today">
                <div class="stat-number"><?php echo $stats['today_registrations']; ?></div>
                <div>Today's Registrations</div>
            </div>
            <div class="stat-card week">
                <div class="stat-number"><?php echo $stats['week_registrations']; ?></div>
                <div>This Week</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" class="controls-row">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search users by name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="role_filter" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                </select>
                <select name="sort_by" class="filter-select">
                    <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                    <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                    <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                    <option value="role" <?php echo $sort_by === 'role' ? 'selected' : ''; ?>>Role</option>
                </select>
                <select name="sort_order" class="filter-select">
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-success" onclick="openModal('addUserModal')">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Users List (<?php echo $total_users; ?> total)</h3>
                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($users_result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo $user['role']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users" style="font-size: 48px; color: #dee2e6; margin-bottom: 10px;"></i>
                                <br>No users found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label for="add_name">Full Name</label>
                    <input type="text" id="add_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="add_email">Email Address</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="add_password">Password</label>
                    <input type="password" id="add_password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="add_role">Role</label>
                    <select id="add_role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_name">Full Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email Address</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Reset Password</h3>
                <span class="close" onclick="closeModal('resetPasswordModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" id="reset_user_id" name="user_id">
                <p>Reset password for: <strong id="reset_user_name"></strong></p>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete User</h3>
                <span class="close" onclick="closeModal('deleteUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" id="delete_user_id" name="user_id">
                <p><strong>Warning:</strong> This action cannot be undone!</p>
                <p>Are you sure you want to delete user: <strong id="delete_user_name"></strong>?</p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editUser(id, name, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            openModal('editUserModal');
        }

        function resetPassword(id, name) {
            document.getElementById('reset_user_id').value = id;
            document.getElementById('reset_user_name').textContent = name;
            openModal('resetPasswordModal');
        }

        function deleteUser(id, name) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_user_name').textContent = name;
            openModal('deleteUserModal');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>