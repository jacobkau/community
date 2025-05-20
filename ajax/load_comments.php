<?php
require_once __DIR__ . "/../config/db.php";
session_start();

// Validate session and input
if (!isset($_SESSION['user_id']) || !isset($_GET['post_id'])) {
    http_response_code(403);
    die("Unauthorized access");
}

$postId = (int)$_GET['post_id'];
$userId = (int)$_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

try {
    // Fetch main comments (parent_id IS NULL)
    $mainCommentsStmt = $pdo->prepare("
        SELECT 
            c.id,
            c.content,
            c.created_at,
            c.edited,
            u.id AS user_id,
            u.username,
            u.profile_pic,
            COUNT(cl.id) AS like_count,
            SUM(cl.user_id = :user_id) AS user_liked
        FROM comments c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN comment_likes cl ON c.id = cl.comment_id
        WHERE c.post_id = :post_id AND c.parent_id IS NULL
        GROUP BY c.id
        ORDER BY c.created_at ASC
    ");

    $mainCommentsStmt->execute([':post_id' => $postId, ':user_id' => $userId]);
    $mainComments = $mainCommentsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mainComments as $comment) {
        // Fetch replies for this comment
        $repliesStmt = $pdo->prepare("
            SELECT 
                c.id,
                c.content,
                c.created_at,
                c.edited,
                u.id AS user_id,
                u.username,
                u.profile_pic,
                COUNT(cl.id) AS like_count,
                SUM(cl.user_id = :user_id) AS user_liked
            FROM comments c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_likes cl ON c.id = cl.comment_id
            WHERE c.parent_id = :parent_id
            GROUP BY c.id
            ORDER BY c.created_at ASC
        ");
        $repliesStmt->execute([':parent_id' => $comment['id'], ':user_id' => $userId]);
        $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Main Comment -->
<div class="comment mb-3 p-3 border rounded" id="comment-<?= $comment['id'] ?>">
    <div class="d-flex align-items-center mb-2">
        <!-- User Avatar -->
        <div class="user-avatar me-2">
            <?php if (!empty($comment['profile_pic'])): ?>
            <img src="<?= htmlspecialchars($comment['profile_pic']) ?>" class="rounded-circle" width="32" height="32"
                alt="<?= htmlspecialchars($comment['username']) ?>'s avatar">
            <?php else: ?>
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                style="width: 32px; height: 32px;">
                <?= strtoupper(substr($comment['username'], 0, 1)) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- User Info -->
        <div class="flex-grow-1">
            <strong><?= htmlspecialchars($comment['username']) ?></strong>
            <small class="text-muted ms-2">
                <?= date('M j, Y g:i a', strtotime($comment['created_at'])) ?>
                <?= $comment['edited'] ? '(edited)' : '' ?>
            </small>
        </div>
    </div>

    <!-- Comment Content -->
    <div class="comment-content mb-2 ps-4">
        <?= nl2br(htmlspecialchars($comment['content'])) ?>
    </div>

    <!-- Comment Actions -->
    <div class="comment-actions d-flex gap-2 align-items-center">
        <!-- Like Button -->
        <button class="btn btn-sm <?= $comment['user_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-comment"
            data-comment-id="<?= $comment['id'] ?>" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <i class="fas fa-thumbs-up"></i>
            <span class="like-count"><?= $comment['like_count'] ?></span>
        </button>

        <!-- Reply Button -->
        <button class="btn btn-sm btn-outline-secondary toggle-reply-form" data-comment-id="<?= $comment['id'] ?>">
            <i class="fas fa-reply"></i> Reply
        </button>
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


        <!-- Edit/Delete (Owner or Admin) -->
        <?php if ($comment['user_id'] === $userId || $isAdmin): ?>
        <div class="dropdown">
            <button class="btn btn-sm btn-link text-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <button class="dropdown-item edit-comment" data-comment-id="<?= $comment['id'] ?>">
                        <i class="fas fa-edit me-2"></i> Edit
                    </button>
                </li>
                <li>
                    <button class="dropdown-item delete-comment text-danger" data-comment-id="<?= $comment['id'] ?>"
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

    <!-- Replies List -->
    <div class="replies ms-4 mt-3">
        <?php foreach ($replies as $reply): ?>
        <div class="reply mb-2 p-2 border rounded bg-light" id="comment-<?= $reply['id'] ?>">
            <div class="d-flex align-items-center mb-2">
                <!-- Reply User Avatar -->
                <div class="user-avatar me-2">
                    <?php if (!empty($reply['profile_pic'])): ?>
                    <img src="<?= htmlspecialchars($reply['profile_pic']) ?>" class="rounded-circle" width="28"
                        height="28" alt="<?= htmlspecialchars($reply['username']) ?>'s avatar">
                    <?php else: ?>
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px;">
                        <?= strtoupper(substr($reply['username'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Reply User Info -->
                <div class="flex-grow-1">
                    <strong><?= htmlspecialchars($reply['username']) ?></strong>
                    <small class="text-muted ms-2">
                        <?= date('M j, Y g:i a', strtotime($reply['created_at'])) ?>
                        <?= $reply['edited'] ? '(edited)' : '' ?>
                    </small>
                </div>
            </div>

            <!-- Reply Content -->
            <div class="comment-content mb-2 ps-3">
                <?= nl2br(htmlspecialchars($reply['content'])) ?>
            </div>

            <!-- Reply Actions -->
            <div class="comment-actions d-flex gap-2 align-items-center">
                <!-- Like Button -->
                <button
                    class="btn btn-sm <?= $reply['user_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-comment"
                    data-comment-id="<?= $reply['id'] ?>"
                    data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <i class="fas fa-thumbs-up"></i>
                    <span class="like-count"><?= $reply['like_count'] ?></span>
                </button>

                <!-- Edit/Delete (Owner or Admin) -->
                <?php if ($reply['user_id'] === $userId || $isAdmin): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-link text-dark dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button class="dropdown-item edit-comment" data-comment-id="<?= $reply['id'] ?>">
                                <i class="fas fa-edit me-2"></i> Edit
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item delete-comment text-danger"
                                data-comment-id="<?= $reply['id'] ?>"
                                data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <i class="fas fa-trash me-2"></i> Delete
                            </button>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
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


// Post Edit Handler
$(document).on('click', '.edit-post', function() {
    const postId = $(this).data('post-id');
    const $postContent = $('#post-content-' + postId);
    const originalContent = $postContent.data('original-content');

    if (!$(this).hasClass('editing')) {
        // Enter edit mode
        $(this).addClass('editing').html('<i class="fas fa-save"></i> Save');
        $postContent.data('original-content', $postContent.html())
            .html(`<textarea class="form-control">${originalContent}</textarea>`);
    } else {
        // Save changes
        const newContent = $postContent.find('textarea').val();
        $.ajax({
            url: 'edit_post.php',
            type: 'POST',
            data: {
                post_id: postId,
                content: newContent,
                csrf_token: $(this).data('csrf-token')
            },
            success: function() {
                $postContent.html(nl2br(newContent));
                $(this).removeClass('editing').html('<i class="fas fa-edit"></i> Edit');
                $('#post-' + postId).append('<small class="text-muted ms-2">(edited)</small>');
            }.bind(this)
        });
    }
});

// Comment CRUD Handlers (Similar pattern for edit/delete/reply)
$(document).on('click', '.edit-comment', function() {
    // Similar logic to post editing
});


// Updated Comment Like Handler
$(document).on('click', '.like-comment', function() {
    const $button = $(this);
    const commentId = $button.data('comment-id');
    const csrfToken = $button.data('csrf-token');
    const isLiked = $button.hasClass('btn-primary');

    $.ajax({
        url: 'ajax/like_comment.php', // Ensure correct path
        type: 'POST',
        data: {
            comment_id: commentId,
            action: isLiked ? 'unlike' : 'like',
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                $button.toggleClass('btn-primary btn-outline-primary');
                $button.find('.like-count').text(response.new_count);
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseJSON?.error || 'Unknown error');
        }
    });
});
// Reply Form Toggle
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
</script>