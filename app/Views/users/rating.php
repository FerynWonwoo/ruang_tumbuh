<?php
/** View peserta. Variabel dari RatingController::tampilkan() */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan &amp; Rating | Ruang Tumbuh</title>

    <link rel="stylesheet" href="assets/css/rating.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include ROOT_PATH . '/includes/sidebar.php'; ?>

<div class="main-area">

    <?php include ROOT_PATH . '/includes/header.php'; ?>

    <main class="page">

        <!-- BANNER -->
        <section class="banner">
            <div class="banner-text">
                <span class="badge-tag">Peserta Bootcamp</span>
                <h1>Ulasan &amp; Rating</h1>
                <p>Bagikan pengalamanmu mengikuti bootcamp dan bantu peserta lain memilih program terbaik.</p>
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
        <?php if ($error && !$modalBuka): ?>
            <p class="notice error" role="alert"><?= aman($error) ?></p>
        <?php endif; ?>

        <!-- FORM ULASAN BARU -->
        <section class="card form-card" id="form-ulasan">
            <div class="form-head">
                <h2>Tulis Ulasanmu</h2>
                <p class="muted">Bagaimana pengalaman belajarmu? Ulasanmu akan tampil setelah disetujui admin.</p>
            </div>

            <form method="post" action="<?= url('rating/simpan') ?>">
                <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                <input type="hidden" name="id" value="">

                <label for="bootcamp">Nama bootcamp</label>
                <?php pilihanBootcamp('bootcamp', $formBaru['bootcamp']); ?>

                <fieldset class="rating-field">
                    <legend>Penilaianmu</legend>
                    <div class="rating-options">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="rating-option">
                                <input type="radio" name="rating" value="<?= $i ?>"
                                       <?= (int) $formBaru['rating'] === $i ? 'checked' : '' ?> required>
                                <span><?= $i ?> ★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <label for="komentar">Ceritakan pengalamanmu</label>
                <textarea id="komentar" name="komentar" rows="4"
                          maxlength="<?= MAX_KOMENTAR ?>"
                          placeholder="Ceritakan materi, mentor, dan pengalaman belajarmu."
                          required><?= aman($formBaru['komentar']) ?></textarea>

                <p class="hint">Tips: bahas materi, mentor, dan proses belajar. Gunakan bahasa sopan dan jangan bagikan data pribadi.</p>

                <div class="actions">
                    <button type="submit" class="button">Kirim Ulasan</button>
                </div>
            </form>
        </section>

        <!-- PENGALAMAN PESERTA -->
        <div class="list-heading">
            <div>
                <h2>Pengalaman Peserta</h2>
                <p class="muted sub">Cerita nyata dari peserta yang sudah belajar bersama kami.</p>
            </div>
            <span class="count-pill"><i class="fa-regular fa-comments"></i> <b id="jumlahUlasan"><?= count($daftarUlasan) ?></b> ulasan</span>
        </div>

        <?php if ($daftarUlasan): ?>
            <div class="chips" id="filterRating" role="group" aria-label="Filter berdasarkan bintang">
                <button type="button" class="chip active" data-filter="all">Semua</button>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <button type="button" class="chip" data-filter="<?= $i ?>"><?= $i ?> ★</button>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <section class="card empty">
                <div class="empty-icon"><i class="fa-regular fa-star"></i></div>
                <h3>Belum ada ulasan</h3>
                <p class="muted">Yuk, bagikan pengalaman bootcamp pertamamu!</p>
            </section>
        <?php endif; ?>

        <section class="card empty" id="emptyFilter" hidden>
            <div class="empty-icon"><i class="fa-regular fa-face-meh"></i></div>
            <h3>Tidak ada ulasan dengan rating ini</h3>
            <p class="muted">Coba pilih filter bintang yang lain.</p>
        </section>

        <div class="review-grid" id="daftar">
            <?php foreach ($daftarUlasan as $u): ?>
                <?php
                    $r       = max(1, min(5, (int) $u['rating']));
                    $milikku = $u['username'] === $username;
                    $avatar  = 'av-' . (abs(crc32($u['username'])) % 6);
                ?>
                <article class="review-card r<?= $r ?>" data-rating="<?= $r ?>">
                    <div class="rc-head">
                        <div class="rc-avatar <?= $avatar ?>"><?= aman(strtoupper(mb_substr($u['username'], 0, 2))) ?></div>
                        <div class="rc-who">
                            <strong>
                                <?= aman($u['username']) ?>
                                <?php if ($milikku): ?><span class="me-tag">Kamu</span><?php endif; ?>
                            </strong>
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

                    <?php if ($milikku): ?>
                        <div class="rc-footer">
                            <span class="badge <?= aman($u['status']) ?>">
                                <?= aman($statusLabel[$u['status']] ?? $u['status']) ?>
                            </span>

                            <div class="rc-actions">
                                <button type="button" class="mini blue" data-edit
                                        data-id="<?= aman($u['id']) ?>"
                                        data-bootcamp="<?= aman($u['bootcamp']) ?>"
                                        data-rating="<?= $r ?>"
                                        data-komentar="<?= aman($u['komentar']) ?>">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>

                                <form method="post" action="<?= url('rating/tarik') ?>">
                                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="id" value="<?= aman($u['id']) ?>">
                                    <button type="submit" class="mini red"
                                            data-confirm="Tarik ulasan ini? Ulasan akan dihapus.">
                                        <i class="fa-regular fa-trash-can"></i> Tarik
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay<?= $modalBuka ? ' open' : '' ?>" id="modalEdit" aria-hidden="<?= $modalBuka ? 'false' : 'true' ?>">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalJudul">

        <div class="modal-head">
            <span class="modal-icon"><i class="fa-regular fa-pen-to-square"></i></span>
            <div>
                <h2 id="modalJudul">Edit Ulasan</h2>
                <p class="muted">Perubahan akan ditinjau ulang admin sebelum tampil kembali.</p>
            </div>
            <button type="button" class="modal-close" data-tutup aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <?php if ($modalBuka && $error): ?>
            <p class="notice error" role="alert"><?= aman($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= url('rating/simpan') ?>">
            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
            <input type="hidden" name="id" id="editId" value="<?= aman($formEdit['id']) ?>">

            <label for="editBootcamp">Nama bootcamp</label>
            <?php pilihanBootcamp('editBootcamp', $formEdit['bootcamp']); ?>

            <fieldset class="rating-field">
                <legend>Penilaianmu</legend>
                <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="rating-option">
                            <input type="radio" name="rating" value="<?= $i ?>"
                                   <?= (int) $formEdit['rating'] === $i ? 'checked' : '' ?> required>
                            <span><?= $i ?> ★</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </fieldset>

            <label for="editKomentar">Ceritakan pengalamanmu</label>
            <textarea id="editKomentar" name="komentar" rows="5"
                      maxlength="<?= MAX_KOMENTAR ?>" required><?= aman($formEdit['komentar']) ?></textarea>

            <div class="actions">
                <button type="button" class="button secondary" data-tutup>Batal</button>
                <button type="submit" class="button">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/rating.js"></script>
</body>
</html>
