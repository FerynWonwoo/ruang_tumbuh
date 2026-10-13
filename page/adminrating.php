<?php
session_start();

// Hanya admin yang boleh membuka halaman ini
if (empty($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
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

if (!isset($_SESSION['ulasan'])) {
    $_SESSION['ulasan'] = [];
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$pesan = $_SESSION['pesan_admin_rating'] ?? '';
$error = $_SESSION['error_admin_rating'] ?? '';

unset($_SESSION['pesan_admin_rating']);
unset($_SESSION['error_admin_rating']);

// ======================================================
// PROSES MODERASI
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = input('csrf');
    $id = input('id');
    $aksi = input('aksi');

    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $_SESSION['error_admin_rating'] =
            'Sesi tidak valid. Silakan muat ulang halaman.';
    } elseif (!isset($_SESSION['ulasan'][$id])) {
        $_SESSION['error_admin_rating'] =
            'Ulasan tidak ditemukan.';
    } else {

        if ($aksi === 'approve') {

            $_SESSION['ulasan'][$id]['status'] = 'approved';

            $_SESSION['pesan_admin_rating'] =
                'Ulasan berhasil dipublikasikan.';

        } elseif ($aksi === 'reject') {

            $_SESSION['ulasan'][$id]['status'] = 'rejected';

            $_SESSION['pesan_admin_rating'] =
                'Ulasan berhasil ditolak.';

        } elseif ($aksi === 'delete') {

            unset($_SESSION['ulasan'][$id]);

            $_SESSION['pesan_admin_rating'] =
                'Ulasan berhasil dihapus.';

        } else {

            $_SESSION['error_admin_rating'] =
                'Aksi tidak dikenali.';
        }
    }

    header('Location: adminrating.php');
    exit;
}

// ======================================================
// HITUNG STATISTIK
// ======================================================
$totalUlasan = count($_SESSION['ulasan']);
$totalPending = 0;
$totalApproved = 0;
$totalRejected = 0;

foreach ($_SESSION['ulasan'] as $ulasan) {

    $status = $ulasan['status'] ?? 'pending';

    if ($status === 'pending') {
        $totalPending++;
    } elseif ($status === 'approved') {
        $totalApproved++;
    } elseif ($status === 'rejected') {
        $totalRejected++;
    }
}

// Data terbaru ditampilkan di atas
$daftarUlasan = array_reverse($_SESSION['ulasan'], true);

$statusLabel = [
    'pending' => 'Menunggu',
    'approved' => 'Dipublikasikan',
    'rejected' => 'Ditolak'
];

$namaAdmin = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Moderasi Ulasan | Ruang Tumbuh</title>

    <link rel="stylesheet" href="../css/rating.css">

    <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="../js/adminrating.js" defer></script>
</head>

<body class="admin-rating">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo-text">
            Ruang<span>Tumbuh</span>
        </div>

        <div class="nav-group">

            <div class="nav-title">
                PANEL ADMIN
            </div>

            <ul class="nav-menu">

                <li>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="adminkategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i>
                        Kelola Kategori
                    </a>
                </li>

                <li>
                    <a href="adminrating.php" class="nav-link active">
                        <i class="fa-regular fa-star"></i>
                        Moderasi Ulasan
                    </a>
                </li>

            </ul>

        </div>

    </aside>


    <!-- MAIN -->
    <div class="main-area">

        <!-- HEADER -->
        <header class="header">

            <div>
                <h3>Moderasi Ulasan</h3>
                <p>
                    Kelola ulasan dan penilaian dari pengguna.
                </p>
            </div>

            <div class="admin-profile">

                <div>
                    <strong><?= aman($namaAdmin) ?></strong>
                    <span>Administrator</span>
                </div>

                <div class="avatar">
                    <?= strtoupper(substr($namaAdmin, 0, 1)) ?>
                </div>

            </div>

        </header>


        <main class="content">

            <!-- NOTIFIKASI -->
            <?php if ($pesan !== ''): ?>
                <div class="alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= aman($pesan) ?>
                </div>
            <?php endif; ?>


            <?php if ($error !== ''): ?>
                <div class="alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= aman($error) ?>
                </div>
            <?php endif; ?>


            <!-- JUDUL -->
            <section class="page-heading">

                <div>
                    <p class="eyebrow">
                        REVIEW & RATING
                    </p>

                    <h1>
                        Pengelolaan Ulasan
                    </h1>

                    <p>
                        Tinjau, publikasikan, tolak, atau hapus ulasan pengguna.
                    </p>
                </div>

                <div class="heading-icon">
                    <i class="fa-solid fa-star"></i>
                </div>

            </section>


            <!-- STATISTIK -->
            <section class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-regular fa-message"></i>
                    </div>

                    <div>
                        <span>Total Ulasan</span>
                        <strong><?= $totalUlasan ?></strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon pending-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>

                    <div>
                        <span>Menunggu</span>
                        <strong><?= $totalPending ?></strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon approved-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <span>Dipublikasikan</span>
                        <strong><?= $totalApproved ?></strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon rejected-icon">
                        <i class="fa-solid fa-xmark"></i>
                    </div>

                    <div>
                        <span>Ditolak</span>
                        <strong><?= $totalRejected ?></strong>
                    </div>

                </div>

            </section>


            <!-- DAFTAR ULASAN -->
            <section class="review-section">

                <div class="review-header">

                    <div>
                        <h2>Daftar Ulasan</h2>

                        <p>
                            Pilih ulasan yang akan dimoderasi.
                        </p>
                    </div>

                    <!-- FILTER -->
                    <div class="filters">

                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="searchReview"
                                placeholder="Cari pengguna atau bootcamp...">
                        </div>

                        <select id="filterStatus">

                            <option value="all">
                                Semua Status
                            </option>

                            <option value="pending">
                                Menunggu
                            </option>

                            <option value="approved">
                                Dipublikasikan
                            </option>

                            <option value="rejected">
                                Ditolak
                            </option>

                        </select>

                    </div>

                </div>


                <?php if (empty($daftarUlasan)): ?>

                    <div class="empty-state">

                        <i class="fa-regular fa-message"></i>

                        <h3>Belum ada ulasan</h3>

                        <p>
                            Ulasan yang dikirim pengguna akan muncul di sini.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="review-list" id="reviewList">

                        <?php foreach ($daftarUlasan as $ulasan): ?>

                            <?php
                            $status = $ulasan['status'] ?? 'pending';
                            $rating = (int) ($ulasan['rating'] ?? 0);
                            ?>

                            <article
                                class="review-card"
                                data-status="<?= aman($status) ?>"
                                data-search="<?= aman(
                                    strtolower(
                                        ($ulasan['username'] ?? '') .
                                        ' ' .
                                        ($ulasan['bootcamp'] ?? '')
                                    )
                                ) ?>">

                                <div class="review-top">

                                    <div class="user">

                                        <div class="user-avatar">
                                            <?= strtoupper(
                                                substr(
                                                    $ulasan['username'] ?? 'U',
                                                    0,
                                                    1
                                                )
                                            ) ?>
                                        </div>

                                        <div>

                                            <strong>
                                                <?= aman($ulasan['username'] ?? '-') ?>
                                            </strong>

                                            <span>
                                                <?= aman($ulasan['tanggal'] ?? '-') ?>
                                            </span>

                                        </div>

                                    </div>


                                    <span class="status <?= aman($status) ?>">
                                        <?= aman(
                                            $statusLabel[$status] ?? $status
                                        ) ?>
                                    </span>

                                </div>


                                <div class="review-content">

                                    <h3>
                                        <?= aman($ulasan['bootcamp'] ?? '-') ?>
                                    </h3>


                                    <div
                                        class="stars"
                                        aria-label="<?= $rating ?> dari 5 bintang">

                                        <?php for ($i = 1; $i <= 5; $i++): ?>

                                            <?php if ($i <= $rating): ?>
                                                <i class="fa-solid fa-star"></i>
                                            <?php else: ?>
                                                <i class="fa-regular fa-star"></i>
                                            <?php endif; ?>

                                        <?php endfor; ?>

                                        <span>
                                            <?= $rating ?>/5
                                        </span>

                                    </div>


                                    <p>
                                        <?= nl2br(
                                            aman($ulasan['komentar'] ?? '')
                                        ) ?>
                                    </p>

                                </div>


                                <div class="review-actions">

                                    <?php if ($status !== 'approved'): ?>

                                        <form
                                            method="post"
                                            action="adminrating.php">

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= aman($_SESSION['csrf']) ?>">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= aman($ulasan['id']) ?>">

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="approve"
                                                class="btn approve">

                                                <i class="fa-solid fa-check"></i>
                                                Setujui

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php if ($status !== 'rejected'): ?>

                                        <form
                                            method="post"
                                            action="adminrating.php">

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= aman($_SESSION['csrf']) ?>">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= aman($ulasan['id']) ?>">

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="reject"
                                                class="btn reject">

                                                <i class="fa-solid fa-xmark"></i>
                                                Tolak

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <form
                                        method="post"
                                        action="adminrating.php"
                                        class="delete-form">

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= aman($_SESSION['csrf']) ?>">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= aman($ulasan['id']) ?>">

                                        <button
                                            type="submit"
                                            name="aksi"
                                            value="delete"
                                            class="btn delete">

                                            <i class="fa-regular fa-trash-can"></i>
                                            Hapus

                                        </button>

                                    </form>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>


                    <div
                        class="empty-filter"
                        id="emptyFilter"
                        hidden>

                        <i class="fa-solid fa-filter-circle-xmark"></i>

                        <h3>Ulasan tidak ditemukan</h3>

                        <p>
                            Coba gunakan kata kunci atau status yang berbeda.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</body>
</html>