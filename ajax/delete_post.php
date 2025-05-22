<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        $postId = (int)$_POST['post_id'];
        $userId = (int)$_SESSION['user_id'];

        // Verify post exists and belongs to user (or is admin)
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            throw new Exception('Post not found');
        }

        if ($post['user_id'] !== $userId && empty($_SESSION['is_admin'])) {
            throw new Exception('Unauthorized to delete this post');
        }

        // Begin transaction
        $pdo->beginTransaction();

        // 1. Delete post media
        $stmt = $pdo->prepare("DELETE FROM post_media WHERE post_id = ?");
        $stmt->execute([$postId]);

        // 2. Delete likes on comments under this post
        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id IN (SELECT id FROM comments WHERE post_id = ?)");
        $stmt->execute([$postId]);

        // 3. Delete comments and replies
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);

        // 4. Delete post likes
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ?");
        $stmt->execute([$postId]);

        // 5. Delete related notifications (if applicable)
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE post_id = ?");
        $stmt->execute([$postId]);

        // 6. Delete the post itself
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);

        // Commit changes
        $pdo->commit();

        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Delete error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete post: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
