<?php 
// dashboard.php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch complete user data from database
$stmt = $conn->prepare("SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$stmt->close();

// Calculate days since registration
$created_date = new DateTime($user['created_at']);
$now = new DateTime();
$days_registered = $created_date->diff($now)->days;

$page_title = 'Dashboard - Student Portal';
include 'includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --card-shadow-hover: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        --text-primary: #2d3748;
        --text-secondary: #718096;
        --bg-light: #f7fafc;
        --border-radius: 20px;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        min-height: 100vh;
    }

    .hero-section {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: var(--border-radius);
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 3rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .welcome-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(45deg, #fff, #e2e8f0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-subtitle {
        font-size: 1.3rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        background: var(--primary-gradient);
        color: white;
    }

    .stat-title {
        font-size: 0.9rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .stat-desc {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .info-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .info-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--card-shadow-hover);
    }

    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.3rem;
        color: white;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: var(--text-secondary);
        flex: 1;
    }

    .info-value {
        font-weight: 600;
        color: var(--text-primary);
        text-align: right;
        flex: 1;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        background: var(--success-gradient);
        color: white;
    }

    .status-badge::before {
        content: '●';
        margin-right: 0.5rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .actions-section {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 1rem 2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-width: 150px;
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: white;
        color: var(--text-primary);
        border: 2px solid #e2e8f0;
        box-shadow: var(--card-shadow);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn:hover::before {
        left: 100%;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem 0.5rem;
        }

        .hero-section {
            padding: 2rem 1rem;
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .info-cards-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card, .info-card {
            padding: 1.5rem;
        }

        .actions-section {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 100%;
            max-width: 300px;
        }

        .info-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-value {
            text-align: left;
        }
    }

    @media (max-width: 480px) {
        .welcome-title {
            font-size: 1.8rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .card-header {
            flex-direction: column;
            text-align: center;
        }

        .card-icon {
            margin-right: 0;
            margin-bottom: 0.5rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 🎓</h1>
            <p class="welcome-subtitle">Ready to continue your learning journey?</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-title">Days Active</div>
            <div class="stat-value"><?php echo $days_registered; ?></div>
            <div class="stat-desc">Since you joined us</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-title">Account Type</div>
            <div class="stat-value"><?php echo ucfirst($user['role']); ?></div>
            <div class="stat-desc">Your current role</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-title">Status</div>
            <div class="stat-value">Active</div>
            <div class="stat-desc">Account is verified</div>
        </div>
    </div>

    <!-- Info Cards Grid -->
    <div class="info-cards-grid">
        <!-- Account Information -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-icon" style="background: var(--primary-gradient);">👤</div>
                <h3 class="card-title">Account Information</h3>
            </div>
            <div class="info-item">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email Address</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">User ID</span>
                <span class="info-value">#<?php echo htmlspecialchars($user['id']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Status</span>
                <span class="info-value">
                    <span class="status-badge">Active</span>
                </span>
            </div>
        </div>

        <!-- Activity Information -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-icon" style="background: var(--secondary-gradient);">📅</div>
                <h3 class="card-title">Activity Timeline</h3>
            </div>
            <div class="info-item">
                <span class="info-label">Member Since</span>
                <span class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Last Updated</span>
                <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Login Status</span>
                <span class="info-value">
                    <span class="status-badge">Online Now</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Session Time</span>
                <span class="info-value" id="session-time">00:00:00</span>
            </div>
        </div>
    </div>

    <!-- Actions Section -->
    <div class="actions-section">
        <!-- <a href="profile.php" class="btn btn-primary">
            ⚙️ Manage Profile
        </a>
        <a href="settings.php" class="btn btn-secondary">
            🔧 Account Settings
        </a> -->
        <a href="logout.php" class="btn btn-secondary">
            🚪 Sign Out
        </a>
    </div>
</div>

<script>
// Session timer
let sessionStart = Date.now();
function updateSessionTime() {
    const elapsed = Date.now() - sessionStart;
    const hours = Math.floor(elapsed / 3600000);
    const minutes = Math.floor((elapsed % 3600000) / 60000);
    const seconds = Math.floor((elapsed % 60000) / 1000);
    
    document.getElementById('session-time').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Update session time every second
setInterval(updateSessionTime, 1000);

// Add smooth scroll behavior and loading animation
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on load
    const cards = document.querySelectorAll('.stat-card, .info-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Add logout confirmation
document.querySelector('a[href="logout.php"]').addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to sign out?')) {
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>