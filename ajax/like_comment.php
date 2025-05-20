<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || 
    !isset($_POST['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$commentId = (int)$_POST['comment_id'];
$userId = (int)$_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    if ($_POST['action'] === 'like') {
        // Check if already liked
        $stmt = $pdo->prepare("
            SELECT 1 FROM comment_likes 
            WHERE user_id = ? AND comment_id = ?
        ");
        $stmt->execute([$userId, $commentId]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO comment_likes (user_id, comment_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $commentId]);
        }
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM comment_likes 
            WHERE user_id = ? AND comment_id = ?
        ");
        $stmt->execute([$userId, $commentId]);
    }

    // Get updated like count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS count 
        FROM comment_likes 
        WHERE comment_id = ?
    ");
    $stmt->execute([$commentId]);
    $count = $stmt->fetch()['count'];

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'new_count' => $count
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}