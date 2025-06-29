<?php
// blog_create.php - Create new blog post
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Create New Post - Student Portal';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            if ($_FILES['image']['size'] <= $max_size) {
                $upload_dir = 'uploads/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
                $image_path = $upload_dir . $filename;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $errors[] = 'Failed to upload image';
                    $image_path = null;
                }
            } else {
                $errors[] = 'Image size must be less than 5MB';
            }
        } else {
            $errors[] = 'Only JPEG, PNG, and GIF images are allowed';
        }
    }
    
    // Insert blog post if no errors
    if (empty($errors)) {
        $query = "INSERT INTO blog_posts (user_id, title, content, image_path, status) 
                  VALUES ('$user_id', '$title', '$content', " . 
                  ($image_path ? "'$image_path'" : "NULL") . ", '$status')";
        
        if (mysqli_query($conn, $query)) {
            $success = 'Blog post created successfully!';
            if ($status == 'published') {
                $success .= ' <a href="blog_list.php">View all posts</a>';
            }
        } else {
            $errors[] = 'Failed to create blog post. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container" style="max-width: 800px;">
    <h2 class="text-center">✍️ Create New Blog Post</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p>• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Post Title</label>
            <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="image">Featured Image (Optional)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" style="padding: 0.5rem;">
            <small style="color: #718096; display: block; margin-top: 0.5rem;">Max size: 5MB. Formats: JPEG, PNG, GIF</small>
        </div>
        
        <div class="form-group">
            <label for="content">Post Content</label>
            <textarea id="content" name="content" rows="12" style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; font-family: inherit; resize: vertical;" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="status">Post Status</label>
            <select id="status" name="status" style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem;">
                <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;">📝 Create Post</button>
            <a href="blog_list.php" class="btn" style="flex: 1; text-decoration: none; text-align: center; background: #6c757d;">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
