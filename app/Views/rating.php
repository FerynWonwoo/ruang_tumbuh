<?php
/** View admin. Variabel dari AdminRatingController::tampilkan() */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Ulasan | Ruang Tumbuh</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/rating.css?v=<?= filemtime(ROOT_PATH . '/assets/css/rating.css') ?>">
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
                    <a href="page/admindashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="page/adminkatalog.php" class="nav-link">
                        <i class="fa-solid fa-shapes"></i> Katalog Bootcamp
                    </a>
                </li>
                <li>
                    <a href="page/adminkategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i> Kategori Program
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/rating') ?>" class="nav-link active">
                        <i class="fa-solid fa-star"></i> Ulasan &amp; Rating
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="page/logout.php" class="logout-link">
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
                   href="<?= url('admin/rating', ['status' => $kunci]) ?>">
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

        <form method="post" action="<?= url('admin/rating/aksi') ?>" id="formModerasi">
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

<script src="assets/js/rating.js"></script>
</body>
</html>
