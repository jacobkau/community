<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";


// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10; // Number of posts to load at once
$offset = ($page - 1) * $limit;
$userId = (int)$_SESSION['user_id'];
$isAdmin = $_SESSION['role'] ?? false;

try {
    $postsQuery = $pdo->prepare("
    SELECT 
        posts.id,
        posts.user_id,
        posts.content,
        posts.created_at,
        users.username,
        users.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id AND comments.parent_id IS NULL) AS comment_count,
        (SELECT COUNT(*) FROM shares WHERE shares.post_id = posts.id) AS share_count,
        EXISTS(
            SELECT 1 FROM likes 
            WHERE likes.post_id = posts.id AND likes.user_id = :user_id
        ) AS has_liked
    FROM posts
    JOIN users ON posts.user_id = users.user_id
    ORDER BY posts.created_at DESC
    LIMIT :limit OFFSET :offset
");
    $postsQuery->bindParam(':limit', $limit, PDO::PARAM_INT);
    $postsQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
    $postsQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $postsQuery->execute();
    $posts = $postsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get media for all fetched posts in one query
    if (!empty($posts)) {
        $postIds = array_column($posts, 'id');

        // Fix 1: Remove duplicate post IDs to prevent media query duplication
        $postIds = array_unique($postIds);

        // Fix 2: Use proper placeholder binding
        $placeholders = rtrim(str_repeat('?,', count($postIds)), ',');

        $mediaQuery = $pdo->prepare("
        SELECT id, post_id, file_path, media_type
        FROM post_media
        WHERE post_id IN ($placeholders)
        ORDER BY post_id
    ");
        $mediaQuery->execute($postIds);

        $mediaByPost = [];
        foreach ($mediaQuery->fetchAll(PDO::FETCH_ASSOC) as $media) {
            $mediaByPost[$media['post_id']][] = $media;
        }

        // Attach media without reference (&) issues
        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $mediaByPost[$post['id']] ?? [];
        }
    }

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

        .facebook-media-grid {
            display: grid;
            gap: 2px;
            border-radius: 8px;
            overflow: hidden;
            max-height: 600px;
        }

        /* Single media item */
        .facebook-media-grid[data-media-count="1"] {
            grid-template-columns: 1fr;
            aspect-ratio: 1/1;
        }

        /* Two media items */
        .facebook-media-grid[data-media-count="2"] {
            grid-template-columns: 1fr 1fr;
        }

        /* Three media items */
        .facebook-media-grid[data-media-count="3"] {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .facebook-media-grid[data-media-count="3"] .media-item:first-child {
            grid-row: span 2;
        }

        /* Four or more media items */
        .facebook-media-grid[data-media-count^="4"],
        .facebook-media-grid[data-media-count^="5"],
        .facebook-media-grid[data-media-count^="6"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }

        .facebook-media-object {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: filter 0.2s ease;
        }

        .media-item:hover .facebook-media-object {
            filter: brightness(0.95);
        }

        .remaining-media-count-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .video-container {
            position: relative;
            height: 100%;
        }

        .video-play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 2.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .facebook-media-grid {
                max-height: 400px;
            }

            .facebook-media-grid[data-media-count="3"] {
                grid-template-columns: 1fr;
                grid-template-rows: 2fr 1fr 1fr;
            }

            .remaining-media-count-overlay {
                font-size: 1.5rem;
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
        <?php if (empty($posts) && !isset($_GET['page'])): ?>
            <div class="alert alert-info">No posts yet. Be the first to post something!</div>
        <?php endif; ?>

        <div id="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post card mb-4" id="post-<?= htmlspecialchars($post['id']) ?>"
                    data-post-id="<?= htmlspecialchars($post['id']) ?>"
                    style="color:var(--text-color);background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">

                    <!-- Post Header -->
                    <div class="card-header d-flex justify-content-between align-items-center"
                        style="background-color: var(--card-header-bg);">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php if (!empty($post['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($post['profile_pic']) ?>" class="rounded-circle" width="40"
                                        height="40" alt="<?= htmlspecialchars($post['username']) ?>">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px; color: white;">
                                        <?= strtoupper(substr($post['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($post['username']) ?></h6>
                                <small style="color: var(--text-color);"><?= format_date($post['created_at']) ?></small>
                            </div>
                        </div>

                        <?php if ($userId === (int)$post['user_id'] || $isAdmin): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item edit-post" data-post-id="<?= $post['id'] ?>"
                                            data-csrf="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item delete-post" data-post-id="<?= $post['id'] ?>"
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
                            <div class="post-media-container mt-3">
                                <div class="facebook-media-grid" data-media-count="<?= count($post['media']) ?>">
                                    <?php
                                    $totalMedia = count($post['media']);
                                    foreach ($post['media'] as $index => $media):
                                        $basePath = 'uploads/posts/';
                                        $mediaUrl = $basePath . htmlspecialchars(basename($media['file_path']));
                                        $mediaType = $media['media_type'];
                                        $mediaId = $media['id'] ?? 0;
                                    ?>
                                        <div class="media-item <?= $index === 0 ? 'main-media' : '' ?>"
                                            data-media-id="<?= $mediaId ?>"
                                            style="position: relative;">
                                            <a href="<?= $mediaUrl ?>" class="glightbox" data-gallery="post-<?= $post['id'] ?>">
                                                <?php if ($mediaType === 'image'): ?>
                                                    <img src="<?= $mediaUrl ?>"
                                                        class="facebook-media-object"
                                                        loading="lazy"
                                                        alt="Post image">
                                                <?php elseif ($mediaType === 'video'): ?>
                                                    <div class="video-container">
                                                        <video class="facebook-media-object"
                                                            muted
                                                            controls
                                                            playsinline
                                                            poster="<?= $basePath . htmlspecialchars(basename($media['thumbnail_path'])) ?? '' ?>">
                                                            <source src="<?= $mediaUrl ?>" type="video/mp4">
                                                        </video>
                                                        <div class="video-play-button">
                                                            <i class="fas fa-play"></i>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                            <?php if ($totalMedia > 4 && $index === 3): ?>
                                                <div class="remaining-media-count-overlay">
                                                    +<?= $totalMedia - 4 ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
                                <button
                                    class="btn btn-sm <?= $post['has_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-post"
                                    data-post-id="<?= $post['id'] ?>"
                                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <i
                                        class="bi <?= $post['has_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
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
                            <button class="btn btn-sm btn-outline-secondary share-post" data-post-id="<?= $post['id'] ?>"
                                data-post-url="<?= htmlspecialchars('https://localhost/community/index.php?id=' . $post['id']) ?>">
                                <i class="fas fa-share"></i>
                                <span class="share-count"><?= $post['share_count'] ?? 0 ?></span> Shares
                            </button>
                        </div>

                        <!-- Comments Section (Initially Hidden) -->
                        <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none;">
                            <!-- Existing Comments (Loaded via AJAX if needed) -->
                            <div class="comments-list mb-3" id="comments-list-<?= $post['id'] ?>"></div>

                            <!-- Comment Input Field -->
                            <form class="comment-form mt-3" data-post-id="<?= $post['id'] ?>">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <div class="input-group">
                                    <textarea class="form-control" name="comment" placeholder="Write a comment..." rows="1"
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
            <?php if (!empty($posts) && ($offset + $limit) < $totalPosts): ?>
                <a href="?page=<?= $page + 1 ?>" class="btn btn-primary" id="load-more-btn">
                    Load More Posts
                </a>
            <?php endif; ?>
        </div>
    </div>

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

            function getReactionIcon(reaction) {
                const icons = {
                    'like': 'bi-hand-thumbs-up-fill',
                    'love': 'bi-heart-fill',
                    'haha': 'bi-emoji-laughing-fill',
                    'wow': 'bi-emoji-surprise-fill',
                    'sad': 'bi-emoji-frown-fill',
                    'angry': 'bi-emoji-angry-fill'
                };
                return icons[reaction] || 'bi-hand-thumbs-up-fill';
            }

            function getReactionText(reaction) {
                const texts = {
                    'like': 'Liked',
                    'love': 'Loved',
                    'haha': 'Haha',
                    'wow': 'Wow',
                    'sad': 'Sad',
                    'angry': 'Angry'
                };
                return texts[reaction] || 'Like';
            }
        });

        /*=========================
            SHARE POST FUNCTIONS
            =========================*/

        $(document).on('click', '.share-post', function() {
            const postId = $(this).data('post-id');
            const postUrl = $(this).data('post-url') || `${window.location.origin}/post.php?id=${postId}`;
            const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
            const shareBtn = $(this);

            // Create modal HTML
            const modalHTML = `
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share this post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="share-options row g-3">
                        <div class="col-6 col-md-4">
                            <button class="btn btn-primary w-100 share-option" data-type="internal">
                                <i class="fas fa-share me-2"></i> Our Platform
                            </button>
                        </div>
                        <div class="col-6 col-md-4">
                            <button class="btn btn-facebook w-100 share-option" data-type="facebook">
                                <i class="fab fa-facebook-f me-2"></i> Facebook
                            </button>
                        </div>
                        <div class="col-6 col-md-4">
                            <button class="btn btn-twitter w-100 share-option" data-type="twitter">
                                <i class="fab fa-twitter me-2"></i> Twitter
                            </button>
                        </div>
                        <div class="col-6 col-md-4">
                            <button class="btn btn-linkedin w-100 share-option" data-type="linkedin">
                                <i class="fab fa-linkedin-in me-2"></i> LinkedIn
                            </button>
                        </div>
                        <div class="col-6 col-md-4">
                            <button class="btn btn-whatsapp w-100 share-option" data-type="whatsapp">
                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                            </button>
                        </div>
                        <div class="col-6 col-md-4">
                            <button class="btn btn-telegram w-100 share-option" data-type="telegram">
                                <i class="fab fa-telegram-plane me-2"></i> Telegram
                            </button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label">Or copy link:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${postUrl}" readonly>
                            <button class="btn btn-outline-secondary copy-link-btn" type="button">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    `;

            // Remove any existing modals first
            $('#shareModal').remove();

            // Add modal to DOM
            $('body').append(modalHTML);

            // Initialize and show modal
            const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
            shareModal.show();

            // Handle copy link button
            $(document).on('click', '.copy-link-btn', function() {
                navigator.clipboard.writeText(postUrl);
                $(this).html('<i class="fas fa-check"></i> Copied!');
                setTimeout(() => {
                    $(this).html('<i class="fas fa-copy"></i>');
                }, 2000);
            });

            // Handle share option clicks
            $(document).on('click', '.share-option', function() {
                const shareType = $(this).data('type');

                if (shareType === 'internal') {
                    $.ajax({
                        url: 'ajax/share_post.php',
                        method: 'POST',
                        data: {
                            post_id: postId,
                            share_type: shareType,
                            csrf_token: csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                const currentCount = parseInt(shareBtn.find('.share-count').text());
                                shareBtn.find('.share-count').text(currentCount + 1);
                                toastr.success('Post shared successfully!');
                            } else {
                                toastr.error(response.message);
                            }
                            shareModal.hide();
                        },
                        error: function() {
                            toastr.error('Failed to share post');
                            shareModal.hide();
                        }
                    });
                } else if (shareType === 'copy') {
                    navigator.clipboard.writeText(postUrl);
                    toastr.success('Link copied to clipboard!');
                    shareModal.hide();
                } else {
                    let shareUrl = '';
                    const encodedUrl = encodeURIComponent(postUrl);
                    const encodedText = encodeURIComponent('Check out this post!');

                    switch (shareType) {
                        case 'facebook':
                            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                            break;
                        case 'twitter':
                            shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedText}`;
                            break;
                        case 'linkedin':
                            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
                            break;
                        case 'whatsapp':
                            shareUrl = `https://wa.me/?text=${encodedText}%20${encodedUrl}`;
                            break;
                        case 'telegram':
                            shareUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`;
                            break;
                    }

                    window.open(shareUrl, '_blank', 'width=600,height=400');
                    shareModal.hide();
                }
            });
        });
    </script>
</body>

</html>