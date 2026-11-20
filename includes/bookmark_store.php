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

// ---------- Bookmark manual & perubahan (untuk CRUD di halaman Bookmark) ----------
define('BOOKMARK_CUSTOM_FILE', __DIR__ . '/../data/bookmark_custom.json');

// Struktur per user: ['custom' => [bookmark manual...], 'override' => [idKatalog => data yang diubah]]
function bookmark_custom_get(string $user): array
{
    $all = [];
    if (file_exists(BOOKMARK_CUSTOM_FILE)) {
        $json = json_decode(file_get_contents(BOOKMARK_CUSTOM_FILE), true);
        $all = is_array($json) ? $json : [];
    }
    $u = $all[$user] ?? [];
    return [
        'custom'   => is_array($u['custom'] ?? null) ? $u['custom'] : [],
        'override' => is_array($u['override'] ?? null) ? $u['override'] : [],
    ];
}

function bookmark_custom_put(string $user, array $data): void
{
    $all = [];
    if (file_exists(BOOKMARK_CUSTOM_FILE)) {
        $json = json_decode(file_get_contents(BOOKMARK_CUSTOM_FILE), true);
        $all = is_array($json) ? $json : [];
    }
    $all[$user] = $data;

    $dir = dirname(BOOKMARK_CUSTOM_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents(
        BOOKMARK_CUSTOM_FILE,
        json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// Ringkasan untuk Dashboard: jumlah bookmark & yang mendekati tenggat (<= 7 hari, belum lewat)
function bookmark_ringkasan(string $user): array
{
    require_once __DIR__ . '/bootcamp_store.php';

    $store = bookmark_custom_get($user);
    $deadlines = [];

    foreach ($store['custom'] as $c) {
        $deadlines[] = $c['deadline'] ?? '';
    }

    $idKatalog = bookmark_ids($user);
    foreach (bootcamp_load() as $bc) {
        $id = (string) $bc['id'];
        if (!in_array($id, $idKatalog, true)) {
            continue;
        }
        $data = array_merge($bc, $store['override'][$id] ?? []);
        $deadlines[] = $data['deadline'] ?? '';
    }

    $mendekati = 0;
    foreach ($deadlines as $d) {
        $ts = $d !== '' ? strtotime($d) : false;
        if ($ts && $ts >= strtotime('today') && $ts <= strtotime('+7 days')) {
            $mendekati++;
        }
    }

    return ['total' => count($deadlines), 'mendekati' => $mendekati];
}