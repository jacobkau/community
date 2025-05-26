<?php

$userId = (int)$_SESSION['user_id'];
?>
<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Profile Navigation -->
        <div class="profile-nav p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
            <ul class="nav nav-pills" id="profileTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#posts" data-tab="posts"><i class="bi bi-newspaper"></i> Posts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about" data-tab="about"><i class="bi bi-info-circle"></i> About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#friends" data-tab="friends"><i class="bi bi-people"></i> Friends</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#photos" data-tab="photos"><i class="bi bi-images"></i> Photos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#activities" data-tab="activities"><i class="bi bi-activity"></i> Activites</a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div id="posts-content" class="tab-content active">
            <?php if ($is_own_profile): ?>
                <!-- Create Post Card -->
                <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                    <div class="d-flex mb-3">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?= htmlspecialchars($profile_user['profile_pic']) ?>"
                                alt="<?= htmlspecialchars($profile_user['username']) ?>'s avatar"
                                class="profile-pic mb-3 rounded-circle"
                                width="40"
                                height="40">
                        <?php else: ?>
                            <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                style="width: 40px; height: 40px;">
                                <?= strtoupper(substr($profile_user['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <button class="form-control text-start rounded-pill bg-light border-0"
                            data-bs-toggle="modal"
                            data-bs-target="#createPostModal">
                            What's on your mind?
                        </button>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <button class="btn btn-sm" style="color:var(--text-color)">
                            <i class="bi bi-camera-fill text-danger"></i> Photo/Video
                        </button>
                        <button class="btn btn-sm" style="color:var(--text-color)">
                            <i class="bi bi-emoji-smile text-warning"></i> Feeling/Activity
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Posts -->
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>

                    <div class="profile-card p-3 mb-4 post-card" id="post-<?= $post['id'] ?>" style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                        <div class="d-flex justify-content-between align-items-center mb-3" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                            <div class="d-flex align-items-center">
                                <img src="<?= htmlspecialchars($post['profile_pic'] ?? '/assets/default-avatar.png') ?>"
                                    class="rounded-circle me-2"
                                    width="40"
                                    height="40"
                                    alt="<?= htmlspecialchars($post['username']) ?>">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($post['username']) ?></h6>
                                    <small class="" style="color:var(--text-color)">
                                        <?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php if ($userId === (int)$post['user_id'] || $isAdmin): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="postDropdown-<?= $post['id'] ?>">
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

                        <div class="mb-3">
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
                <?php endforeach; ?>

            <?php else: ?>
                <div class="profile-card p-5 text-center" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                    <i class="bi bi-newspaper display-4 mb-3"></i>
                    <h5>No posts to show</h5>
                    <p style="color:var(--text-color)">
                        <?= $is_own_profile ? "When you share photos or updates, they'll appear here." : "This user hasn't posted anything yet." ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- About Tab Content -->
        <div id="about-content" class="tab-content">
            <!-- Intro Card -->
            <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                <h5 class="fw-bold mb-3">Intro</h5>
                <div class="mb-3">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <span>Joined <?= date('F Y', strtotime($profile_user['created_at'])) ?></span>
                </div>
                <?php if (!empty($profile_user['bio'])): ?>
                    <div class="mb-3">
                        <i class="bi bi-pencil-fill me-2"></i>
                        <span><?= htmlspecialchars($profile_user['bio']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($is_own_profile): ?>
                    <a href="settings.php" class="btn w-100">Edit Details</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Friends Tab Content -->
        <div id="friends-content" class="tab-content">
            <div class="profile-card p-3" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Friends</h5>
                    <a href="#" class="text-decoration-none">See All Friends</a>
                </div>

                <?php if (!empty($friends)): ?>
                    <p class="text-muted small mb-3">
                        <?= number_format(count($friends)) ?> friends
                        <?php if ($mutual_friends > 0 && !$is_own_profile): ?>
                            • <?= $mutual_friends ?> mutual
                        <?php endif; ?>
                    </p>
                    <div class="row g-2">
                        <?php foreach (array_slice($friends, 0, 6) as $friend): ?>
                            <div class="col-6">
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($friend['profile_pic'] ?? 'https://via.placeholder.com/300') ?>" class="img-fluid rounded" alt="Friend">
                                    <div class="small mt-1"><?= htmlspecialchars($friend['username']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-color)">No friends to show.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Photos Tab Content -->
        <div id="photos-content" class="tab-content">
            <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Photos</h5>
                    <a href="#" class="text-decoration-none">See All Photos</a>
                </div>

                <?php if (!empty($user_photos)): ?>
                    <div class="row g-2">
                        <?php foreach (array_slice($user_photos, 0, 9) as $photo): ?>
                            <div class="col-4">
                                <img src="<?= htmlspecialchars($photo['url']) ?>" class="img-fluid rounded" alt="Photo">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-color)">No photos available.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="activities-content" class="tab-content">
            <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Activities</h5>
                    <a href="#" class="text-decoration-none">See All Activities</a>
                </div>
                <!-- Activity Feed -->
                <?php if (!empty($activities)): ?>
                    <div class="card mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px;color:var(--text-color)">
                        <div class="card-body">
                            <h5 class="card-title">Recent Activity</h5>
                            <ul class="list-unstyled">
                                <?php foreach ($activities as $activity): ?>
                                    <li class="mb-2">
                                        <small class="text-muted"><?= htmlspecialchars($activity['activity_type']) ?>:</small><br>
                                        <?= htmlspecialchars($activity['activity_content']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                <?php else: ?>
                    <p style="color:var(--text-color)">No activities available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Intro Card (Duplicate for right column) -->
        <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
            <h5 class="fw-bold mb-3">Intro</h5>
            <div class="mb-3">
                <i class="bi bi-info-circle-fill me-2"></i>
                <span>Joined <?= date('F Y', strtotime($profile_user['created_at'])) ?></span>
            </div>
            <?php if (!empty($profile_user['bio'])): ?>
                <div class="mb-3">
                    <i class="bi bi-pencil-fill me-2"></i>
                    <span><?= htmlspecialchars($profile_user['bio']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($is_own_profile): ?>
                <a href="settings.php" class="btn btn-outline-secondary w-100">Edit Details</a>
            <?php endif; ?>
        </div>

        <!-- Photos Card (Duplicate for right column) -->
        <div class="profile-card p-3 mb-4" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Photos</h5>
                <a href="#" class="text-decoration-none">See All Photos</a>
            </div>

            <?php if (!empty($user_photos)): ?>
                <div class="row g-2">
                    <?php foreach (array_slice($user_photos, 0, 3) as $photo): ?>
                        <div class="col-4">
                            <img src="<?= htmlspecialchars($photo['url']) ?>" class="img-fluid rounded" alt="Photo">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--text-color)">No photos available.</p>
            <?php endif; ?>
        </div>

        <!-- Friends Card (Duplicate for right column) -->
        <div class="profile-card p-3" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Friends</h5>
                <a href="#" class="text-decoration-none">See All Friends</a>
            </div>

            <?php if (!empty($friends)): ?>
                <p class="text-muted small mb-3">
                    <?= number_format(count($friends)) ?> friends
                    <?php if ($mutual_friends > 0 && !$is_own_profile): ?>
                        • <?= $mutual_friends ?> mutual
                    <?php endif; ?>
                </p>
                <div class="row g-2">
                    <?php foreach (array_slice($friends, 0, 6) as $friend): ?>
                        <div class="col-6">
                            <div class="mb-2">
                                <img src="<?= htmlspecialchars($friend['profile_pic'] ?? 'https://via.placeholder.com/300') ?>" class="img-fluid rounded" alt="Friend">
                                <div class="small mt-1"><?= htmlspecialchars($friend['username']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--text-color)">No friends to show.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px">
            <div class="modal-header">
                <h5 class="modal-title">Create Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createPostForm">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="5" placeholder="What's on your mind?"></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="file" class="form-control" name="post_image" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Post</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabs = document.querySelectorAll('#profileTabs .nav-link');
        const tabContents = document.querySelectorAll('.tab-content');

        function switchTab(tabId) {
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.toggle('active', tab.getAttribute('data-tab') === tabId);
            });

            // Update active content
            tabContents.forEach(content => {
                content.classList.toggle('active', content.id === `${tabId}-content`);
            });

            // Update URL hash without scrolling
            history.pushState(null, null, `#${tabId}`);
        }

        // Click event for tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                switchTab(tabId);
            });
        });

        // Check URL hash on page load
        if (window.location.hash) {
            const tabId = window.location.hash.substring(1);
            if (tabId) {
                switchTab(tabId);
            }
        }

        // Listen for back/forward navigation
        window.addEventListener('popstate', function() {
            if (window.location.hash) {
                const tabId = window.location.hash.substring(1);
                if (tabId) {
                    switchTab(tabId);
                }
            }
        });

        // Like post functionality
        document.querySelectorAll('.like-post').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const csrfToken = this.getAttribute('data-csrf-token');
                const likeCountElement = this.closest('.post-card').querySelector('.like-count');
                const icon = this.querySelector('i');

                fetch('ajax/like_post', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({
                            post_id: postId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            likeCountElement.textContent = data.like_count;
                            if (data.action === 'liked') {
                                icon.className = 'bi bi-hand-thumbs-up-fill';
                                this.textContent = ' Unlike';
                            } else {
                                icon.className = 'bi bi-hand-thumbs-up';
                                this.textContent = ' Like';
                            }
                        }
                    });
            });
        });


        // Post deletion
        document.querySelectorAll('.delete-post').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this post?')) {
                    const postId = this.getAttribute('data-post-id');
                    const csrfToken = this.getAttribute('data-csrf-token');

                    fetch('ajax/delete_post', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfToken
                            },
                            body: JSON.stringify({
                                post_id: postId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById(`post-${postId}`).remove();
                            }
                        });
                }
            });
        });

        // Create post form
        document.getElementById('createPostForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('ajax/create_post', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': '<?= htmlspecialchars($_SESSION['csrf_token']) ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('createPostModal')).hide();

                        // Reset form
                        this.reset();

                        // Reload posts or prepend new post
                        window.location.reload();
                    }
                });
        });
    });



    /*===============
        ADDED
        =======================*/
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

<style>
    /* Tab styling */
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* Facebook-like active tab styling */
    .nav-pills .nav-link.active {
        background-color: var(--primary-color);
        color: white;
        border-radius: 5px;
    }

    .nav-pills .nav-link {
        color: var(--text-color);
        transition: all 0.2s ease;
    }

    .nav-pills .nav-link:hover {
        background-color: var(--hover-bg-color);
        border-radius: 5px;
    }

    /* Post card styling */
    .post-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .post-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
</style>