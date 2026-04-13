<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id) {
    $stmt = $pdo->prepare("SELECT id, image_path, is_primary FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$room_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($images);
} else {
    echo json_encode([]);
}
?>