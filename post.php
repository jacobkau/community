<?php
require_once __DIR__ . "/config/db.php";


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Secure pagination inputs
$limit = max(1, min(50, (int)($_GET['limit'] ?? 5))); // Limit 1-50
$offset = max(0, (int)($_GET['offset'] ?? 0)); // Offset ≥ 0
$userId = (int)$_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

// Fetch posts with likes/comments data
try {
    $postsQuery = $pdo->prepare("
        SELECT 
            posts.*, 
            users.username, 
            users.profile_pic,
            COUNT(DISTINCT likes.id) AS like_count,
            COUNT(DISTINCT comments.id) AS comment_count,
            EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = :user_id) AS has_liked
        FROM posts
        JOIN users ON posts.user_id = users.id
        LEFT JOIN likes ON posts.id = likes.post_id
        LEFT JOIN comments ON posts.id = comments.post_id AND comments.parent_id IS NULL
        GROUP BY posts.id
        ORDER BY posts.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $postsQuery->bindParam(':limit', $limit, PDO::PARAM_INT);
    $postsQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
    $postsQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $postsQuery->execute();
    $posts = $postsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get total posts count (for pagination)
    if ($offset === 0) {
        $totalPosts = $pdo->query("SELECT COUNT(*) as total FROM posts")->fetch()['total'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Error loading posts.");
}



// Output posts (HTML)
foreach ($posts as $post): ?>
    <div class="post card mb-4" id="post-<?= htmlspecialchars($post['id']) ?>">
        <div class="card-body">
            <!-- Post Header -->
            <div class="post-header mb-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <!-- User Avatar -->
                    <div class="user-avatar me-3">
                        <?php if (!empty($post['profile_pic'])): ?>
                            <img src="<?= htmlspecialchars($post['profile_pic']) ?>"
                                alt="<?= htmlspecialchars($post['username']) ?>'s avatar"
                                class="rounded-circle" width="40" height="40">
                        <?php else: ?>
                            <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px;">
                                <?= strtoupper(substr($post['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- User Info -->
                    <div>
                        <h5 class="card-title mb-0"><?= htmlspecialchars($post['username']) ?></h5>
                        <small class="text-muted">
                            <?= date('M j, Y \a\t g:i a', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>

                <!-- Edit/Delete Dropdown (for post owner/admin) -->
                <?php if ($userId === (int)$post['user_id'] || $isAdmin): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item edit-post"
                                    data-post-id="<?= $post['id'] ?>"
                                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <i class="fas fa-edit me-2"></i> Edit
                                </button>
                            </li>
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
            <div class="post-content mb-3">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>

            <!-- Post Image/Video -->
            <?php if (!empty($post['image_path'])): ?>
                <div class="post-image mb-3">
                    <img src="<?= htmlspecialchars($post['image_path']) ?>"
                        alt="Post image"
                        class="img-fluid rounded"
                        style="max-height: 500px; object-fit: contain;">
                </div>
            <?php endif; ?>

            <?php if (!empty($post['video_path'])): ?>
                <div class="post-video mb-3">
                    <video class="img-fluid rounded" controls>
                        <source src="<?= htmlspecialchars($post['video_path']) ?>" type="video/mp4">
                        Your browser does not support videos.
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
                    <button class="btn btn-sm <?= $post['has_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-post"
                        data-post-id="<?= $post['id'] ?>"
                        data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <i class="bi <?= $post['has_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
                        <?= $post['has_liked'] ? 'Unlike' : 'Like' ?>
                    </button>

                    <button class="btn btn-sm btn-outline-secondary toggle-comments"
                        data-post-id="<?= $post['id'] ?>">
                        <i class="fa-solid fa-comment"></i> Comment
                    </button>
                </div>

                <button class="btn btn-sm btn-outline-primary share-btn"
                    data-url="<?= generatePostUrl($post['id']) ?>">
                    <i class="fas fa-share-alt"></i> Share
                </button>
            </div>

            <!-- Comments Section (Initially Hidden) -->
            <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none;">
                <!-- Existing Comments (Loaded via AJAX if needed) -->
                <div class="comments-list mb-3" id="comments-list-<?= $post['id'] ?>"></div>

                <!-- Comment Input Field (Initially Hidden) -->
                <form class="comment-form mt-3" data-post-id="<?= $post['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="input-group">
                        <textarea
                            class="form-control"
                            name="comment"
                            placeholder="Write a comment..."
                            rows="1"
                            required></textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<script>
    $(document).ready(function() {
        // Toggle comments section when "Comment" button is clicked
        $('.toggle-comments').click(function() {
            const postId = $(this).data('post-id');
            const commentsSection = $('#comments-' + postId);

            // Toggle visibility
            commentsSection.toggle();

            // Load comments if not already loaded
            if (commentsSection.is(':visible') && $('#comments-list-' + postId).is(':empty')) {
                loadComments(postId);
            }
        });

        // Load comments via AJAX
        function loadComments(postId) {
            $.ajax({
                url: 'ajax/load_comments.php',
                type: 'GET',
                data: {
                    post_id: postId
                },
                success: function(response) {
                    $('#comments-list-' + postId).html(response);
                },
                error: function(xhr, status, error) {
                    console.error("Error loading comments:", error);
                }
            });
        }

        // Submit comment via AJAX
        $('.comment-form').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const postId = form.data('post-id');

            $.ajax({
                url: 'ajax/add_comment.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    form.find('textarea').val(''); // Clear input
                    loadComments(postId); // Refresh comments
                },
                error: function(xhr, status, error) {
                    console.error("Error posting comment:", error);
                }
            });
        });
    });
</script>