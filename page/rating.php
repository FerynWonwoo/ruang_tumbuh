<?php
session_start();

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function input($nama)
{
    $nilai = $_POST[$nama] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
}

$username = $_SESSION['username'];
$admin = ($_SESSION['role'] ?? 'user') === 'admin';

// Tempat menyimpan ulasan tanpa database.
if (!isset($_SESSION['ulasan'])) {
    $_SESSION['ulasan'] = [];
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$pesan = $_SESSION['pesan_ulasan'] ?? '';
unset($_SESSION['pesan_ulasan']);

$form = [
    'id' => '',
    'bootcamp' => '',
    'rating' => 5,
    'komentar' => ''
];

// Proses formulir.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = input('aksi');
    $id = input('id');
    $ulasan = $_SESSION['ulasan'][$id] ?? null;
    $milikSendiri = $ulasan && $ulasan['username'] === $username;

    if (!hash_equals($_SESSION['csrf'], input('csrf'))) {
        $error = 'Muat ulang halaman, lalu coba lagi.';
    } elseif ($aksi === 'simpan' && !$admin) {
        $form = [
            'id' => $id,
            'bootcamp' => input('bootcamp'),
            'rating' => (int) input('rating'),
            'komentar' => input('komentar')
        ];

        if ($id !== '' && !$milikSendiri) {
            $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
        } elseif ($form['bootcamp'] === '' || $form['komentar'] === '') {
            $error = 'Nama bootcamp dan ulasan wajib diisi.';
        } elseif (
            strlen($form['bootcamp']) > 600 ||
            strlen($form['komentar']) > 8000
        ) {
            $error = 'Nama bootcamp atau ulasan terlalu panjang.';
        } elseif ($form['rating'] < 1 || $form['rating'] > 5) {
            $error = 'Pilih rating 1 sampai 5.';
        } else {
            // Buat ID untuk ulasan baru.
            if ($id === '') {
                $id = bin2hex(random_bytes(8));
            }

            $_SESSION['ulasan'][$id] = [
                'id' => $id,
                'username' => $username,
                'bootcamp' => $form['bootcamp'],
                'rating' => $form['rating'],
                'komentar' => $form['komentar'],
                'status' => 'pending',
                'tanggal' => $ulasan['tanggal'] ?? date('d/m/Y')
            ];

            $pesan = 'Ulasan disimpan dan menunggu moderasi admin.';
        }
    } elseif ($aksi === 'tarik' && !$admin && $milikSendiri) {
        unset($_SESSION['ulasan'][$id]);
        $pesan = 'Ulasan berhasil ditarik.';
    } elseif (
        $admin && $ulasan &&
        in_array($aksi, ['approved', 'rejected', 'hapus'], true)
    ) {
        if ($aksi === 'hapus') {
            unset($_SESSION['ulasan'][$id]);
            $pesan = 'Ulasan berhasil dihapus.';
        } else {
            $_SESSION['ulasan'][$id]['status'] = $aksi;
            $pesan = 'Status ulasan berhasil diperbarui.';
        }
    } else {
        $error = 'Tindakan tidak diizinkan atau ulasan tidak ditemukan.';
    }

    if ($error === '') {
        $_SESSION['pesan_ulasan'] = $pesan;
        header('Location: rating.php');
        exit;
    }
}

// Ambil ulasan yang akan diedit.
$editId = $_GET['edit'] ?? '';

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    is_string($editId) && $editId !== '' && !$admin
) {
    $data = $_SESSION['ulasan'][$editId] ?? null;

    if ($data && $data['username'] === $username) {
        $form = $data;
    } else {
        $error = 'Ulasan tidak ditemukan atau bukan milikmu.';
    }
}

// Menyiapkan daftar ulasan dan menghitung rating.
$daftarUlasan = [];
$totalRating = 0;
$totalPublik = 0;

foreach (array_reverse($_SESSION['ulasan'], true) as $ulasan) {
    if ($ulasan['status'] === 'approved') {
        $totalRating += $ulasan['rating'];
        $totalPublik++;
    }

    if (
        $admin ||
        $ulasan['status'] === 'approved' ||
        $ulasan['username'] === $username
    ) {
        $daftarUlasan[] = $ulasan;
    }
}

$rataRating = $totalPublik > 0
    ? number_format($totalRating / $totalPublik, 1)
    : '—';

$statusLabel = [
    'pending' => 'Menunggu moderasi',
    'approved' => 'Dipublikasikan',
    'rejected' => 'Ditolak'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan & Rating | Ruang Tumbuh</title>
    <link rel="stylesheet" href="../css/rating.css">
    <script src="../js/ulasan.js" defer></script>
    <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
      <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="logo-text">Ruang<span>Tumbuh</span></div>
            
            <div class="nav-group" style="margin-top: 32px;">
                <div class="nav-title">MENU UTAMA</div>
                <ul class="nav-menu">
                    <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                    <li><a href="#" class="nav-link"><i class="fa-solid fa-shapes"></i> Katalog Bootcamp</a></li>
                    <li><a href="kategori.php" class="nav-link"><i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
                    <li><a href="bookmark.php" class="nav-link"><i class="fa-regular fa-bookmark"></i> Bookmark</a></li>
                    <li><a href="pengingat.php" class="nav-link"><i class="fa-regular fa-clock"></i> Pengingat Tenggat</a></li>
                    <li><a href="#" class="nav-link"><i class="fa-regular fa-pen-to-square"></i> Catatan Pribadi</a></li>
                    <li><a href="rating.php" class="nav-link active"><i class="fa-regular fa-star"></i> Ulasan & Rating</a></li>
                </ul>
            </div>

            <div class="nav-group">
                <div class="nav-title">AKUN</div>
                <ul class="nav-menu">
                    <li><a href="profile.php" class="nav-link"><i class="fa-regular fa-user"></i> Profil Pengguna</a></li>
                </ul>
            </div>
        </div>
         <a href="logout.php" class="logout-link">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>
    </aside>
<main>
    <nav class="breadcrumb">
        <a href="dashboard.php">Dashboard</a> / Ulasan & Rating
    </nav>

    <section class="page-heading">
        <div>
            <p class="eyebrow">BELAJAR DARI PENGALAMAN</p>
            <h1>Ulasan & Rating</h1>
            <p class="muted">
                <?= $admin
                    ? 'Kelola ulasan pengalaman belajar peserta.'
                    : 'Bagikan pengalamanmu mengikuti bootcamp.' ?>
            </p>
        </div>

        <span class="heading-icon" aria-hidden="true">★</span>
    </section>

    <?php if ($pesan): ?>
        <p class="notice success" role="status"><?= aman($pesan) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="notice error" role="alert"><?= aman($error) ?></p>
    <?php endif; ?>

    <div class="layout">
        <aside>
            <section class="card summary">
                <h2>Penilaian Komunitas</h2>
                <div class="score">
                    <?= $rataRating ?> <span>/ 5</span>
                </div>
                <p class="muted">
                    Dari <?= $totalPublik ?> ulasan yang dipublikasikan.
                </p>
                <p class="hint">Gabungan penilaian seluruh bootcamp.</p>
            </section>

            <section class="card guidelines">
                <h2>Ulasan yang membantu</h2>
                <ul>
                    <li>Ceritakan pengalaman mengikuti bootcamp.</li>
                    <li>Bahas materi, mentor, dan proses belajar.</li>
                    <li>Gunakan bahasa yang sopan.</li>
                    <li>Hindari membagikan data pribadi.</li>
                </ul>
            </section>
        </aside>

        <div class="content">
            <?php if (!$admin): ?>
                <section class="card" id="form-ulasan">
                    <h2>
                        <?= $form['id'] !== '' ? 'Edit Ulasan' : 'Tulis Ulasanmu' ?>
                    </h2>
                    <p class="muted">Bagaimana pengalaman belajarmu?</p>

                    <form method="post" action="rating.php">
                        <input type="hidden" name="csrf"
                               value="<?= aman($_SESSION['csrf']) ?>">
                        <input type="hidden" name="aksi" value="simpan">
                        <input type="hidden" name="id"
                               value="<?= aman($form['id']) ?>">

                        <label for="bootcamp">Nama bootcamp</label>
                        <input type="text" id="bootcamp" name="bootcamp"
                               value="<?= aman($form['bootcamp']) ?>"
                               placeholder="Contoh: UI/UX Design Bootcamp"
                               maxlength="150" required>

                        <fieldset class="rating-field">
                            <legend>Penilaianmu</legend>

                            <div class="rating-options">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-option">
                                        <input type="radio" name="rating"
                                               value="<?= $i ?>"
                                               <?= (int) $form['rating'] === $i ? 'checked' : '' ?>
                                               required>
                                        <span><?= $i ?> ★</span>
                                    </label>
                                <?php endfor; ?>
                            </div>

                            <p class="hint">
                                1 = Sangat kurang · 5 = Sangat baik
                            </p>
                        </fieldset>

                        <label for="komentar">Ceritakan pengalamanmu</label>
                        <textarea id="komentar" name="komentar"
                                  rows="5" maxlength="2000"
                                  placeholder="Ceritakan materi, mentor, dan pengalaman belajarmu."
                                  required><?= aman($form['komentar']) ?></textarea>

                        <p class="hint">
                            Ulasan baru maupun yang diedit akan ditinjau admin.
                        </p>

                        <div class="actions">
                            <?php if ($form['id'] !== ''): ?>
                                <a href="rating.php" class="button secondary">
                                    Batal
                                </a>
                            <?php endif; ?>

                            <button type="submit" class="button">
                                <?= $form['id'] !== ''
                                    ? 'Simpan Perubahan'
                                    : 'Kirim Ulasan' ?>
                            </button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <div class="list-heading">
                <h2>
                    <?= $admin ? 'Moderasi Ulasan' : 'Pengalaman Peserta' ?>
                </h2>
                <span><?= count($daftarUlasan) ?> ulasan</span>
            </div>

            <?php if (!$daftarUlasan): ?>
                <section class="card empty">
                    <h3>Belum ada ulasan</h3>
                    <p class="muted">
                        <?= $admin
                            ? 'Ulasan peserta akan muncul di sini.'
                            : 'Yuk, bagikan pengalaman bootcamp pertamamu!' ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php foreach ($daftarUlasan as $ulasan): ?>
                <article class="card review">
                    <div class="review-top">
                        <div>
                            <strong><?= aman($ulasan['username']) ?></strong>
                            <p class="date"><?= aman($ulasan['tanggal']) ?></p>
                        </div>

                        <span class="stars"
                              aria-label="<?= $ulasan['rating'] ?> dari 5 bintang">
                            <?= str_repeat('★', $ulasan['rating']) ?>
                            <span><?= str_repeat('☆', 5 - $ulasan['rating']) ?></span>
                        </span>
                    </div>

                    <h3><?= aman($ulasan['bootcamp']) ?></h3>
                    <p class="review-text"><?= aman($ulasan['komentar']) ?></p>

                    <?php if ($admin || $ulasan['username'] === $username): ?>
                        <div class="review-footer">
                            <span class="badge <?= aman($ulasan['status']) ?>">
                                <?= $statusLabel[$ulasan['status']] ?>
                            </span>

                            <div class="review-actions">
                                <?php if (!$admin): ?>
                                    <a class="text-button"
                                       href="rating.php?edit=<?= aman($ulasan['id']) ?>#form-ulasan">
                                        Edit
                                    </a>
                                <?php endif; ?>

                                <form method="post" action="rating.php"
                                      class="review-actions">
                                    <input type="hidden" name="csrf"
                                           value="<?= aman($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="id"
                                           value="<?= aman($ulasan['id']) ?>">

                                    <?php if ($admin): ?>
                                        <?php if ($ulasan['status'] !== 'approved'): ?>
                                            <button class="text-button approve"
                                                    name="aksi" value="approved">
                                                Setujui
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($ulasan['status'] !== 'rejected'): ?>
                                            <button class="text-button"
                                                    name="aksi" value="rejected">
                                                Tolak
                                            </button>
                                        <?php endif; ?>

                                        <button class="text-button danger"
                                                name="aksi" value="hapus"
                                                data-confirm="Hapus ulasan ini?">
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <button class="text-button danger"
                                                name="aksi" value="tarik"
                                                data-confirm="Tarik ulasan ini? Ulasan akan dihapus.">
                                            Tarik Ulasan
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</main>

</body>
</html>