<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$current_user_id = $_SESSION['user_id'];
$conversation_id = filter_input(INPUT_POST, 'conversation_id', FILTER_VALIDATE_INT);
$message_content = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

if (!$conversation_id || empty($message_content)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid input']));
}

try {
    // Verify user is part of conversation
    $stmt = $pdo->prepare("SELECT * FROM conversations 
                          WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $current_user_id, $current_user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Not authorized']));
    }

    // Insert message
    $insert_stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) 
                                 VALUES (?, ?, ?)");
    $insert_stmt->execute([$conversation_id, $current_user_id, $message_content]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Message send error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}