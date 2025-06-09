<?php
// Start session and include config
session_start();
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
function generatePostUrl($postId)
{
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/post/$postId";
}

function generateCommentUrl($postId, $commentId)
{
    return generatePostUrl($postId) . "#comment-$commentId";
}
 

// Fetch the specific post
$post = [];
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
        EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS has_liked
    FROM posts
    JOIN users ON posts.user_id = users.user_id
    WHERE posts.id = ?
");
$stmt->execute([$_SESSION['user_id'] ?? 0, $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch related posts (for the sidebar)
$related_stmt = $pdo->prepare("
    SELECT id FROM posts 
    WHERE user_id = ? AND id != ?
    ORDER BY created_at DESC 
    LIMIT 5
");
$related_stmt->execute([$post['user_id'] ?? 0, $post_id]);
$related_posts = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = $post['title'] ?? 'Post Not Found';
require_once __DIR__ . "/includes/header.php";
?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">


                <?php try { ?>
                    <?php if ($post): ?>
                        <div class="post card mb-4 post-highlight" id="post-<?= htmlspecialchars($post['id'] ?? '') ?>" style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                            <div class="card-body">
                                <!-- Post Header -->
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php if (!empty($post['profile_pic'])): ?>
                                                <img src="<?= htmlspecialchars($post['profile_pic']) ?>"
                                                    alt="<?= htmlspecialchars($post['username']) ?>'s avatar"
                                                    class="rounded-circle"
                                                    width="40"
                                                    height="40">
                                            <?php else: ?>
                                                <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <?= strtoupper(substr($post['username'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ms-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($post['username'] ?? 'Unknown User') ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($post['created_at'] ?? '') ?></small>
                                        </div>
                                    </div>
                                    <!-- Edit/Delete Dropdown for Post Owner/Admin -->
                                    <?php if (($_SESSION['user_id'] ?? null) == $post['user_id'] || ($_SESSION['is_admin'] ?? false)): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                id="postDropdown-<?= $post['id'] ?>"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="postDropdown-<?= $post['id'] ?>">
                                                <!-- Edit Button -->
                                                <li>
                                                    <button class="dropdown-item edit-post"
                                                        data-post-id="<?= $post['id'] ?>"
                                                        data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                        <i class="fas fa-edit me-2"></i> Edit
                                                    </button>
                                                </li>
                                                <!-- Delete Button -->
                                                <li>
                                                    <button class="dropdown-item delete-post"
                                                        data-post-id="<?= $post['id'] ?>"
                                                        data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                        <i class="fas fa-trash me-2"></i> Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Content -->
                                <h5 class="card-title mt-3"><?= htmlspecialchars($post['title'] ?? '') ?></h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></p>

                                <!-- Post Image -->
                                <?php if (!empty($post['image_path']) && is_string($post['image_path'])): ?>
                                    <div class="post-image mb-3">
                                        <img src="<?= htmlspecialchars($post['image_path']) ?>"
                                            alt="Post image"
                                            class="img-fluid rounded"
                                            style="max-height: 500px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                                <!-- Post Video -->
                                <?php if (!empty($post['video_path'])): ?>
                                    <div class="post-video mb-3">
                                        <video class="img-fluid rounded" controls>
                                            <source src="<?= htmlspecialchars($post['video_path']) ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Stats -->
                                <div class="post-stats mb-2">
                                    <span class="text-muted me-3">
                                        <i class="far fa-thumbs-up"></i> <?= $post['like_count'] ?> likes
                                    </span>
                                    <span class="text-muted">
                                        <i class="far fa-comment"></i> <?= $post['comment_count'] ?> comments
                                    </span>
                                </div>

                                <!-- Post Actions -->
                                <div class="post-actions mb-3 d-flex justify-content-between">
                                    <div class="d-flex gap-2">
                                        <!-- Update your like button to include the CSRF token -->
                                        <button class="btn btn-sm bg-primary text-white border border-primary like-post"
                                            data-post-id="<?= $post['id'] ?>"
                                            data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <i class="bi <?= $post['has_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
                                            <?= $post['has_liked'] ? 'Unlike' : 'Like' ?>
                                            (<span class="like-count"><?= $post['like_count'] ?></span>)
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary comment-count" data-post-id="<?= $post['id'] ?>">
                                            <i class="fa-solid fa-comment"></i>
                                            <span class="comment-count"><?= $post['comment_count'] ?></span>
                                        </button>
                                    </div>

                                    <!-- POST SHARE BUTTON -->
                                    <button class="btn btn-sm btn-outline-primary share-btn"
                                        data-type="post"
                                        data-url="<?= generatePostUrl($post['id']) ?>">
                                        <i class="fas fa-share-alt"></i> Share
                                    </button>
                                </div>

                                <!-- Comments Section -->
                                <div class="comments-section">
                                    <div class="comments mb-3" id="comments-container-<?= $post['id'] ?>" style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                        <?php
                                        $userId = $_SESSION['user_id'] ?? 0;
                                        $commentStmt = $pdo->prepare("
                        SELECT 
                            c.*, 
                            u.username,
                            u.profile_pic,
                            COUNT(cl.id) AS like_count,
                            SUM(cl.user_id = ?) AS user_liked
                        FROM comments c
                        JOIN users u ON c.user_id = u.user_id
                        LEFT JOIN comment_likes cl ON c.id = cl.comment_id
                        WHERE c.post_id = ? AND c.parent_id IS NULL
                        GROUP BY c.id
                        ORDER BY c.created_at ASC
                        LIMIT 3
                    ");
                                        $commentStmt->execute([$userId, $post['id']]);
                                        $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
                                        $totalCommentLikes = array_sum(array_column($comments, 'like_count'));
                                        ?>

                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment mb-2 p-2 border rounded" id="comment-<?= $comment['id'] ?>" style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                                <div class="comment-header d-flex align-items-center mb-2">
                                                    <!-- Comment User Avatar -->
                                                    <div class="user-avatar me-2">
                                                        <?php if (!empty($comment['profile_pic'])): ?>
                                                            <img src="<?= htmlspecialchars($comment['profile_pic']) ?>"
                                                                alt="<?= htmlspecialchars($comment['username']) ?>'s avatar"
                                                                class="rounded-circle"
                                                                width="32"
                                                                height="32">
                                                        <?php else: ?>
                                                            <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                                style="width: 32px; height: 32px; font-size: 0.9rem;">
                                                                <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Comment User Info -->
                                                    <div>
                                                        <strong class="d-block"><?= htmlspecialchars($comment['username']) ?></strong>
                                                        <small class="text-muted"><?= date('M j, Y \a\t g:i a', strtotime($comment['created_at'])) ?></small>
                                                    </div>
                                                </div>

                                                <div class="comment-content mb-2">
                                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                                </div>

                                                <!-- Comment Actions -->
                                                <div class="comment-actions mt-3 d-flex justify-content-between">
                                                    <div class="comment-interaction d-flex gap-2">
                                                        <!-- Like Button -->
                                                        <button class="btn btn-sm <?= $comment['user_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-comment"
                                                            data-comment-id="<?= $comment['id'] ?>"
                                                            data-post-id="<?= $post['id'] ?>">
                                                            <i class="<?= $comment['user_liked'] ? 'fas' : 'far' ?> fa-thumbs-up"></i>
                                                            <?= $comment['user_liked'] ? 'Unlike' : 'Like' ?>
                                                            (<span class="like-count"><?= $comment['like_count'] ?></span>)
                                                        </button>


                                                        <!-- COMMENT SHARE BUTTON -->
                                                        <button class="btn btn-sm btn-outline-primary share-btn"
                                                            data-type="comment"
                                                            data-url="<?= generateCommentUrl($post['id'], $comment['id']) ?>">
                                                            <i class="fas fa-share-alt"></i> Share
                                                        </button>
                                                    </div>

                                                    <!-- Edit/Delete for Users & Admins -->
                                                    <?php if (($userId && $userId == $comment['user_id']) || ($_SESSION['is_admin'] ?? false)): ?>
                                                        <div class="comment-management d-flex gap-2">
                                                            <button class="btn btn-sm btn-outline-warning edit-comment"
                                                                data-comment-id="<?= $comment['id'] ?>">
                                                                <i class="far fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger delete-comment"
                                                                data-comment-id="<?= $comment['id'] ?>"
                                                                data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                                <i class="far fa-trash-alt"></i> Delete
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($post['comment_count'] > count($comments)): ?>
                                            <button class="btn btn-sm btn-link view-more-comments" data-post-id="<?= $post['id'] ?>">
                                                View all <?= $post['comment_count'] ?> comments
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="post-total-likes mb-2">
                                        <span class="badge bg-info">
                                            <i class="fas fa-star"></i>
                                            <?= $totalCommentLikes ?> total comment likes
                                        </span>
                                    </div>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form class="comment-form p-3 border rounded w-100 theme-card" data-post-id="<?= $post['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                            <div class="position-relative">
                                                <textarea class="form-control form-control-lg pe-5 theme-input"
                                                    name="comment"
                                                    placeholder="Write a comment..."
                                                    style="background-color: var(--input-bg); color: var(--text-color); border-color: var(--border-color);"
                                                    rows="1"
                                                    required></textarea>
                                                <button type="submit" class="btn btn-primary position-absolute end-0 bottom-0 mb-2 me-2">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </form>

                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Post not found.</div>
                    <?php endif; ?>
                <?php } catch (Throwable $e) { ?>
                    <div class="alert alert-danger">Rendering Error: <?= htmlspecialchars($e->getMessage()) ?></div>
                <?php } ?>

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
    <!-- Corrected footer include path -->
    <?php require_once __DIR__ . "/includes/footer.php"; ?>

    <!-- Include post functions -->
    <?php require_once __DIR__ . "/post_functions.php"; ?>

    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight the post on load
            const postElement = document.getElementById('post-<?= $post_id ?>');
            if (postElement) {
                postElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>