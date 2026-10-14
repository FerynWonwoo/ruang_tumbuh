<?php
define('BOOKMARK_FILE', __DIR__ . '/../data/bookmark.json');

function bookmark_user(): string
{
    return $_SESSION['username'] ?? ($_SESSION['nama'] ?? '');
}

function bookmark_load_all(): array
{
    if (!file_exists(BOOKMARK_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(BOOKMARK_FILE), true);
    return is_array($data) ? $data : [];
}

function bookmark_ids(string $user): array
{
    $all = bookmark_load_all();
    return array_map('strval', $all[$user] ?? []);
}

// true = sekarang tersimpan, false = baru dihapus dari bookmark
function bookmark_toggle(string $user, string $id): bool
{
    $all = bookmark_load_all();
    $list = array_map('strval', $all[$user] ?? []);

    if (in_array($id, $list, true)) {
        $list = array_values(array_diff($list, [$id]));
        $saved = false;
    } else {
        $list[] = $id;
        $saved = true;
    }
    $all[$user] = $list;

    file_put_contents(
        BOOKMARK_FILE,
        json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
    return $saved;
}