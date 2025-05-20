<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// CSRF Validation
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    $postId = $data['post_id'];
    $userId = $_SESSION['user_id'];
    
    // Toggle like
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $existingLike = $stmt->fetch();

    if ($existingLike) {
        // Remove like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existingLike['id']]);
        $hasLiked = false;
    } else {
        // Add like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$userId, $postId]);
        $hasLiked = true;
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    $count = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'like_count' => $count,
        'has_liked' => $hasLiked
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>