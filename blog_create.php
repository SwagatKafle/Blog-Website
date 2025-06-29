<?php
// blog_create.php - Enhanced blog post creation with categories and tags
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Create New Post - Student Portal';
$errors = [];
$success = '';

// Fetch categories for dropdown
$categories_query = "SELECT id, name, color FROM blog_categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row;
}

// Fetch existing tags for suggestions
$tags_query = "SELECT name FROM blog_tags ORDER BY name";
$tags_result = mysqli_query($conn, $tags_query);
$existing_tags = [];
while ($row = mysqli_fetch_assoc($tags_result)) {
    $existing_tags[] = $row['name'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $user_id = $_SESSION['user_id'];
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $tags_input = trim($_POST['tags']);
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Title must be less than 255 characters';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    } elseif (strlen($content) < 50) {
        $errors[] = 'Content must be at least 50 characters long';
    }
    
    if (empty($selected_categories)) {
        $errors[] = 'Please select at least one category';
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            if ($_FILES['image']['size'] <= $max_size) {
                $upload_dir = 'uploads/blog/images/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
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
            $errors[] = 'Only JPEG, PNG, GIF, and WebP images are allowed';
        }
    }
    
    // Process and validate tags
    $processed_tags = [];
    if (!empty($tags_input)) {
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array); // Remove empty elements
        
        foreach ($tags_array as $tag) {
            if (strlen($tag) > 50) {
                $errors[] = "Tag '$tag' is too long (max 50 characters)";
            } elseif (strlen($tag) < 2) {
                $errors[] = "Tag '$tag' is too short (min 2 characters)";
            } else {
                $processed_tags[] = $tag;
            }
        }
        
        if (count($processed_tags) > 10) {
            $errors[] = 'Maximum 10 tags allowed per post';
        }
    }
    
    // Insert blog post if no errors
    if (empty($errors)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert blog post
            $query = "INSERT INTO blog_posts (user_id, title, content, image_path, status) 
                      VALUES ('$user_id', '$title', '$content', " . 
                      ($image_path ? "'$image_path'" : "NULL") . ", '$status')";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception('Failed to create blog post');
            }
            
            $post_id = mysqli_insert_id($conn);
            
            // Insert category relationships
            foreach ($selected_categories as $category_id) {
                $category_id = intval($category_id);
                $cat_query = "INSERT INTO post_categories (post_id, category_id) VALUES ('$post_id', '$category_id')";
                if (!mysqli_query($conn, $cat_query)) {
                    throw new Exception('Failed to assign categories');
                }
            }
            
            // Process tags
            if (!empty($processed_tags)) {
                foreach ($processed_tags as $tag_name) {
                    $tag_name = mysqli_real_escape_string($conn, $tag_name);
                    
                    // Check if tag exists, if not create it
                    $tag_check = "SELECT id FROM blog_tags WHERE name = '$tag_name'";
                    $tag_result = mysqli_query($conn, $tag_check);
                    
                    if (mysqli_num_rows($tag_result) > 0) {
                        $tag_row = mysqli_fetch_assoc($tag_result);
                        $tag_id = $tag_row['id'];
                    } else {
                        $tag_insert = "INSERT INTO blog_tags (name) VALUES ('$tag_name')";
                        if (!mysqli_query($conn, $tag_insert)) {
                            throw new Exception('Failed to create tag');
                        }
                        $tag_id = mysqli_insert_id($conn);
                    }
                    
                    // Link tag to post
                    $post_tag_query = "INSERT INTO post_tags (post_id, tag_id) VALUES ('$post_id', '$tag_id')";
                    if (!mysqli_query($conn, $post_tag_query)) {
                        throw new Exception('Failed to assign tags');
                    }
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success = 'Blog post created successfully!';
            if ($status == 'published') {
                $success .= ' <a href="blog_view.php?id=' . $post_id . '" style="color: #007bff; text-decoration: underline;">View Post</a> | <a href="blog_list.php" style="color: #007bff; text-decoration: underline;">View All Posts</a>';
            } else {
                $success .= ' <a href="blog_list.php" style="color: #007bff; text-decoration: underline;">View All Posts</a>';
            }
            
            // Clear form data on success
            $_POST = [];
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container" style="max-width: 900px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <h2 style="margin: 0; color: #2d3748;">✍️ Create New Blog Post</h2>
        <a href="blog_list.php" class="btn" style="background: #6c757d; padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none;">← Back to Posts</a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="background: #fed7d7; border: 1px solid #fc8181; color: #742a2a; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;">⚠️ Please fix the following errors:</h4>
            <?php foreach ($errors as $error): ?>
                <p style="margin: 0.25rem 0;">• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" style="background: #c6f6d5; border: 1px solid #68d391; color: #22543d; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <p style="margin: 0;">✅ <?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">📝 Post Title *</label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                   placeholder="Enter an engaging title for your post..."
                   style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s;"
                   maxlength="255"
                   required>
            <small style="color: #718096; display: block; margin-top: 0.25rem;">Maximum 255 characters</small>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="categories" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">📂 Categories * (Select at least one)</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: #f7fafc;">
                <?php foreach ($categories as $category): ?>
                    <label style="display: flex; align-items: center; padding: 0.5rem; background: white; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                        <input type="checkbox" 
                               name="categories[]" 
                               value="<?php echo $category['id']; ?>"
                               style="margin-right: 0.5rem;"
                               <?php echo (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) ? 'checked' : ''; ?>>
                        <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo $category['color']; ?>; border-radius: 50%; margin-right: 0.5rem;"></span>
                        <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($category['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="tags" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">🏷️ Tags (Optional)</label>
            <input type="text" 
                   id="tags" 
                   name="tags" 
                   value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>" 
                   placeholder="web development, php, tutorial, beginner..."
                   style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            <small style="color: #718096; display: block; margin-top: 0.25rem;">
                Separate tags with commas. Max 10 tags, 50 characters each.<br>
                <strong>Existing tags:</strong> <?php echo implode(', ', array_slice($existing_tags, 0, 10)); ?><?php echo count($existing_tags) > 10 ? '...' : ''; ?>
            </small>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="image" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">🖼️ Featured Image (Optional)</label>
            <div style="position: relative;">
                <input type="file" 
                       id="image" 
                       name="image" 
                       accept="image/jpeg,image/png,image/gif,image/webp" 
                       style="width: 100%; padding: 1rem; border: 2px dashed #cbd5e0; border-radius: 8px; background: #f7fafc; cursor: pointer;"
                       onchange="previewImage(this)">
                <div id="imagePreview" style="margin-top: 1rem; display: none;">
                    <img id="preview" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
            </div>
            <small style="color: #718096; display: block; margin-top: 0.5rem;">Max size: 5MB. Formats: JPEG, PNG, GIF, WebP</small>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="content" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">📄 Post Content *</label>
            <textarea id="content" 
                      name="content" 
                      rows="15" 
                      placeholder="Write your blog post content here... (minimum 50 characters)"
                      style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; font-family: inherit; resize: vertical; line-height: 1.6;"
                      required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            <div style="display: flex; justify-content: between; align-items: center; margin-top: 0.5rem;">
                <small style="color: #718096;">Minimum 50 characters required</small>
                <small id="charCount" style="color: #718096; margin-left: auto;">0 characters</small>
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="status" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">📊 Post Status</label>
            <select id="status" 
                    name="status" 
                    style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; background: white;">
                <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>
                    📝 Draft - Save for later editing
                </option>
                <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>
                    🌟 Published - Make it live immediately
                </option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <button type="submit" 
                    class="btn" 
                    style="flex: 1; min-width: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s;">
                ✨ Create Post
            </button>
            <button type="button" 
                    onclick="saveDraft()" 
                    class="btn" 
                    style="flex: 1; min-width: 200px; background: #e2e8f0; color: #4a5568; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                💾 Save as Draft
            </button>
        </div>
    </form>
</div>

<script>
// Image preview functionality
function previewImage(input) {
    const preview = document.getElementById('preview');
    const previewDiv = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewDiv.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewDiv.style.display = 'none';
    }
}

// Character count for content
document.getElementById('content').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount + ' characters';
    
    if (charCount < 50) {
        document.getElementById('charCount').style.color = '#e53e3e';
    } else {
        document.getElementById('charCount').style.color = '#38a169';
    }
});

// Save as draft functionality
function saveDraft() {
    document.getElementById('status').value = 'draft';
    document.querySelector('form').submit();
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const categories = document.querySelectorAll('input[name="categories[]"]:checked');
    
    if (title.length === 0) {
        alert('Please enter a title for your post.');
        e.preventDefault();
        return;
    }
    
    if (content.length < 50) {
        alert('Content must be at least 50 characters long.');
        e.preventDefault();
        return;
    }
    
    if (categories.length === 0) {
        alert('Please select at least one category.');
        e.preventDefault();
        return;
    }
});

// Auto-save functionality (optional)
let autoSaveTimer;
function autoSave() {
    // Implementation for auto-saving drafts
    console.log('Auto-saving...');
}

// Set up auto-save every 30 seconds
document.getElementById('content').addEventListener('input', function() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(autoSave, 30000);
});

// Add hover effects
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<style>
/* Enhanced form styling */
.form-group input[type="text"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #4299e1;
    outline: none;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-group label[style*="cursor: pointer"]:hover {
    background-color: #edf2f7 !important;
}

/* Category checkbox styling */
input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #4299e1;
}

/* Responsive design */
@media (max-width: 768px) {
    .form-container {
        margin: 1rem;
        max-width: none !important;
    }
    
    .form-container > div[style*="display: flex"] {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-container h2 {
        font-size: 1.5rem;
    }
    
    form[style*="padding: 2rem"] {
        padding: 1rem !important;
    }
    
    div[style*="display: flex; gap: 1rem; flex-wrap: wrap"] {
        flex-direction: column;
    }
    
    .btn {
        min-width: auto !important;
    }
}

/* Animation for success/error messages */
.alert {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php include 'includes/footer.php'; ?>