<?php
// blog_list.php - Professional Blog Posts Listing
include 'config.php';

$page_title = 'Blog Posts - Student Portal';

// Configuration
$posts_per_page = 9;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $posts_per_page;

// Filter and Sort Parameters
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$author_filter = isset($_GET['author']) ? (int)$_GET['author'] : 0;

// Build WHERE clause
$where_conditions = ["bp.status = 'published'"];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($author_filter > 0) {
    $where_conditions[] = "bp.user_id = ?";
    $params[] = $author_filter;
    $param_types .= 'i';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'latest' => 'bp.created_at DESC',
    'oldest' => 'bp.created_at ASC',
    'title_asc' => 'bp.title ASC',
    'title_desc' => 'bp.title DESC',
    'author' => 'u.name ASC, bp.created_at DESC'
];

$order_clause = isset($sort_options[$sort_by]) ? $sort_options[$sort_by] : $sort_options['latest'];

// Get total posts count for pagination
$count_query = "SELECT COUNT(*) as total FROM blog_posts bp JOIN users u ON bp.user_id = u.id $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_posts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get blog posts with user info
$query = "SELECT bp.id, bp.title, bp.content, bp.image_path, bp.created_at, bp.updated_at,
                 u.name as author_name, u.id as author_id
          FROM blog_posts bp 
          JOIN users u ON bp.user_id = u.id 
          $where_clause
          ORDER BY $order_clause
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
$all_params = array_merge($params, [$posts_per_page, $offset]);
$all_param_types = $param_types . 'ii';

mysqli_stmt_bind_param($stmt, $all_param_types, ...$all_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get authors for filter dropdown
$authors_query = "SELECT DISTINCT u.id, u.name 
                  FROM users u 
                  JOIN blog_posts bp ON u.id = bp.user_id 
                  WHERE bp.status = 'published' 
                  ORDER BY u.name";
$authors_result = mysqli_query($conn, $authors_query);

// Helper functions
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function generateExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function buildUrl($params = []) {
    $current_params = $_GET;
    $merged_params = array_merge($current_params, $params);
    $merged_params = array_filter($merged_params, function($value) {
        return $value !== '' && $value !== null;
    });
    return '?' . http_build_query($merged_params);
}

include 'includes/header.php';
?>

<div class="blog-container">
    <!-- Header Section -->
    <div class="blog-header">
        <div class="header-content">
            <h1 class="page-title">📝 Blog Posts</h1>
            <div class="header-stats">
                <span class="post-count"><?php echo $total_posts; ?> post<?php echo $total_posts != 1 ? 's' : ''; ?></span>
            </div>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="blog_create.php" class="btn btn-primary">
                <span class="btn-icon">✍️</span>
                Write New Post
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form" id="filtersForm">
            <div class="search-group">
                <input type="text" 
                       name="search" 
                       placeholder="Search posts..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="search-input">
                <button type="submit" class="search-btn">🔍</button>
            </div>

            <div class="filter-group">
                <select name="sort" class="filter-select" onchange="document.getElementById('filtersForm').submit();">
                    <option value="latest" <?php echo $sort_by == 'latest' ? 'selected' : ''; ?>>Latest First</option>
                    <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title_asc" <?php echo $sort_by == 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="title_desc" <?php echo $sort_by == 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                    <option value="author" <?php echo $sort_by == 'author' ? 'selected' : ''; ?>>By Author</option>
                </select>

                <select name="author" class="filter-select" onchange="document.getElementById('filtersForm').submit();">
                    <option value="">All Authors</option>
                    <?php while ($author = mysqli_fetch_assoc($authors_result)): ?>
                        <option value="<?php echo $author['id']; ?>" <?php echo $author_filter == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if (!empty($search) || $author_filter > 0): ?>
                <a href="blog_list.php" class="clear-filters">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Blog Posts Grid -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="blog-grid">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <article class="blog-card">
                    <?php if ($post['image_path']): ?>
                        <div class="blog-image">
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <h2 class="blog-title">
                            <a href="blog_view.php?id=<?php echo $post['id']; ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h2>
                        
                        <div class="blog-meta">
                            <span class="meta-author">
                                <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                            </span>
                            <span class="meta-date">
                                <?php echo formatDate($post['created_at']); ?>
                            </span>
                            <?php if ($post['updated_at'] != $post['created_at']): ?>
                                <span class="meta-updated" title="Last updated: <?php echo formatDate($post['updated_at']); ?>">
                                    Updated
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="blog-excerpt">
                            <?php echo htmlspecialchars(generateExcerpt($post['content'])); ?>
                        </p>
                        
                        <a href="blog_view.php?id=<?php echo $post['id']; ?>" class="read-more">
                            Read More →
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Blog pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo buildUrl(['page' => $page - 1]); ?>" class="pagination-btn">
                        ← Previous
                    </a>
                <?php endif; ?>

                <div class="pagination-numbers">
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="<?php echo buildUrl(['page' => 1]); ?>" class="pagination-number">1</a>
                        <?php if ($start > 2): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?php echo buildUrl(['page' => $i]); ?>" 
                           class="pagination-number <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                        <a href="<?php echo buildUrl(['page' => $total_pages]); ?>" class="pagination-number">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo buildUrl(['page' => $page + 1]); ?>" class="pagination-btn">
                        Next →
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h2 class="empty-title">
                <?php if (!empty($search) || $author_filter > 0): ?>
                    No posts found
                <?php else: ?>
                    No blog posts yet
                <?php endif; ?>
            </h2>
            <p class="empty-description">
                <?php if (!empty($search) || $author_filter > 0): ?>
                    Try adjusting your search or filters to find what you're looking for.
                <?php else: ?>
                    Be the first to share your thoughts and experiences!
                <?php endif; ?>
            </p>
            
            <div class="empty-actions">
                <?php if (!empty($search) || $author_filter > 0): ?>
                    <a href="blog_list.php" class="btn btn-secondary">View All Posts</a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="blog_create.php" class="btn btn-primary">Write Your First Post</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login to Write</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --primary-color: #667eea;
    --primary-hover: #5a67d8;
    --secondary-color: #764ba2;
    --text-primary: #2d3748;
    --text-secondary: #4a5568;
    --text-muted: #718096;
    --border-color: #e2e8f0;
    --bg-card: rgba(255, 255, 255, 0.95);
    --bg-overlay: rgba(255, 255, 255, 0.9);
    --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.1);
    --radius: 12px;
    --radius-lg: 16px;
}

.blog-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Header Section */
.blog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    margin: 0;
}

.header-stats {
    background: var(--bg-overlay);
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    backdrop-filter: blur(10px);
}

.post-count {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-icon {
    font-size: 1.1rem;
}

/* Filters Section */
.filters-section {
    background: var(--bg-card);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-group {
    display: flex;
    flex: 1;
    min-width: 200px;
}

.search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius) 0 0 var(--radius);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.3s ease;
}

.search-input:focus {
    border-color: var(--primary-color);
}

.search-btn {
    padding: 0.75rem 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0 var(--radius) var(--radius) 0;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.search-btn:hover {
    background: var(--primary-hover);
}

.filter-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    background: white;
    color: var(--text-primary);
    font-size: 0.95rem;
    outline: none;
    cursor: pointer;
    min-width: 140px;
}

.filter-select:focus {
    border-color: var(--primary-color);
}

.clear-filters {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem;
    border-radius: var(--radius);
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: rgba(102, 126, 234, 0.1);
}

/* Blog Grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.blog-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.blog-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(102, 126, 234, 0.2);
}

.blog-image {
    height: 200px;
    overflow: hidden;
    position: relative;
}

.blog-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.blog-card:hover .blog-image img {
    transform: scale(1.05);
}

.blog-content {
    padding: 1.5rem;
}

.blog-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.3;
}

.blog-title a {
    color: var(--text-primary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.blog-title a:hover {
    color: var(--primary-color);
}

.blog-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    font-size: 0.85rem;
}

.meta-author {
    color: var(--text-secondary);
}

.meta-date {
    color: var(--text-muted);
}

.meta-updated {
    background: #e6fffa;
    color: #065f46;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.blog-excerpt {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1.25rem;
    font-size: 0.95rem;
}

.read-more {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.read-more:hover {
    color: var(--secondary-color);
    transform: translateX(4px);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.pagination-btn,
.pagination-number {
    padding: 0.75rem 1rem;
    background: var(--bg-card);
    color: var(--text-primary);
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    min-width: 44px;
    text-align: center;
}

.pagination-btn:hover,
.pagination-number:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.pagination-number.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.pagination-dots {
    color: var(--text-muted);
    padding: 0.75rem 0.5rem;
}

.pagination-numbers {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

/* Empty State */
.empty-state {
    text-align: center;
    background: var(--bg-card);
    padding: 4rem 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    max-width: 500px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-title {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1.75rem;
    font-weight: 600;
}

.empty-description {
    color: var(--text-muted);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
    .blog-container {
        padding: 1rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .blog-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-form {
        flex-direction: column;
        gap: 1rem;
    }
    
    .search-group {
        min-width: unset;
    }
    
    .filter-group {
        justify-content: stretch;
    }
    
    .filter-select {
        flex: 1;
        min-width: unset;
    }
    
    .blog-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        gap: 0.25rem;
    }
    
    .pagination-btn,
    .pagination-number {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .empty-state {
        padding: 3rem 1.5rem;
    }
    
    .empty-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>