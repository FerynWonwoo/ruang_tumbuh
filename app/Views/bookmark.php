<?php
if (!defined('BOOKMARK_MVC_RENDER')) {
    ob_start();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    require_once __DIR__ . '/../Models/Bookmark.php';
    require_once __DIR__ . '/../Controllers/BookmarkController.php';

    $paths = BookmarkController::resolvePaths();

    // Proteksi halaman: tolak akses langsung lewat URL tanpa login sebelum menyentuh DB
    if (empty($_SESSION['user_id']) && empty($_SESSION['username'])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: ' . $paths['loginUrl'], true, 302);
        exit;
    }

    try {
        ob_start();
        require_once __DIR__ . '/../../config/koneksimvc.php';
        ob_end_clean();
    } catch (Throwable $e) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        error_log('Bookmark DB connection error: ' . $e->getMessage());
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gangguan Koneksi | RuangTumbuh</title></head><body style="margin:0;font-family:Inter,Arial,sans-serif;background:#F8FAF9;color:#1F2937;display:grid;place-items:center;min-height:100vh;padding:24px;"><div style="max-width:460px;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:16px;padding:28px;text-align:center;"><h1 style="font-size:20px;margin:0 0 10px;color:#143D35;">Koneksi Database Bermasalah</h1><p style="font-size:14px;color:#6B7280;line-height:1.6;margin:0;">Fitur Bookmark belum dapat terhubung ke database <strong>ruangtumbuh</strong>. Pastikan MySQL aktif lalu muat ulang halaman ini.</p></div></body></html>';
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

    $bookmarkModel = new Bookmark($conn);
    $bookmarkController = new BookmarkController($bookmarkModel);
    $bookmarkController->handle();
    return;
}

if (!function_exists('aman')) {
    function aman($teks): string
    {
        return BookmarkController::aman($teks);
    }
}

$totalTersimpan = isset($totalTersimpan) ? (int) $totalTersimpan : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Bookmark & Program Tersimpan</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= aman($paths['assetUrl']) ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= aman($paths['assetUrl']) ?>/css/bookmark.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <div class="main-area">

        <!-- HEADER GLOBAL -->
        <?php include __DIR__ . '/../../includes/header.php'; ?>

        <main class="dashboard-container">
            <!-- BANNER UTAMA -->
            <section class="welcome-banner">
                <div class="banner-content-wrapper">
                    <div class="banner-text-area">
                        <span class="badge-tag">• Arsip Peluang Pengembangan Diri</span>
                        <h1 class="banner-title">Bookmark & Program Tersimpan</h1>
                        <p class="banner-subtitle">Simpan dan kelola seluruh peluang magang, beasiswa, dan bootcamp
                            favoritmu di satu tempat.</p>
                    </div>

                    <div class="banner-actions">
                        <div class="banner-side-group">
                            <div class="banner-count-pill">
                                <i class="fa-solid fa-bookmark"></i>
                                <span><?= $totalTersimpan ?> Tersimpan</span>
                            </div>

                            <button type="button" class="btn-tambah-main" onclick="openForm()">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Bookmark</span>
                            </button>
                        </div>

                        <div class="banner-floating-badge" aria-hidden="true">
                            <i class="fa-regular fa-bookmark"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KOTAK PENCARIAN & TOMBOL FILTER KATEGORI -->
            <div class="filter-toolbar">
                <div class="search-input-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchBookmark" placeholder="Cari judul program atau penyelenggara..."
                        autocomplete="off">
                </div>

                <div class="filter-chips-wrap">
                    <button type="button" class="filter-btn active" data-filter="Semua">Semua</button>
                    <?php foreach ($kategoriFilter as $kf): ?>
                        <button type="button" class="filter-btn" data-filter="<?= aman($kf) ?>"><?= aman($kf) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($pesan !== ''): ?>
                <div class="alert-message success" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= aman($pesan) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert-message error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= aman($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- GRID KARTU BOOKMARK -->
            <div class="bookmark-grid" id="bookmarkGrid">
                <?php if (empty($daftarBookmark)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-bookmark"></i>
                        <h3>Belum ada bookmark tersimpan</h3>
                        <p>Klik tombol <strong>Tambah Bookmark</strong> di atas atau tekan ikon bookmark pada program di
                            Katalog.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftarBookmark as $item): ?>
                        <?php
                        $itemJson = htmlspecialchars(
                            json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        $judulLower = function_exists('mb_strtolower') ? mb_strtolower((string) $item['judul'], 'UTF-8') : strtolower((string) $item['judul']);
                        $orgLower = function_exists('mb_strtolower') ? mb_strtolower((string) $item['penyelenggara'], 'UTF-8') : strtolower((string) $item['penyelenggara']);
                        ?>
                        <article class="bookmark-card clickable-card" data-kategori="<?= aman($item['kategori']) ?>"
                            data-judul="<?= aman($judulLower) ?>" data-penyelenggara="<?= aman($orgLower) ?>"
                            onclick="openDetailModal(<?= $itemJson ?>)">

                            <div>
                                <div class="card-header-tag">
                                    <span class="kategori-tag"><?= aman($item['kategori']) ?></span>
                                    <span class="status-tag <?= aman($item['status_class']) ?>">
                                        <span class="badge-status-dot"></span> <?= aman($item['status']) ?>
                                    </span>
                                </div>

                                <h3 class="card-title"><?= aman($item['judul']) ?></h3>
                                <p class="penyelenggara">
                                    <i class="fa-regular fa-building"></i>
                                    <?= aman($item['penyelenggara'] !== '' ? $item['penyelenggara'] : 'Penyelenggara belum dicantumkan') ?>
                                </p>
                            </div>

                            <div class="card-footer">
                                <span class="deadline">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= aman($item['deadline']) ?>
                                </span>

                                <div class="card-actions">
                                    <button type="button" class="btn-action btn-detail"
                                        onclick="event.stopPropagation(); openDetailModal(<?= $itemJson ?>)">
                                        <i class="fa-regular fa-eye"></i> Detail
                                    </button>

                                    <button type="button" class="btn-action btn-edit"
                                        onclick="event.stopPropagation(); openForm(<?= $itemJson ?>)">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>

                                    <form method="post" action="bookmark.php" class="form-hapus-bookmark"
                                        style="display:inline;" data-judul="<?= aman($item['judul']) ?>"
                                        onclick="event.stopPropagation();">
                                        <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <button type="submit" class="btn-action btn-hapus">
                                            <i class="fa-regular fa-trash-can"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL DETAIL -->
    <div class="modal-overlay" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailJudul">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Detail Program Favorit</h3>
                <button type="button" class="btn-close" onclick="closeModal('detailModal')"
                    aria-label="Tutup">&times;</button>
            </div>
            <div class="detail-body">
                <div class="card-header-tag" style="margin-bottom: 12px;">
                    <span class="kategori-tag" id="detailKategori">-</span>
                    <span class="status-tag active" id="detailStatusBadge">
                        <span class="badge-status-dot"></span> <span id="detailStatusText">Aktif</span>
                    </span>
                </div>
                <h2 id="detailJudul" class="detail-judul">-</h2>
                <p class="detail-org">
                    <i class="fa-regular fa-building"></i> Penyelenggara: <strong id="detailPenyelenggara">-</strong>
                </p>

                <hr class="detail-divider">

                <div class="detail-section">
                    <label class="detail-label">Deskripsi & Persyaratan Program:</label>
                    <p id="detailDeskripsi" class="detail-desc">-</p>
                </div>

                <div class="detail-meta">
                    <i class="fa-regular fa-calendar"></i> Batas Tenggat: <strong id="detailDeadline">-</strong>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-batal" onclick="closeModal('detailModal')">Tutup</button>
                <a id="detailLink" href="#" target="_blank" rel="noopener noreferrer" class="btn-simpan"
                    style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Daftar Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT -->
    <div class="modal-overlay" id="formModal" role="dialog" aria-modal="true" aria-labelledby="formTitle">
        <div class="modal-card fm-card">
            <div class="modal-header fm-head">
                <h3 id="formTitle" class="modal-title">Tambah Bookmark</h3>
                <button type="button" class="btn-close" onclick="closeModal('formModal')"
                    aria-label="Tutup">&times;</button>
            </div>

            <div id="clientFormError" class="alert-message error"
                style="display:none; margin-bottom:12px; font-size:13px; padding:10px 12px;"></div>

            <form method="post" action="bookmark.php" id="formBookmark" novalidate>
                <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                <input type="hidden" name="aksi" id="fAksi" value="tambah">
                <input type="hidden" name="id" id="fId" value="">

                <label class="fm-label">Judul program <span class="req">*</span>
                    <input type="text" name="judul" id="fJudul" required minlength="3" maxlength="150"
                        placeholder="Contoh: Beasiswa Unggulan Kemendikbud">
                </label>

                <div class="fm-row">
                    <label class="fm-label">Kategori <span class="req">*</span>
                        <select name="kategori" id="fKategori" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php foreach ($kategoriSaran as $ks): ?>
                                <option value="<?= aman($ks) ?>"><?= aman($ks) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="fm-label">Penyelenggara
                        <input type="text" name="penyelenggara" id="fPenyelenggara" maxlength="100"
                            placeholder="Nama instansi / perusahaan">
                    </label>
                </div>

                <div class="fm-row">
                    <label class="fm-label">Batas tenggat
                        <input type="date" name="deadline" id="fDeadline" min="2000-01-01" max="2100-12-31">
                    </label>
                    <label class="fm-label">Link pendaftaran
                        <input type="url" name="link" id="fLink" maxlength="500" placeholder="https://...">
                    </label>
                </div>

                <label class="fm-label">Deskripsi & Catatan Persyaratan
                    <textarea name="deskripsi" id="fDeskripsi" maxlength="1000"
                        placeholder="Tulis catatan persyaratan, berkas, atau info penting lainnya..."></textarea>
                </label>

                <div class="fm-foot">
                    <button type="button" class="btn-batal" onclick="closeModal('formModal')">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan Bookmark</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS BOOKMARK -->
    <div class="modal-overlay" id="modalHapusBookmark" role="alertdialog" aria-modal="true"
        aria-labelledby="hapusBookmarkJudul" aria-describedby="hapusBookmarkPesan">
        <div class="modal-card modal-confirm-card">
            <div class="modal-danger-icon" aria-hidden="true">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="modal-confirm-title" id="hapusBookmarkJudul">Hapus Bookmark?</h3>
            <p class="modal-confirm-desc" id="hapusBookmarkPesan">
                Apakah kamu yakin ingin menghapus bookmark "<strong id="hapusNamaBookmark"></strong>"? Data yang sudah
                dihapus tidak dapat dikembalikan.
            </p>
            <div class="modal-confirm-actions">
                <button type="button" class="btn-confirm-cancel"
                    onclick="closeModal('modalHapusBookmark')">Batal</button>
                <button type="button" class="btn-confirm-danger" id="btnKonfirmasiHapusBookmark">
                    <i class="fa-regular fa-trash-can"></i> Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <?php if ($old !== null): ?>
        <script type="application/json"
            id="oldBookmarkData"><?= json_encode($old, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endif; ?>

    <script src="<?= aman($paths['assetUrl']) ?>/js/bookmark.js"></script>
</body>

</html>