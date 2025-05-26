<?php
require_once __DIR__ . "/../config/db.php";
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

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

// Validate inputs
$userId = (int)$_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Post content cannot be empty']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert new post
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    $stmt->execute([$userId, $content]);
    $postId = $pdo->lastInsertId();

    // Process file uploads
    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Process image uploads
    if (!empty($_FILES['post_images']['name'][0])) {
        $imageCount = count($_FILES['post_images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['post_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            
            $tmpName = $_FILES['post_images']['tmp_name'][$i];
            $mimeType = mime_content_type($tmpName);
            
            $allowedImageTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif'
            ];
            
            if (!array_key_exists($mimeType, $allowedImageTypes)) continue;
            
            $filename = uniqid() . '.' . $allowedImageTypes[$mimeType];
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($tmpName, $destination)) {
                $stmt = $pdo->prepare("
                    INSERT INTO post_media (post_id, file_path, media_type)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$postId, 'uploads/posts/' . $filename, 'image']);
            }
        }
    }

    // Process video uploads
    if (!empty($_FILES['post_videos']['name'][0])) {
        $videoCount = count($_FILES['post_videos']['name']);
        for ($i = 0; $i < $videoCount; $i++) {
            if ($_FILES['post_videos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            
            $tmpName = $_FILES['post_videos']['tmp_name'][$i];
            $mimeType = mime_content_type($tmpName);
            
            $allowedVideoTypes = [
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/quicktime' => 'mov'
            ];
            
            if (!array_key_exists($mimeType, $allowedVideoTypes)) continue;
            
            $filename = uniqid() . '.' . $allowedVideoTypes[$mimeType];
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($tmpName, $destination)) {
                $stmt = $pdo->prepare("
                    INSERT INTO post_media (post_id, file_path, media_type)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$postId, 'uploads/posts/' . $filename, 'video']);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Post created successfully', 'post_id' => $postId]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Post create error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create post: ' . $e->getMessage()]);
}