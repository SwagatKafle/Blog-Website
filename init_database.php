<?php
// init_database.php - Database Initialization Script
// Run this file ONCE to create the database and tables

include 'config.php';

echo "<h2>🚀 Database Initialization Script</h2>";
echo "<p>Setting up your Student Portal database...</p><br>";

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

// Step 4: Create users table
$create_table_query = "
                                                                                                                                                                                                                                        CREATE TABLE IF NOT EXISTS users (
                                                                                                                                                                                                                                            id INT(11) PRIMARY KEY AUTO_INCREMENT,
                                                                                                                                                                                                                                            name VARCHAR(100) NOT NULL,
                                                                                                                                                                                                                                            email VARCHAR(100) UNIQUE NOT NULL,
                                                                                                                                                                                                                                            password VARCHAR(255) NOT NULL,
                                                                                                                                                                                                                                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                                                                                                                                                                                                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                                                                                                                                                                                                                        )";

if (mysqli_query($conn, $create_table_query)) {
    echo "<p>✅ Table 'users' created successfully (or already exists)!</p>";
} else {
    die("<p style='color: red;'>❌ Error creating table: " . mysqli_error($conn) . "</p>");
}

// Step 5: Check if table exists and show structure
$check_table_query = "DESCRIBE users";
$result = mysqli_query($conn, $check_table_query);

if ($result) {
    echo "<p>✅ Table structure verified!</p>";
    echo "<h3>📋 Users Table Structure:</h3>";
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

// Step 6: Insert sample admin user (optional)
$admin_email = 'admin@student.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_name = 'Administrator';

// Check if admin user already exists
$check_admin = "SELECT id FROM users WHERE email = '$admin_email'";
$admin_result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($admin_result) == 0) {
    $insert_admin = "INSERT INTO users (name, email, password) VALUES ('$admin_name', '$admin_email', '$admin_password')";
    
    if (mysqli_query($conn, $insert_admin)) {
        echo "<p>✅ Sample admin user created!</p>";
        echo "<div style='background-color: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        echo "<strong>📧 Admin Login Credentials:</strong><br>";
        echo "Email: <code>admin@student.com</code><br>";
        echo "Password: <code>admin123</code>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not create admin user: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Admin user already exists in the database.</p>";
}

// Step 7: Show current user count
$count_query = "SELECT COUNT(*) as total FROM users";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);

echo "<p>📊 Total users in database: <strong>" . $count_row['total'] . "</strong></p>";

// Step 8: Test database connection with config.php settings
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

echo "<br><div style='background-color: #e8f4fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0;'>";
echo "<h3>🎉 Database Setup Complete!</h3>";
echo "<p><strong>What's been created:</strong></p>";
echo "<ul>";
echo "<li>✅ Database: <code>$database</code></li>";
echo "<li>✅ Table: <code>users</code> with proper structure</li>";
echo "<li>✅ Sample admin user (if needed)</li>";
echo "<li>✅ All configurations tested</li>";
echo "</ul>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Update your <code>config.php</code> with the correct database credentials</li>";
echo "<li>Start using your login and registration system</li>";
echo "<li>You can now delete this <code>init_database.php</code> file for security</li>";
echo "</ol>";
echo "</div>";

echo "<br><p style='color: #666; font-size: 12px;'>⚠️ <strong>Security Note:</strong> Delete this file after running it once to prevent unauthorized database access.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    line-height: 1.6;
    background-color: #f5f5f5;
}

table {
    margin: 10px 0;
    background-color: white;
}

code {
    background-color: #f4f4f4;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

h2, h3 {
    color: #333;
}

p {
    margin: 8px 0;
}
</style>