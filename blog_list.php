<?php
// blog_list.php - Enhanced professional blog listing page with auto-filtering
include 'config.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

$page_title = 'All Blog Posts - Student Portal';

// Search and filter inputs
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$author_filter = isset($_GET['author']) ? (int)$_GET['author'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Fetch categories and authors
$categories = mysqli_query($conn, "SELECT id, name FROM blog_categories ORDER BY name");
if (!$categories) {
    die('Query Error: ' . mysqli_error($conn));
}
$authors = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name");
if (!$authors) {
    die('Query Error: ' . mysqli_error($conn));
}

// Build query
$query = "SELECT bp.*, u.name AS author,
          GROUP_CONCAT(bc.name) as categories
          FROM blog_posts bp
          JOIN users u ON bp.user_id = u.id
          LEFT JOIN post_categories pc ON bp.id = pc.post_id
          LEFT JOIN blog_categories bc ON pc.category_id = bc.id
          WHERE 1";

if (!empty($search)) {
    $query .= " AND (bp.title LIKE '%$search%' OR bp.content LIKE '%$search%')";
}
if ($category_filter > 0) {
    $query .= " AND pc.category_id = $category_filter";
}
if ($author_filter > 0) {
    $query .= " AND bp.user_id = $author_filter";
}

$query .= " GROUP BY bp.id";

switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY bp.created_at ASC";
        break;
    case 'title':
        $query .= " ORDER BY bp.title ASC";
        break;
    default:
        $query .= " ORDER BY bp.created_at DESC";
        break;
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die('Query Error: ' . mysqli_error($conn));
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .filters-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 3rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1.5rem;
            align-items: center;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.2rem;
        }

        .filter-select {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
        }

        .results-count {
            color: #666;
            font-weight: 500;
        }

        .loading-spinner {
            display: none;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .post-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .post-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .post-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .post-title a {
            color: #2d3748;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .post-title a:hover {
            color: #667eea;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .author-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .post-categories {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .category-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .post-content {
            padding: 0 1.5rem;
        }

        .post-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .post-footer {
            padding: 1.5rem;
            border-top: 1px solid #f0f0f0;
            text-align: right;
        }

        .read-more {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .read-more:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .no-results p {
            color: #666;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>📚 Blog Posts</h1>
            <p>Discover insights, stories, and knowledge from our community</p>
                    <!-- if logged in, show option to create blog -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <p style="margin-top: 3rem;">
                <a href="blog_create.php" class="btn btn-secondary">
                    📝 Create New Post
                </a>
            </p>
        <?php endif; ?>
        </div>

        <div class="filters-container">
            <div class="filters-grid">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" 
                           id="search" 
                           class="search-input" 
                           placeholder="Search posts by title or content..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <select id="category" class="filter-select">
                    <option value="0">📂 All Categories</option>
                    <?php 
                    mysqli_data_seek($categories, 0);
                    while ($cat = mysqli_fetch_assoc($categories)): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select id="author" class="filter-select">
                    <option value="0">👤 All Authors</option>
                    <?php 
                    mysqli_data_seek($authors, 0);
                    while ($author = mysqli_fetch_assoc($authors)): 
                    ?>
                        <option value="<?php echo $author['id']; ?>" <?php echo ($author_filter == $author['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select id="sort" class="filter-select">
                    <option value="latest" <?php echo ($sort_by == 'latest') ? 'selected' : ''; ?>>🆕 Newest First</option>
                    <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>📜 Oldest First</option>
                    <option value="title" <?php echo ($sort_by == 'title') ? 'selected' : ''; ?>>🔤 Alphabetical</option>
                </select>
            </div>
        </div>

        <div class="results-info">
            <div class="results-count">
                <?php 
                $total_posts = mysqli_num_rows($result);
                echo "Showing $total_posts " . ($total_posts === 1 ? 'post' : 'posts');
                ?>
            </div>
        </div>

        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
        </div>

        <div id="postsContainer">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="posts-grid">
                    <?php while ($post = mysqli_fetch_assoc($result)): ?>
                        <article class="post-card">
                            <div class="post-header">
                                <h2 class="post-title">
                                    <a href="blog_view.php?id=<?php echo $post['id']; ?>">
                                        <!-- image -->
                                        <img src="<?php echo $post['image_path']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: auto; border-radius: 15px; margin-bottom: 1rem;">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h2>
                                
                                <div class="post-meta">
                                    <div class="author-info">
                                        <div class="author-avatar">
                                            <?php echo strtoupper(substr($post['author'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($post['author']); ?></span>
                                    </div>
                                    <span>•</span>
                                    <span>📅 <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                </div>

                                <?php if ($post['categories']): ?>
                                    <div class="post-categories">
                                        <?php 
                                        $cats = explode(',', $post['categories']);
                                        foreach ($cats as $cat): 
                                            if (trim($cat)):
                                        ?>
                                            <span class="category-tag"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="post-content">
                                <p class="post-excerpt">
                                    <?php 
                                    $excerpt = strip_tags($post['content']);
                                    echo htmlspecialchars(substr($excerpt, 0, 180)) . (strlen($excerpt) > 180 ? '...' : '');
                                    ?>
                                </p>
                            </div>

                            <div class="post-footer">
                                <a href="blog_view.php?id=<?php echo $post['id']; ?>" class="read-more">
                                    Read More <span>→</span>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">📭</div>
                    <h3>No posts found</h3>
                    <p>Try adjusting your search criteria or browse all posts</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-filter functionality
        let searchTimeout;
        
        function updateFilters() {
            const search = document.getElementById('search').value;
            const category = document.getElementById('category').value;
            const author = document.getElementById('author').value;
            const sort = document.getElementById('sort').value;
            
            // Show loading spinner
            document.getElementById('loadingSpinner').style.display = 'flex';
            document.getElementById('postsContainer').style.opacity = '0.5';
            
            // Build URL with current filters
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (category && category !== '0') params.set('category', category);
            if (author && author !== '0') params.set('author', author);
            if (sort) params.set('sort', sort);
            
            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            
            // Update URL without reloading
            window.history.pushState({}, '', url);
            
            // Simulate loading and reload page
            setTimeout(() => {
                window.location.href = url;
            }, 300);
        }
        
        // Search input with debounce
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateFilters, 500);
        });
        
        // Dropdown filters
        document.getElementById('category').addEventListener('change', updateFilters);
        document.getElementById('author').addEventListener('change', updateFilters);
        document.getElementById('sort').addEventListener('change', updateFilters);
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.post-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>