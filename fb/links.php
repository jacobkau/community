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
                    <?php
                    // Fetch comments for the current post
                    $stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic
                               FROM comments c
                               JOIN users u ON c.user_id = u.id
                               WHERE c.post_id = ?
                               ORDER BY c.created_at ASC");
                    $stmt->execute([$post['id']]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

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
                            <?php if (($_SESSION['user_id'] ?? null) == $post['user_id'] || ($_SESSION['is_admin'] ?? false)): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        id="postDropdown-<?= $post['id'] ?>"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
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

                        <?php if (!empty($post['image'])): ?>
                            <img src="/uploads/posts/<?= htmlspecialchars($post['image']) ?>"
                                class="img-fluid rounded mb-3"
                                alt="Post image">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between small border-top border-bottom py-2 mb-2">
                            <div>
                                <span class="like-count"><?= $post['like_count'] ?></span> likes
                            </div>
                            <div>
                                <span class="comment-count"><?= $post['comment_count'] ?></span> comments
                            </div>
                        </div>

                        <div class="d-flex justify-content-between" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px;">
                            <button class="btn btn-sm bg-primary text-white border border-primary like-post"
                                data-post-id="<?= $post['id'] ?>"
                                data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <i class="bi <?= $post['has_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
                                <?= $post['has_liked'] ? 'Unlike' : 'Like' ?>
                            </button>
                            <button class="btn btn-sm flex-grow-1 comment-btn" style="color:var(--text-color)"
                                data-post-id="<?= $post['id'] ?>">
                                <i class="bi bi-chat-left-text"></i> Comment
                            </button>
                            <button class="btn btn-sm flex-grow-1 share-btn" style="color:var(--text-color)">
                                <i class="bi bi-share"></i> Share
                            </button>
                        </div>

                        <!-- Comment section -->
                        <div class="mt-3">
                            <h6 class="mb-2">Comments</h6>

                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="d-flex mb-2">
                                        <img src="<?= htmlspecialchars($comment['profile_pic'] ?? '/assets/default-avatar.png') ?>"
                                            class="rounded-circle me-2"
                                            width="32"
                                            height="32"
                                            alt="<?= htmlspecialchars($comment['username']) ?>">
                                        <div>
                                            <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                            <small style="color:var(--text-color)"><?= date('M j, Y \a\t g:i A', strtotime($comment['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:var(--text-color)">No comments yet.</p>
                            <?php endif; ?>

                            <form class="add-comment-form mt-2" data-post-id="<?= $post['id'] ?>">
                                <div class="input-group">
                                    <input type="text" name="comment" class="form-control form-control-sm" placeholder="Write a comment..." required>
                                    <button class="btn btn-sm btn-primary" type="submit">Post</button>
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
                    <button class="btn btn-outline-secondary w-100">Edit Details</button>
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
                <button class="btn btn-outline-secondary w-100">Edit Details</button>
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

                fetch('/like_post', {
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

                    fetch('/delete_post', {
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

            fetch('/create_post', {
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
</style>