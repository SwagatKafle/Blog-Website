<?php
// login.php
include 'config.php';

$page_title = 'Login - Student Portal';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Check credentials
    if (empty($errors)) {
        $query = "SELECT id, name, email, role, password FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role']; 
                
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password';
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2 class="text-center">Welcome Back</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p>• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Sign In</button>
    </form>
    
    <p class="text-center mt-2">
        Don't have an account? <a href="register.php" class="link">Create one here</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>