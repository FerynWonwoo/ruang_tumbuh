<?php
if (!defined('CATATAN_MVC_RENDER')) {
    ob_start();

    if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Pengguna';

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    require_once __DIR__ . '/../Models/Catatan.php';
    require_once __DIR__ . '/../Controllers/CatatanController.php';

    $paths = CatatanController::resolvePaths();

    // Tolak akses langsung lewat URL tanpa sesi login sebelum menyentuh database
    if (empty($_SESSION['user_id']) && empty($_SESSION['username'])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: ' . $paths['loginUrl'], true, 302);
        exit;
    }

    try {
        // Bersihkan potensi whitespace output dari file koneksi kelompok tanpa mengubah file tersebut
        ob_start();
        require_once __DIR__ . '/../../config/koneksimvc.php';
        ob_end_clean();
    } catch (Throwable $e) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        error_log('Catatan DB connection error: ' . $e->getMessage());
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gangguan Koneksi | RuangTumbuh</title></head><body style="margin:0;font-family:Inter,Arial,sans-serif;background:#F8FAF9;color:#1F2937;display:grid;place-items:center;min-height:100vh;padding:24px;"><div style="max-width:460px;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:16px;padding:28px;text-align:center;box-shadow:0 8px 24px rgba(15,23,42,0.06);"><h1 style="font-size:20px;margin:0 0 10px;color:#143D35;">Koneksi Database Bermasalah</h1><p style="font-size:14px;color:#6B7280;line-height:1.6;margin:0;">Fitur Catatan Pribadi belum dapat terhubung ke database <strong>ruangtumbuh</strong>. Pastikan layanan MySQL aktif lalu muat ulang halaman ini.</p></div></body></html>';
        exit;
    }

    if (!isset($conn) || !($conn instanceof mysqli)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gangguan Koneksi | RuangTumbuh</title></head><body style="margin:0;font-family:Inter,Arial,sans-serif;background:#F8FAF9;color:#1F2937;display:grid;place-items:center;min-height:100vh;padding:24px;"><div style="max-width:460px;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:16px;padding:28px;text-align:center;"><h1 style="font-size:20px;margin:0 0 10px;color:#143D35;">Koneksi Database Tidak Tersedia</h1><p style="font-size:14px;color:#6B7280;line-height:1.6;margin:0;">Silakan periksa konfigurasi database kelompok dan muat ulang halaman.</p></div></body></html>';
        exit;
    }

    $catatanModel = new Catatan($conn);
    $catatanController = new CatatanController($catatanModel);
    $catatanController->handle();
    return;
}

if (!function_exists('aman')) {
    function aman($teks): string
    {
        return CatatanController::aman($teks);
    }
}

$menuUtama = [
    'dashboard.php' => ['fa-solid fa-table-cells-large', 'Dashboard'],
    'katalog.php'   => ['fa-solid fa-shapes',            'Katalog Bootcamp'],
    'kategori.php'  => ['fa-solid fa-layer-group',       'Kategori Program'],
    'bookmark.php'  => ['fa-regular fa-bookmark',        'Bookmark'],
    'pengingat.php' => ['fa-regular fa-clock',           'Pengingat Tenggat'],
    'catatan.php'   => ['fa-regular fa-pen-to-square',   'Catatan Pribadi'],
    'rating.php'    => ['fa-regular fa-star',            'Ulasan & Rating'],
];

$cssPath = __DIR__ . '/../../public/css/catatan.css';
$jsPath  = __DIR__ . '/../../public/js/catatan.js';
$cssVer  = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
$jsVer   = is_file($jsPath) ? (string) filemtime($jsPath) : '1';

$sedangFilter = ($keyword !== '' || $filterKategori !== '');

$buatUrlFilter = static function (string $kategoriBaru, string $keywordAktif) use ($selfUrl): string {
    $query = [];
    if ($keywordAktif !== '') {
        $query['cari'] = $keywordAktif;
    }
    if ($kategoriBaru !== '') {
        $query['kategori'] = $kategoriBaru;
    }
    return $selfUrl . (!empty($query) ? '?' . http_build_query($query) : '');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Pribadi | RuangTumbuh</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= aman($assetUrl) ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= aman($assetUrl) ?>/css/header.css">
    <link rel="stylesheet" href="<?= aman($assetUrl) ?>/css/catatan.css?v=<?= aman($cssVer) ?>">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<div class="app">
    <!-- SIDEBAR TERINTEGRASI DENGAN MENU KELOMPOK -->
    <aside class="sidebar" id="appSidebar" aria-label="Navigasi utama">
        <div>
            <div class="sidebar-brand-row">
                <a href="<?= aman($viewsUrl) ?>/dashboard.php" class="logo-text">
                    Ruang<span>Tumbuh</span>
                </a>
                <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup menu navigasi">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="nav-group nav-group-spaced">
                <div class="nav-title">MENU UTAMA</div>

                <ul class="nav-menu">
                    <?php foreach ($menuUtama as $file => [$ikon, $label]): ?>
                        <li>
                            <a
                                href="<?= aman($viewsUrl . '/' . $file) ?>"
                                class="nav-link <?= $file === 'catatan.php' ? 'active' : '' ?>"
                                <?= $file === 'catatan.php' ? 'aria-current="page"' : '' ?>
                            >
                                <i class="<?= aman($ikon) ?>"></i>
                                <span><?= aman($label) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="<?= aman($logoutUrl) ?>" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        <!-- HEADER GLOBAL KONSISTEN DENGAN APLIKASI -->
        <header class="header">
            <div class="header-left">
                <button
                    type="button"
                    class="mobile-menu-btn"
                    id="mobileMenuBtn"
                    aria-label="Buka menu navigasi"
                    aria-controls="appSidebar"
                    aria-expanded="false"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>
                <a href="<?= aman($viewsUrl) ?>/dashboard.php" class="mobile-brand">
                    Ruang<span>Tumbuh</span>
                </a>
            </div>
<a href="<?= aman($profileUrl) ?>" class="profile-section" title="Lihat profil">
    <div class="profile-info">
        <div class="profile-name"><?= aman($_SESSION['username'] ?? 'Pengguna') ?></div>
        <div class="profile-role"><?= aman($displayRole) ?></div>
    </div>
    <div class="avatar"><?= aman($avatarInitials) ?></div>
</a>
        </header>

        <main class="content">
            <!-- BANNER UTAMA -->
            <section class="page-header">
                <div class="page-header-text">
                    <div class="header-label">
                        <span class="label-dot"></span>
                        Ruang Catatan Personal
                    </div>

                    <h1>Catatan Pribadi</h1>

                    <p>
                        Simpan ide, rencana, target, dan hal penting dalam perjalanan pengembangan dirimu.
                    </p>
                </div>

                <div class="page-header-actions">
                    <a href="#form-catatan" class="save-button">
                        <i class="fa-solid fa-plus"></i>
                        <span>Buat Catatan</span>
                    </a>
                    <div class="header-decoration" aria-hidden="true">
                        <div class="note-icon">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- NOTIFIKASI SUKSES / ERROR -->
            <?php if ($pesanSukses !== ''): ?>
                <div class="alert-box alert-success" role="status">
                    <div class="alert-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="alert-body">
                        <strong>Berhasil!</strong>
                        <p><?= aman($pesanSukses) ?></p>
                    </div>
                    <button type="button" class="alert-close" aria-label="Tutup notifikasi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert-box alert-error" role="alert">
                    <div class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="alert-body">
                        <strong>Periksa kembali input Anda:</strong>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= aman($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button type="button" class="alert-close" aria-label="Tutup pesan kesalahan">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK -->
            <section class="statistics" aria-label="Ringkasan catatan">
                <div class="stat-card">
                    <div class="stat-top">
                        <span>Total Catatan</span>
                        <div class="stat-icon"><i class="fa-solid fa-note-sticky"></i></div>
                    </div>
                    <strong><?= (int) $statistik['total'] ?></strong>
                    <small>Catatan tersimpan di akunmu</small>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Pembaruan Terakhir</span>
                        <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
                    </div>
                    <strong class="stat-date"><?= aman($tanggalTerakhir) ?></strong>
                    <small>Terus catat perkembanganmu</small>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Kategori Aktif</span>
                        <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                    </div>
                    <strong><?= (int) $statistik['jumlah_kategori'] ?></strong>
                    <small>Dari <?= count($daftarKategori) ?> kategori tersedia</small>
                </div>
            </section>

            <!-- FORM TAMBAH / EDIT CATATAN -->
            <section class="note-form-card <?= $isEditMode ? 'is-editing' : '' ?>" id="form-catatan">
                <div class="section-heading">
                    <div>
                        <?php if ($isEditMode): ?>
                            <span class="edit-mode-badge">
                                <i class="fa-regular fa-pen-to-square"></i> Mode Edit
                            </span>
                        <?php endif; ?>
                        <h2><?= $isEditMode ? 'Edit Catatan' : 'Buat Catatan Baru' ?></h2>
                        <p>
                            <?= $isEditMode
                                ? 'Perbarui judul, kategori, atau isi catatanmu lalu simpan perubahan.'
                                : 'Tuliskan hal penting yang ingin kamu simpan. Kolom bertanda * wajib diisi.' ?>
                        </p>
                    </div>
                </div>

                <div class="alert-box alert-error client-error-box" id="clientErrorBox" role="alert" hidden>
                    <div class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="alert-body">
                        <strong>Formulir belum valid:</strong>
                        <ul id="clientErrorList"></ul>
                    </div>
                </div>

                <form method="POST" action="<?= aman($selfUrl) ?>#form-catatan" class="note-form" id="formCatatan">
                    <input type="hidden" name="csrf" value="<?= aman($csrfToken) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= aman($form['id']) ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <label for="judul">Judul Catatan <span class="required-mark">*</span></label>
                                <span class="char-counter" id="judulCounter">0 / <?= CatatanController::JUDUL_MAX ?></span>
                            </div>
                            <input
                                type="text"
                                id="judul"
                                name="judul"
                                placeholder="Contoh: Persiapan Beasiswa LPDP"
                                value="<?= aman($form['judul']) ?>"
                                minlength="<?= CatatanController::JUDUL_MIN ?>"
                                maxlength="<?= CatatanController::JUDUL_MAX ?>"
                                class="<?= isset($fieldErrors['judul']) ? 'is-invalid' : '' ?>"
                                aria-invalid="<?= isset($fieldErrors['judul']) ? 'true' : 'false' ?>"
                                aria-describedby="error-judul"
                                autocomplete="off"
                                required
                            >
                            <span class="field-error" id="error-judul" role="alert">
                                <?= aman($fieldErrors['judul'] ?? '') ?>
                            </span>
                        </div>

                        <div class="form-group category-group">
                            <div class="label-row">
                                <label for="kategori">Kategori <span class="required-mark">*</span></label>
                            </div>
                            <select
                                id="kategori"
                                name="kategori"
                                class="<?= isset($fieldErrors['kategori']) ? 'is-invalid' : '' ?>"
                                aria-invalid="<?= isset($fieldErrors['kategori']) ? 'true' : 'false' ?>"
                                aria-describedby="error-kategori"
                                required
                            >
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($daftarKategori as $kat): ?>
                                    <option
                                        value="<?= aman($kat) ?>"
                                        <?= $form['kategori'] === $kat ? 'selected' : '' ?>
                                    >
                                        <?= aman($kat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-error" id="error-kategori" role="alert">
                                <?= aman($fieldErrors['kategori'] ?? '') ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label for="isi">Isi Catatan <span class="required-mark">*</span></label>
                            <span class="char-counter" id="isiCounter">0 / <?= number_format(CatatanController::ISI_MAX, 0, ',', '.') ?></span>
                        </div>
                        <textarea
                            id="isi"
                            name="isi"
                            rows="5"
                            placeholder="Tuliskan rencana, ide, atau poin pentingmu di sini..."
                            minlength="<?= CatatanController::ISI_MIN ?>"
                            maxlength="<?= CatatanController::ISI_MAX ?>"
                            class="<?= isset($fieldErrors['isi']) ? 'is-invalid' : '' ?>"
                            aria-invalid="<?= isset($fieldErrors['isi']) ? 'true' : 'false' ?>"
                            aria-describedby="error-isi"
                            required
                        ><?= aman($form['isi']) ?></textarea>
                        <span class="field-error" id="error-isi" role="alert">
                            <?= aman($fieldErrors['isi'] ?? '') ?>
                        </span>
                    </div>

                    <div class="form-actions">
                        <?php if ($isEditMode): ?>
                            <a href="<?= aman($selfUrl) ?>" class="button-cancel">
                                <i class="fa-solid fa-xmark"></i> Batal Edit
                            </a>
                        <?php endif; ?>

                        <button type="submit" class="button-save">
                            <i class="fa-solid fa-<?= $isEditMode ? 'check' : 'plus' ?>"></i>
                            <?= $isEditMode ? 'Simpan Perubahan' : 'Simpan Catatan' ?>
                        </button>
                    </div>
                </form>
            </section>

            <!-- PENCARIAN & FILTER KATEGORI -->
            <section class="filter-toolbar" aria-label="Pencarian dan filter catatan">
                <form method="GET" action="<?= aman($selfUrl) ?>" class="search-form">
                    <?php if ($filterKategori !== ''): ?>
                        <input type="hidden" name="kategori" value="<?= aman($filterKategori) ?>">
                    <?php endif; ?>

                    <div class="search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            name="cari"
                            id="inputCari"
                            placeholder="Cari judul atau isi catatan pribadi..."
                            value="<?= aman($keyword) ?>"
                            maxlength="<?= CatatanController::CARI_MAX ?>"
                            autocomplete="off"
                        >
                    </div>

                    <div class="search-actions">
                        <button type="submit" class="btn-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Cari</span>
                        </button>
                        <?php if ($sedangFilter): ?>
                            <a href="<?= aman($selfUrl) ?>" class="btn-reset">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <nav class="category-filters" aria-label="Filter berdasarkan kategori">
                    <a
                        href="<?= aman($buatUrlFilter('', $keyword)) ?>"
                        class="filter-chip <?= $filterKategori === '' ? 'active' : '' ?>"
                    >
                        Semua
                    </a>
                    <?php foreach ($daftarKategori as $kat): ?>
                        <a
                            href="<?= aman($buatUrlFilter($kat, $keyword)) ?>"
                            class="filter-chip <?= $filterKategori === $kat ? 'active' : '' ?>"
                        >
                            <?= aman($kat) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </section>

            <!-- DAFTAR CATATAN -->
            <section class="notes-section" id="daftar-catatan">
                <div class="section-heading">
                    <div>
                        <h2>Catatan Saya</h2>
                        <p>
                            <?php if ($sedangFilter): ?>
                                Menampilkan hasil filter
                                <?= $filterKategori !== '' ? 'kategori <strong>' . aman($filterKategori) . '</strong>' : '' ?>
                                <?= ($filterKategori !== '' && $keyword !== '') ? ' dan ' : '' ?>
                                <?= $keyword !== '' ? 'kata kunci "<strong>' . aman($keyword) . '</strong>"' : '' ?>.
                            <?php else: ?>
                                Semua catatan pribadi yang sudah kamu simpan.
                            <?php endif; ?>
                        </p>
                    </div>

                    <span class="note-count"><?= count($catatanTampil) ?> Catatan</span>
                </div>

                <?php if (count($catatanTampil) > 0): ?>
                    <div class="notes-grid">
                        <?php foreach ($catatanTampil as $catatan): ?>
                            <?php
                            $katClass = 'badge-' . strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $catatan['kategori']));
                            $tglTampil = CatatanController::formatTanggal($catatan['updated_at'] ?? $catatan['created_at'] ?? null, true);
                            $pernahDiubah = !empty($catatan['created_at']) && !empty($catatan['updated_at']) && $catatan['updated_at'] !== $catatan['created_at'];
                            ?>
                            <article class="note-card">
                                <div class="note-card-header">
                                    <span class="category-badge <?= aman($katClass) ?>">
                                        <?= aman($catatan['kategori']) ?>
                                    </span>

                                    <div class="note-options">
                                        <button
                                            type="button"
                                            class="view-button js-detail-btn"
                                            title="Lihat detail catatan"
                                            aria-label="Lihat detail catatan <?= aman($catatan['judul']) ?>"
                                            data-judul="<?= aman($catatan['judul']) ?>"
                                            data-kategori="<?= aman($catatan['kategori']) ?>"
                                            data-isi="<?= aman($catatan['isi']) ?>"
                                            data-tanggal="<?= aman($tglTampil) ?>"
                                        >
                                            <i class="fa-regular fa-eye"></i>
                                        </button>

                                        <a
                                            href="<?= aman($selfUrl . '?edit=' . (int) $catatan['id']) ?>#form-catatan"
                                            class="edit-button"
                                            title="Edit catatan"
                                            aria-label="Edit catatan <?= aman($catatan['judul']) ?>"
                                        >
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= aman($selfUrl) ?>"
                                            class="delete-form"
                                            data-judul="<?= aman($catatan['judul']) ?>"
                                        >
                                            <input type="hidden" name="csrf" value="<?= aman($csrfToken) ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $catatan['id'] ?>">
                                            <button
                                                type="submit"
                                                class="delete-button"
                                                title="Hapus catatan"
                                                aria-label="Hapus catatan <?= aman($catatan['judul']) ?>"
                                            >
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <h3 class="note-title"><?= aman($catatan['judul']) ?></h3>

                                <p class="note-content"><?= nl2br(aman($catatan['isi'])) ?></p>

                                <div class="note-footer">
                                    <i class="fa-regular fa-clock"></i>
                                    <span><?= aman($tglTampil) ?></span>
                                    <?php if ($pernahDiubah): ?>
                                        <span class="edited-tag">Diperbarui</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-regular fa-note-sticky"></i>
                        </div>

                        <h3><?= $sedangFilter ? 'Catatan tidak ditemukan' : 'Belum ada catatan pribadi' ?></h3>

                        <p>
                            <?php if ($sedangFilter): ?>
                                Tidak ada catatan yang sesuai dengan pencarian atau filter yang dipilih.
                            <?php else: ?>
                                Mulai tulis catatan pertamamu agar ide dan target belajarmu tidak terlupakan.
                            <?php endif; ?>
                        </p>

                        <?php if ($sedangFilter): ?>
                            <a href="<?= aman($selfUrl) ?>" class="empty-action-btn">
                                <i class="fa-solid fa-rotate-left"></i> Tampilkan Semua Catatan
                            </a>
                        <?php else: ?>
                            <a href="#form-catatan" class="empty-action-btn">
                                <i class="fa-solid fa-plus"></i> Buat Catatan Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<!-- MODAL DETAIL CATATAN -->
<div class="modal-backdrop" id="modalDetail" aria-hidden="true" hidden>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="detailModalJudul">
        <div class="modal-header">
            <span class="category-badge" id="detailModalKategori"></span>
            <button type="button" class="modal-close-btn" data-close-modal="modalDetail" aria-label="Tutup detail">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <h3 class="modal-title" id="detailModalJudul"></h3>
        <div class="modal-meta">
            <i class="fa-regular fa-clock"></i>
            <span id="detailModalTanggal"></span>
        </div>
        <div class="modal-body-text" id="detailModalIsi"></div>
        <div class="modal-footer">
            <button type="button" class="button-cancel" data-close-modal="modalDetail">Tutup</button>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal-backdrop" id="modalHapus" aria-hidden="true" hidden>
    <div class="modal-dialog modal-dialog-sm" role="alertdialog" aria-modal="true" aria-labelledby="hapusModalJudul" aria-describedby="hapusModalPesan">
        <div class="modal-danger-icon" aria-hidden="true">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="modal-title modal-title-center" id="hapusModalJudul">Hapus Catatan?</h3>
        <p class="modal-confirm-text" id="hapusModalPesan">
            Apakah kamu yakin ingin menghapus catatan "<strong id="hapusNamaCatatan"></strong>"? Data yang sudah dihapus tidak dapat dikembalikan.
        </p>
        <div class="modal-footer modal-footer-center">
            <button type="button" class="button-cancel" data-close-modal="modalHapus">Batal</button>
            <button type="button" class="button-danger" id="btnKonfirmasiHapus">
                <i class="fa-regular fa-trash-can"></i> Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script src="<?= aman($assetUrl) ?>/js/catatan.js?v=<?= aman($jsVer) ?>" defer></script>
</body>
</html>
