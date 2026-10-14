<?php
session_start();
require_once __DIR__ . '/../includes/bookmark_store.php';

header('Content-Type: application/json');

$user = bookmark_user();
if ($user === '' || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$saved = bookmark_toggle($user, (string) $_POST['id']);
echo json_encode(['ok' => true, 'saved' => $saved]);