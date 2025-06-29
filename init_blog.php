<?php
// blog_init.php - Initialize blog tables
include 'config.php';

echo "<h2>🚀 Blog System Initialization</h2>";
echo "<p>Setting up blog tables...</p><br>";

// Create blog posts table
$create_posts_table = "
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_posts_table)) {
    echo "<p>✅ Table 'blog_posts' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_posts table: " . mysqli_error($conn) . "</p>";
}

// Create uploads directory
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>✅ Uploads directory created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create uploads directory!</p>";
    }
} else {
    echo "<p>ℹ️ Uploads directory already exists.</p>";
}

echo "<br><div style='background-color: #e8f4fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<h3>🎉 Blog System Ready!</h3>";
echo "<p>You can now create, edit, and manage blog posts with images!</p>";
echo "</div>";

mysqli_close($conn);
?>
