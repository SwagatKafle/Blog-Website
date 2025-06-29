<?php
// register.php
include 'config.php';

$page_title = 'Register - Student Portal';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_email = "SELECT id FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = 'Email already registered';
        }
    }
    
    // Insert user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (name, email, password, created_at) VALUES ('$name', '$email', '$hashed_password', NOW())";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = 'Registration successful! You can now login.';
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2 class="text-center">Create Account</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p>• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn">Create Account</button>
    </form>
    
    <p class="text-center mt-2">
        Already have an account? <a href="login.php" class="link">Sign in here</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>