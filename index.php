<?php
require_once __DIR__ . "/config/db.php";
session_start();
// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/header.php';
?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0" style="border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 ms-sm-auto px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-8" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
                <div class="container" id="post-container" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="create-post card mb-4" style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                            <form id="create-post-form" method="POST" class="card-body" enctype="multipart/form-data" action="ajax/create_post.php">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <textarea name="content" class="form-control mb-3" placeholder="What's on your mind?" required></textarea>

                                <!-- Media Uploads -->
                                <div class="mb-3">
                                    <label class="btn btn-outline-secondary">
                                        <i class="fas fa-image"></i> Add Images
                                        <input type="file" name="post_images[]" accept="image/*" multiple hidden>
                                    </label>

                                    <label class="btn btn-outline-secondary">
                                        <i class="fas fa-video"></i> Add Videos
                                        <input type="file" name="post_videos[]" accept="video/*" multiple hidden>
                                    </label>

                                    <!-- Preview area -->
                                    <div id="media-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                </div>

                                <button type="submit" class="btn btn-primary">Post</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Posts Display Area -->
                    <div class="posts-container" style="border-color: var(--border-color); color: var(--text-color);">
                        <?php include("post.php"); ?>

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
            </div>
        </main>

        <aside class="col-lg-3 order-lg-last">
            <div class="d-none d-lg-block position-fixed vh-100 end-0 p-3"
                style="width: 400px; top: 2vh; z-index: 100;margin-right:7vh; overflow-y: auto;">
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>

            <!-- Mobile version -->
            <div class="d-lg-none">
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>
        </aside>
    </div>

    <?php require_once __DIR__ . '/post_functions.php'; ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
    // File preview handling
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.getElementById('media-preview');
            
            // Process images
            if (this.name === 'post_images[]' && this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxHeight = '100px';
                        img.style.maxWidth = '100px';
                        img.className = 'img-thumbnail';
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            }
            
            // Process videos
            if (this.name === 'post_videos[]' && this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('video/')) return;
                    
                    const div = document.createElement('div');
                    div.className = 'video-thumbnail';
                    div.innerHTML = `
                        <video width="120" height="90" controls>
                            <source src="${URL.createObjectURL(file)}" type="${file.type}">
                        </video>
                        <small>${file.name}</small>
                    `;
                    preview.appendChild(div);
                });
            }
        });
    });

    // AJAX form submission
    document.getElementById('create-post-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Post created successfully!');
                form.reset();
                document.getElementById('media-preview').innerHTML = '';
                // Optionally refresh the posts list
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the post');
        });
    });
    </script>
</body>
</html>