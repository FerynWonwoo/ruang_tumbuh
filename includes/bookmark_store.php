<?php
require_once __DIR__ . '/../config/koneksimvc.php';
require_once __DIR__ . '/../app/Models/Bookmark.php';

function bookmark_get_user_id(): int
{
    global $conn;

    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    $username = trim((string) ($_SESSION['username'] ?? ''));
    if ($username !== '' && ($conn instanceof mysqli)) {
        $model = new Bookmark($conn);
        $userRow = $model->findUserByUsername($username);
        if ($userRow) {
            $_SESSION['user_id'] = (int) $userRow['id'];
            return (int) $userRow['id'];
        }
    }

    return 0;
}

function bookmark_user(): string
{
    return (string) ($_SESSION['username'] ?? ($_SESSION['nama'] ?? ''));
}

function bookmark_ids(string $user = ''): array
{
    global $conn;
    $userId = bookmark_get_user_id();
    if ($userId <= 0 || !($conn instanceof mysqli)) {
        return [];
    }
    return (new Bookmark($conn))->getSavedProgramIds($userId);
}

function bookmark_toggle(string $user, string $id, array $fallbackData = []): bool
{
    global $conn;
    $userId = bookmark_get_user_id();
    if ($userId <= 0 || !($conn instanceof mysqli)) {
        return false;
    }
    return (new Bookmark($conn))->toggleFromKatalog($userId, $id, $fallbackData);
}

function bookmark_ringkasan(string $user = ''): array
{
    global $conn;
    $userId = bookmark_get_user_id();
    if ($userId <= 0 || !($conn instanceof mysqli)) {
        return ['total' => 0, 'mendekati' => 0];
    }
    return (new Bookmark($conn))->getSummaryByUser($userId);
}