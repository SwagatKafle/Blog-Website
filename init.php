<?php
// complete_init_database.php - Complete Database Initialization Script
// Run this file ONCE to create the database and all tables for the Student Portal + Blog System

include 'config.php';

echo "<h2>🚀 Complete Student Portal & Blog System Initialization</h2>";
echo "<p>Setting up your complete database with users, roles, and blog system...</p><br>";

// Step 1: Connect to MySQL server (without selecting a database)
$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("<p style='color: red;'>❌ Connection failed: " . mysqli_connect_error() . "</p>");
}

echo "<p>✅ Connected to MySQL server successfully!</p>";

// Step 2: Create database if it doesn't exist                      
$create_db_query = "CREATE DATABASE IF NOT EXISTS $database";

if (mysqli_query($conn, $create_db_query)) {
    echo "<p>✅ Database '$database' created successfully (or already exists)!</p>";
} else {
    die("<p style='color: red;'>❌ Error creating database: " . mysqli_error($conn) . "</p>");
}

// Step 3: Select the database
mysqli_select_db($conn, $database);
echo "<p>✅ Database '$database' selected!</p>";

// Step 4: Create users table with role
$create_users_table = "
CREATE TABLE IF NOT EXISTS users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
)";

if (mysqli_query($conn, $create_users_table)) {
    echo "<p>✅ Table 'users' created successfully with role system!</p>";
} else {
    die("<p style='color: red;'>❌ Error creating users table: " . mysqli_error($conn) . "</p>");
}

// Step 5: Create blog posts table
$create_posts_table = "
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    views INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
)";

if (mysqli_query($conn, $create_posts_table)) {
    echo "<p>✅ Table 'blog_posts' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_posts table: " . mysqli_error($conn) . "</p>";
}

// Step 6: Create blog categories table
$create_categories_table = "
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_categories_table)) {
    echo "<p>✅ Table 'blog_categories' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_categories table: " . mysqli_error($conn) . "</p>";
}

// Step 7: Create post categories junction table
$create_post_categories_table = "
CREATE TABLE IF NOT EXISTS post_categories (
    post_id INT(11) NOT NULL,
    category_id INT(11) NOT NULL,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_post_categories_table)) {
    echo "<p>✅ Table 'post_categories' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating post_categories table: " . mysqli_error($conn) . "</p>";
}

// Step 8: Create blog comments table
$create_comments_table = "
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    post_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    parent_id INT(11) DEFAULT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_status (status)
)";

if (mysqli_query($conn, $create_comments_table)) {
    echo "<p>✅ Table 'blog_comments' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_comments table: " . mysqli_error($conn) . "</p>";
}

// Step 9: Create blog likes table
$create_likes_table = "
CREATE TABLE IF NOT EXISTS blog_likes (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    post_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
)";

if (mysqli_query($conn, $create_likes_table)) {
    echo "<p>✅ Table 'blog_likes' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_likes table: " . mysqli_error($conn) . "</p>";
}

// Step 10: Create comment likes table
$create_comment_likes_table = "
CREATE TABLE IF NOT EXISTS comment_likes (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    comment_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comment_like (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comment_id (comment_id),
    INDEX idx_user_id (user_id)
)";

if (mysqli_query($conn, $create_comment_likes_table)) {
    echo "<p>✅ Table 'comment_likes' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating comment_likes table: " . mysqli_error($conn) . "</p>";
}

// Step 11: Create blog tags table
$create_tags_table = "
CREATE TABLE IF NOT EXISTS blog_tags (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_tags_table)) {
    echo "<p>✅ Table 'blog_tags' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blog_tags table: " . mysqli_error($conn) . "</p>";
}

// Step 12: Create post tags junction table
$create_post_tags_table = "
CREATE TABLE IF NOT EXISTS post_tags (
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_post_tags_table)) {
    echo "<p>✅ Table 'post_tags' created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating post_tags table: " . mysqli_error($conn) . "</p>";
}

// Step 13: Create uploads directory structure
$directories = [
    'uploads/',
    'uploads/blog/',
    'uploads/blog/images/',
    'uploads/blog/thumbnails/'
];

echo "<h3>📁 Creating Directory Structure</h3>";
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>✅ Directory '$dir' created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create directory '$dir'!</p>";
        }
    } else {
        echo "<p>ℹ️ Directory '$dir' already exists.</p>";
    }
}

// if no users in the database, create sample users

if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users")) == 0) {
    echo "<p>ℹ️ No users found in the database. Creating sample users...</p>";
echo "<h3>👤 Creating Sample Users</h3>";
// Step 14: Insert sample admin user
$admin_email = 'admin@student.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_name = 'Administrator';

// Check if admin user already exists
$check_admin = "SELECT id FROM users WHERE email = '$admin_email'";
$admin_result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($admin_result) == 0) {
    $insert_admin = "INSERT INTO users (name, email, password, role) VALUES ('$admin_name', '$admin_email', '$admin_password', 'admin')";
    
    if (mysqli_query($conn, $insert_admin)) {
        echo "<p>✅ Admin user created successfully!</p>";
        echo "<div style='background-color: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        echo "<strong>👤 Admin Login Credentials:</strong><br>";
        echo "Email: <code>admin@student.com</code><br>";
        echo "Password: <code>admin123</code><br>";
        echo "Role: <code>admin</code>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not create admin user: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Admin user already exists in the database.</p>";
}

// Step 15: Insert sample regular user
$user_email = 'user@student.com';
$user_password = password_hash('user123', PASSWORD_DEFAULT);
$user_name = 'Regular User';

$check_user = "SELECT id FROM users WHERE email = '$user_email'";
$user_result = mysqli_query($conn, $check_user);

if (mysqli_num_rows($user_result) == 0) {
    $insert_user = "INSERT INTO users (name, email, password, role) VALUES ('$user_name', '$user_email', '$user_password', 'user')";
    
    if (mysqli_query($conn, $insert_user)) {
        echo "<p>✅ Regular user created successfully!</p>";
        echo "<div style='background-color: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0;'>";
        echo "<strong>👤 Regular User Login Credentials:</strong><br>";
        echo "Email: <code>user@student.com</code><br>";
        echo "Password: <code>user123</code><br>";
        echo "Role: <code>user</code>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not create regular user: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Regular user already exists in the database.</p>";
}
} else {
    echo "<p>ℹ️ Users already exist in the database. Skipping sample user creation.</p>";
}

// Step 16: Insert sample categories
$sample_categories = [
    ['Technology', 'Posts about technology and programming', '#007bff'],
    ['Education', 'Educational content and tutorials', '#28a745'],
    ['Lifestyle', 'Lifestyle and personal development', '#ffc107'],
    ['News', 'Latest news and updates', '#dc3545'],
    ['Entertainment', 'Fun and entertainment content', '#6f42c1']
];

echo "<h3>📂 Creating Sample Categories</h3>";
foreach ($sample_categories as $category) {
    $check_category = "SELECT id FROM blog_categories WHERE name = '" . $category[0] . "'";
    $result = mysqli_query($conn, $check_category);
    
    if (mysqli_num_rows($result) == 0) {
        $insert_category = "INSERT INTO blog_categories (name, description, color) VALUES ('" . 
                          $category[0] . "', '" . $category[1] . "', '" . $category[2] . "')";
        
        if (mysqli_query($conn, $insert_category)) {
            echo "<p>✅ Category '" . $category[0] . "' created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating category '" . $category[0] . "': " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>ℹ️ Category '" . $category[0] . "' already exists.</p>";
    }
}

// Step 17: Insert sample tags
$sample_tags = ['PHP', 'MySQL', 'JavaScript', 'Tutorial', 'Beginner', 'Advanced', 'Web Development', 'Database'];

echo "<h3>🏷️ Creating Sample Tags</h3>";
foreach ($sample_tags as $tag) {
    $check_tag = "SELECT id FROM blog_tags WHERE name = '$tag'";
    $result = mysqli_query($conn, $check_tag);
    
    if (mysqli_num_rows($result) == 0) {
        $insert_tag = "INSERT INTO blog_tags (name) VALUES ('$tag')";
        
        if (mysqli_query($conn, $insert_tag)) {
            echo "<p>✅ Tag '$tag' created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating tag '$tag': " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>ℹ️ Tag '$tag' already exists.</p>";
    }
}

// Step 18: Show users table structure
$check_users_table = "DESCRIBE users";
$result = mysqli_query($conn, $check_users_table);

if ($result) {
    echo "<h3>👥 Users Table Structure:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

// Step 19: Show complete database structure overview
echo "<h3>📋 Complete Database Structure Overview</h3>";

$tables = [
    'users' => 'User accounts with role-based access (user/admin)',
    'blog_posts' => 'Main blog posts with content and metadata',
    'blog_categories' => 'Categories for organizing posts',
    'post_categories' => 'Many-to-many relationship between posts and categories',
    'blog_comments' => 'Comments on blog posts (supports nested replies)',
    'blog_likes' => 'Likes for blog posts',
    'comment_likes' => 'Likes for comments',
    'blog_tags' => 'Tags for posts',
    'post_tags' => 'Many-to-many relationship between posts and tags'
];

echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background-color: #f0f0f0;'><th>Table Name</th><th>Description</th><th>Status</th></tr>";

foreach ($tables as $table => $description) {
    $check_table = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_table);
    $status = mysqli_num_rows($result) > 0 ? "✅ Exists" : "❌ Missing";
    
    echo "<tr>";
    echo "<td><strong>$table</strong></td>";
    echo "<td>$description</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Step 20: Display statistics
echo "<h3>📊 Database Statistics</h3>";

$stats_queries = [
    'Total Users' => 'SELECT COUNT(*) as count FROM users',
    'Admin Users' => 'SELECT COUNT(*) as count FROM users WHERE role = "admin"',
    'Regular Users' => 'SELECT COUNT(*) as count FROM users WHERE role = "user"',
    'Blog Posts' => 'SELECT COUNT(*) as count FROM blog_posts',
    'Categories' => 'SELECT COUNT(*) as count FROM blog_categories',
    'Tags' => 'SELECT COUNT(*) as count FROM blog_tags',
    'Comments' => 'SELECT COUNT(*) as count FROM blog_comments',
    'Likes' => 'SELECT COUNT(*) as count FROM blog_likes'
];

echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background-color: #f0f0f0;'><th>Item</th><th>Count</th></tr>";

foreach ($stats_queries as $label => $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];
    } else {
        $count = 'N/A';
    }
    
    echo "<tr>";
    echo "<td><strong>$label</strong></td>";
    echo "<td>$count</td>";
    echo "</tr>";
}
echo "</table>";

// Step 21: Test database connection
echo "<h3>🔧 Testing Database Configuration</h3>";
$test_conn = mysqli_connect($host, $username, $password, $database);

if ($test_conn) {
    echo "<p>✅ Database connection test successful!</p>";
    echo "<p>✅ Your config.php settings are correct!</p>";
    mysqli_close($test_conn);
} else {
    echo "<p style='color: red;'>❌ Database connection test failed!</p>";
}

// Close connection
mysqli_close($conn);

// Final success message
echo "<br><div style='background-color: #d4edda; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 5px;'>";
echo "<h3>🎉 Complete Student Portal & Blog System Setup Complete!</h3>";
echo "<p><strong>✅ Successfully created:</strong></p>";
echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>👥 <strong>users</strong> - User accounts with role system (user/admin)</li>";
echo "<li>📝 <strong>blog_posts</strong> - Main blog posts table with views tracking</li>";
echo "<li>📂 <strong>blog_categories</strong> - Organized content categories</li>";
echo "<li>🔗 <strong>post_categories</strong> - Posts-to-categories relationships</li>";
echo "<li>💬 <strong>blog_comments</strong> - Nested comments system</li>";
echo "<li>👍 <strong>blog_likes</strong> - Post likes system</li>";
echo "<li>💙 <strong>comment_likes</strong> - Comment likes system</li>";
echo "<li>🏷️ <strong>blog_tags</strong> - Flexible tagging system</li>";
echo "<li>🔗 <strong>post_tags</strong> - Posts-to-tags relationships</li>";
echo "<li>📁 Complete uploads directory structure</li>";
echo "<li>👤 Sample admin and regular user accounts</li>";
echo "<li>🎨 Sample categories and tags</li>";
echo "</ul>";

echo "<p><strong>👤 Default User Accounts:</strong></p>";
echo "<div style='display: flex; gap: 20px; margin: 10px 0;'>";
echo "<div style='background-color: #fff3cd; padding: 10px; border-radius: 5px; flex: 1;'>";
echo "<strong>🔑 Admin Account:</strong><br>";
echo "Email: admin@student.com<br>";
echo "Password: admin123<br>";
echo "Role: admin";
echo "</div>";
echo "<div style='background-color: #d1ecf1; padding: 10px; border-radius: 5px; flex: 1;'>";
echo "<strong>👤 User Account:</strong><br>";
echo "Email: user@student.com<br>";
echo "Password: user123<br>";
echo "Role: user";
echo "</div>";
echo "</div>";

echo "<p><strong>🚀 Key Features Available:</strong></p>";
echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>✅ Role-based user authentication (admin/user)</li>";
echo "<li>✅ Create, edit, and delete blog posts</li>";
echo "<li>✅ Image uploads with organized storage</li>";
echo "<li>✅ Category and tag management</li>";
echo "<li>✅ Nested comment system (replies to comments)</li>";
echo "<li>✅ Like/unlike posts and comments</li>";
echo "<li>✅ View tracking for posts</li>";
echo "<li>✅ Draft/published status for posts</li>";
echo "<li>✅ Content moderation for comments</li>";
echo "</ul>";

echo "<p><strong>🔧 Next Steps:</strong></p>";
echo "<ol style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>Create login/registration pages with role checking</li>";
echo "<li>Build admin dashboard for content management</li>";
echo "<li>Create blog interface files (post creation, viewing, etc.)</li>";
echo "<li>Implement role-based access control in your PHP files</li>";
echo "<li>Add AJAX for likes and comments</li>";
echo "<li>Implement image upload and resize functionality</li>";
echo "<li>Add search and filtering capabilities</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 5px;'>";
echo "<h4>⚠️ Security Recommendations:</h4>";
echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>🔒 Always sanitize user input</li>";
echo "<li>🔒 Use prepared statements for database queries</li>";
echo "<li>🔒 Implement proper file upload validation</li>";
echo "<li>🔒 Add CSRF protection to forms</li>";
echo "<li>🔒 Implement rate limiting for comments and likes</li>";
echo "<li>🔒 Use role checking: if (\$_SESSION['role'] == 'admin') { /* admin only code */ }</li>";
echo "<li>🔒 Change default passwords immediately</li>";
echo "<li>🔒 Delete this initialization file after setup</li>";
echo "</ul>";
echo "</div>";

echo "<p style='color: #666; font-size: 12px; margin-top: 20px;'>⚠️ <strong>Security Note:</strong> Delete this file after running it once to prevent unauthorized access to your database structure.</p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    line-height: 1.6;
    background-color: #f8f9fa;
    color: #343a40;
}

h2 {
    color: #007bff;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

h3 {
    color: #28a745;
    margin-top: 30px;
    margin-bottom: 15px;
}

table {
    margin: 15px 0;
    background-color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
}

th {
    background-color: #007bff;
    color: white;
    font-weight: 600;
    padding: 12px;
}

td {
    padding: 10px 12px;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #dee2e6;
}

tr:nth-child(even) {
    background-color: #f8f9fa;
}

tr:hover {
    background-color: #e9ecef;
}

code {
    background-color: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 0.9em;
    color: #495057;
}

p {
    margin: 12px 0;
}

ul, ol {
    margin: 15px 0;
    padding-left: 25px;
}

li {
    margin: 8px 0;
    line-height: 1.5;
}

.success {
    color: #28a745;
    font-weight: 500;
}

.error {
    color: #dc3545;
    font-weight: 500;
}

.info {
    color: #17a2b8;
    font-weight: 500;
}

div[style*="background-color"] {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>