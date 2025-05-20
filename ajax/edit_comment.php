<?php
session_start();
require '../config/db.php'; // Ensure correct path to database config

header('Content-Type: application/json');

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get input values
$commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
$newContent = trim($_POST['content']);

if ($commentId <= 0 || empty($newContent)) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment or content']);
    exit;
}

// Update comment in database
$stmt = $pdo->prepare("UPDATE comments SET content = :content WHERE id = :id");
$success = $stmt->execute(['content' => $newContent, 'id' => $commentId]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
}
?>