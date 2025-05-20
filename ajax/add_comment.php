<?php
session_start();
header('Content-Type: application/json'); // Tells browser this is JSON

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $postId = $_POST['post_id'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!is_numeric($postId) || empty($comment)) {
            throw new Exception('Invalid input');
        }

        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $_SESSION['user_id'], $comment]);

        // Get new comment data
        $stmt = $pdo->prepare("
            SELECT comments.*, users.username 
            FROM comments 
            JOIN users ON comments.user_id = users.id 
            WHERE comments.id = LAST_INSERT_ID()
        ");
        $stmt->execute();
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get updated comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);
        $commentCount = $stmt->fetchColumn();

        // Defensive check: if created_at is null, use current time
        $createdAt = $newComment['created_at'] ?? date('Y-m-d H:i:s');

        echo json_encode([
            'success' => true,
            'username' => $newComment['username'],
            'comment' => nl2br(htmlspecialchars($newComment['content'])),
            'created_at' => date('M j, Y \a\t g:i a', strtotime($createdAt)),
            'comment_count' => $commentCount
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
