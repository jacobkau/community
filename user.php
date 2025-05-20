<?php
require_once __DIR__.'/config/db.php';
session_start();

// Get user ID or username from URL
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$target_username = isset($_GET['user']) ? trim($_GET['user']) : null;

// Fetch user by ID or username
if ($target_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
} elseif ($target_username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$target_username]);
} else {
    die("No user specified.");
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$current_user_id = $_SESSION['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($user['username']) ?>'s Profile</title>
    <link rel="shortcut icon" href="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" type="image/x-icon">
    <style>
        body { background-color: #121212; color: #e0e0e0; }
        .card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 10px; }
    </style>
</head>
<?php require_once __DIR__. "/includes/header.php"?>
<body>
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)</h2>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <p>Joined: <?= htmlspecialchars(date('F j, Y', strtotime($user['created_at']))) ?></p>

            <?php if ($current_user_id && $current_user_id !== $user['id']): ?>
                <div id="follow-section">
                    <button id="follow-btn" class="btn btn-sm btn-outline-light">Loading...</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Posts by <?= htmlspecialchars($user['first_name']) ?></h4>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user['id']]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if ($posts): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($posts as $post): ?>
                        <li class="list-group-item bg-dark text-white">
                            <h5><?= htmlspecialchars($post['title']) ?></h5>
                            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                            <small>Posted on <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($post['created_at']))) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>This user hasn't posted anything yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($current_user_id && $current_user_id !== $user['id']): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById('follow-btn');
    const targetId = <?= (int)$user['id'] ?>;

    fetch('check_follow_status.php?target_user_id=' + targetId)
        .then(res => res.json())
        .then(data => {
            btn.textContent = data.following ? 'Unfollow' : 'Follow';
        });

    btn.addEventListener('click', () => {
        fetch('ajax/follow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `target_user_id=${targetId}&action=${btn.textContent.toLowerCase()}&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.textContent = btn.textContent === 'Follow' ? 'Unfollow' : 'Follow';
            } else {
                alert(data.message || 'Something went wrong.');
            }
        });
    });
});
</script>
<?php endif; ?>
<?php require_once __DIR__. "/includes/footer.php";
require_once __DIR__. "/post_functions.php"?>
</body>
</html>