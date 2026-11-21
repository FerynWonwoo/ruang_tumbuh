<?php
if (!isset($daftar)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../config/koneksimvc.php';
    require_once __DIR__ . '/../Helpers/rating_helpers.php';
    require_once __DIR__ . '/../Models/Rating.php';
    require_once __DIR__ . '/../Controllers/AdminRatingController.php';

    $ratingModel = new Rating($conn);
    $adminRatingController = new AdminRatingController($ratingModel);
    $adminRatingController->tampilkan();
    exit;
}

$e = 'rating_e';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Moderasi Ulasan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/css/rating.css?v=<?= @filemtime(__DIR__ . '/../../public/css/rating.css') ?>">
</head>
<body>

    <aside class="sidebar">
        <div>
            <div class="logo-text">Ruang<span>Tumbuh</span></div>

            <div class="nav-group" style="margin-top:32px;">
                <div class="nav-title">MENU ADMIN</div>
                <ul class="nav-menu">
                    <li><a href="<?= $e($basePath) ?>/app/Views/admindashboard.php" class="nav-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                    <li><a href="<?= $e($basePath) ?>/app/Views/adminkatalog.php" class="nav-link"><i class="fa-solid fa-shapes"></i> Katalog Program</a></li>
                    <li><a href="<?= $e($basePath) ?>/index.php?page=kategori" class="nav-link"><i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
                    <li><a href="<?= $e($basePath) ?>/index.php?page=admin-rating" class="nav-link active" aria-current="page"><i class="fa-solid fa-star"></i> Rating</a></li>
                </ul>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="<?= $e($basePath) ?>/app/Views/logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <div class="main-area">
        <header class="header">
            <h3>Dashboard Admin</h3>
            <div class="header-actions">
                <div class="profile-section">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user ?? 'Admin') ?>&background=F59E0B&color=fff" class="profile-avatar" alt="Avatar">
                    <div>
                        <div class="profile-name"><?= $e($nama_user ?? 'Admin') ?></div>
                        <div class="profile-role">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

    <main class="page">

        <section class="banner">
            <div>
                <span class="badge-tag"><i class="fa-solid fa-user-shield"></i> Admin</span>
                <h1>Moderasi Ulasan</h1>
                <p>Pilih ulasan mana yang dipublikasikan. Hanya ulasan yang dipublikasikan yang tampil di halaman peserta dan masuk hitungan rating.</p>
            </div>

            <div class="banner-stats">
                <div class="stat-pill">
                    <span class="stat-icon amber"><i class="fa-regular fa-clock"></i></span>
                    <span class="stat-text"><?= (int) $jumlah['pending'] ?> <small>menunggu</small></span>
                </div>
                <div class="stat-pill">
                    <span class="stat-icon"><i class="fa-regular fa-eye"></i></span>
                    <span class="stat-text"><?= (int) $jumlah['approved'] ?> <small>tayang</small></span>
                </div>
            </div>
        </section>

        <?php if ($pesan): ?>
            <p class="notice success" role="status"><?= $e($pesan) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= $e($error) ?></p>
        <?php endif; ?>

        <nav class="chips" aria-label="Filter status">
            <?php foreach ($tab as $kunci => $nama): ?>
                <a class="chip<?= $filter === $kunci ? ' active' : '' ?>"
                   href="<?= $e($basePath) ?>/index.php?page=admin-rating&amp;status=<?= $e($kunci) ?>">
                    <?= $e($nama) ?> <span class="chip-count"><?= (int) $jumlah[$kunci] ?></span>
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

        <form method="post" action="<?= $e($basePath) ?>/index.php?page=admin-rating-aksi" id="formModerasi">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
            <input type="hidden" name="filter" value="<?= $e($filter) ?>">

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
                    $r = max(1, min(5, (int) $u['rating']));
                    $avatar = 'av-' . (abs(crc32($u['username'])) % 6);
                    ?>
                    <article class="review-card r<?= $r ?>" data-rating="<?= $r ?>">
                        <div class="rc-head">
                            <label class="cek cek-kartu" aria-label="Pilih ulasan <?= $e($u['username']) ?>">
                                <input type="checkbox" name="ids[]" value="<?= $e($u['id']) ?>" data-item>
                            </label>
                            <div class="rc-avatar <?= $avatar ?>"><?= $e(strtoupper(mb_substr($u['username'], 0, 2))) ?></div>
                            <div class="rc-who">
                                <strong><?= $e($u['username']) ?></strong>
                                <span class="rc-date"><i class="fa-regular fa-calendar"></i><?= $e(rating_tanggal($u['tanggal'] ?? '')) ?></span>
                            </div>
                            <span class="chip-rating"><i class="fa-solid fa-star"></i> <?= $r ?>.0</span>
                        </div>

                        <h3 class="rc-bootcamp">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span><?= $e($u['bootcamp']) ?></span>
                        </h3>

                        <div class="stars" aria-label="<?= $r ?> dari 5 bintang"><?= bintang($r) ?></div>

                        <p class="review-text"><?= $e($u['komentar']) ?></p>
                        <button type="button" class="read-more" hidden>Baca selengkapnya</button>

                        <div class="rc-footer">
                            <span class="badge <?= $e($u['status']) ?>"><?= $e($statusLabel[$u['status']] ?? $u['status']) ?></span>

                            <div class="rc-actions">
                                <?php if ($u['status'] !== 'approved'): ?>
                                    <button type="submit" class="mini green" name="satu" value="publish:<?= $e($u['id']) ?>">
                                        <i class="fa-regular fa-eye"></i> Publikasikan
                                    </button>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'rejected'): ?>
                                    <button type="submit" class="mini amber" name="satu" value="hide:<?= $e($u['id']) ?>">
                                        <i class="fa-regular fa-eye-slash"></i> Sembunyikan
                                    </button>
                                <?php endif; ?>
                                <button type="submit" class="mini red" name="satu" value="hapus:<?= $e($u['id']) ?>"
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

<script src="<?= $e($basePath) ?>/public/js/rating.js?v=<?= @filemtime(__DIR__ . '/../../public/js/rating.js') ?>"></script>
</body>
</html>
