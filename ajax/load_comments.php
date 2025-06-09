<?php
session_start();
require_once __DIR__ . "/../config/db.php";


if (!isset($_SESSION['user_id']) || !isset($_GET['post_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized access']));
}
$postId = (int)$_GET['post_id'];
$userId = (int)$_SESSION['user_id'];
$isAdmin = $_SESSION['role'] ?? false;

try {
    // Fetch main comments
    $mainCommentsStmt = $pdo->prepare("
    
        SELECT 
            c.id, c.content, c.created_at, c.edited,
            u.user_id AS user_id, u.username, u.profile_pic,
            COUNT(cl.id) AS like_count,
            SUM(cl.user_id = :user_id) AS user_liked
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN comment_likes cl ON c.id = cl.comment_id
        WHERE c.post_id = :post_id AND c.parent_id IS NULL
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $mainCommentsStmt->execute([':post_id' => $postId, ':user_id' => $userId]);
    $mainComments = $mainCommentsStmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($mainComments as $comment) {
        $isLiked = $comment['user_liked'] > 0;
        $likeClass = $isLiked ? 'btn-primary' : 'btn-outline-primary';
        $likeText = $isLiked ? 'Liked' : 'Like';

      
        // Fetch replies for this comment
        $repliesStmt = $pdo->prepare("
            SELECT 
                c.id,
                c.content,
                c.created_at,
                c.edited,
                u.user_id AS user_id,
                u.username,
                u.profile_pic,
                COUNT(cl.id) AS like_count,
                SUM(cl.user_id = :user_id) AS user_liked
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            LEFT JOIN comment_likes cl ON c.id = cl.comment_id
            WHERE c.parent_id = :parent_id
            GROUP BY c.id
            ORDER BY c.created_at ASC
        ");
        $repliesStmt->execute([':parent_id' => $comment['id'], ':user_id' => $userId]);
        $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
        <style>
            .comment-system {
                max-width: 650px;
                margin: 0 auto;
                border: 1px solid var(--border-color);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            }
            

            .comment-bubble {
                background-color:var(--border-color);
                border-radius: 18px;
                padding: 8px 12px;
                position: relative;
                color: var(--text-color);
                margin-left: 8px;
            }

            .comment-bubble::after {
                content: '';
                position: absolute;
                left: -8px;
                top: 12px;
                width: 0;
                height: 0;
                border-top: 8px solid transparent;
                border-bottom: 8px solid transparent;
                border-right: 8px solid var(--border-color);
                color:var(--border-color);
            }

            .comment-actions .action-btn {
                color:var(--text-color);
                font-size: 0.875rem;                
                padding: 6px 8px;
                transition: all 0.2s ease;
            }

            .comment-actions .action-btn:hover {
                color: #216fdb;
                text-decoration: none;
            }

            .replies {
                border-left: 2px solid var(--border-color);
                margin-left: 32px;
                padding-left: 16px;
            }
            .reply{
                background-color:var(--card-bg);
            }

            .reply .comment-bubble {
               background-color: var(--border-color);
               border:1px solid var(--border-color);
            }

            .reply-form {
                margin-top: 8px;
                transition: all 0.3s ease;
                max-height: 50;
                overflow: hidden;
            }

            .reply-form.visible {
                max-height: 250px;
            }

            .reply-form .form-control {
                border-radius: 18px;
                background-color: #f0f2f5;
                border: none;
                padding: 8px 12px;
            }

            .reply-form .btn-primary {
                border-radius: 18px;
                padding: 6px 16px;
                font-weight: 600;
            }

            .timestamp {
                color: #65676b;
                font-size: 0.8125rem;
            }

            .avatar {
                border: 1px solid #dddfe2;
                object-fit: cover;
            }

            .like-active {
                background-color: rgba(33, 111, 219, 0.1) !important;
                border-radius: 4px;
                animation: like-pulse 0.3s ease;
            }

            @keyframes like-pulse {
                0% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.1);
                }

                100% {
                    transform: scale(1);
                }
            }

            .text-primary {
                color: #216fdb !important;
                font-weight: 600 !important;
            }
        </style>

        <div class="comment-system">
            <div class="comment" id="main-comment-<?= $comment['id'] ?>" style="color:var(--text-color);background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                <div class="d-flex align-items-start gap-2">
                    <div class="avatar-container">
                        <?php if (!empty($comment['profile_pic'])): ?>
                            <img src="<?= htmlspecialchars($comment['profile_pic']) ?>"
                                class="avatar rounded-circle"
                                width="40"
                                height="40"
                                alt="<?= htmlspecialchars($comment['username']) ?>">
                        <?php else: ?>
                            <div class="avatar rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                style="width:40px;height:40px">
                                <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow-1">
                        <div class="comment-bubble">
                            <div class="d-flex align-items-center mb-1">
                                <div class="fw-bold me-2"><?= htmlspecialchars($comment['username']) ?></div>
                                <div class="timestamp" style="color:var(--text-color)">
                                    <?= date('M j, Y \a\t g:i a', strtotime($comment['created_at'])) ?>
                                    <?= $comment['edited'] ? '· Edited' : '' ?>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </div>

                        <div class="comment-actions mt-1 d-flex align-items-center gap-2">
                            <button class="btn btn-sm <?= $comment['user_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> <?= $likeClass ?> like-comment"
                                data-comment-id="<?= $comment['id'] ?>"
                                data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <i class="fas fa-thumbs-up"></i>
                                <span class="like-text"><?= $likeText ?></span>
                            </button>
                            <span class="text-muted">·</span>
                            <button class="action-btn btn btn-link p-0 toggle-reply-form"
                                data-comment-id="<?= $comment['id'] ?>">
                                <i class="fas fa-reply"></i> Reply
                            </button>

                            <span class="text-muted">·</span>
                            <span class="timestamp">
                                <?= $comment['like_count'] ?> likes
                            </span>

                            <?php if ($comment['user_id'] === $userId || $isAdmin): ?>
                                <div class="dropdown ms-auto">
                                    <button class="btn btn-link p-0" style="color:var(--text-color)"
                                        data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm">
                                        <li>
                                            <button class="dropdown-item edit-comment"
                                                data-comment-id="<?= $comment['id'] ?>">
                                                <i class="fas fa-edit me-2"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger delete-comment"
                                                data-comment-id="<?= $comment['id'] ?>"
                                                data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <i class="fas fa-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        </div>
                        <!-- Reply Form (Hidden Initially) -->
                        <form class="reply-form mt-2" id="reply-form-<?= $comment['id'] ?>" style="display: none;"
                            data-parent-id="<?= $comment['id'] ?>">
                            <input type="hidden" name="post_id" value="<?= $postId ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <div class="input-group">
                                <textarea class="form-control" name="content" placeholder="Write a reply..." required></textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>


                        <!-- Replies -->
                        <div class="replies mt-2" >
                            <?php
                            $repliesStmt = $pdo->prepare("
                        SELECT 
                            c.id, c.content, c.created_at, c.edited,
                            u.user_id AS user_id, u.username, u.profile_pic,
                            COUNT(DISTINCT cl.id) AS like_count,
                            SUM(cl.user_id = :user_id) AS user_liked
                        FROM comments c
                        JOIN users u ON c.user_id = u.user_id
                        LEFT JOIN comment_likes cl ON c.id = cl.comment_id
                        WHERE c.parent_id = :parent_id
                        GROUP BY c.id
                        ORDER BY c.created_at DESC
                    ");
                            $repliesStmt->execute([':parent_id' => $comment['id'], ':user_id' => $userId]);
                            $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($replies as $reply): ?>
                                <div class="reply mb-2" id="comment-<?= $reply['id'] ?>">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="avatar-container">
                                            <?php if (!empty($reply['profile_pic'])): ?>
                                                <img src="<?= htmlspecialchars($reply['profile_pic']) ?>"
                                                    class="avatar rounded-circle"
                                                    width="32"
                                                    height="32"
                                                    alt="<?= htmlspecialchars($reply['username']) ?>">
                                            <?php else: ?>
                                                <div class="avatar rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                                    style="width:32px;height:32px">
                                                    <?= strtoupper(substr($reply['username'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-grow-1">
                                            <div class="comment-bubble">
                                                <div class="d-flex align-items-center mb-1">
                                                    <div class="fw-bold me-2"><?= htmlspecialchars($reply['username']) ?></div>
                                                    <div class="timestamp" style="color:var(--text-color)">
                                                        <?= date('M j, Y \a\t g:i a', strtotime($reply['created_at'])) ?>
                                                        <?= $reply['edited'] ? '· Edited' : '' ?>
                                                    </div>
                                                </div>
                                                <div class="comment-content">
                                                    <?= nl2br(htmlspecialchars($reply['content'])) ?>
                                                </div>
                                            </div>

                                            <div class="comment-actions mt-1 d-flex align-items-center gap-2">
                                                <button class="action-btn btn btn-link p-0 like-comment"
                                                    data-comment-id="<?= $reply['id'] ?>"
                                                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <span class="<?= $reply['user_liked'] ? 'text-primary' : '' ?>">
                                                        <i class="fas fa-thumbs-up"></i> <?= $reply['user_liked'] ? 'Liked' : 'Like' ?>
                                                    </span>
                                                </button>
                                                <span class="text-muted">·</span>
                                                <span class="timestamp">
                                                    <?= $reply['like_count'] ?> likes
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
} catch (PDOException $e) {
    http_response_code(500);
    die("Error loading comments: " . $e->getMessage());
}
?>
<script>
    $(document).on('click', '.toggle-reply-form', function() {
        const commentId = $(this).data('comment-id');
        $(`#reply-form-${commentId}`).toggle();
    });

    // Reply Submission Handler
    $(document).on('submit', '.reply-form', function(e) {
        e.preventDefault();
        const $form = $(this);
        const postId = $form.find('[name="post_id"]').val();
        const parentId = $form.data('parent-id');
        const csrfToken = $form.find('[name="csrf_token"]').val();
        const content = $form.find('textarea').val();

        $.ajax({
            url: 'ajax/reply_comment.php',
            type: 'POST',
            data: {
                post_id: postId,
                parent_id: parentId,
                content: content,
                csrf_token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $form[0].reset();
                    $form.hide();
                    // Add the new reply to the DOM
                    $(`#comment-${parentId} .replies`).append(response.html);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseJSON?.error || 'Unknown error');
            }
        });
    });

    $(document).on('click', '.like-comment', function() {
        const $button = $(this);
        const commentId = $button.data('comment-id');
        const csrfToken = $button.data('csrf-token');
        const isLiked = $button.hasClass('btn-primary');

        $.ajax({
            url: 'ajax/like_comment.php',
            type: 'POST',
            data: {
                comment_id: commentId,
                action: isLiked ? 'unlike' : 'like',
                csrf_token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $button.toggleClass('btn-primary btn-outline-primary');

                    // Toggle the text
                    const newText = isLiked ? 'Like' : 'Liked';
                    $button.find('.like-text').text(newText);

                    // Update like count
                    $button.find('.like-count').text(response.new_count);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseJSON?.error || 'Unknown error');
            }
        });
    });



    // Handle reply form submission
    document.querySelectorAll('.reply-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const commentId = this.dataset.parentId;

            try {
                const response = await fetch('ajax/post_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        post_id: formData.get('post_id'),
                        parent_id: commentId,
                        content: formData.get('content'),
                        csrf_token: formData.get('csrf_token')
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Reset and hide form
                    this.reset();
                    this.classList.remove('visible');
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
</script>