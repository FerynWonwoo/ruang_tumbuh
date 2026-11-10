<?php
// Simpan di folder yang sama dengan rating.php
session_start();
// =========================
// KONFIGURASI & HELPER DATA
// (blok ini identik di rating.php dan adminrating.php,
//  kalau diubah di satu file, samakan di file satunya)
// =========================
const DATA_FILE = __DIR__ . '/../data/ulasan.json';
const MAX_KOMENTAR = 2000;

// Ganti dengan kategori bootcamp yang sudah ada di sistemmu
const DAFTAR_BOOTCAMP = [
    'UI/UX Design Bootcamp',
    'Web Development Bootcamp',
    'Data Science Bootcamp',
    'Digital Marketing Bootcamp',
];

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function input($nama)
{
    $nilai = $_POST[$nama] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
}

function idValid(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
}

// Baca semua ulasan (data bersama, bukan per-session)
function bacaUlasan(): array
{
    if (!is_file(DATA_FILE)) {
        return [];
    }
    $data = json_decode((string) file_get_contents(DATA_FILE), true);
    return is_array($data) ? $data : [];
}

// Baca-ubah-tulis dengan file lock supaya aman dari tabrakan.
// Mengembalikan false kalau file/folder tidak bisa diakses.
function ubahUlasan(callable $fn): bool
{
    $dir = dirname(DATA_FILE);

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    // Blokir akses langsung ke folder data lewat browser (Apache)
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents(
            $htaccess,
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
        );
    }

    $fp = fopen(DATA_FILE, 'c+');
    if (!$fp) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    $data = json_decode((string) stream_get_contents($fp), true);
    $data = $fn(is_array($data) ? $data : []);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Hanya admin (sama seperti adminkatalog.php / adminkategori.php)
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$nama_user = $_SESSION['username'] ?? 'Admin RuangTumbuh';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

const FILTER_VALID = ['pending', 'approved', 'rejected', 'all'];

$statusLabel = [
    'pending'  => 'Menunggu persetujuan',
    'approved' => 'Dipublikasikan',
    'rejected' => 'Disembunyikan',
];

$tab = [
    'pending'  => 'Menunggu',
    'approved' => 'Dipublikasikan',
    'rejected' => 'Disembunyikan',
    'all'      => 'Semua',
];

function bintang(int $r): string
{
    return str_repeat('★', $r) . '<span>' . str_repeat('☆', 5 - $r) . '</span>';
}

$pesan = $_SESSION['pesan_admin'] ?? '';
unset($_SESSION['pesan_admin']);
$error = '';

// Filter aktif (GET untuk tampilan, POST untuk redirect balik)
$filter = $_POST['filter'] ?? $_GET['status'] ?? 'pending';
$filter = is_string($filter) && in_array($filter, FILTER_VALID, true) ? $filter : 'pending';

// =========================
// PROSES AKSI
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'], input('csrf'))) {
        $error = 'Muat ulang halaman, lalu coba lagi.';
    } else {
        $aksi = '';
        $ids  = [];

        // Aksi satuan: value = "aksi:id"
        if (isset($_POST['satu']) && is_string($_POST['satu'])) {
            [$aksi, $satuId] = array_pad(explode(':', $_POST['satu'], 2), 2, '');
            $ids = [$satuId];
        }
        // Aksi massal: value = "aksi", id dari checkbox
        elseif (isset($_POST['massa']) && is_string($_POST['massa'])) {
            $aksi = $_POST['massa'];
            $ids  = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
        }

        $ids = array_values(array_unique(array_filter(
            $ids,
            fn($i) => is_string($i) && idValid($i)
        )));

        if (!in_array($aksi, ['publish', 'hide', 'hapus'], true)) {
            $error = 'Aksi tidak dikenal.';
        } elseif (!$ids) {
            $error = 'Pilih minimal satu ulasan.';
        } else {
            $jumlahProses = 0;

            $ok = ubahUlasan(function ($data) use ($ids, $aksi, &$jumlahProses) {
                foreach ($ids as $id) {
                    if (!isset($data[$id])) {
                        continue;
                    }
                    if ($aksi === 'hapus') {
                        unset($data[$id]);
                    } else {
                        $data[$id]['status'] = $aksi === 'publish' ? 'approved' : 'rejected';
                    }
                    $jumlahProses++;
                }
                return $data;
            });

            if (!$ok) {
                $error = 'Data tidak bisa disimpan. Cek izin folder data.';
            } elseif ($jumlahProses === 0) {
                $error = 'Ulasan tidak ditemukan.';
            } else {
                $kata = ['publish' => 'dipublikasikan', 'hide' => 'disembunyikan', 'hapus' => 'dihapus'][$aksi];
                $_SESSION['pesan_admin'] = $jumlahProses . ' ulasan berhasil ' . $kata . '.';
                header('Location: adminrating.php' . ($filter !== 'pending' ? '?status=' . $filter : ''));
                exit;
            }
        }
    }
}

// =========================
// DATA
// =========================
$jumlah = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$daftar = [];

foreach (array_reverse(bacaUlasan(), true) as $u) {
    // data lama tanpa status dianggap tayang
    $u['status'] = in_array($u['status'] ?? '', ['pending', 'approved', 'rejected'], true)
        ? $u['status']
        : 'approved';

    $jumlah['all']++;
    $jumlah[$u['status']]++;

    if ($filter === 'all' || $u['status'] === $filter) {
        $daftar[] = $u;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Ulasan | Ruang Tumbuh</title>

    <link rel="stylesheet" href="../css/rating.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/rating.css?v=<?= filemtime(__DIR__ . '/../css/rating.css') ?>">
</head>
<body>

<!-- SIDEBAR ADMIN -->
<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>

        <div class="nav-group first">
            <div class="nav-title">MENU ADMIN</div>

            <ul class="nav-menu">
                <li>
                    <a href="admindashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="adminkatalog.php" class="nav-link">
                        <i class="fa-solid fa-shapes"></i> Katalog Bootcamp
                    </a>
                </li>
                <li>
                    <a href="adminkategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i> Kategori Program
                    </a>
                </li>
                <li>
                    <a href="adminrating.php" class="nav-link active">
                        <i class="fa-solid fa-star"></i> Ulasan &amp; Rating
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>

<div class="main-area">

    <header class="header">
        <div class="profile-section">
            <div class="profile-info">
                <div class="profile-name"><?= aman($nama_user) ?></div>
                <div class="profile-role">Administrator</div>
            </div>
            <div class="avatar"><?= aman(strtoupper(mb_substr($nama_user, 0, 2))) ?></div>
        </div>
    </header>

    <main class="page">

        <!-- BANNER -->
        <section class="banner">
            <div class="banner-text">
                <span class="badge-tag">Admin</span>
                <h1>Moderasi Ulasan</h1>
                <p>Pilih ulasan mana yang dipublikasikan. Hanya ulasan yang dipublikasikan yang tampil di halaman peserta dan masuk hitungan rating.</p>
            </div>

            <div class="banner-stats">
                <div class="stat-pill">
                    <span class="stat-icon amber"><i class="fa-regular fa-clock"></i></span>
                    <span class="stat-text"><?= $jumlah['pending'] ?> <small>menunggu</small></span>
                </div>
                <div class="stat-pill">
                    <span class="stat-icon"><i class="fa-regular fa-eye"></i></span>
                    <span class="stat-text"><?= $jumlah['approved'] ?> <small>tayang</small></span>
                </div>
            </div>
        </section>

        <?php if ($pesan): ?>
            <p class="notice success" role="status"><?= aman($pesan) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= aman($error) ?></p>
        <?php endif; ?>

        <!-- FILTER STATUS -->
        <nav class="chips" aria-label="Filter status">
            <?php foreach ($tab as $kunci => $nama): ?>
                <a class="chip<?= $filter === $kunci ? ' active' : '' ?>"
                   href="adminrating.php?status=<?= $kunci ?>">
                    <?= $nama ?> <span class="chip-count"><?= $jumlah[$kunci] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (!$daftar): ?>
            <section class="card empty">
                <div class="empty-icon"><i class="fa-regular fa-circle-check"></i></div>
                <h3>Tidak ada ulasan di kategori ini</h3>
                <p class="muted">Coba pilih filter lain.</p>
            </section>
        <?php else: ?>

        <form method="post" action="adminrating.php" id="formModerasi">
            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
            <input type="hidden" name="filter" value="<?= aman($filter) ?>">

            <!-- BULK BAR -->
            <div class="bulk-bar">
                <label class="cek">
                    <input type="checkbox" id="pilihSemua">
                    <span>Pilih semua</span>
                </label>
                <span class="bulk-info"><b id="jumlahDipilih">0</b> dipilih</span>

                <div class="bulk-actions">
                    <button type="submit" class="mini green" name="massa" value="publish" data-bulk>
                        <i class="fa-regular fa-eye"></i> Publikasikan
                    </button>
                    <button type="submit" class="mini amber" name="massa" value="hide" data-bulk>
                        <i class="fa-regular fa-eye-slash"></i> Sembunyikan
                    </button>
                    <button type="submit" class="mini red" name="massa" value="hapus" data-bulk
                            data-confirm="Hapus semua ulasan yang dipilih? Tindakan ini tidak bisa dibatalkan.">
                        <i class="fa-regular fa-trash-can"></i> Hapus
                    </button>
                </div>
            </div>

            <div class="review-grid">
                <?php foreach ($daftar as $u): ?>
                    <?php
                        $r      = max(1, min(5, (int) $u['rating']));
                        $avatar = 'av-' . (abs(crc32($u['username'])) % 6);
                    ?>
                    <article class="review-card r<?= $r ?>" data-rating="<?= $r ?>">
                        <div class="rc-head">
                            <label class="cek cek-kartu" aria-label="Pilih ulasan <?= aman($u['username']) ?>">
                                <input type="checkbox" name="ids[]" value="<?= aman($u['id']) ?>" data-item>
                            </label>
                            <div class="rc-avatar <?= $avatar ?>"><?= aman(strtoupper(mb_substr($u['username'], 0, 2))) ?></div>
                            <div class="rc-who">
                                <strong><?= aman($u['username']) ?></strong>
                                <span class="rc-date"><i class="fa-regular fa-calendar"></i> <?= aman($u['tanggal'] ?? '') ?></span>
                            </div>
                            <span class="chip-rating"><i class="fa-solid fa-star"></i> <?= $r ?>.0</span>
                        </div>

                        <h3 class="rc-bootcamp">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span><?= aman($u['bootcamp']) ?></span>
                        </h3>

                        <div class="stars" aria-label="<?= $r ?> dari 5 bintang"><?= bintang($r) ?></div>

                        <p class="review-text"><?= aman($u['komentar']) ?></p>
                        <button type="button" class="read-more" hidden>Baca selengkapnya</button>

                        <div class="rc-footer">
                            <span class="badge <?= aman($u['status']) ?>"><?= aman($statusLabel[$u['status']]) ?></span>

                            <div class="rc-actions">
                                <?php if ($u['status'] !== 'approved'): ?>
                                    <button type="submit" class="mini green" name="satu" value="publish:<?= aman($u['id']) ?>">
                                        <i class="fa-regular fa-eye"></i> Publikasikan
                                    </button>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'rejected'): ?>
                                    <button type="submit" class="mini amber" name="satu" value="hide:<?= aman($u['id']) ?>">
                                        <i class="fa-regular fa-eye-slash"></i> Sembunyikan
                                    </button>
                                <?php endif; ?>
                                <button type="submit" class="mini red" name="satu" value="hapus:<?= aman($u['id']) ?>"
                                        data-confirm="Hapus ulasan ini?" aria-label="Hapus ulasan">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </form>

        <?php endif; ?>
    </main>
</div>

<script src="../js/rating.js"></script>
</body>
</html>
