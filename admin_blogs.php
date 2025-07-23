<?php

include 'config.php';

// error
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Handle blog actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_blog':
            $user_id = (int)$_SESSION['user_id'];
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $content = mysqli_real_escape_string($conn, $_POST['content']);
            $status = $_POST['status'];
            $image_path = null;
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/blog/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    $filename = uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image_path = $filepath;
                    }
                }
            }
            
            $insert_blog = "INSERT INTO blog_posts (user_id, title, content, image_path, status) VALUES ('$user_id', '$title', '$content', '$image_path', '$status')";
            if (mysqli_query($conn, $insert_blog)) {
                $message = "Blog post created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating blog post: " . mysqli_error($conn);
                $message_type = "error";
            }
            break;
            
        case 'update_blog':
            $blog_id = (int)$_POST['blog_id'];
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $content = mysqli_real_escape_string($conn, $_POST['content']);
            $status = $_POST['status'];
            
            // Handle image upload for update
            $image_update = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/blog/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    $filename = uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image_update = ", image_path = '$filepath'";
                    }
                }
            }
            
            $update_query = "UPDATE blogs SET title = '$title', content = '$content', status = '$status'$image_update WHERE id = $blog_id";
            if (mysqli_query($conn, $update_query)) {
                $message = "Blog post updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating blog post: " . mysqli_error($conn);
                $message_type = "error";
            }
            break;
            
        case 'delete_blog':
            $blog_id = (int)$_POST['blog_id'];
            
            // Get image path before deletion to remove file
            $get_image = "SELECT image_path FROM blog_posts WHERE id = $blog_id";
            $image_result = mysqli_query($conn, $get_image);
            $image_data = mysqli_fetch_assoc($image_result);
            
            $delete_query = "DELETE FROM blog_posts WHERE id = $blog_id";
            if (mysqli_query($conn, $delete_query)) {
                // Delete image file if exists
                if ($image_data['image_path'] && file_exists($image_data['image_path'])) {
                    unlink($image_data['image_path']);
                }
                $message = "Blog post deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting blog post: " . mysqli_error($conn);
                $message_type = "error";
            }
            break;
            
        case 'update_status':
            $blog_id = (int)$_POST['blog_id'];
            $new_status = $_POST['new_status'];
            
            $update_status = "UPDATE blog_posts SET status = '$new_status' WHERE id = $blog_id";
            if (mysqli_query($conn, $update_status)) {
                $message = "Blog status updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating blog status: " . mysqli_error($conn);
                $message_type = "error";
            }
            break;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$author_filter = $_GET['author_filter'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build query with filters
$where_conditions = [];
if (!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(b.title LIKE '%$search_term%' OR b.content LIKE '%$search_term%')";
}
if (!empty($status_filter)) {
    $where_conditions[] = "b.status = '$status_filter'";
}
if (!empty($author_filter)) {
    $where_conditions[] = "b.user_id = '$author_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get blogs with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total blogs for pagination
$count_query = "SELECT COUNT(*) as total FROM blog_posts b $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_blogs = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_blogs / $per_page);

// Get blogs for current page with user names
$blogs_query = "
    SELECT b.*, u.name as author_name 
    FROM blog_posts b 
    LEFT JOIN users u ON b.user_id = u.id 
    $where_clause 
    ORDER BY b.$sort_by $sort_order 
    LIMIT $per_page OFFSET $offset
";
$blogs_result = mysqli_query($conn, $blogs_query);

// Get blog statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_blogs,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_posts,
        SUM(views) as total_views
    FROM blog_posts
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get authors for filter dropdown
$authors_query = "SELECT DISTINCT u.id, u.name FROM users u INNER JOIN blog_posts b ON u.id = b.user_id ORDER BY u.name";
$authors_result = mysqli_query($conn, $authors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Admin Panel</title>
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
        .stat-card.published { border-left-color: #28a745; }
        .stat-card.draft { border-left-color: #ffc107; }
        .stat-card.today { border-left-color: #17a2b8; }
        .stat-card.views { border-left-color: #6f42c1; }

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
        .btn-info { background: #17a2b8; color: white; }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .blogs-table {
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-published {
            background: #28a745;
            color: white;
        }

        .status-draft {
            background: #ffc107;
            color: #212529;
        }

        .blog-title {
            font-weight: 600;
            color: #495057;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .blog-excerpt {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6c757d;
            font-size: 14px;
        }

        .blog-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .no-image {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
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
            
            .table-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-blog"></i> Blog Management</h1>
            <div class="user-info">
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
                <div class="stat-number"><?php echo $stats['total_blogs']; ?></div>
                <div>Total Posts</div>
            </div>
            <div class="stat-card published">
                <div class="stat-number"><?php echo $stats['published_count']; ?></div>
                <div>Published</div>
            </div>
            <div class="stat-card draft">
                <div class="stat-number"><?php echo $stats['draft_count']; ?></div>
                <div>Drafts</div>
            </div>
            <div class="stat-card today">
                <div class="stat-number"><?php echo $stats['today_posts']; ?></div>
                <div>Today's Posts</div>
            </div>
            <div class="stat-card views">
                <div class="stat-number"><?php echo number_format($stats['total_views']); ?></div>
                <div>Total Views</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" class="controls-row">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search posts by title or content..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="status_filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
                <select name="author_filter" class="filter-select">
                    <option value="">All Authors</option>
                    <?php while ($author = mysqli_fetch_assoc($authors_result)): ?>
                        <option value="<?php echo $author['id']; ?>" <?php echo $author_filter == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="sort_by" class="filter-select">
                    <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                    <option value="updated_at" <?php echo $sort_by === 'updated_at' ? 'selected' : ''; ?>>Last Updated</option>
                    <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                    <option value="views" <?php echo $sort_by === 'views' ? 'selected' : ''; ?>>Views</option>
                </select>
                <select name="sort_order" class="filter-select">
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-success" onclick="openModal('addBlogModal')">
                    <i class="fas fa-plus"></i> Add Post
                </button>
            </form>
        </div>

        <!-- Blogs Table -->
        <div class="blogs-table">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Blog Posts (<?php echo $total_blogs; ?> total)</h3>
                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($blogs_result) > 0): ?>
                        <?php while ($blog = mysqli_fetch_assoc($blogs_result)): ?>
                            <tr>
                                <td>
                                    <?php if ($blog['image_path'] && file_exists($blog['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($blog['image_path']); ?>" 
                                             alt="Blog image" class="blog-image">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="blog-title" title="<?php echo htmlspecialchars($blog['title']); ?>">
                                        <?php echo htmlspecialchars($blog['title']); ?>
                                    </div>
                                    <div class="blog-excerpt" title="<?php echo htmlspecialchars(strip_tags($blog['content'])); ?>">
                                        <?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 100)); ?>...
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($blog['author_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $blog['status']; ?>">
                                        <?php echo $blog['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($blog['views']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($blog['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" 
                                                onclick="viewBlog(<?php echo $blog['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editBlog(<?php echo $blog['id']; ?>, '<?php echo htmlspecialchars($blog['title']); ?>', '<?php echo htmlspecialchars($blog['content']); ?>', '<?php echo $blog['status']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="toggleStatus(<?php echo $blog['id']; ?>, '<?php echo $blog['status']; ?>')">
                                            <i class="fas fa-toggle-<?php echo $blog['status'] === 'published' ? 'off' : 'on'; ?>"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="deleteBlog(<?php echo $blog['id']; ?>, '<?php echo htmlspecialchars($blog['title']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-blog" style="font-size: 48px; color: #dee2e6; margin-bottom: 10px;"></i>
                                <br>No blog posts found matching your criteria.
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
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo $status_filter; ?>&author_filter=<?php echo $author_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo $status_filter; ?>&author_filter=<?php echo $author_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo $status_filter; ?>&author_filter=<?php echo $author_filter; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="page-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
<script>

// Modal Management Functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    // Clear form data when closing
    const form = document.querySelector(`#${modalId} form`);
    if (form) {
        form.reset();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Blog Management Functions
function viewBlog(blogId) {
    // Open blog in new tab/window for viewing
    window.open(`view_blog.php?id=${blogId}`, '_blank');
}

function editBlog(blogId, title, content, status) {
    // Populate edit modal with blog data
    document.getElementById('edit_blog_id').value = blogId;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_content').value = content;
    document.getElementById('edit_status').value = status;
    
    openModal('editBlogModal');
}

function deleteBlog(blogId, title) {
    if (confirm(`Are you sure you want to delete the blog post "${title}"? This action cannot be undone.`)) {
        // Create and submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_blog">
            <input type="hidden" name="blog_id" value="${blogId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleStatus(blogId, currentStatus) {
    const newStatus = currentStatus === 'published' ? 'draft' : 'published';
    const action = newStatus === 'published' ? 'publish' : 'unpublish';
    
    if (confirm(`Are you sure you want to ${action} this blog post?`)) {
        // Create and submit status update form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="blog_id" value="${blogId}">
            <input type="hidden" name="new_status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Form Validation Functions
function validateBlogForm(formId) {
    const form = document.getElementById(formId);
    const title = form.querySelector('input[name="title"]').value.trim();
    const content = form.querySelector('textarea[name="content"]').value.trim();
    
    if (!title) {
        alert('Please enter a blog title.');
        return false;
    }
    
    if (title.length > 255) {
        alert('Blog title must be less than 255 characters.');
        return false;
    }
    
    if (!content) {
        alert('Please enter blog content.');
        return false;
    }
    
    if (content.length < 50) {
        alert('Blog content must be at least 50 characters long.');
        return false;
    }
    
    return true;
}

// Image Preview Functions
function previewImage(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, JPEG, PNG, or GIF).');
            input.value = '';
            return;
        }
        
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image file size must be less than 5MB.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 5px;">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
}

// Table Sorting Functions
function sortTable(column, order) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort_by', column);
    currentUrl.searchParams.set('sort_order', order);
    window.location.href = currentUrl.toString();
}

// Search and Filter Functions
function clearFilters() {
    const form = document.querySelector('.controls form');
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.type === 'text') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
}

function exportBlogData() {
    if (confirm('Export all blog data to CSV?')) {
        window.location.href = 'export_blogs.php';
    }
}

// Auto-save Draft Function (for long content)
let autoSaveTimer;
function enableAutoSave(formId) {
    const form = document.getElementById(formId);
    const contentTextarea = form.querySelector('textarea[name="content"]');
    
    if (contentTextarea) {
        contentTextarea.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                saveDraft(formId);
            }, 30000); // Auto-save every 30 seconds
        });
    }
}

function saveDraft(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    formData.set('action', 'save_draft');
    
    fetch('auto_save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Draft saved automatically', 'success');
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}

// Character Counter for Title and Content
function setupCharacterCounters() {
    const titleInputs = document.querySelectorAll('input[name="title"]');
    const contentTextareas = document.querySelectorAll('textarea[name="content"]');
    
    titleInputs.forEach(input => {
        addCharacterCounter(input, 255, 'title');
    });
    
    contentTextareas.forEach(textarea => {
        addCharacterCounter(textarea, null, 'content');
    });
}

function addCharacterCounter(element, maxLength, type) {
    const counter = document.createElement('div');
    counter.className = 'character-counter';
    counter.style.cssText = 'font-size: 12px; color: #6c757d; text-align: right; margin-top: 5px;';
    
    element.parentNode.appendChild(counter);
    
    function updateCounter() {
        const length = element.value.length;
        let text = `${length} characters`;
        
        if (maxLength) {
            text += ` / ${maxLength}`;
            if (length > maxLength) {
                counter.style.color = '#dc3545';
            } else if (length > maxLength * 0.8) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#6c757d';
            }
        } else {
            if (type === 'content' && length < 50) {
                counter.style.color = '#dc3545';
                text += ' (minimum 50)';
            } else {
                counter.style.color = '#6c757d';
            }
        }
        
        counter.textContent = text;
    }
    
    element.addEventListener('input', updateCounter);
    updateCounter(); // Initial count
}

// Initialize functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupCharacterCounters();
    
    // Enable auto-save for add and edit forms
    if (document.getElementById('addBlogForm')) {
        enableAutoSave('addBlogForm');
    }
    if (document.getElementById('editBlogForm')) {
        enableAutoSave('editBlogForm');
    }
    
    // Add form validation to submit buttons
    document.querySelectorAll('form[id*="Blog"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateBlogForm(this.id)) {
                e.preventDefault();
            }
        });
    });
});

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N: New blog post
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        openModal('addBlogModal');
    }
    
    // Escape: Close any open modal
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            openModal.style.display = 'none';
        }
    }
});
                </script>    
</body>
</html>

