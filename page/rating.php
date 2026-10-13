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

// Dropdown bootcamp (pilih dari daftar, tanpa ketik)
function pilihanBootcamp(string $id, string $dipilih): void
{
    echo '<select id="' . $id . '" name="bootcamp" required>';
    echo '<option value=""' . ($dipilih === '' ? ' selected' : '') . ' disabled>Pilih bootcamp</option>';
    foreach (DAFTAR_BOOTCAMP as $nama) {
        $selected = $nama === $dipilih ? ' selected' : '';
        echo '<option value="' . aman($nama) . '"' . $selected . '>' . aman($nama) . '</option>';
    }
    echo '</select>';
}

$username = $_SESSION['username'];
$admin    = ($_SESSION['role'] ?? 'user') === 'admin';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$pesan = $_SESSION['pesan_ulasan'] ?? '';
unset($_SESSION['pesan_ulasan']);

$formKosong = ['id' => '', 'bootcamp' => '', 'rating' => 5, 'komentar' => ''];
$form       = $formKosong;

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
            $error = 'Bootcamp dan ulasan wajib diisi.';
        } elseif (!in_array($form['bootcamp'], DAFTAR_BOOTCAMP, true)) {
            $error = 'Pilih bootcamp dari daftar yang tersedia.';
        } elseif (mb_strlen($form['komentar']) > MAX_KOMENTAR) {
            $error = 'Ulasan terlalu panjang.';
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
// MODE EDIT (fallback link ?edit=ID -> modal langsung terbuka)
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

// Form atas = ulasan baru, modal = edit
$modalBuka = !$admin && $form['id'] !== '';
$formBaru  = $modalBuka ? $formKosong : $form;
$formEdit  = $modalBuka ? $form : $formKosong;

// =========================
// DAFTAR & RATING
// =========================
$semua         = array_reverse(bacaUlasan(), true);
$daftarUlasan  = [];
$totalRating   = 0;
$totalPublik   = 0;
$sebaran       = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

foreach ($semua as $u) {
    if ($u['status'] === 'approved') {
        $totalRating += $u['rating'];
        $totalPublik++;
        $r = max(1, min(5, (int) $u['rating']));
        $sebaran[$r]++;
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

function bintang(int $r): string
{
    return str_repeat('★', $r) . '<span>' . str_repeat('☆', 5 - $r) . '</span>';
}
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
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-area">

     <?php include __DIR__ . '/../includes/header.php'; ?>


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

        <!-- FORM ULASAN BARU (user) -->
        <?php if (!$admin): ?>
            <section class="card form-card" id="form-ulasan">
                <div class="form-head">
                    <h2>Tulis Ulasanmu</h2>
                    <p class="muted">Bagaimana pengalaman belajarmu? Ulasanmu langsung tampil setelah dikirim.</p>
                </div>

                <form method="post" action="rating.php">
                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                    <input type="hidden" name="aksi" value="simpan">
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
        <?php endif; ?>

        <!-- PENGALAMAN PESERTA -->
        <div class="list-heading">
            <div>
                <h2><?= $admin ? 'Moderasi Ulasan' : 'Pengalaman Peserta' ?></h2>
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
                <p class="muted">
                    <?= $admin
                        ? 'Ulasan peserta akan muncul di sini.'
                        : 'Yuk, bagikan pengalaman bootcamp pertamamu!' ?>
                </p>
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
                            <span class="rc-date"><i class="fa-regular fa-calendar"></i> <?= aman($u['tanggal']) ?></span>
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

                    <?php if ($admin || $milikku || $u['status'] !== 'approved'): ?>
                        <div class="rc-footer">
                            <span class="badge <?= aman($u['status']) ?>">
                                <?= aman($statusLabel[$u['status']] ?? $u['status']) ?>
                            </span>

                            <?php if ($admin || $milikku): ?>
                                <div class="rc-actions">
                                    <?php if (!$admin): ?>
                                        <button type="button" class="mini blue" data-edit
                                                data-id="<?= aman($u['id']) ?>"
                                                data-bootcamp="<?= aman($u['bootcamp']) ?>"
                                                data-rating="<?= $r ?>"
                                                data-komentar="<?= aman($u['komentar']) ?>">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>
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
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<!-- MODAL EDIT (user) -->
<?php if (!$admin): ?>
    <div class="modal-overlay<?= $modalBuka ? ' open' : '' ?>" id="modalEdit" aria-hidden="<?= $modalBuka ? 'false' : 'true' ?>">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalJudul">

            <div class="modal-head">
                <span class="modal-icon"><i class="fa-regular fa-pen-to-square"></i></span>
                <div>
                    <h2 id="modalJudul">Edit Ulasan</h2>
                    <p class="muted">Perbarui pengalamanmu, perubahan langsung tampil.</p>
                </div>
                <button type="button" class="modal-close" data-tutup aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <?php if ($modalBuka && $error): ?>
                <p class="notice error" role="alert"><?= aman($error) ?></p>
            <?php endif; ?>

            <form method="post" action="rating.php">
                <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                <input type="hidden" name="aksi" value="simpan">
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
<?php endif; ?>

<script>
(function () {
    var modal = document.getElementById('modalEdit');

    function bukaModal(d) {
        if (!modal) return;
        document.getElementById('editId').value = d.id || '';
        document.getElementById('editKomentar').value = d.komentar || '';

        var pilih = document.getElementById('editBootcamp');
        pilih.value = d.bootcamp || '';
        if (pilih.selectedIndex === -1) pilih.value = ''; // kategori lama tidak ada di daftar

        modal.querySelectorAll('input[name="rating"]').forEach(function (r) {
            r.checked = r.value === String(d.rating);
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        pilih.focus();
    }

    function tutupModal() {
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (location.search) history.replaceState(null, '', 'rating.php');
    }

    // Filter rating
    function terapkanFilter(nilai) {
        var tampil = 0;
        document.querySelectorAll('.review-card').forEach(function (kartu) {
            var cocok = nilai === 'all' || kartu.dataset.rating === nilai;
            kartu.style.display = cocok ? '' : 'none';
            if (cocok) tampil++;
        });
        var jumlah = document.getElementById('jumlahUlasan');
        if (jumlah) jumlah.textContent = tampil;
        var kosong = document.getElementById('emptyFilter');
        if (kosong) kosong.hidden = tampil !== 0;
    }

    document.addEventListener('click', function (e) {
        // Konfirmasi hapus/tarik
        var tombol = e.target.closest('[data-confirm]');
        if (tombol && !confirm(tombol.dataset.confirm)) {
            e.preventDefault();
            return;
        }

        // Buka modal edit
        var edit = e.target.closest('[data-edit]');
        if (edit) {
            bukaModal(edit.dataset);
            return;
        }

        // Tutup modal
        if (e.target.closest('[data-tutup]') || e.target === modal) {
            tutupModal();
            return;
        }

        // Baca selengkapnya
        var more = e.target.closest('.read-more');
        if (more) {
            var teks = more.previousElementSibling;
            var terbuka = teks.classList.toggle('open');
            more.textContent = terbuka ? 'Tutup' : 'Baca selengkapnya';
            return;
        }

        // Filter bintang
        var chip = e.target.closest('.chip');
        if (chip) {
            document.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            terapkanFilter(chip.dataset.filter);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('open')) tutupModal();
    });

    // Tampilkan tombol "Baca selengkapnya" hanya jika teks terpotong
    document.querySelectorAll('.review-text').forEach(function (p) {
        if (p.scrollHeight > p.clientHeight + 1) p.nextElementSibling.hidden = false;
    });
})();
</script>

</body>
</html>