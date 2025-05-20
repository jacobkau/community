<?php
session_start();
header('Content-Type: application/json');

// Verify this is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Request forbidden']);
    exit;
}

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$commentId = filter_var($data['comment_id'] ?? null, FILTER_VALIDATE_INT);

if (!$commentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check user permissions
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        throw new Exception('Comment not found');
    }
    
    // Check if user is admin
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $isAdmin = $user['is_admin'] ?? false;
    $isOwner = ($comment['user_id'] == $_SESSION['user_id']);
    
    if (!$isOwner && !$isAdmin) {
        http_response_code(403);
        throw new Exception('Not authorized to delete this comment');
    }
    
    // Delete comment likes first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$commentId]);
    
    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>