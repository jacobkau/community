<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get pagination parameters
$lastPostId = isset($_GET['last_post_id']) ? (int)$_GET['last_post_id'] : null;
$limit = 10; // Number of posts to load at once
$userId = (int)$_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

try {
    $postsQuery = $pdo->prepare("
    SELECT 
        posts.id,
        posts.user_id,
        posts.content,
        posts.created_at,
        users.username,
        users.profile_pic,
        GROUP_CONCAT(DISTINCT post_media.file_path) AS media_paths,
        COUNT(DISTINCT likes.id) AS like_count,
        COUNT(DISTINCT comments.id) AS comment_count,
        COUNT(DISTINCT shares.id) AS share_count,
        EXISTS(
            SELECT 1 FROM likes 
            WHERE likes.post_id = posts.id AND likes.user_id = :user_id
        ) AS has_liked
    FROM posts
    JOIN users ON posts.user_id = users.id
    LEFT JOIN post_media ON posts.id = post_media.post_id
    LEFT JOIN likes ON posts.id = likes.post_id
    LEFT JOIN shares ON posts.id = shares.post_id
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

// Helper function to format date
function format_date($datetime)
{
    $timestamp = strtotime($datetime);
    if (!$timestamp) return htmlspecialchars($datetime);
    return date("M d, Y H:i", $timestamp);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Feed</title>
    <style>
        .reaction-options {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 0;
            background: white;
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }

        .media-grid {
            position: relative;
            min-height: 200px;
        }

        .media-item {
            transition: opacity 0.2s ease;
        }

        .media-more-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            border-radius: 8px;
        }

        .media-upload {
            transition: all 0.2s ease;
        }

        .media-upload:hover {
            border-color: #0d6efd !important;
            color: #0d6efd !important;
        }

        .media-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .post-media a {
            flex: 1 0 48%;
            max-width: 48%;
        }

        @media (min-width: 768px) {
            .post-media a {
                flex: 1 0 23%;
                max-width: 23%;
            }
        }

        .reaction-btn:hover .reaction-options {
            display: flex;
        }

        .reaction-option {
            font-size: 1.5rem;
            margin: 0 3px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .reaction-option:hover {
            transform: scale(1.3) translateY(-5px);
        }

        .comment-box {
            display: none;
        }

        .post-image img {
            max-height: 500px;
            width: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <?php if (empty($posts) && !isset($_GET['last_post_id'])): ?>
            <div class="alert alert-info">No posts yet. Be the first to post something!</div>
        <?php endif; ?>

        <div id="posts-container">
            <?php foreach ($posts as $post):
                $stmtMedia = $pdo->prepare("SELECT media_type, file_path FROM post_media WHERE post_id = ?");
                $stmtMedia->execute([$post['id']]);
                $post['media'] = $stmtMedia->fetchAll(PDO::FETCH_ASSOC); ?>
                <div class="post card mb-4" id="post-<?= htmlspecialchars($post['id']) ?>"
                    data-post-id="<?= htmlspecialchars($post['id']) ?>"
                    style="color:var(--text-color);background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">

                    <!-- Post Header -->
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: var(--card-header-bg);">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php if (!empty($post['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($post['profile_pic']) ?>"
                                        class="rounded-circle" width="40" height="40"
                                        alt="<?= htmlspecialchars($post['username']) ?>">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px; color: white;">
                                        <?= strtoupper(substr($post['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($post['username']) ?></h6>
                                <small class="text-muted"><?= format_date($post['created_at']) ?></small>
                            </div>
                        </div>

                        <?php if ($userId === (int)$post['user_id'] || $isAdmin): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item edit-post"
                                            data-post-id="<?= $post['id'] ?>"
                                            data-csrf="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item delete-post"
                                            data-post-id="<?= $post['id'] ?>"
                                            data-csrf="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Post Content -->
                    <div class="card-body">
                        <div class="post-content mb-3">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>


                        <?php if (!empty($post['media'])): ?>
                            <div class="post-media d-flex flex-wrap gap-2 mt-3" style="position: relative;">
                                <?php
                                $maxToShow = 4;
                                $totalMedia = count($post['media']);
                                foreach ($post['media'] as $index => $media):
                                    if ($index >= $maxToShow) break;
                                    $mediaUrl = htmlspecialchars($media['file_path']);
                                    $mediaType = $media['media_type'];
                                    $mediaId = isset($media['id']) ? htmlspecialchars($media['id']) : $index;
                                ?>
                                    <div data-media-id="<?= $mediaId ?>" class="edit-media-wrapper">
                                        <a href="<?= $mediaUrl ?>" class="glightbox" data-gallery="post-<?= $post['id'] ?>">
                                            <?php if ($mediaType === 'image'): ?>
                                                <img src="<?= $mediaUrl ?>" class="img-fluid rounded" style="height: 200px; object-fit: cover;">
                                            <?php elseif ($mediaType === 'video'): ?>
                                                <video class="rounded" style="height: 200px; object-fit: cover;" muted>
                                                    <source src="<?= $mediaUrl ?>">
                                                </video>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($totalMedia > $maxToShow): ?>
                                    <div class="position-absolute d-flex justify-content-center align-items-center bg-dark bg-opacity-75 text-white rounded"
                                        style="top: 0; left: 0; right: 0; bottom: 0; cursor: pointer;"
                                        onclick="openLightbox('post-<?= $post['id'] ?>')">
                                        +<?= $totalMedia - $maxToShow ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>





                        <!-- Post Stats -->
                        <div class="d-flex justify-content-between mb-3" style="color:var(--text-color)">
                            <div>
                                <span class="me-3">
                                    <i class="fas fa-thumbs-up"></i> <?= $post['like_count'] ?>
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-comment"></i> <?= $post['comment_count'] ?>
                                </span>
                                <span>
                                    <i class="fas fa-share"></i> <?= $post['share_count'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Post Actions -->
                        <div class="d-flex justify-content-between border-top border-bottom py-2 mb-3">
                            <div class="reaction-btn position-relative">
                                <button class="btn btn-sm <?= $post['has_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-post"
                                    data-post-id="<?= $post['id'] ?>"
                                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <i class="bi <?= $post['has_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
                                    <?= $post['has_liked'] ? 'Unlike' : 'Like' ?>
                                    <span class="reaction-count"></span>
                                </button>
                                <div class="reaction-options">
                                    <div class="reaction-option" data-reaction="like">👍</div>
                                    <div class="reaction-option" data-reaction="love">❤️</div>
                                    <div class="reaction-option" data-reaction="haha">😆</div>
                                    <div class="reaction-option" data-reaction="wow">😮</div>
                                    <div class="reaction-option" data-reaction="sad">😢</div>
                                    <div class="reaction-option" data-reaction="angry">😡</div>
                                </div>

                            </div>
                            <button class="btn btn-sm btn-outline-secondary toggle-comments"
                                data-post-id="<?= $post['id'] ?>">
                                <i class="fas fa-comment"></i> <?= $post['comment_count'] ?> Comments
                            </button>
                            <button class="btn btn-sm btn-outline-secondary share-post"
                                data-post-id="<?= $post['id'] ?>">
                                <i class="fas fa-share"></i> Share
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
        </div>

        <div class="text-center py-3" id="load-more-container">
            <?php if (!empty($posts)): ?>
                <button class="btn btn-primary" id="load-more-btn"
                    data-last-post-id="<?= end($posts)['id'] ?>">
                    Load More Posts
                </button>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>
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
    });
    $(document).ready(function() {
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

        // Show reaction options on hover
        $('.reaction-btn').hover(
            function() {
                $(this).find('.reaction-options').addClass('show');
            },
            function() {
                $(this).find('.reaction-options').removeClass('show');
            }
        );

        // Handle reaction selection
        $('.reaction-option').click(function(e) {
            e.stopPropagation();
            const reactionBtn = $(this).closest('.reaction-btn');
            const postId = reactionBtn.closest('.post').data('post-id');
            const reaction = $(this).data('reaction');

            // Update UI immediately
            reactionBtn.find('.like-post i').removeClass('bi-hand-thumbs-up bi-hand-thumbs-up-fill')
                .addClass(getReactionIcon(reaction));
            reactionBtn.find('.like-post').text(getReactionText(reaction));

            // Hide options
            reactionBtn.find('.reaction-options').removeClass('show');

            // Send to server
            $.ajax({
                url: 'ajax/save_reaction.php',
                method: 'POST',
                data: {
                    post_id: postId,
                    reaction: reaction,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        // Update reaction count
                        reactionBtn.find('.reaction-count').text(response.total_reactions);

                        // Update like button state
                        reactionBtn.find('.like-post')
                            .removeClass('btn-outline-primary')
                            .addClass('btn-primary');
                    }
                }
            });
        });




    });
</script>