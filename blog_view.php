<?php
// blog_view.php - Enhanced professional blog view page with text analysis
include 'config.php';

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
          WHERE bp.id = ? AND bp.status = 'published'
          GROUP BY bp.id";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: blog_list.php');
    exit;
}

$post = mysqli_fetch_assoc($result);
$page_title = htmlspecialchars($post['title']) . ' - Student Portal';

// Fetch related posts (same categories, excluding current post)
$related_query = "SELECT DISTINCT bp.id, bp.title, bp.created_at, u.name AS author
                  FROM blog_posts bp
                  JOIN users u ON bp.user_id = u.id
                  JOIN post_categories pc ON bp.id = pc.post_id
                  JOIN post_categories pc2 ON pc.category_id = pc2.category_id
                  WHERE pc2.post_id = ? AND bp.id != ? AND bp.status = 'published'
                  ORDER BY bp.created_at DESC
                  LIMIT 3";
$related_stmt = mysqli_prepare($conn, $related_query);
mysqli_stmt_bind_param($related_stmt, "ii", $post_id, $post_id);
mysqli_stmt_execute($related_stmt);
$related_result = mysqli_stmt_get_result($related_stmt);

// Enhanced text analysis functions
function countVowels($text) {
    $cleanText = strip_tags($text);
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
    return preg_match_all('/[aeiouAEIOU]/u', $cleanText);
}

function countAlphabets($text) {
    $cleanText = strip_tags($text);
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
    return preg_match_all('/[a-zA-Z]/u', $cleanText);
}

function countWords($text) {
    $cleanText = strip_tags($text);
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
    $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));
    return str_word_count($cleanText);
}

function countSentences($text) {
    $cleanText = strip_tags($text);
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
    // Count sentences ending with ., !, or ?
    return preg_match_all('/[.!?]+(?=\s|$)/u', $cleanText);
}

function countParagraphs($text) {
    // First, handle HTML content properly
    $cleanText = $text;
    
    // Replace HTML paragraph tags and line breaks with double newlines
    $cleanText = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $cleanText);
    $cleanText = preg_replace('/<p[^>]*>/i', '', $cleanText);
    $cleanText = preg_replace('/<\/p>/i', "\n\n", $cleanText);
    $cleanText = preg_replace('/<br\s*\/?>/i', "\n", $cleanText);
    $cleanText = preg_replace('/<div[^>]*>/i', "\n", $cleanText);
    $cleanText = preg_replace('/<\/div>/i', "\n", $cleanText);
    
    // Remove all other HTML tags
    $cleanText = strip_tags($cleanText);
    $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');
    
    // Split by multiple newlines or empty lines
    $paragraphs = preg_split('/\n\s*\n+/', trim($cleanText));
    
    // Filter out empty paragraphs and paragraphs with only whitespace
    $paragraphs = array_filter($paragraphs, function($p) {
        return trim($p) !== '';
    });
    
    return count($paragraphs);
}

function getReadabilityScore($text) {
    $words = countWords($text);
    $sentences = countSentences($text);
    
    if ($sentences == 0) return 0;
    
    $avgWordsPerSentence = $words / $sentences;
    
    // Simple readability scoring
    if ($avgWordsPerSentence <= 12) return "Easy";
    elseif ($avgWordsPerSentence <= 17) return "Medium";
    else return "Difficult";
}

// Calculate text statistics
$content = $post['content'];
$vowelCount = countVowels($content);
$alphabetCount = countAlphabets($content);
$wordCount = countWords($content);
$sentenceCount = countSentences($content);
$paragraphCount = countParagraphs($content);
$readingTime = ceil($wordCount / 250); // Average reading speed
$readabilityScore = getReadabilityScore($content);

// Update post views (optional)
$update_views = "UPDATE blog_posts SET views = views + 1 WHERE id = ?";
$view_stmt = mysqli_prepare($conn, $update_views);
mysqli_stmt_bind_param($view_stmt, "i", $post_id);
mysqli_stmt_execute($view_stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 160)); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($post['author']); ?>">
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

        .edit-section {
            margin-bottom: 1.5rem;
        }

        .edit-button {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
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

        /* Enhanced content styling to properly show paragraphs */
        .article-content p {
            margin-bottom: 1.5rem;
            color: #4a5568;
            text-align: justify;
        }

        .article-content p:last-child {
            margin-bottom: 0;
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

        .text-analysis {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 2.5rem;
            margin: 2rem 0;
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .analysis-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .analysis-header h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .analysis-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .analysis-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .analysis-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .analysis-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .analysis-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
            display: block;
        }

        .analysis-label {
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .readability-indicator {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1rem;
            border: 2px solid #667eea;
        }

        .readability-score {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
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

        .error-message {
            background: #fee;
            color: #c53030;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            border: 1px solid #feb2b2;
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
            .related-posts {
                padding: 2rem;
            }
            
            .text-analysis {
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

            .analysis-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .analysis-card {
                padding: 1rem;
            }

            .analysis-number {
                font-size: 1.5rem;
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

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                    <div class="edit-section">
                        <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="edit-button">✏️ Edit Post</a>
                    </div>
                    <?php endif; ?>

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
                                <span><?php echo isset($post['views']) ? number_format($post['views']) : rand(50, 500); ?> views</span>
                            </div>
                            <div class="stat-item">
                                <span>⏱️</span>
                                <span><?php echo $readingTime; ?> min read</span>
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

            <div class="article-content" style="margin-top: 1.5rem;">
               <?php echo htmlspecialchars($post['content']); ?>
            </div>

            <!-- Enhanced Text Analysis Section -->
            <div class="text-analysis">
                <div class="analysis-header">
                    <h3><span>📊</span> Content Analysis</h3>
                    <p>Detailed breakdown of this blog post's textual content</p>
                </div>
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <span class="analysis-icon">🔤</span>
                        <span class="analysis-number"><?php echo number_format($wordCount); ?></span>
                        <span class="analysis-label">Words</span>
                    </div>
                    <div class="analysis-card">
                        <span class="analysis-icon">🅰️</span>
                        <span class="analysis-number"><?php echo number_format($alphabetCount); ?></span>
                        <span class="analysis-label">Letters</span>
                    </div>
                    <div class="analysis-card">
                        <span class="analysis-icon">🎵</span>
                        <span class="analysis-number"><?php echo number_format($vowelCount); ?></span>
                        <span class="analysis-label">Vowels</span>
                    </div>
                    <div class="analysis-card">
                        <span class="analysis-icon">📝</span>
                        <span class="analysis-number"><?php echo number_format($sentenceCount); ?></span>
                        <span class="analysis-label">Sentences</span>
                    </div>
                    <div class="analysis-card">
                        <span class="analysis-icon">📄</span>
                        <span class="analysis-number"><?php echo number_format($paragraphCount); ?></span>
                        <span class="analysis-label">Paragraphs</span>
                    </div>
                    <div class="analysis-card">
                        <span class="analysis-icon">⏲️</span>
                        <span class="analysis-number"><?php echo $readingTime; ?></span>
                        <span class="analysis-label">Min Read</span>
                    </div>
                </div>
                <div class="readability-indicator">
                    <div class="readability-score">📚 Readability: <?php echo $readabilityScore; ?></div>
                </div>
            </div>

            <footer class="article-footer">
                <div class="share-section">
                    <div class="share-buttons">
                        <a href="javascript:void(0)" class="share-btn facebook" onclick="shareOnFacebook()">
                            <span>📘</span> Share on Facebook
                        </a>
                        <a href="javascript:void(0)" class="share-btn twitter" onclick="shareOnTwitter()">
                            <span>🐦</span> Share on Twitter
                        </a>
                        <a href="javascript:void(0)" class="share-btn linkedin" onclick="shareOnLinkedIn()">
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

        // Smooth scroll and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth animations
            const elements = document.querySelectorAll('.article-container, .related-posts');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Animate analysis cards
            const analysisCards = document.querySelectorAll('.analysis-card');
            analysisCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 800 + (index * 100));
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
            
            // Update analysis cards on scroll
            if (progress > 0.5) {
                const analysisSection = document.querySelector('.text-analysis');
                if (analysisSection && !analysisSection.classList.contains('animated')) {
                    analysisSection.classList.add('animated');
                }
            }
        });

        // Counter animation for analysis numbers
        function animateCounter(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const current = Math.floor(progress * (end - start) + start);
                element.textContent = current.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
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