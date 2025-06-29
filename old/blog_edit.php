<?php
// blog_edit.php - Edit existing blog post
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id == 0) {
    header('Location: blog_list.php');
    exit;
}

// Get blog post and verify ownership
$query = "SELECT * FROM blog_posts WHERE id = $post_id AND user_id = " . $_SESSION['user_id'];
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: blog_list.php');
    exit;
}

$post = mysqli_fetch_assoc($result);
$page_title = 'Edit Post - Student Portal';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    
    // Handle image upload
    $image_path = $post['image_path']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            if ($_FILES['image']['size'] <= $max_size) {
                // Delete old image if exists
                if ($post['image_path'] && file_exists($post['image_path'])) {
                    unlink($post['image_path']);
                }
                
                $upload_dir = 'uploads/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
                $image_path = $upload_dir . $filename;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $errors[] = 'Failed to upload image';
                    $image_path = $post['image_path']; // Revert to old image
                }
            } else {
                $errors[] = 'Image size must be less than 5MB';
            }
        } else {
            $errors[] = 'Only JPEG, PNG, and GIF images are allowed';
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if ($post['image_path'] && file_exists($post['image_path'])) {
            unlink($post['image_path']);
        }
        $image_path = null;
    }
    
    // Update blog post if no errors
    if (empty($errors)) {
        $query = "UPDATE blog_posts SET 
                  title = '$title', 
                  content = '$content', 
                  image_path = " . ($image_path ? "'$image_path'" : "NULL") . ", 
                  status = '$status',
                  updated_at = NOW()
                  WHERE id = $post_id";
        
        if (mysqli_query($conn, $query)) {
            $success = 'Blog post updated successfully!';
            // Refresh post data
            $result = mysqli_query($conn, "SELECT * FROM blog_posts WHERE id = $post_id");
            $post = mysqli_fetch_assoc($result);
        } else {
            $errors[] = 'Failed to update blog post. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container" style="max-width: 800px;">
    <h2 class="text-center">✏️ Edit Blog Post</h2>
    
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
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="image">Featured Image</label>
            
            <?php if ($post['image_path']): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Current image" style="max-width: 200px; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <div style="margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                            <input type="checkbox" name="remove_image" value="1">
                            Remove current image
                        </label>
                    </div>
                </div>
            <?php endif; ?>
            
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" style="padding: 0.5rem;">
            <small style="color: #718096; display: block; margin-top: 0.5rem;">Upload new image (Max size: 5MB. Formats: JPEG, PNG, GIF)</small>
        </div>
        
        <div class="form-group">
            <label for="content">Post Content</label>
            <textarea id="content" name="content" rows="12" style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; font-family: inherit; resize: vertical;" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="status">Post Status</label>
            <select id="status" name="status" style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem;">
                <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <button type="submit" class="btn" style="flex: 1; min-width: 150px;">💾 Update Post</button>
            <a href="blog_view.php?id=<?php echo $post['id']; ?>" class="btn" style="flex: 1; min-width: 150px; text-decoration: none; text-align: center; background: #6c757d;">👁️ View Post</a>
            <a href="blog_list.php" class="btn" style="flex: 1; min-width: 150px; text-decoration: none; text-align: center; background: #dc3545;">❌ Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>