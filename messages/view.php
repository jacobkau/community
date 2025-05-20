<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$conversation_id = $_GET['id'] ?? null;
$current_user_id = $_SESSION['user_id'];

try {
    // Verify user is part of conversation
    $stmt = $pdo->prepare("SELECT * FROM conversations 
                          WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $current_user_id, $current_user_id]);
    $conversation = $stmt->fetch();

    if (!$conversation) {
        header('Location: /community/messages');
        exit;
    }

    // Get other participant
    $other_user_id = $conversation['user1_id'] == $current_user_id ? $conversation['user2_id'] : $conversation['user1_id'];
    $user_stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
    $user_stmt->execute([$other_user_id]);
    $other_user = $user_stmt->fetch();

    // Get messages
    $messages_stmt = $pdo->prepare("SELECT m.*, u.username, u.avatar 
                                   FROM messages m
                                   JOIN users u ON u.id = m.sender_id
                                   WHERE m.conversation_id = ?
                                   ORDER BY m.created_at ASC");
    $messages_stmt->execute([$conversation_id]);
    $messages = $messages_stmt->fetchAll();

    // Mark messages as read
    $pdo->prepare("UPDATE messages SET is_read = 1 
                  WHERE conversation_id = ? AND sender_id != ?")
        ->execute([$conversation_id, $current_user_id]);

} catch (PDOException $e) {
    $error_message = "Error loading conversation: " . $e->getMessage();
}


//=========================
// FETCH FOLLOWERS
// =====================

$current_user_id = $_SESSION['user_id'];

// Initialize variables to avoid undefined errors
$followers_count = 0;
$following_count = 0;
$follow_back = 0;
$activities = [];
$users = [];
$suggestions = [];

try {
    // Pagination setup
    $limit = 10;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Get total users for pagination (excluding current user)
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ?");
    $total_stmt->execute([$current_user_id]);
    $total_users = $total_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);

    // Get follower count for current user
    $stmt_followers = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_user_id = ?");
    $stmt_followers->execute([$current_user_id]);
    $followers_count = $stmt_followers->fetchColumn();

    // Get following count for current user
    $stmt_following = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_user_id = ?");
    $stmt_following->execute([$current_user_id]);
    $following_count = $stmt_following->fetchColumn();

    // Check if users follow each other (for follow back button)
    // Note: This needs to be handled per user in the loop later

    // Get activity feed - wrapped in try-catch in case table doesn't exist
    try {
        $stmt = $pdo->prepare("SELECT activity_type, activity_content FROM user_activity 
                              WHERE user_id IN (SELECT followed_user_id FROM follows WHERE follower_user_id = ?) 
                              ORDER BY activity_date DESC LIMIT 10");
        $stmt->execute([$current_user_id]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail if activity table doesn't exist
        $activities = [];
    }

    // Get users and check follow status
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar,
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

    // Handle search if query parameter exists
    if (isset($_GET['query'])) {
        $query = trim($_GET['query']);
        if (!empty($query)) {
            $stmt = $pdo->prepare("SELECT id, username, avatar, 
                EXISTS (
                    SELECT 1 FROM follows f
                    WHERE f.follower_user_id = ? AND f.followed_user_id = u.id
                ) AS is_following
                FROM users u 
                WHERE username LIKE ? AND id != ?
                LIMIT 20");
            $stmt->execute([$current_user_id, "%$query%", $current_user_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Get suggested users based on shared interests
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.username, u.avatar,
                          EXISTS (
                              SELECT 1 FROM follows f
                              WHERE f.follower_user_id = ? AND f.followed_user_id = u.id
                          ) AS is_following
                       FROM users u
                       JOIN user_interests ui ON ui.user_id = u.id
                       JOIN user_interests ci ON ci.interest_id = ui.interest_id
                       WHERE ci.user_id = ? AND u.id != ?
                       LIMIT 5");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error and show a user-friendly message
    error_log("Database error: " . $e->getMessage());
    $error_message = "Jacob! A database error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation with <?= htmlspecialchars($other_user['username']) ?></title>
</head>

<?php include __DIR__ . '/../includes/header.php'; ?>

<body data-theme="light">
    <div class="row" style="margin-top: 5vh;">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0"
            style=" border-color: var(--border-color); color: var(--text-color);">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <main class="col-12 col-lg-10 ms-sm-auto px-md-4 pt-4">
            <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">

                <div class="d-flex align-items-center mb-4">
                    <a href="/community/messages" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h2 class="mb-0"><?= htmlspecialchars($other_user['username']) ?></h2>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="card mb-3">
                    <div class="card-body" style="height: 60vh; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-chat-left" style="font-size: 3rem;"></i>
                                <p class="mt-3">No messages yet</p>
                                <p>Start the conversation!</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column">
                                <?php foreach ($messages as $message): ?>
                                    <div class="mb-3 <?= $message['sender_id'] == $current_user_id ? 'align-self-end' : 'align-self-start' ?>">
                                        <div class="<?= $message['sender_id'] == $current_user_id ? 'bg-primary text-white' : 'bg-light' ?> rounded p-3" style="max-width: 70%;">
                                            <p class="mb-0"><?= htmlspecialchars($message['content']) ?></p>
                                            <small class="d-block text-end <?= $message['sender_id'] == $current_user_id ? 'text-white-50' : 'text-muted' ?>">
                                                <?= date('h:i A', strtotime($message['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <form id="message-form" class="d-flex">
                    <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                    <input type="text" name="message" class="form-control me-2" placeholder="Type your message..." required>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </main>
        <aside class="col-lg-3 order-lg-last">
            <div class="d-none d-lg-block position-fixed vh-100 end-0 p-3"
                style="width: 500px; top: 2vh; z-index: 100;margin-right:7vh; overflow-y: auto;">
                <!-- Right sidebar content from right.php -->
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>

            <!-- Mobile version (hidden on lg and up) -->
            <div class="d-lg-none">
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>
        </aside>
    </div>


    <?php include __DIR__ . '/../includes/footer.php'; 
    require_once __DIR__ . "/post_functions.php"; ?>
    <script>
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/community/messages/send.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error sending message');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending message');
                });
        });
    </script>
</body>

</html>