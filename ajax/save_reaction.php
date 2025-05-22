<?php
session_start();
require_once __DIR__ . "/../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if (!isset($_POST['post_id']) || !isset($_POST['reaction']) || !isset($_POST['csrf_token'])) {
    die(json_encode(['success' => false, 'error' => 'Missing data']));
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}

$postId = (int)$_POST['post_id'];
$userId = (int)$_SESSION['user_id'];
$reaction = $_POST['reaction'];

try {
    // Check if user already reacted
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing reaction
        $pdo->prepare("UPDATE likes SET reaction_type = ? WHERE post_id = ? AND user_id = ?")
           ->execute([$reaction, $postId, $userId]);
    } else {
        // Insert new reaction
        $pdo->prepare("INSERT INTO likes (post_id, user_id, reaction_type) VALUES (?, ?, ?)")
           ->execute([$postId, $userId, $reaction]);
    }
    
    // Get new reaction count
    $count = $pdo->query("SELECT COUNT(*) FROM likes WHERE post_id = $postId")
                ->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'total_reactions' => $count,
        'reaction' => $reaction
    ]);
    
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Database error']));
}