<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Student Portal'; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link type="image/png" sizes="120x120" rel="icon" href="assets/favicon.png">
    <style>
        
    </style>
</head>
<body>
<header>
        <div class="container">
            <div class="header-content">
                <div class="logo"><a href="index.php">Swagat Blogger</a></div>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="blog_list.php">Blog</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <!-- if role is "admin" -->
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a href="admin_users.php">Manage Users</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>