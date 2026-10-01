<?php
session_start();

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// =========================
// HELPER
// =========================
const DATA_FILE = __DIR__ . '/../data/ulasan.json';
const MAX_BOOTCAMP = 150;
const MAX_KOMENTAR = 2000;

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function input($nama)
{
    $nilai = $_POST[$nama] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
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

// Baca-ubah-tulis dengan file lock supaya aman dari tabrakan
function ubahUlasan(callable $fn): void
{
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $fp = fopen(DATA_FILE, 'c+');
    flock($fp, LOCK_EX);

    $data = json_decode((string) stream_get_contents($fp), true);
    $data = $fn(is_array($data) ? $data : []);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function idValid(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
}

$username = $_SESSION['username'];
$admin    = ($_SESSION['role'] ?? 'user') === 'admin';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$pesan = $_SESSION['pesan_ulasan'] ?? '';
unset($_SESSION['pesan_ulasan']);

$form = ['id' => '', 'bootcamp' => '', 'rating' => 5, 'komentar' => ''];

// =========================
// PROSES FORM
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = input('aksi');
    $id   = input('id');

    if (!hash_equals($_SESSION['csrf'], input('csrf'))) {
        $error = 'Muat ulang halaman, lalu coba lagi.';
    }

    // ---- Simpan / edit (user) ----
    elseif ($aksi === 'simpan' && !$admin) {

        $form = [
            'id'       => $id,
            'bootcamp' => input('bootcamp'),
            'rating'   => (int) input('rating'),
            'komentar' => input('komentar'),
        ];

        if ($form['bootcamp'] === '' || $form['komentar'] === '') {
            $error = 'Nama bootcamp dan ulasan wajib diisi.';
        } elseif (
            mb_strlen($form['bootcamp']) > MAX_BOOTCAMP ||
            mb_strlen($form['komentar']) > MAX_KOMENTAR
        ) {
            $error = 'Nama bootcamp atau ulasan terlalu panjang.';
        } elseif ($form['rating'] < 1 || $form['rating'] > 5) {
            $error = 'Pilih rating 1 sampai 5.';
        } elseif ($id !== '' && !idValid($id)) {
            $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
        } else {
            ubahUlasan(function ($data) use (&$error, &$pesan, $form, $id, $username) {
                $lama = $data[$id] ?? null;

                if ($id !== '' && (!$lama || $lama['username'] !== $username)) {
                    $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
                    return $data;
                }

                $idBaru = $id !== '' ? $id : bin2hex(random_bytes(8));

                // Langsung tayang tanpa verifikasi admin.
                // Kalau admin sudah menyembunyikannya, tetap tersembunyi walau diedit.
                $disembunyikan = ($lama['status'] ?? '') === 'rejected';

                $data[$idBaru] = [
                    'id'       => $idBaru,
                    'username' => $username,
                    'bootcamp' => $form['bootcamp'],
                    'rating'   => $form['rating'],
                    'komentar' => $form['komentar'],
                    'status'   => $disembunyikan ? 'rejected' : 'approved',
                    'tanggal'  => $lama['tanggal'] ?? date('d/m/Y'),
                ];

                $pesan = $disembunyikan
                    ? 'Ulasan diperbarui, tetapi sedang disembunyikan admin.'
                    : ($id !== '' ? 'Ulasan berhasil diperbarui.' : 'Ulasan berhasil dipublikasikan.');
                return $data;
            });
        }
    }

    // ---- Tarik ulasan (user) ----
    elseif ($aksi === 'tarik' && !$admin && idValid($id)) {
        ubahUlasan(function ($data) use (&$error, &$pesan, $id, $username) {
            if (!isset($data[$id]) || $data[$id]['username'] !== $username) {
                $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
                return $data;
            }
            unset($data[$id]);
            $pesan = 'Ulasan berhasil ditarik.';
            return $data;
        });
    }

    // ---- Moderasi (admin) ----
    elseif ($admin && idValid($id) && in_array($aksi, ['approved', 'rejected', 'hapus'], true)) {
        ubahUlasan(function ($data) use (&$error, &$pesan, $id, $aksi) {
            if (!isset($data[$id])) {
                $error = 'Ulasan tidak ditemukan.';
                return $data;
            }
            if ($aksi === 'hapus') {
                unset($data[$id]);
                $pesan = 'Ulasan berhasil dihapus.';
            } else {
                $data[$id]['status'] = $aksi;
                $pesan = 'Status ulasan berhasil diperbarui.';
            }
            return $data;
        });
    }

    else {
        $error = 'Tindakan tidak diizinkan atau ulasan tidak ditemukan.';
    }

    if ($error === '') {
        $_SESSION['pesan_ulasan'] = $pesan;
        header('Location: rating.php');
        exit;
    }
}

// =========================
// MODE EDIT
// =========================
$editId = $_GET['edit'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && is_string($editId) && $editId !== '' && !$admin) {
    $data = bacaUlasan()[$editId] ?? null;

    if ($data && $data['username'] === $username) {
        $form = $data;
    } else {
        $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
    }
}

// =========================
// DAFTAR & RATING
// =========================
$semua        = array_reverse(bacaUlasan(), true);
$daftarUlasan = [];
$totalRating  = 0;
$totalPublik  = 0;
$jumlahPending = 0;

foreach ($semua as $u) {
    if ($u['status'] === 'approved') {
        $totalRating += $u['rating'];
        $totalPublik++;
    }
    if ($u['status'] === 'pending') {
        $jumlahPending++;
    }
    if ($admin || $u['status'] === 'approved' || $u['username'] === $username) {
        $daftarUlasan[] = $u;
    }
}

$rataRating = $totalPublik > 0 ? number_format($totalRating / $totalPublik, 1) : '—';

$statusLabel = [
    'pending'  => 'Menunggu moderasi',
    'approved' => 'Dipublikasikan',
    'rejected' => 'Disembunyikan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan &amp; Rating | Ruang Tumbuh</title>

    <link rel="stylesheet" href="../css/rating.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>

        <div class="nav-group first">
            <div class="nav-title">MENU UTAMA</div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-table-cells-large"></i>Dashboard</a></li>
                <li><a href="katalog.php" class="nav-link"><i class="fa-solid fa-shapes"></i>Katalog Bootcamp</a></li>
                <li><a href="kategori.php" class="nav-link"><i class="fa-solid fa-layer-group"></i>Kategori Program</a></li>
                <li><a href="bookmark.php" class="nav-link"><i class="fa-regular fa-bookmark"></i>Bookmark</a></li>
                <li><a href="pengingat.php" class="nav-link"><i class="fa-regular fa-clock"></i>Pengingat Tenggat</a></li>
                <li><a href="catatan.php" class="nav-link"><i class="fa-regular fa-pen-to-square"></i>Catatan Pribadi</a></li>
                <li><a href="rating.php" class="nav-link active"><i class="fa-regular fa-star"></i>Ulasan &amp; Rating</a></li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>

<div class="main-area">

    <header class="header">
`        <a href="profile.php" class="profile-section" title="Lihat profil">
            <div class="profile-info">
                <div class="profile-name"><?= aman($username) ?></div>
                <div class="profile-role"><?= $admin ? 'Admin' : 'Pengguna' ?></div>
            </div>
            <div class="avatar"> 
                <?= aman(strtoupper(mb_substr($username, 0, 2))) ?>
            </div>
        </a>`
    </header>

    <main class="page">

        <!-- BANNER -->
        <section class="banner">
            <div class="banner-text">
                <span class="badge-tag">Peserta Bootcamp</span>
                <h1>Ulasan &amp; Rating</h1>
                <p>
                    <?= $admin
                        ? 'Pantau ulasan peserta. Ulasan yang tidak pantas bisa disembunyikan atau dihapus.'
                        : 'Bagikan pengalamanmu mengikuti bootcamp dan bantu peserta lain memilih program terbaik.' ?>
                </p>
            </div>

            <div class="banner-stats">
                <div class="stat-pill">
                    <span class="stat-icon"><i class="fa-solid fa-star"></i></span>
                    <span class="stat-text"><?= $rataRating ?> <small>/ 5 · <?= $totalPublik ?> ulasan</small></span>
                </div>
            </div>
        </section>

        <?php if ($pesan): ?>
            <p class="notice success" role="status"><?= aman($pesan) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= aman($error) ?></p>
        <?php endif; ?>

        <!-- FORM (user) -->
        <?php if (!$admin): ?>
            <section class="card form-card" id="form-ulasan">
                <div class="form-head">
                    <div>
                        <h2><?= $form['id'] !== '' ? 'Edit Ulasan' : 'Tulis Ulasanmu' ?></h2>
                        <p class="muted">Bagaimana pengalaman belajarmu? Ulasanmu langsung tampil setelah dikirim.</p>
                    </div>
                </div>

                <form method="post" action="rating.php">
                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= aman($form['id']) ?>">

                    <div class="form-grid">
                        <div>
                            <label for="bootcamp">Nama bootcamp</label>
                            <input type="text" id="bootcamp" name="bootcamp"
                                   value="<?= aman($form['bootcamp']) ?>"
                                   placeholder="Contoh: UI/UX Design Bootcamp"
                                   maxlength="<?= MAX_BOOTCAMP ?>" required>
                        </div>

                        <fieldset class="rating-field">
                            <legend>Penilaianmu</legend>
                            <div class="rating-options">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-option">
                                        <input type="radio" name="rating" value="<?= $i ?>"
                                               <?= (int) $form['rating'] === $i ? 'checked' : '' ?> required>
                                        <span><?= $i ?> ★</span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                    </div>

                    <label for="komentar">Ceritakan pengalamanmu</label>
                    <textarea id="komentar" name="komentar" rows="4"
                              maxlength="<?= MAX_KOMENTAR ?>"
                              placeholder="Ceritakan materi, mentor, dan pengalaman belajarmu."
                              required><?= aman($form['komentar']) ?></textarea>

                    <p class="hint">Tips: bahas materi, mentor, dan proses belajar. Gunakan bahasa sopan dan jangan bagikan data pribadi.</p>

                    <div class="actions">
                        <?php if ($form['id'] !== ''): ?>
                            <a href="rating.php" class="button secondary">Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="button">
                            <?= $form['id'] !== '' ? 'Simpan Perubahan' : 'Kirim Ulasan' ?>
                        </button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <!-- DAFTAR -->
        <div class="list-heading">
            <h2><?= $admin ? 'Moderasi Ulasan' : 'Pengalaman Peserta' ?></h2>
            <span><?= count($daftarUlasan) ?> ulasan</span>
        </div>

        <?php if (!$daftarUlasan): ?>
            <section class="card empty">
                <div class="empty-icon"><i class="fa-regular fa-star"></i></div>
                <h3>Belum ada ulasan</h3>
                <p class="muted">
                    <?= $admin
                        ? 'Ulasan peserta akan muncul di sini.'
                        : 'Yuk, bagikan pengalaman bootcamp pertamamu!' ?>
                </p>
            </section>
        <?php endif; ?>

        <div class="review-grid" id="daftar">
            <?php foreach ($daftarUlasan as $u): ?>
                <?php $r = (int) $u['rating']; ?>
                <article class="review-card">

                    <div class="rc-top">
                        <span class="chip-rating"><i class="fa-solid fa-star"></i> <?= $r ?>.0</span>
                        <span class="badge <?= aman($u['status']) ?>">
                            <?= aman($statusLabel[$u['status']] ?? $u['status']) ?>
                        </span>
                    </div>

                    <h3><?= aman($u['bootcamp']) ?></h3>

                    <p class="rc-user">
                        <i class="fa-regular fa-user"></i> <?= aman($u['username']) ?>
                        <span class="stars" aria-label="<?= $r ?> dari 5 bintang"><?= str_repeat('★', $r) ?><span><?= str_repeat('☆', 5 - $r) ?></span></span>
                    </p>

                    <p class="review-text" title="Klik untuk membaca selengkapnya"><?= aman($u['komentar']) ?></p>

                    <div class="rc-footer">
                        <span class="rc-date"><i class="fa-regular fa-calendar"></i> <?= aman($u['tanggal']) ?></span>

                        <?php if ($admin || $u['username'] === $username): ?>
                            <div class="rc-actions">
                                <?php if (!$admin): ?>
                                    <a class="mini blue" href="rating.php?edit=<?= aman($u['id']) ?>#form-ulasan">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </a>
                                <?php endif; ?>

                                <form method="post" action="rating.php">
                                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="id" value="<?= aman($u['id']) ?>">

                                    <?php if ($admin): ?>
                                        <?php if ($u['status'] !== 'approved'): ?>
                                            <button type="submit" class="mini green" name="aksi" value="approved">
                                                <i class="fa-regular fa-eye"></i> Tampilkan
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($u['status'] !== 'rejected'): ?>
                                            <button type="submit" class="mini amber" name="aksi" value="rejected">
                                                <i class="fa-regular fa-eye-slash"></i> Sembunyikan
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" class="mini red" name="aksi" value="hapus"
                                                data-confirm="Hapus ulasan ini?">
                                            <i class="fa-regular fa-trash-can"></i> Hapus
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="mini red" name="aksi" value="tarik"
                                                data-confirm="Tarik ulasan ini? Ulasan akan dihapus.">
                                            <i class="fa-regular fa-trash-can"></i> Tarik
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<script>
    // Konfirmasi hapus/tarik
    document.addEventListener('click', function (e) {
        var tombol = e.target.closest('[data-confirm]');
        if (tombol && !confirm(tombol.dataset.confirm)) {
            e.preventDefault();
            return;
        }
        // Buka/tutup teks ulasan panjang
        var teks = e.target.closest('.review-text');
        if (teks) teks.classList.toggle('open');
    });
</script>

</body>
</html>