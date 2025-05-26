<?php
require_once __DIR__ . '/../config/db.php';
session_start();
header('Content-Type: application/json');

// Validate session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate inputs
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$userId = (int)$_SESSION['user_id'];
$shareType = $_POST['share_type'] ?? 'internal'; // internal, facebook, twitter, etc.

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

try {
    // Only track internal shares in our database
    if ($shareType === 'internal') {
        // Check if user already shared this post
        $checkStmt = $pdo->prepare("SELECT id FROM shares WHERE post_id = ? AND user_id = ?");
        $checkStmt->execute([$postId, $userId]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already shared this post']);
            exit;
        }

        // Record the share
        $insertStmt = $pdo->prepare("INSERT INTO shares (post_id, user_id, shared_at) VALUES (?, ?, NOW())");
        $insertStmt->execute([$postId, $userId]);
        
        // Update share count in posts table
        $updateStmt = $pdo->prepare("UPDATE posts SET share_count = share_count + 1 WHERE id = ?");
        $updateStmt->execute([$postId]);
        
        // Notify post owner (optional)
        $notifyStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, source_id, source_type, created_at)
            SELECT p.user_id, 'share', ?, 'post', NOW()
            FROM posts p WHERE p.id = ?
        ");
        $notifyStmt->execute([$postId, $postId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Post shared successfully']);
} catch (PDOException $e) {
    error_log("Share post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}