<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Get notifications for current user
    $stmt = $pdo->prepare("SELECT * FROM notifications 
                          WHERE user_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$current_user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
        ->execute([$current_user_id]);
} catch (PDOException $e) {
    $error_message = "Error fetching notifications: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>

</head>

<?php include __DIR__ . '/includes/header.php'; ?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-9 px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
                <h2><i class="bi bi-bell"></i> Notifications</h2>
                <a href="#" class="btn btn-outline-secondary">Mark all as read</a>


                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <?php if (empty($notifications)): ?>
                    <div class="card mt-4">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No notifications yet</h5>
                            <p class="text-muted">When you get notifications, they'll appear here</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <a href="<?= htmlspecialchars($notification['link'] ?? '#') ?>"
                                class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'list-group-item-primary' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if ($notification['type'] === 'like'): ?>
                                            <i class="bi bi-heart-fill text-danger" style="font-size: 1.5rem;"></i>
                                        <?php elseif ($notification['type'] === 'comment'): ?>
                                            <i class="bi bi-chat-fill text-primary" style="font-size: 1.5rem;"></i>
                                        <?php else: ?>
                                            <i class="bi bi-bell-fill text-warning" style="font-size: 1.5rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                        <small class="text-muted"><?= time_elapsed_string($notification['created_at']) ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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