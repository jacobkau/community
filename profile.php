<?php
session_start();
require_once __DIR__ . '/config/db.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get profile user ID from URL
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

try {
    // Get profile user data
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM follows WHERE follower_user_id = u.id) AS following_count,
            (SELECT COUNT(*) FROM follows WHERE followed_user_id = u.id) AS followers_count,
            EXISTS (
                SELECT 1 FROM follows 
                WHERE follower_user_id = ? AND followed_user_id = u.id
            ) AS is_following
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $profile_user_id]);
    $profile_user = $stmt->fetch();

    if (!$profile_user) {
        header('Location: users.php');
        exit;
    }

    // Check if current user is viewing their own profile
    $is_own_profile = ($_SESSION['user_id'] == $profile_user_id);

    // Get user posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
            EXISTS (
                SELECT 1 FROM likes 
                WHERE post_id = p.id AND user_id = ?
            ) AS has_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $profile_user_id]);
    $posts = $stmt->fetchAll();

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

    // Get mutual friends count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS mutual_count
        FROM follows f1
        JOIN follows f2 ON f2.followed_user_id = f1.followed_user_id
        WHERE f1.follower_user_id = ? AND f2.follower_user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $profile_user_id]);
    $mutual_friends = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while loading the profile.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile_user['username']) ?> | Social Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.x/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="shortcut icon" href="/uploads/<?= htmlspecialchars($profile_user['username']) ?>" type="image/x-icon">

    <style>
        .cover-container {
            margin-top: 5vh;
            height: 200px;
            background-color: var(--border-color);
            background-size: cover;
            background-position: center;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .profile-img {
            width: 168px;
            height: 168px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            position: absolute;
            bottom: -30px;
            left: 10px;
        }

        .profile-nav {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .profile-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .post-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-card h5 {
            color: var(--text-color);
        }

        .like-btn.active {
            color: #1877f2;
        }

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

        .form-control {
            background-color: var(--border-color);
            color: var(--text-color);
            border-color: var(--input-border);
        }

        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--text-color);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25);
        }

        .text-muted {
            color: var(--secondary-text) !important;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
    </style>
</head>

<?php include __DIR__ . '/includes/header.php'; ?>

<body data-theme="light">
    <!-- Cover Photo -->
    <div class=" container-fluid col-md-9 cover-container"
        style="background-image: url('/community/uploads/default.jpg'); opacity:0.4"></div>

    <div class="container mt-5">
        <!-- Profile Header -->
        <div class="row position-relative mb-5">
            <div class="col-12">
                <?php include "fb/top.php" ?>
            </div>
        </div>
        <!-- Main Content -->
        <?php include "fb/links.php" ?>

    </div>

    <script>
        $(document).ready(function() {
            // Follow/unfollow button handler
            $(document).on('click', '.follow-btn', function() {
                const btn = $(this);
                const targetUserId = btn.data('user-id');
                const action = btn.data('action');

                btn.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                btn.prop('disabled', true);

                $.post('ajax/follow.php', {
                        target_user_id: targetUserId,
                        action: action,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    })
                    .done(function(response) {
                        if (response.success) {
                            if (action === 'follow') {
                                btn.html('<i class="bi bi-check-circle-fill"></i> Following');
                                btn.removeClass('btn-primary').addClass('btn-outline-secondary');
                                btn.data('action', 'unfollow');
                            } else {
                                btn.html('<i class="bi bi-person-plus-fill"></i> Add Friend');
                                btn.removeClass('btn-outline-secondary').addClass('btn-primary');
                                btn.data('action', 'follow');
                            }
                        } else {
                            alert(response.message || 'Operation failed');
                        }
                    })
                    .fail(function() {
                        alert('Request failed. Please try again.');
                    })
                    .always(function() {
                        btn.prop('disabled', false);
                    });
            });


            // Create post form
            $('#createPostForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: 'ajax/create_post.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#createPostModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.message || 'Error creating post');
                        }
                    },
                    error: function() {
                        alert('Request failed. Please try again.');
                    }
                });
            });



            // Comment form submission
            $(document).on('submit', '.add-comment-form', function(e) {
                e.preventDefault();
                const form = $(this);
                const postId = form.data('post-id');
                const commentText = form.find('input[name="comment"]').val();

                $.post('ajax/add_comment.php', {
                        post_id: postId,
                        comment: commentText,
                        csrf_token: CSRF_TOKEN
                    })
                    .done(function(response) {
                        if (response.success) {
                            // Add the new comment to the list
                            const commentHtml = `
                    <div class="d-flex mb-2">
                        <img src="${response.profile_pic}" class="rounded-circle me-2" width="32" height="32">
                        <div>
                            <strong>${response.username}</strong>
                            <p class="mb-1">${response.comment}</p>
                            <small>Just now</small>
                        </div>
                    </div>
                `;

                            form.before(commentHtml);
                            form.find('input').val('');

                            // Update comment count
                            const commentCount = $('#post-' + postId).find('.comment-count');
                            commentCount.text(parseInt(commentCount.text()) + 1);
                        }
                    });
            });

            // Edit profile button
            $(document).on('click', '.edit-profile-btn', function() {
                window.location.href = 'profile.php';
            });


        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.x/dist/js/bootstrap.bundle.min.js"></script>
    <?php include "includes/footer.php"; ?>
    <?php require_once __DIR__ . "/post_functions.php"; ?>

</body>

</html>