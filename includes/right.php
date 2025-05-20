<!-- Network Card -->
<?php
// Initialize variables to avoid undefined errors
$target_username = null;
$followers_count = 0;
$following_count = 0;
$activities = [];
$users = [];
$suggestions = [];
$error_message = null;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $current_username = $_SESSION['username'];
    $profile_pic = $_SESSION['profile_pic'] ?? '/assets/default-avatar.png';

    try {
        // Get target user for messaging if specified
        $target_user_id = filter_input(INPUT_POST, 'target_user_id', FILTER_VALIDATE_INT);
        if ($target_user_id) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            $target_username = $target['username'] ?? null;
        }

        // Get follower count for current user
        $stmt_followers = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_user_id = ?");
        $stmt_followers->execute([$current_user_id]);
        $followers_count = $stmt_followers->fetchColumn();

        // Get following count for current user
        $stmt_following = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_user_id = ?");
        $stmt_following->execute([$current_user_id]);
        $following_count = $stmt_following->fetchColumn();

        // Get activity feed for the logged-in user specifically
        try {
            $stmt = $pdo->prepare("
                SELECT activity_type, activity_content, activity_date 
                FROM user_activity 
                WHERE user_id = ?
                ORDER BY activity_date DESC 
                LIMIT 10
            ");
            $stmt->execute([$current_user_id]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add current user info to each activity
            foreach ($activities as &$activity) {
                $activity['username'] = $current_username;
                $activity['profile_pic'] = $profile_pic;
            }
        } catch (PDOException $e) {
            // Fallback if activity table doesn't exist or has issues
            error_log("Activity feed error: " . $e->getMessage());
            $activities = [];
        }

        // Handle search if query parameter exists
        if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
            $query = '%' . trim($_GET['query']) . '%';
            $stmt = $pdo->prepare("
                SELECT id, username, profile_pic, 
                    EXISTS (
                        SELECT 1 FROM follows f
                        WHERE f.follower_user_id = ? AND f.followed_user_id = u.id
                    ) AS is_following
                FROM users u 
                WHERE username LIKE ? AND id != ?
                LIMIT 20
            ");
            $stmt->execute([$current_user_id, $query, $current_user_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Regular user listing with pagination
            $limit = 10;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $offset = ($page - 1) * $limit;

            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.profile_pic,
                    EXISTS (
                        SELECT 1 FROM follows f
                        WHERE f.follower_user_id = ? AND f.followed_user_id = u.id
                    ) AS is_following
                FROM users u
                WHERE u.id != ?
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$current_user_id, $current_user_id, $limit, $offset]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get suggested users based on shared interests
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.username, u.profile_pic,
                EXISTS (
                    SELECT 1 FROM follows f
                    WHERE f.follower_user_id = ? AND f.followed_user_id = u.id
                ) AS is_following
            FROM users u
            JOIN user_interests ui ON ui.user_id = u.id
            JOIN user_interests ci ON ci.interest_id = ui.interest_id
            WHERE ci.user_id = ? AND u.id != ?
            LIMIT 5
        ");
        $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error_message = "A database error occurred. Please try again later.";
    }
} else {
    $current_user_id = null;
    $error_message = "Please log in to view your network.";
}
?>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<?php if ($current_user_id): ?>
    <div class="card mb-4 mt-5" style="background-color: var(--card-bg); border-color: var(--border-color);border-radius:10px;color:var(--text-color)">
        <div class="card-body text-center">
            <h5 class="card-title">Your Network</h5>
            <div class="d-flex justify-content-around">
                <div>
                    <div class="fs-3"><?= number_format($followers_count) ?></div>
                    <div style="color: var(--text-color);">Followers</div>
                </div>
                <div>
                    <div class="fs-3"><?= number_format($following_count) ?></div>
                    <div style="color: var(--text-color);">Following</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($activities)): ?>
        <div class="card mb-4 activity-feed" style="border-radius:10px; background:var(--card-bg); border:1px solid var(--border-color);">
            <div class="card-body">
                <h5 class="card-title">Your Recent Activity</h5>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($activities as $activity): ?>
                        <li class="notification-item d-flex align-items-start py-2 px-3 mb-1">
                            <img src="<?= htmlspecialchars($activity['profile_pic'] ?? '/assets/default-avatar.png') ?>"
                                class="rounded-circle me-3"
                                width="40" height="40">
                            <div class="flex-grow-1">
                                <strong class="d-block"><?= htmlspecialchars($activity['username']) ?></strong>
                                <span><?= htmlspecialchars($activity['activity_content']) ?></span>
                                <div class="text-muted small"><?= date('M j, Y g:i A', strtotime($activity['activity_date'])) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Suggested Users -->
    <?php if (!empty($suggestions)): ?>
        <div class="card mb-4" style="border-radius:10px; background:var(--card-bg); border:1px solid var(--border-color);">
            <?php foreach ($suggestions as $user): ?>
               <div class="card-body">
                <h5 class="card-title">Your Recent Activity</h5>

                    <div class="fb-card p-3 text-center user-card">
                        <div class="text-center">
                            <?php if (!empty($user['profile_pic'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_pic']) ?>"
                                    alt="<?= htmlspecialchars($user['username']) ?>'s avatar"
                                    class="profile-pic mb-3 rounded-circle">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                    style="width: 100px; height: 100px; font-size: 2rem;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-1">
                            <a href="/profile.php?id=<?= $user['id'] ?>" class="text-decoration-none" style="color: var(--text-color);">
                                <?= htmlspecialchars($user['username']) ?>
                            </a>
                        </h5>
                        <?php if ($user['mutual_friends'] > 0): ?>
                            <p class="mutual-friends mb-2">
                                <?= $user['mutual_friends'] ?> mutual friend<?= $user['mutual_friends'] > 1 ? 's' : '' ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($user['bio'] ?? 'No bio yet') ?></p>

                        <button class="btn w-100 mb-2 fb-btn-primary follow-btn"
                            data-user-id="<?= $user['id'] ?>"
                            data-action="follow">
                            Add Friend
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-2">No suggestions available</p>
        </div>
    <?php endif; ?>
<?php endif; ?>