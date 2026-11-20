<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/bookmark_store.php';

header('Content-Type: application/json; charset=UTF-8');

$userId = bookmark_get_user_id();
$user = bookmark_user();

if ($userId <= 0 && $user === '') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'pesan' => 'Belum login atau sesi habis']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'pesan' => 'Permintaan tidak valid']);
    exit;
}

$saved = bookmark_toggle($user, (string) $_POST['id'], $_POST);
echo json_encode(['ok' => true, 'saved' => $saved]);