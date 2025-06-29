<?php
// blog_view.php - Enhanced professional blog view page
include 'config.php';
session_start();

// Get blog post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    header('Location: blog_list.php');
    exit;
}

// Fetch blog post with author and categories
$query = "SELECT bp.*, u.name AS author, u.email AS author_email,
          GROUP_CONCAT(bc.name) as categories
          FROM blog_posts bp
          JOIN users u ON bp.user_id = u.id
          LEFT JOIN post_categories pc ON bp.id = pc.post_id
          LEFT JOIN blog_categories bc ON pc.category_id = bc.id
          WHERE bp.id = $post_id
          GROUP BY bp.id";

$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: blog_list.php');
    exit;
}

$post = mysqli_fetch_assoc($result);
$page_title = htmlspecialchars($post['title']) . ' - Student Portal';

// Fetch comments for this post
$comments_query = "SELECT c.*, u.name AS commenter_name
                   FROM blog_comments c
                   JOIN users u ON c.user_id = u.id
                   WHERE c.post_id = $post_id
                   ORDER BY c.created_at DESC";
$comments_result = mysqli_query($conn, $comments_query);

// Fetch related posts (same categories, excluding current post)
$related_query = "SELECT DISTINCT bp.id, bp.title, bp.created_at, u.name AS author
                  FROM blog_posts bp
                  JOIN users u ON bp.user_id = u.id
                  JOIN post_categories pc ON bp.id = pc.post_id
                  JOIN post_categories pc2 ON pc.category_id = pc2.category_id
                  WHERE pc2.post_id = $post_id AND bp.id != $post_id
                  ORDER BY bp.created_at DESC
                  LIMIT 3";
$related_result = mysqli_query($conn, $related_query);

// include 'includes/header.php';
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
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateX(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .article-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 3rem;
        }

        .article-header {
            padding: 0;
            background: none;
            border-bottom: none;
        }

        .featured-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 20px 20px 0 0;
        }

        .header-content {
            padding: 3rem 3rem 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .article-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .author-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .author-info h4 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .author-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .post-stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .categories-section {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .category-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .article-content {
            padding: 3rem;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .article-content h1,
        .article-content h2,
        .article-content h3,
        .article-content h4,
        .article-content h5,
        .article-content h6 {
            color: #2d3748;
            margin: 2rem 0 1rem;
            font-weight: 600;
        }

        .article-content h2 {
            font-size: 1.8rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .article-content h3 {
            font-size: 1.4rem;
        }

        .article-content p {
            margin-bottom: 1.5rem;
            color: #4a5568;
        }

        .article-content ul,
        .article-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }

        .article-content li {
            margin-bottom: 0.5rem;
        }

        .article-content blockquote {
            background: rgba(102, 126, 234, 0.05);
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            border-radius: 0 10px 10px 0;
        }

        .article-content code {
            background: #f7fafc;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #e53e3e;
        }

        .article-content pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1.5rem;
            border-radius: 10px;
            overflow-x: auto;
            margin: 1.5rem 0;
        }

        .article-content pre code {
            background: none;
            color: inherit;
            padding: 0;
        }

        .article-footer {
            padding: 2rem 3rem;
            background: rgba(102, 126, 234, 0.05);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .share-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .share-buttons {
            display: flex;
            gap: 1rem;
        }

        .share-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .share-btn.facebook { background: #1877f2; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.linkedin { background: #0077b5; }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .like-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .like-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .like-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .comments-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 3rem;
        }

        .comments-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .comments-header h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .comment-form {
            background: rgba(102, 126, 234, 0.05);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .comment-form textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 1rem;
        }

        .comment-form textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .comment-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .comment-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .comment-item {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .commenter-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .comment-meta h5 {
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .comment-meta p {
            color: #666;
            font-size: 0.9rem;
        }

        .related-posts {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .related-posts h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .related-item {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .related-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .related-item h4 {
            margin-bottom: 0.5rem;
        }

        .related-item a {
            color: #2d3748;
            text-decoration: none;
            font-weight: 600;
        }

        .related-item a:hover {
            color: #667eea;
        }

        .related-item p {
            color: #666;
            font-size: 0.9rem;
        }

            @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .featured-image {
                height: 250px;
            }
            
            .header-content,
            .article-content,
            .article-footer,
            .comments-section,
            .related-posts {
                padding: 2rem;
            }
            
            .article-title {
                font-size: 2rem;
            }
            
            .article-meta {
                gap: 1rem;
            }
            
            .share-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="blog_list.php" class="back-button">
            <span>←</span> Back to Blog Posts
        </a>

        <article class="article-container">
            <header class="article-header">
                <?php if (!empty($post['image_path']) && file_exists($post['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>" 
                         class="featured-image">
                <?php endif; ?>
                
                <div class="header-content">
                    <h1 class="article-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <div class="article-meta">
                        <div class="author-section">
                            <div class="author-avatar">
                                <?php echo strtoupper(substr($post['author'], 0, 1)); ?>
                            </div>
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($post['author']); ?></h4>
                                <p>📅 <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="post-stats">
                            <div class="stat-item">
                                <span>👀</span>
                                <span><?php echo rand(50, 500); ?> views</span>
                            </div>
                            <div class="stat-item">
                                <span>💬</span>
                                <span><?php echo mysqli_num_rows($comments_result); ?> comments</span>
                            </div>
                            <div class="stat-item">
                                <span>⏱️</span>
                                <span><?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> min read</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($post['categories']): ?>
                        <div class="categories-section">
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
            </header>

            <div class="article-content">
                <?php echo $post['content']; ?>
            </div>

            <footer class="article-footer">
                <div class="share-section">
                    <div class="share-buttons">
                        <a href="#" class="share-btn facebook" onclick="shareOnFacebook()">
                            <span>📘</span> Share on Facebook
                        </a>
                        <a href="#" class="share-btn twitter" onclick="shareOnTwitter()">
                            <span>🐦</span> Share on Twitter
                        </a>
                        <a href="#" class="share-btn linkedin" onclick="shareOnLinkedIn()">
                            <span>💼</span> Share on LinkedIn
                        </a>
                    </div>
                    
                    <div class="like-section">
                        <button class="like-btn" onclick="toggleLike()">
                            <span id="likeIcon">❤️</span>
                            <span id="likeText">Like this post</span>
                        </button>
                    </div>
                </div>
            </footer>
        </article>

        <section class="comments-section">
            <div class="comments-header">
                <h3>💬 Comments (<?php echo mysqli_num_rows($comments_result); ?>)</h3>
                <p>Share your thoughts and join the discussion</p>
            </div>

            <form class="comment-form" method="POST" action="add_comment.php">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <textarea name="comment" placeholder="Write your comment here..." required></textarea>
                <button type="submit" class="comment-submit">Post Comment</button>
            </form>

            <div class="comments-list">
                <?php if (mysqli_num_rows($comments_result) > 0): ?>
                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="commenter-avatar">
                                    <?php echo strtoupper(substr($comment['commenter_name'], 0, 1)); ?>
                                </div>
                                <div class="comment-meta">
                                    <h5><?php echo htmlspecialchars($comment['commenter_name']); ?></h5>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="comment-content">
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            💭 No comments yet. Be the first to share your thoughts!
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (mysqli_num_rows($related_result) > 0): ?>
            <section class="related-posts">
                <h3>📚 Related Posts</h3>
                <div class="related-grid">
                    <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                        <div class="related-item">
                            <h4>
                                <a href="blog_view.php?id=<?php echo $related['id']; ?>">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            </h4>
                            <p>By <?php echo htmlspecialchars($related['author']); ?> • <?php echo date('M j, Y', strtotime($related['created_at'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script>
        // Share functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
        }

        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
        }

        function shareOnLinkedIn() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
        }

        // Like functionality
        let isLiked = false;
        function toggleLike() {
            const likeIcon = document.getElementById('likeIcon');
            const likeText = document.getElementById('likeText');
            const likeBtn = document.querySelector('.like-btn');
            
            if (isLiked) {
                likeIcon.textContent = '❤️';
                likeText.textContent = 'Like this post';
                likeBtn.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
                isLiked = false;
            } else {
                likeIcon.textContent = '💖';
                likeText.textContent = 'You liked this!';
                likeBtn.style.background = 'linear-gradient(135deg, #e53e3e, #ff6b6b)';
                isLiked = true;
            }
        }

        // Smooth scroll for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth animations
            const elements = document.querySelectorAll('.article-container, .comments-section, .related-posts');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Auto-expand images
            const images = document.querySelectorAll('.article-content img');
            images.forEach(img => {
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                img.style.borderRadius = '10px';
                img.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                img.style.margin = '1rem 0';
            });
        });

        // Reading progress indicator
        window.addEventListener('scroll', function() {
            const article = document.querySelector('.article-content');
            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = window.pageYOffset;
            
            const progress = Math.min(Math.max((scrollTop - articleTop + windowHeight) / articleHeight, 0), 1);
            
            // You can add a progress bar here if desired
        });
    </script>
</body>
</html>

<?php 
// include 'includes/footer.php';
 ?>