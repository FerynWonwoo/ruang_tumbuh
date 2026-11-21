<?php
if (!isset($daftarUlasan)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../config/koneksimvc.php';
    require_once __DIR__ . '/../Helpers/rating_helpers.php';
    require_once __DIR__ . '/../Models/Rating.php';
    require_once __DIR__ . '/../Controllers/RatingController.php';

    $ratingModel = new Rating($conn);
    $ratingController = new RatingController($ratingModel);
    $ratingController->tampilkan();
    exit;
}

$appBase = $basePath;
$e = 'rating_e';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Ulasan &amp; Rating</title>
    

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $e($basePath) ?>/public/css/rating.css?v=<?= @filemtime(__DIR__ . '/../../public/css/rating.css') ?>">
</head>
<body>

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-area">
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="page">

        <!-- BANNER -->
        <section class="banner">
            <div>
                <span class="badge-tag">Komunitas</span>
                <h1>Ulasan &amp; Rating</h1>
                <p>Bagikan pengalamanmu mengikuti bootcamp dan lihat ulasan peserta lain sebelum memutuskan mendaftar.</p>
            </div>

            <div class="banner-stats">
                <div class="stat-pill">
                    <span class="stat-icon amber"><i class="fa-solid fa-star"></i></span>
                    <span class="stat-text"><?= $e($rataRating) ?> <small>/ 5.0</small></span>
                </div>
                <div class="stat-pill">
                    <span class="stat-icon"><i class="fa-regular fa-comments"></i></span>
                    <span class="stat-text"><?= (int) $totalPublik ?> <small>ulasan tayang</small></span>
                </div>
            </div>
        </section>

        <?php if ($pesan): ?>
            <p class="notice success" role="status"><?= $e($pesan) ?></p>
        <?php endif; ?>
        <?php if ($error && !$modalBuka): ?>
            <p class="notice error" role="alert"><?= $e($error) ?></p>
        <?php endif; ?>

        <!-- FORM ULASAN BARU -->
        <section class="card form-card">
            <div class="form-head">
                <h2>Tulis Ulasan</h2>
                <p class="muted">Ulasanmu akan ditinjau admin sebelum tampil untuk peserta lain.</p>
            </div>

            <form method="post" action="<?= $e($basePath) ?>/index.php?page=rating-simpan">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">

                <label for="bootcampBaru">Bootcamp</label>
                <select id="bootcampBaru" name="bootcamp" required>
                    <option value="">— Pilih bootcamp —</option>
                    <?php foreach ($daftarBootcamp as $judul): ?>
                        <option value="<?= $e($judul) ?>" <?= $formBaru['bootcamp'] === $judul ? 'selected' : '' ?>>
                            <?= $e($judul) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <fieldset class="rating-field">
                    <legend>Rating</legend>
                    <div class="rating-options">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="rating-option">
                                <input type="radio" name="rating" value="<?= $i ?>" required
                                    <?= (int) $formBaru['rating'] === $i ? 'checked' : '' ?>>
                                <span><?= $i ?> ★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <label for="komentarBaru">Komentar</label>
                <textarea id="komentarBaru" name="komentar" rows="4" required
                    maxlength="<?= (int) $maxKomentar ?>"
                    placeholder="Ceritakan pengalamanmu..."><?= $e($formBaru['komentar']) ?></textarea>
                <p class="hint">Maksimal <?= (int) $maxKomentar ?> karakter.</p>

                <div class="actions">
                    <button type="submit" class="button">
                        <i class="fa-regular fa-paper-plane"></i> Kirim Ulasan
                    </button>
                </div>
            </form>
        </section>

        <!-- DAFTAR ULASAN -->
        <section>
            <div class="list-heading">
                <div>
                    <h2>Ulasan Peserta</h2>
                    <p class="muted sub">Ulasan yang sudah dipublikasikan dan ulasan milikmu.</p>
                </div>
                <span class="count-pill"><b id="jumlahUlasan"><?= count($daftarUlasan) ?></b> ulasan</span>
            </div>

            <?php if (!$daftarUlasan): ?>
                <div class="card empty" style="margin-top:16px;">
                    <div class="empty-icon"><i class="fa-regular fa-star"></i></div>
                    <h3>Belum ada ulasan</h3>
                    <p class="muted">Jadilah yang pertama membagikan pengalamanmu.</p>
                </div>
            <?php else: ?>

                <nav class="chips" aria-label="Filter bintang" style="margin:16px 0;">
                    <button type="button" class="chip active" data-filter="all">Semua</button>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <button type="button" class="chip" data-filter="<?= $i ?>"><?= $i ?> ★</button>
                    <?php endfor; ?>
                </nav>

                <div class="review-grid">
                    <?php foreach ($daftarUlasan as $u): ?>
                        <?php
                        $r = max(1, min(5, (int) $u['rating']));
                        $milikku = $u['username'] === ($_SESSION['username'] ?? '');
                        $avatar = 'av-' . (abs(crc32($u['username'])) % 6);
                        ?>
                        <article class="review-card r<?= $r ?>" data-rating="<?= $r ?>">
                            <div class="rc-head">
                                <div class="rc-avatar <?= $avatar ?>"><?= $e(strtoupper(mb_substr($u['username'], 0, 2))) ?></div>
                                <div class="rc-who">
                                    <strong>
                                        <?= $e($u['username']) ?>
                                        <?php if ($milikku): ?><span class="me-tag">Kamu</span><?php endif; ?>
                                    </strong>
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

                            <?php if ($milikku): ?>
                                <div class="rc-footer">
                                    <span class="badge <?= $e($u['status']) ?>"><?= $e($statusLabel[$u['status']] ?? $u['status']) ?></span>

                                    <div class="rc-actions">
                                        <button type="button" class="mini blue" data-edit
                                            data-id="<?= $e($u['id']) ?>"
                                            data-bootcamp="<?= $e($u['bootcamp']) ?>"
                                            data-rating="<?= $r ?>"
                                            data-komentar="<?= $e($u['komentar']) ?>">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>

                                        <form method="post" action="<?= $e($basePath) ?>/index.php?page=rating-tarik">
                                            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
                                            <input type="hidden" name="id" value="<?= $e($u['id']) ?>">
                                            <button type="submit" class="mini red" data-confirm="Tarik ulasan ini? Ulasan akan dihapus.">
                                                <i class="fa-regular fa-trash-can"></i> Tarik
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="card empty" id="emptyFilter" hidden style="margin-top:16px;">
                    <div class="empty-icon"><i class="fa-regular fa-star"></i></div>
                    <h3>Tidak ada ulasan dengan bintang ini</h3>
                </div>

            <?php endif; ?>
        </section>
    </main>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay<?= $modalBuka ? ' open' : '' ?>" id="modalEdit" aria-hidden="<?= $modalBuka ? 'false' : 'true' ?>">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulModal">
        <div class="modal-head">
            <span class="modal-icon"><i class="fa-regular fa-pen-to-square"></i></span>
            <div>
                <h2 id="judulModal">Edit Ulasan</h2>
                <p class="muted">Setelah diubah, ulasan kembali menunggu persetujuan admin.</p>
            </div>
            <button type="button" class="modal-close" data-tutup aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <?php if ($error && $modalBuka): ?>
            <p class="notice error" role="alert"><?= $e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= $e($basePath) ?>/index.php?page=rating-simpan">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
            <input type="hidden" name="id" id="editId" value="<?= $e($formEdit['id']) ?>">

            <label for="editBootcamp">Bootcamp</label>
            <select id="editBootcamp" name="bootcamp" required>
                <option value="">— Pilih bootcamp —</option>
                <?php
                $adaDiDaftar = in_array($formEdit['bootcamp'], $daftarBootcamp, true);
                if ($formEdit['bootcamp'] !== '' && !$adaDiDaftar): ?>
                    <option value="<?= $e($formEdit['bootcamp']) ?>" selected><?= $e($formEdit['bootcamp']) ?></option>
                <?php endif; ?>
                <?php foreach ($daftarBootcamp as $judul): ?>
                    <option value="<?= $e($judul) ?>" <?= $formEdit['bootcamp'] === $judul ? 'selected' : '' ?>>
                        <?= $e($judul) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <fieldset class="rating-field">
                <legend>Rating</legend>
                <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="rating-option">
                            <input type="radio" name="rating" value="<?= $i ?>" required
                                <?= (int) $formEdit['rating'] === $i ? 'checked' : '' ?>>
                            <span><?= $i ?> ★</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </fieldset>

            <label for="editKomentar">Komentar</label>
            <textarea id="editKomentar" name="komentar" rows="4" required
                maxlength="<?= (int) $maxKomentar ?>"><?= $e($formEdit['komentar']) ?></textarea>

            <div class="actions">
                <button type="button" class="button secondary" data-tutup>Batal</button>
                <button type="submit" class="button">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>


</body>
</html>
