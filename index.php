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



// User status
$user_id = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;
$email = $_SESSION['email'] ?? false;




try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // if it's in a separate file

    $user_id = $_SESSION['user_id'] ?? null;
    $isAdmin = $_SESSION['is_admin'] ?? false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // === CSRF Check ===
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token.');
        }
        //=== HANDLE VIDEO ===


        // === DELETE POST ===
        if (isset($_POST['delete_post'])) {
            header('Content-Type: application/json');
            $postId = (int)$_POST['post_id'];

            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post || ($post['user_id'] != $user_id && !$isAdmin)) {
                throw new Exception('Unauthorized');
            }

            if ($post['image_path']) {
                $path = __DIR__ . '/../' . $post['image_path'];
                if (file_exists($path)) unlink($path);
            }
            if ($post['video_path']) {
                $videoPath = __DIR__ . '/../' . $post['video_path'];
                if (file_exists($videoPath)) unlink($videoPath);
            }


            $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);

            echo json_encode(['success' => true]);
            exit;
        }

        // === EDIT POST ===
        if (isset($_POST['edit_post'])) {
            header('Content-Type: application/json');
            $postId = (int)$_POST['post_id'];
            $content = htmlspecialchars(trim($_POST['content']));
            $removeImage = isset($_POST['remove_image']);

            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post || ($post['user_id'] != $user_id && !$isAdmin)) {
                throw new Exception('Unauthorized');
            }

            $imagePath = $post['image_path'];

            if ($removeImage && $imagePath) {
                $fullPath = __DIR__ . '/../' . $imagePath;
                if (file_exists($fullPath)) unlink($fullPath);
                $imagePath = null;
            }

            if (!empty($_FILES['new_image']['tmp_name'])) {
                $upload = handleImageUpload($_FILES['new_image']);
                if ($upload['error']) throw new Exception($upload['error']);

                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
                $imagePath = $upload['path'];
            }

            $stmt = $pdo->prepare("UPDATE posts SET content = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$content, $imagePath, $postId]);

            echo json_encode([
                'success' => true,
                'content' => nl2br(htmlspecialchars($content)),
                'image_path' => $imagePath,
                'updated_at' => date('M j, Y \a\t g:i a')
            ]);
            exit;

            $videoPath = $post['video_path'];
            $removeVideo = isset($_POST['remove_video']);

            if ($removeVideo && $videoPath) {
                $fullVideoPath = __DIR__ . '/../' . $videoPath;
                if (file_exists($fullVideoPath)) unlink($fullVideoPath);
                $videoPath = null;
            }

            if (!empty($_FILES['new_video']['tmp_name'])) {
                $videoUpload = handleVideoUpload($_FILES['new_video']);
                if ($videoUpload['error']) throw new Exception($videoUpload['error']);

                if ($videoPath && file_exists(__DIR__ . '/../' . $videoPath)) {
                    unlink(__DIR__ . '/../' . $videoPath);
                }
                $videoPath = $videoUpload['path'];
            }

            $stmt = $pdo->prepare("UPDATE posts SET content = ?, image_path = ?, video_path = ? WHERE id = ?");
            $stmt->execute([$content, $imagePath, $videoPath, $postId]);
        }

        // === CREATE POST ===
        if (isset($_POST['content']) && !$user_id) {
            throw new Exception('User not logged in.');
        }

        if (!isset($_POST['edit_post'], $_POST['delete_post'])) {
            $content = htmlspecialchars(trim($_POST['content']));
            if (empty($content)) throw new Exception('Post content cannot be empty.');

            $imagePath = null;
            if (!empty($_FILES['post_image']['tmp_name'])) {
                $upload = handleImageUpload($_FILES['post_image']);
                if ($upload['error']) throw new Exception($upload['error']);
                $imagePath = $upload['path'];
            }

            $videoPath = null;

            if (!empty($_FILES['post_video']['name'])) {
                $videoName = basename($_FILES['post_video']['name']);
                $targetDir = "uploads/videos/";
                $targetFile = $targetDir . time() . "_" . $videoName;
                $videoFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['mp4', 'webm', 'mov'];

                if (in_array($videoFileType, $allowedTypes)) {
                    if ($_FILES['post_video']['size'] <= 20000000) { // 20MB limit
                        if (move_uploaded_file($_FILES['post_video']['tmp_name'], $targetFile)) {
                            $videoPath = $targetFile; // Store this in the DB
                        } else {
                            echo "Error uploading video.";
                        }
                    } else {
                        echo "Video is too large.";
                    }
                } else {
                    echo "Unsupported video format.";
                }
            }
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_path, video_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $content, $imagePath, $videoPath]);


            header('Location: index.php');
            exit;
        }
    }

    // === GET POSTS ===
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
            EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS has_liked
        FROM posts
        JOIN users ON posts.user_id = users.id
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}




function generatePostUrl($postId)
{
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/post/$postId";
}

function generateCommentUrl($postId, $commentId)
{
    return generatePostUrl($postId) . "#comment-$commentId";
}


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

    $uploadDir = __DIR__ . '/../uploads/';
    $relativePath = '/uploads/' . $newName;
    $fullPath = $uploadDir . $newName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['error' => 'Failed to move uploaded file.'];
    }

    return ['error' => false, 'path' => $relativePath];
}

function handleVideoUpload($file)
{
    $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime']; // MP4, WebM, MOV
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

    $uploadDir = __DIR__ . '/../uploads/videos/';
    $relativePath = 'uploads/videos/' . $newName;
    $fullPath = $uploadDir . $newName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['error' => 'Failed to move uploaded video.'];
    }

    return ['error' => false, 'path' => $relativePath];
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
                                <label for="title" class="form-label fs-4">
                                    What is in your mind, <strong class="fs-4"><?= htmlspecialchars($_SESSION['username']) ?>?</strong>
                                </label>
                                <textarea name="content" class="form-control mb-3"
                                    placeholder="What's on your mind?" maxlength="2000" required></textarea>

                                <!-- Upload Fields -->
                                <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                                    <!-- Image upload -->
                                    <input type="file" id="post_image" name="post_image" accept="image/*" hidden>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"
                                        onclick="document.getElementById('post_image').click();" title="Upload Image">
                                        <i class="fas fa-image"></i> Photos
                                    </a>

                                    <!-- Video upload -->
                                    <input type="file" id="post_video" name="post_video" accept="video/*" hidden>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"
                                        onclick="document.getElementById('post_video').click();" title="Upload Video">
                                        <i class="fas fa-video"></i> Videos
                                    </a>

                                    <!-- Upload hint -->
                                    <small class="text-muted w-100 mt-1">
                                        Optional: upload an image or video (Max 2MB each. Formats: JPG, PNG, MP4, etc.)
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    Post
                                </button>
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