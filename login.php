<?php
session_start();
require_once __DIR__.'/config/db.php';

// Verify database connection
if (!$pdo) {
    die("Database connection failed");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        try {
            // Case-insensitive username search
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Verify password - with fallback for legacy plain text passwords
                if (password_verify($password, $user['password'])) {
                    // Successful login
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['profile_pic'] = $user['profile_pic'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit();
                }
            }
            
            // Generic error message
            $error = "Invalid username or password";
            
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}

require_once __DIR__.'/includes/header.php'; 
?>


<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6" style="padding: 5%;">
            <div class="card"
                style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);padding:30px">
                <div class="card-header">
                    <h2 class="text-center">Login</h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" class="form-control" name="username" id="username" required
                                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>

                        <div class="form-group" style="margin-top: 16px;">
                            <label for="password">Password:</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>

                        <div class="form-group d-flex justify-content-between" style="margin-top: 16px;">
                            <button type="submit" class="btn btn-primary btn-block">Login</button>

                            <div class="nav-item">
                                <a href="register.php" class="nav-link text-dark"> Register Here....</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__. '/includes/footer.php'; ?>
<?php require_once __DIR__ . '/post_functions.php'; ?>