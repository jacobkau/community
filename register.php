<?php
require_once __DIR__ . '/config/db.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please refresh the page and try again.";
    }

    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username cannot exceed 50 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers and underscores";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match("/[^a-zA-Z0-9]/", $password)) {
        $errors[] = "Password must contain at least one special character";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Passwords do not match";
    }

    // Check if username/email exists
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Check username (case-sensitive)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username already exists";
            }

            // Check email (case-insensitive)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email already exists";
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error during registration check: " . $e->getMessage());
            $errors[] = "Registration temporarily unavailable. Please try again later.";
        }
    }

    // Register user if no errors
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            if ($hashed_password === false) {
                throw new Exception("Password hashing failed");
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashed_password]);

            // Regenerate session ID and clear CSRF token
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);

            // Set success message
            $_SESSION['registration_success'] = [
                'username' => $username,
                'email' => $email
            ];

            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Registration failed due to a system error. Please try again.";
        }
    }
}

// Display the form
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-5" style="padding:50px">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm" style="background-color: var(--card-bg); border-color: var(--border-color);color:var(--text-color)">
                <div class="card-header bg-primary text-white">
                    <h2 class="text-center mb-0">Create Account</h2>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">Registration Error</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="register-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                                required
                                minlength="3"
                                maxlength="50"
                                pattern="[a-zA-Z0-9_]+">
                            <div class="form-text" style="color:var(--text-color)">3-50 characters (letters, numbers, underscores)</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                required
                                maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                required
                                minlength="8"
                                pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$">
                            <div class="form-text" style="color:var(--text-color)">
                                Minimum 8 characters with: uppercase, number, and special character
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center" style="color:var(--text-color)">
                        Already have an account? <a href="login.php" class="fw-bold">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const password = document.getElementById('password');
    const confirm = document.getElementById('password_confirm');
    
    // Real-time password match indicator
    confirm.addEventListener('input', function() {
        if (password.value !== confirm.value) {
            confirm.setCustomValidity("Passwords do not match");
        } else {
            confirm.setCustomValidity("");
        }
    });
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        // Additional client-side validation
        if (password.value !== confirm.value) {
            e.preventDefault();
            alert('Passwords do not match');
            return false;
        }
        
        // Check password strength
        const hasUpper = /[A-Z]/.test(password.value);
        const hasNumber = /[0-9]/.test(password.value);
        const hasSpecial = /[^a-zA-Z0-9]/.test(password.value);
        
        if (!hasUpper || !hasNumber || !hasSpecial) {
            e.preventDefault();
            alert('Password must contain at least one uppercase letter, one number, and one special character');
            return false;
        }
        
        return true;
    });
});
</script>

<?php 
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/post_functions.php';
?>