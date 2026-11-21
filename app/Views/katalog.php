
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['username'])) {
    header('Location: /ruang_tumbuh/index.php');
    exit;
}

require_once __DIR__ . '/../../config/koneksimvc.php';
require_once __DIR__ . '/../../includes/bootcamp_store.php';

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$hariIni = strtotime('today');

$semuaProgram = bootcamp_load();
$kategoriList = bootcamp_kategori_list($conn ?? null, $semuaProgram);
$jumlahPerKategori = bootcamp_hitung_kategori($semuaProgram);

$paramKategori = trim((string) ($_GET['kategori'] ?? ''));
$kategoriAktif = bootcamp_kategori_cari($kategoriList, $paramKategori);
$kategoriTidakAda = $paramKategori !== '' && $kategoriAktif === null;

$urut = (string) ($_GET['urut'] ?? 'mulai-tercepat');
if (!in_array($urut, BOOTCAMP_URUT, true)) {
    $urut = 'mulai-tercepat';
}

$q = trim((string) ($_GET['q'] ?? ''));

if ($kategoriAktif !== null) {
    $programBidang = array_values(array_filter($semuaProgram, function ($p) use ($kategoriAktif) {
        return strcasecmp($p['kategori'], $kategoriAktif['nama_kategori']) === 0;
    }));
} elseif ($kategoriTidakAda) {
    $programBidang = [];
} else {
    $programBidang = $semuaProgram;
}

$paramBidangUrl = $kategoriAktif ? bootcamp_kategori_param($kategoriAktif) : '';

$jumlahPerJenis = bootcamp_hitung_jenis($programBidang);
$jenisAktif = bootcamp_jenis_cocok((string) ($_GET['jenis'] ?? ''));

$program = $jenisAktif === null
    ? $programBidang
    : array_values(array_filter($programBidang, function ($p) use ($jenisAktif) {
        return $p['jenis'] === $jenisAktif;
    }));

$program = array_map(function ($p) use ($hariIni) {
    return bootcamp_siapkan($p, $hariIni);
}, bootcamp_urut($program, $urut, $hariIni));

require_once __DIR__ . '/../../includes/bookmark_store.php';
$tersimpan = bookmark_ids();

$judulHalaman = $kategoriAktif ? $kategoriAktif['nama_kategori'] : 'Katalog Program';

$subJudul = $kategoriAktif
    ? ($kategoriAktif['deskripsi'] !== '' ? $kategoriAktif['deskripsi'] : 'Daftar program pada kategori ini.')
    : 'Cari dan temukan program pengembangan diri yang sesuai dengan kebutuhanmu.';

$opsiUrut = [
    'mulai-tercepat' => 'Mulai tercepat',
    'mulai-terjauh' => 'Mulai terjauh',
    'deadline-terdekat' => 'Deadline daftar terdekat',
    'nama' => 'Nama A–Z',
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RuangTumbuh - <?= aman($judulHalaman); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="/ruang_tumbuh/public/css/dashboard.css">
    <link rel="stylesheet" href="/ruang_tumbuh/public/css/katalog.css?v=7">
</head>

<body>

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-area">

        <?php include __DIR__ . '/../../includes/header.php'; ?>

        <main class="dashboard-container">

            <section class="katalog-header">
                <div>
                    <span class="label-header">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <?= $kategoriAktif ? 'BIDANG' : 'PENGEMBANGAN DIRI'; ?>
                    </span>

                    <h1><?= aman($judulHalaman); ?></h1>
                    <p><?= aman($subJudul); ?></p>
                </div>

                <div class="jumlah-program">
                    <span>Total Program</span>
                    <strong id="jumlahHasil"><?= count($program); ?> program</strong>
                </div>
            </section>

            <!-- Filter jenis program -->
            <nav class="kategori-chips" id="kategoriChips"
                data-kategori="<?= aman($kategoriAktif ? bootcamp_kategori_param($kategoriAktif) : ''); ?>"
                aria-label="Filter jenis program">

                <a href="/ruang_tumbuh/index.php?page=katalog<?= $paramBidangUrl !== '' ? '&kategori=' . urlencode($paramBidangUrl) : ''; ?>"
                    data-jenis=""
                    class="chip <?= $jenisAktif === null ? 'active' : ''; ?>">
                    Semua <span><?= count($programBidang); ?></span>
                </a>

                <?php foreach (BOOTCAMP_JENIS as $jenis): ?>
                    <a href="/ruang_tumbuh/index.php?page=katalog<?= $paramBidangUrl !== '' ? '&kategori=' . urlencode($paramBidangUrl) : ''; ?>&jenis=<?= urlencode($jenis); ?>"
                        data-jenis="<?= aman($jenis); ?>"
                        class="chip <?= $jenisAktif === $jenis ? 'active' : ''; ?>">

                        <?= aman($jenis); ?>
                        <span><?= (int) $jumlahPerJenis[$jenis]; ?></span>
                    </a>
                <?php endforeach; ?>

            </nav>

            <section class="katalog-toolbar" aria-label="Pencarian dan pengurutan katalog">

                <label class="katalog-search-box" for="searchBootcamp">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input type="search"
                        id="searchBootcamp"
                        value="<?= aman($q); ?>"
                        placeholder="Cari nama program, penyelenggara, atau kata kunci..."
                        autocomplete="off">
                </label>

                <label class="katalog-sort">
                    <span>Urutkan:</span>

                    <select id="sortBootcamp" aria-label="Urutkan program">
                        <?php foreach ($opsiUrut as $nilai => $label): ?>
                            <option value="<?= aman($nilai); ?>"
                                <?= $urut === $nilai ? 'selected' : ''; ?>>
                                <?= aman($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

            </section>

            <section class="katalog-list-section">

                <div class="section-title">
                    <div>
                        <h2>Daftar Program</h2>
                        <p>Pilih program untuk melihat informasi selengkapnya.</p>
                    </div>

                    <span id="jumlahProgramText">
                        <?= count($program); ?> program tersedia
                    </span>
                </div>

                <div class="bootcamp-grid"
                    id="bootcampGrid"
                    data-today="<?= (int) $hariIni; ?>">

                    <?php foreach ($program as $data): ?>

                        <?php
                        $id = $data['id'];
                        $saved = in_array($id, $tersimpan, true);

                        $teksCari = mb_strtolower(implode(' ', [
                            $data['judul'],
                            $data['penyelenggara'],
                            $data['kategori'],
                            $data['jenis'],
                            $data['mode'],
                            $data['durasi'],
                            $data['deskripsi'],
                        ]));
                        ?>

                        <article class="bootcamp-card"
                            data-id="<?= aman($id); ?>"
                            data-search="<?= aman($teksCari); ?>"
                            data-title="<?= aman(mb_strtolower($data['judul'])); ?>"
                            data-mulai="<?= (int) $data['mulai_ts']; ?>"
                            data-deadline="<?= (int) $data['deadline_ts']; ?>">

                            <div class="card-image-wrap">

                                <?php if ($data['gambar_src'] !== ''): ?>
                                    <img class="program-image"
                                        src="<?= aman($data['gambar_src']); ?>"
                                        alt="Poster <?= aman($data['judul']); ?>"
                                        loading="lazy">
                                <?php else: ?>
                                    <div class="program-image-placeholder">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </div>
                                <?php endif; ?>

                                <span class="mode">
                                    <?= aman($data['mode'] !== '' ? $data['mode'] : '-'); ?>
                                </span>

                                <button type="button"
                                    class="btn-bookmark <?= $saved ? 'active' : ''; ?>"
                                    data-id="<?= aman($id); ?>"
                                    data-judul="<?= aman($data['judul']); ?>"
                                    data-kategori="<?= aman($data['kategori']); ?>"
                                    data-penyelenggara="<?= aman($data['penyelenggara']); ?>"
                                    data-deadline="<?= aman($data['deadline_tampil']); ?>"
                                    data-deskripsi="<?= aman($data['deskripsi']); ?>"
                                    data-link="<?= aman($data['link'] !== '' ? $data['link'] : '#'); ?>"
                                    aria-label="<?= $saved ? 'Hapus bookmark' : 'Simpan bookmark'; ?>"
                                    aria-pressed="<?= $saved ? 'true' : 'false'; ?>">

                                    <i class="fa-<?= $saved ? 'solid' : 'regular'; ?> fa-bookmark"></i>
                                </button>

                            </div>

                            <div class="card-content">

                                <span class="kategori">
                                    <?= aman($data['jenis']); ?> · <?= aman($data['kategori']); ?>
                                </span>

                                <h3><?= aman($data['judul']); ?></h3>

                                <div class="penyelenggara">
                                    <i class="fa-regular fa-building"></i>
                                    <?= aman($data['penyelenggara']); ?>
                                </div>

                                <p class="deskripsi">
                                    <?= aman($data['deskripsi']); ?>
                                </p>

                                <div class="info">
                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        <?= aman($data['durasi'] !== '' ? $data['durasi'] : '-'); ?>
                                    </span>

                                    <span>
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= $data['mulai_tampil'] !== ''
                                            ? 'Mulai ' . aman($data['mulai_tampil'])
                                            : 'Jadwal belum diisi'; ?>
                                    </span>
                                </div>

                                <div class="info-daftar <?= $data['pendaftaran_tutup'] ? 'tutup' : ''; ?>">
                                    <i class="fa-solid fa-hourglass-half"></i>

                                    <?php if ($data['deadline_tampil'] === ''): ?>
                                        Deadline belum diisi
                                    <?php elseif ($data['pendaftaran_tutup']): ?>
                                        Pendaftaran ditutup (<?= aman($data['deadline_tampil']); ?>)
                                    <?php else: ?>
                                        Daftar sebelum <?= aman($data['deadline_tampil']); ?>
                                    <?php endif; ?>
                                </div>

                            </div>

                            <div class="card-footer">

                                <strong><?= aman($data['harga_tampil']); ?></strong>

                                <div class="card-actions"
                                    style="display:flex;align-items:center;gap:12px;">

                                    <a class="btn-done"
                                        href="/ruang_tumbuh/index.php?page=rating&amp;bootcamp=<?= urlencode($data['judul']); ?>"
                                        title="Sudah selesai? Beri ulasan"
                                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:9999px;background:#059669;color:#fff;font-size:10px;font-weight:700;line-height:1;text-decoration:none;white-space:nowrap;">

                                        <i class="fa-solid fa-circle-check"
                                            style="font-size:10px;color:#fff;"></i>
                                        Done
                                    </a>

                                    <button type="button"
                                        class="btn-detail-program"
                                        data-aksi="detail"
                                        data-id="<?= aman($id); ?>">

                                        Lihat Detail
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>

                                </div>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div class="katalog-empty"
                    id="katalogEmpty"
                    <?= count($program) ? 'hidden' : ''; ?>>

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3 id="emptyJudul">

                        <?php if ($kategoriTidakAda): ?>
                            Bidang tidak ditemukan

                        <?php elseif ($jenisAktif !== null): ?>
                            Belum ada <?= aman($jenisAktif); ?>
                            <?= $kategoriAktif
                                ? ' di bidang ' . aman($kategoriAktif['nama_kategori'])
                                : ''; ?>

                        <?php elseif ($kategoriAktif): ?>
                            Belum ada program di bidang ini

                        <?php else: ?>
                            Program belum tersedia
                        <?php endif; ?>

                    </h3>

                    <p id="emptyTeks">
                        <?= ($kategoriAktif || $kategoriTidakAda || $jenisAktif !== null)
                            ? 'Coba pilih jenis lain atau lihat semua program.'
                            : 'Program yang kamu cari belum tersedia saat ini.'; ?>
                    </p>

                    <?php if ($kategoriAktif || $kategoriTidakAda || $jenisAktif !== null): ?>
                        <a href="/ruang_tumbuh/index.php?page=katalog"
                            class="btn-lihat-semua">
                            Lihat semua program
                        </a>
                    <?php endif; ?>

                </div>

            </section>

        </main>
    </div>

    <div class="modal" id="modalDetail" aria-hidden="true">

        <div class="detail-box"
            role="dialog"
            aria-modal="true"
            aria-labelledby="detailJudul">

            <button class="close-detail"
                type="button"
                data-aksi="tutup-detail"
                aria-label="Tutup detail">

                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="detail-poster" id="detailPoster" hidden>
                <img src="" alt="">
            </div>

            <div class="detail-icon" id="detailIcon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <span class="kategori" id="detailKategori"></span>
            <h2 id="detailJudul"></h2>
            <p id="detailPenyelenggara"></p>

            <div class="detail-info">
                <div>
                    <span>Mode</span>
                    <strong id="detailMode"></strong>
                </div>

                <div>
                    <span>Durasi</span>
                    <strong id="detailDurasi"></strong>
                </div>

                <div>
                    <span>Mulai</span>
                    <strong id="detailMulai"></strong>
                </div>

                <div>
                    <span>Deadline daftar</span>
                    <strong id="detailDeadline"></strong>
                </div>

                <div>
                    <span>Harga</span>
                    <strong id="detailHarga"></strong>
                </div>
            </div>

            <h4>Deskripsi Program</h4>
            <p class="detail-deskripsi" id="detailDeskripsi"></p>

            <a href="#"
                id="detailLink"
                class="btn-daftar"
                target="_blank"
                rel="noopener noreferrer">

                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Daftar Program
            </a>

        </div>
    </div>

    <script type="application/json" id="programData">
        <?= bootcamp_json($program); ?>
    </script>

    <script src="/ruang_tumbuh/public/js/katalog.js?v=6"></script>

</body>
</html>
