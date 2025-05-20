<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

// Validate input
if (!isset($_SESSION['user_id']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$required = ['post_id', 'parent_id', 'content'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
}

try {
    // Insert reply
    $stmt = $pdo->prepare("
        INSERT INTO comments 
        (user_id, post_id, parent_id, content) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        (int)$_POST['post_id'],
        (int)$_POST['parent_id'],
        trim($_POST['content'])
    ]);

    // Get the new reply data
    $replyId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$replyId]);
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate HTML for the new reply
    $html = '
    <div class="reply mb-2 p-2 border rounded bg-light" id="comment-'.$reply['id'].'">
        <div class="d-flex align-items-center mb-2">
            <div class="user-avatar me-2">';
    
    if (!empty($reply['profile_pic'])) {
        $html .= '<img src="'.htmlspecialchars($reply['profile_pic']).'" 
                  class="rounded-circle" width="28" height="28">';
    } else {
        $html .= '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                  style="width: 28px; height: 28px;">'
                 .strtoupper(substr($reply['username'], 0, 1)).
                '</div>';
    }
    
    $html .= '</div>
            <div class="flex-grow-1">
                <strong>'.htmlspecialchars($reply['username']).'</strong>
                <small class="text-muted ms-2">'
                    .date('M j, Y g:i a', strtotime($reply['created_at'])).
                '</small>
            </div>
        </div>
        <div class="comment-content mb-2 ps-3">'
            .nl2br(htmlspecialchars($reply['content'])).
        '</div>
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}