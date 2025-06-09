<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /community/login.php');
    exit;
}

// Handle POST requests (settings update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized request']);
        exit;
    }

    $id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio'] ?? '');
    $password = $_POST['password'];

    // Profile picture handling
    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profile_pic = "uploads/" . $id . "." . $ext;
        $upload_path = __DIR__ . '/' . $profile_pic;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path);
    }

    try {
        // Build the update query dynamically
        $query = "UPDATE users SET username = :username, email = :email, bio = :bio";
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':bio' => $bio,
            ':id' => $id
        ];

        if ($profile_pic) {
            $query .= ", profile_pic = :profile_pic";
            $params[':profile_pic'] = $profile_pic;
        }

        if (!empty($password)) {
            $query .= ", password = :password";
            $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $query .= " WHERE user_id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Account settings updated successfully']);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update account: ' . $e->getMessage()]);
        exit;
    }
}

// For GET requests, show the settings page
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <style>
        .profile-pic-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #dee2e6;
        }

        .settings-container {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<?php require_once __DIR__ . "/includes/header.php"; ?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
                <h2 class="mb-4"><i class="bi bi-gear"></i> Account Settings</h2>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <form id="settingsForm" enctype="multipart/form-data">
                    <!-- Profile Picture -->
                    <div class="mb-4 text-center">
                        <img src="<?= htmlspecialchars($user['profile_pic'] ?? '/assets/default-avatar.png') ?>"
                            class="profile-pic-preview" id="profilePicPreview">
                        <div>
                            <input type="file" name="profile_pic" id="profilePicInput" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profilePicInput').click()">
                                <i class="bi bi-camera"></i> Change Photo
                            </button>
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                            class="form-control" required>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                            class="form-control" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>

                    <!-- Bio -->
                    <div class="mb-4">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitText">Save Changes</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>

                <div id="settingsResult" class="mt-3"></div>
            </div>
        </main>

        <aside class="col-lg-3 order-lg-last">
            <div class="d-none d-lg-block position-fixed vh-100 end-0 p-3"
                style="width: 400px; top: 2vh; z-index: 100;margin-right:7vh; overflow-y: auto;">
                <!-- Right sidebar content from right.php -->
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>

            <!-- Mobile version (hidden on lg and up) -->
            <div class="d-lg-none">
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>
        </aside>
    </div><br><br>


    <?php require_once __DIR__ . "/includes/footer.php"; ?>
    <?php require_once __DIR__ . "/post_functions.php"; ?>

    <script>
        // Profile picture preview
        document.getElementById('profilePicInput').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePicPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form submission
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const resultDiv = document.getElementById('settingsResult');

            // Show loading state
            submitBtn.disabled = true;
            submitText.textContent = 'Saving...';
            submitSpinner.classList.remove('d-none');
            resultDiv.innerHTML = '';

            const formData = new FormData(this);

            fetch('settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Create alert
                    const alert = document.createElement('div');
                    alert.className = `alert alert-${data.success ? 'success' : 'danger'}`;
                    alert.textContent = data.message;
                    resultDiv.appendChild(alert);

                    if (data.success) {
                        // Refresh the page after 1.5 seconds
                        setTimeout(() => window.location.reload(), 1500);
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        An error occurred while saving your settings.
                    </div>
                `;
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitText.textContent = 'Save Changes';
                    submitSpinner.classList.add('d-none');
                });
        });
    </script>
</body>

</html>