<?php
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

$user_id = $_SESSION['user_id'];


// Fetch user posts
$stmt_posts = $pdo->prepare("SELECT id,content, created_at FROM posts WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt_posts->execute(['user_id' => $user_id]);
$posts = $stmt_posts->fetchAll();

// Fetch user comments
$stmt_comments = $pdo->prepare("SELECT c.id, c.content AS comment_content, c.created_at, 
                                       p.id AS post_id, p.content AS post_content
                                FROM comments c
                                JOIN posts p ON c.post_id = p.id
                                WHERE c.user_id = :user_id
                                ORDER BY c.created_at DESC");

$stmt_comments->execute(['user_id' => $user_id]);
$comments = $stmt_comments->fetchAll();

// Fetch user bookmarks
$stmt_bookmarks = $pdo->prepare("SELECT p.id, p.created_at 
                                 FROM bookmarks b
                                 JOIN posts p ON b.post_id = p.id
                                 WHERE b.user_id = :user_id
                                 ORDER BY p.created_at DESC");
$stmt_bookmarks->execute(['user_id' => $user_id]);
$bookmarks = $stmt_bookmarks->fetchAll();
?>

<?php require_once __DIR__ . "/includes/header.php" ?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">

                <h1 class="mb-4">My participation</h1>

                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="posts-tab" data-bs-toggle="tab" href="#posts" role="tab" aria-controls="posts" aria-selected="true">My Posts</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="comments-tab" data-bs-toggle="tab" href="#comments" role="tab" aria-controls="comments" aria-selected="false">My Comments</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="bookmarks-tab" data-bs-toggle="tab" href="#bookmarks" role="tab" aria-controls="bookmarks" aria-selected="false">My Bookmarks</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-3" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="posts" role="tabpanel" aria-labelledby="posts-tab">
                        <h2>My Posts</h2>
                        <ul class="list-group">
                            <?php foreach ($posts as $post): ?>
                                <li class="list-group-item <?= (isset($_GET['id']) && $_GET['id'] == $post['id']) ? 'active-post' : '' ?>" style="background-color: var(--card-bg); color:var(--text-color);padding:15px;border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem; border-bottom-right-radius: 1.5rem; border-bottom-left-radius: 0;">
                                    <!-- Move the <a> inside the <li> -->
                                    <a href="/community/get_post.php?id=<?= htmlspecialchars($post['id']) ?>" class="text-decoration-none text-reset">
                                                                              <!-- Username and post date -->
                                        <small><strong>You </strong> Posted on <?= date('F j, Y', strtotime($post['created_at'])) ?></small>
                                        <!-- Post content preview -->
                                        <p>✔️&nbsp;<?= htmlspecialchars(substr($post['content'], 0, 100)) ?><?= strlen($post['content']) > 100 ? '...' : '' ?></p>
                                    </a>
                                </li>
                                <hr class="my-3">
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- My Comments -->
                    <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                        <h2>My Comments</h2>
                        <ul class="list-group">
                            <?php foreach ($comments as $comment): ?>
                                <li class="list-group-item" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
                                    <h5 style="background-color: var(--card-bg-2)">
                                        <a href="/post.php?id=<?= htmlspecialchars($comment['post_id']) ?>" class="text-decoration-none;color:var(--card-bg-2)">
                                            Post <?= htmlspecialchars($comment['post_id']) ?>
                                        </a>
                                    </h5>
                                    <div class="p-3 mb-2"
                                        style="
        margin-left: 15px;
        background-color: var(--card-bg-2);
        border-top-left-radius: 1.5rem;
        border-top-right-radius: 1.5rem;
        border-bottom-right-radius: 1.5rem;
        border-bottom-left-radius: 0;">
                                        <p class="mb-1 text-muted" style="color:var(--text-color)"><strong><em><?= htmlspecialchars(substr($comment['post_content'], 0, 100)) ?><?= strlen($comment['post_content']) > 100 ? '...' : '' ?></em></strong></p>
                                    </div>
                                    <p style=" color:var(--text-color)"><strong>Your Comment:</strong> <?= nl2br(htmlspecialchars($comment['comment_content'])) ?></p>
                                    <small class="text-muted">Commented on <?= date('F j, Y', strtotime($comment['created_at'])) ?></small>
                                </li>
                                <hr class="my-3">
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- My Bookmarks -->
                    <div class="tab-pane fade" id="bookmarks" role="tabpanel" aria-labelledby="bookmarks-tab">
                        <h2>My Bookmarks</h2>
                        <ul class="list-group">
                            <?php foreach ($bookmarks as $bookmark): ?>
                                <li class="list-group-item">
                                    <h5><a href="/post.php?id=<?= $bookmark['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($bookmark['title']) ?></a></h5>
                                    <small>Bookmarked on <?= date('F j, Y', strtotime($bookmark['created_at'])) ?></small>
                                </li>
                                <hr class="my-3">
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
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


</body>
<div class="check py-5" style="margin-top: 7vh;">
    <?php require_once __DIR__ . "/includes/footer.php" ?>
</div>
    <?php require_once __DIR__ . "/post_functions.php" ?>