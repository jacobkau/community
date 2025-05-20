<?php
session_start();
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Get conversations for current user
    $stmt = $pdo->prepare("SELECT c.*, 
                          u.username AS other_username,
                          u.avatar AS other_avatar,
                          (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
                          (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_time
                          FROM conversations c
                          JOIN users u ON u.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)
                          WHERE c.user1_id = ? OR c.user2_id = ?
                          ORDER BY last_message_time DESC");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching messages: " . $e->getMessage();
}

// Helper function to format time
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
</head>

<?php include __DIR__ . '/includes/header.php'; ?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="container-fluid col-lg-8  px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:20px;border-radius:10px">
                <h2><i class="bi bi-envelope"></i> Messages</h2>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <?php if (empty($conversations)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-envelope" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No messages yet</h5>
                            <p class="text-muted">When you send or receive messages, they'll appear here</p>
                            <a href="/community/following.php" class="btn btn-primary">Find people to message</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($conversations as $conv): ?>
                            <a href="/community/messages/<?= $conv['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($conv['other_avatar'] ?? '/assets/default-avatar.png') ?>"
                                        class="rounded-circle me-3" width="50" height="50" alt="<?= htmlspecialchars($conv['other_username']) ?>">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($conv['other_username']) ?></h6>
                                            <small class="text-muted"><?= time_elapsed_string($conv['last_message_time']) ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate"><?= htmlspecialchars($conv['last_message']) ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

    <?php include __DIR__ . '/includes/footer.php';
    require_once __DIR__ . "/post_functions.php"; ?>

</body>

</html>