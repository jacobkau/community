<?php
require_once __DIR__ . "/../config/db.php";
session_start();
header('Content-Type: application/json');

// Validate session and request
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['edit_post'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate inputs
$postId = (int)($_POST['post_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$removeMedia = array_filter($_POST['remove_media'] ?? [], 'is_numeric');

try {
    // Verify post ownership
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $postOwner = $stmt->fetchColumn();

    if ($postOwner != $userId) {
        throw new Exception('Unauthorized operation');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Process media removal
    if (!empty($removeMedia)) {
        $placeholders = implode(',', array_fill(0, count($removeMedia), '?'));
        $stmt = $pdo->prepare("
            SELECT id, file_path 
            FROM post_media 
            WHERE id IN ($placeholders)
            AND post_id = ?
        ");
        $stmt->execute(array_merge($removeMedia, [$postId]));
        $mediaToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            DELETE FROM post_media 
            WHERE id IN ($placeholders)
            AND post_id = ?
        ");
        $stmt->execute(array_merge($removeMedia, [$postId]));

        foreach ($mediaToDelete as $media) {
            $filePath = realpath(__DIR__ . '/../' . $media['file_path']);
            if ($filePath && is_writable($filePath)) {
                unlink($filePath);
            }
        }
    }

    // Process new media uploads
    if (!empty($_FILES['new_media'])) {
        $uploadDir = __DIR__ . '/../uploads/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($_FILES['new_media']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['new_media']['error'][$key] !== UPLOAD_ERR_OK) continue;

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'video/mp4' => 'mp4'
            ];

            if (!array_key_exists($mimeType, $allowedTypes)) continue;

            $filename = uniqid() . '.' . $allowedTypes[$mimeType];
            $destination = $uploadDir . $filename;

            if (move_uploaded_file($tmpName, $destination)) {
                $stmt = $pdo->prepare("
                    INSERT INTO post_media (post_id, file_path, media_type)
                    VALUES (?, ?, ?)
                ");
                $mediaType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
                $stmt->execute([$postId, 'uploads/posts/' . $filename, $mediaType]);
            }
        }
    }

    // Update post content
    $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $postId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Post update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
