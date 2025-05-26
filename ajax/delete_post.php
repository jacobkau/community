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

        // Check ownership or admin
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            throw new Exception('Post not found');
        }

        if ($post['user_id'] !== $userId && empty($_SESSION['is_admin'])) {
            throw new Exception('Unauthorized to delete this post');
        }

        $pdo->beginTransaction();

        // Delete media
        $stmt = $pdo->prepare("SELECT file_path FROM post_media WHERE post_id = ?");
        $stmt->execute([$postId]);
        $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mediaFiles as $file) {
            $path = realpath(__DIR__ . '/../' . $file['file_path']);
            if ($path && file_exists($path)) {
                @unlink($path); // suppress errors
            }
        }

        $pdo->prepare("DELETE FROM post_media WHERE post_id = ?")->execute([$postId]);
        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id IN (SELECT id FROM comments WHERE post_id = ?)")->execute([$postId]);
        $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$postId]);
        $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$postId]);
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);

        $pdo->commit();

        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Delete error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete post: ' . $e->getMessage()]);
        exit;
    }
} else {
    // ✳️ Fallback for invalid request
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?>
