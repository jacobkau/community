<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Check
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token.');
        }

        // CREATE POST
        if (isset($_POST['content'])) {
            $content = htmlspecialchars(trim($_POST['content']));
            if (empty($content)) throw new Exception('Post content cannot be empty.');

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert the post
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                $stmt->execute([$user_id, $content]);
                $postId = $pdo->lastInsertId();

                // Handle multiple image uploads
                if (!empty($_FILES['post_images']['name'][0])) {
                    $uploadOrder = 1;
                    foreach ($_FILES['post_images']['tmp_name'] as $key => $tmpName) {
                        $file = [
                            'name' => $_FILES['post_images']['name'][$key],
                            'type' => $_FILES['post_images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['post_images']['error'][$key],
                            'size' => $_FILES['post_images']['size'][$key]
                        ];

                        $upload = handleImageUpload($file);
                        if ($upload['error']) throw new Exception($upload['error']);

                        // Save to media table
                        $stmt = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type, upload_order) VALUES (?, ?, 'image', ?)");
                        $stmt->execute([$postId, $upload['path'], $uploadOrder++]);
                    }
                }

                // Handle multiple video uploads
                if (!empty($_FILES['post_videos']['name'][0])) {
                    $uploadOrder = 1;
                    foreach ($_FILES['post_videos']['tmp_name'] as $key => $tmpName) {
                        $file = [
                            'name' => $_FILES['post_videos']['name'][$key],
                            'type' => $_FILES['post_videos']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['post_videos']['error'][$key],
                            'size' => $_FILES['post_videos']['size'][$key]
                        ];

                        $upload = handleVideoUpload($file);
                        if ($upload['error']) throw new Exception($upload['error']);

                        // Save to media table
                        $stmt = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type, upload_order) VALUES (?, ?, 'video', ?)");
                        $stmt->execute([$postId, $upload['path'], $uploadOrder++]);
                    }
                }

                $pdo->commit();
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }

   
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Upload handlers (updated to return more info)
function handleImageUpload($file)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload error code: ' . $file['error']];
    }

    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) {
        return ['error' => 'Only JPG, PNG, GIF, and WEBP images are allowed.'];
    }

    if ($file['size'] > $maxFileSize) {
        return ['error' => 'Image size exceeds 5MB limit.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('img_', true) . '.' . $ext;

    $uploadDir = __DIR__ . '/../uploads/posts/';
    $relativePath = 'uploads/posts/' . $newName;
    $fullPath = $uploadDir . $newName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['error' => 'Failed to move uploaded file.'];
    }

    return [
        'error' => false,
        'path' => $relativePath,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'type' => mime_content_type($fullPath)
    ];
}

function handleVideoUpload($file)
{
    $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
    $maxFileSize = 20 * 1024 * 1024; // 20MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload error code: ' . $file['error']];
    }

    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) {
        return ['error' => 'Unsupported video format. Allowed: MP4, WebM, MOV'];
    }

    if ($file['size'] > $maxFileSize) {
        return ['error' => 'Video size exceeds 20MB limit.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('vid_', true) . '.' . $ext;

    $uploadDir = __DIR__ . '/../uploads/posts/';
    $relativePath = 'uploads/posts/' . $newName;
    $fullPath = $uploadDir . $newName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['error' => 'Failed to move uploaded video.'];
    }

    return [
        'error' => false,
        'path' => $relativePath,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'type' => mime_content_type($fullPath)
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 ms-sm-auto px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-8" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
                <div class="container" id="post-container"
                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="create-post card mb-4"
                            style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                            <form method="POST" class="card-body" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <textarea name="content" class="form-control mb-3" placeholder="What's on your mind?" required></textarea>

                                <!-- Media Uploads -->
                                <div class="mb-3">
                                    <label class="btn btn-outline-secondary">
                                        <i class="fas fa-image"></i> Add Images
                                        <input type="file" name="post_images[]" accept="image/*" multiple hidden>
                                    </label>

                                    <label class="btn btn-outline-secondary">
                                        <i class="fas fa-video"></i> Add Videos
                                        <input type="file" name="post_videos[]" accept="video/*" multiple hidden>
                                    </label>

                                    <!-- Preview area -->
                                    <div id="media-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                </div>

                                <button type="submit" class="btn btn-primary">Post</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Posts Display Area -->
                    <div class="posts-container"
                        style=" border-color: var(--border-color); color: var(--text-color);">

                        <?php

                        include("post.php");
                        ?>

                        <!-- Load More Button -->
                        <div class="pagination-bar text-center my-4">
                            <button class="btn btn-primary load-more-btn"
                                data-offset="<?= $limit ?>"
                                data-limit="<?= $limit ?>"
                                data-total-posts="<?= $totalPosts ?? 0 ?>">
                                See More...
                            </button>
                            <p class="no-more-posts-message" style="display:none;">No more posts to load.</p>
                        </div>
                    </div>
                </div>
        </main>
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
    </div>

    <?php require_once __DIR__ . '/post_functions.php'; ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>
<script>
    document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const preview = document.getElementById('media-preview');
        preview.innerHTML = '';
        
        // Process images
        if (this.name === 'post_images[]' && this.files.length > 0) {
            Array.from(this.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxHeight = '100px';
                    img.style.maxWidth = '100px';
                    img.className = 'img-thumbnail';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }
        
        // Process videos
        if (this.name === 'post_videos[]' && this.files.length > 0) {
            Array.from(this.files).forEach(file => {
                if (!file.type.startsWith('video/')) return;
                
                const div = document.createElement('div');
                div.className = 'video-thumbnail';
                div.innerHTML = `
                    <video width="120" height="90" controls>
                        <source src="${URL.createObjectURL(file)}" type="${file.type}">
                    </video>
                    <small>${file.name}</small>
                `;
                preview.appendChild(div);
            });
        }
    });
});
</script>