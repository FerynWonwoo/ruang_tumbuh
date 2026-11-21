<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Hanya admin yang boleh masuk
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/koneksimvc.php';
require_once __DIR__ . '/../../includes/bootcamp_store.php';

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$hariIni = strtotime('today');
$semuaProgram = bootcamp_load();
$kategoriList = bootcamp_kategori_list($conn ?? null, $semuaProgram);
$namaKategori = array_column($kategoriList, 'nama_kategori');

function katalog_baca_input(array $post, array $namaKategori): array
{
    $d = [];
    foreach (['judul', 'penyelenggara', 'kategori', 'jenis', 'mode', 'durasi',
              'tanggal_mulai', 'deadline', 'harga', 'link', 'deskripsi'] as $k) {
        $d[$k] = trim((string) ($post[$k] ?? ''));
    }

    $err = [];
    if ($d['judul'] === '' || mb_strlen($d['judul']) > 150) {
        $err[] = 'Nama program wajib diisi (maksimal 150 karakter).';
    }
    if ($d['penyelenggara'] === '' || mb_strlen($d['penyelenggara']) > 100) {
        $err[] = 'Penyelenggara wajib diisi (maksimal 100 karakter).';
    }
    if ($d['kategori'] === '' || ($namaKategori && !in_array($d['kategori'], $namaKategori, true))) {
        $err[] = 'Pilih bidang yang tersedia.';
    }
    if (!in_array($d['jenis'], BOOTCAMP_JENIS, true)) {
        $err[] = 'Pilih jenis program (Lomba, Bootcamp, Magang, Karier, Beasiswa).';
    }
    if (!in_array($d['mode'], BOOTCAMP_MODE, true)) {
        $err[] = 'Pilih mode program.';
    }
    if ($d['durasi'] === '' || mb_strlen($d['durasi']) > 50) {
        $err[] = 'Durasi wajib diisi (maksimal 50 karakter).';
    }
    $mulai = bootcamp_ts($d['tanggal_mulai']);
    $batas = bootcamp_ts($d['deadline']);
    if ($mulai === 0) {
        $err[] = 'Tanggal mulai program wajib diisi dengan tanggal yang valid.';
    }
    if ($batas === 0) {
        $err[] = 'Deadline pendaftaran wajib diisi dengan tanggal yang valid.';
    }
    if ($mulai > 0 && $batas > 0 && $batas > $mulai) {
        $err[] = 'Deadline pendaftaran tidak boleh setelah tanggal mulai program.';
    }
    if ($d['harga'] === '' || mb_strlen($d['harga']) > 50) {
        $err[] = 'Harga wajib diisi (contoh: Gratis atau 50.000).';
    }
    if (!filter_var($d['link'], FILTER_VALIDATE_URL)
        || !preg_match('#^https?://#i', $d['link'])) {
        $err[] = 'Link pendaftaran harus berupa URL http:// atau https://.';
    }
    if ($d['deskripsi'] === '' || mb_strlen($d['deskripsi']) > 2000) {
        $err[] = 'Deskripsi wajib diisi (maksimal 2000 karakter).';
    }
    return [$d, $err];
}

function katalog_redirect(): void
{
    header('Location: adminkatalog.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['flash'] = ['tipe' => 'error', 'pesan' => ['Sesi form tidak valid. Muat ulang halaman lalu coba lagi.']];
        katalog_redirect();
    }

    $aksi = (string) ($_POST['aksi'] ?? '');
    $id = (string) ($_POST['id'] ?? '');

    $posisi = null;
    foreach ($semuaProgram as $i => $p) {
        if ($p['id'] === $id) {
            $posisi = $i;
            break;
        }
    }

    if ($aksi === 'hapus') {
    if ($posisi === null) {
        $_SESSION['flash'] = [
            'tipe' => 'error',
            'pesan' => ['Program tidak ditemukan.']
        ];
        katalog_redirect();
    }

    $idProgram = $semuaProgram[$posisi]['id'];
    $gambarLama = $semuaProgram[$posisi]['gambar'];

    if (bootcamp_hapus($idProgram)) {
        bootcamp_hapus_gambar($gambarLama);

        $_SESSION['flash'] = [
            'tipe' => 'sukses',
            'pesan' => ['Program berhasil dihapus.']
        ];
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'error',
            'pesan' => ['Gagal menghapus program dari database.']
        ];
    }

    katalog_redirect();
}

    if ($aksi === 'tambah' || $aksi === 'edit') {
        [$input, $errors] = katalog_baca_input($_POST, $namaKategori);

        if ($aksi === 'edit' && $posisi === null) {
            $errors[] = 'Program yang diedit tidak ditemukan.';
        }

        // Poster
        $gambarBaru = null;
        $errUpload = null;
        if (!$errors) {
            $gambarBaru = bootcamp_upload_gambar($_FILES['gambar'] ?? [], $errUpload);
            if ($errUpload !== null) {
                $errors[] = $errUpload;
            }
        }

        if ($errors) {
            $input['id'] = $id;
            $_SESSION['flash'] = ['tipe' => 'error', 'pesan' => $errors];
            $_SESSION['katalog_old'] = ['aksi' => $aksi, 'data' => $input];
            katalog_redirect();
        }

        $gambarLamaHapus = '';

        if ($aksi === 'tambah') {
            $input['id'] = uniqid();
            $input['gambar'] = $gambarBaru ?? '';
            $semuaProgram[] = $input;
        } else {
            $lama = $semuaProgram[$posisi]['gambar'];
            if ($gambarBaru !== null) {
                $input['gambar'] = $gambarBaru;
                $gambarLamaHapus = $lama;
            } elseif (!empty($_POST['hapus_gambar'])) {
                $input['gambar'] = '';
                $gambarLamaHapus = $lama;
            } else {
                $input['gambar'] = $lama;
            }
            $input['id'] = $id;
            $semuaProgram[$posisi] = $input;
        }

        if (bootcamp_save($semuaProgram)) {
    if ($gambarLamaHapus !== '') {
        bootcamp_hapus_gambar($gambarLamaHapus);
    }

    $_SESSION['flash'] = [
        'tipe' => 'sukses',
        'pesan' => [
            $aksi === 'tambah'
                ? 'Program berhasil ditambahkan.'
                : 'Perubahan program berhasil disimpan.'
        ]
    ];
} else {
    if ($gambarBaru !== null) {
        bootcamp_hapus_gambar($gambarBaru);
    }

    $_SESSION['flash'] = [
        'tipe' => 'error',
        'pesan' => ['Gagal menyimpan data program ke database. Periksa log error.']
    ];
}
        katalog_redirect();
    }

    katalog_redirect();
}

$flash = $_SESSION['flash'] ?? null;
$old = $_SESSION['katalog_old'] ?? null;
unset($_SESSION['flash'], $_SESSION['katalog_old']);

$urut = (string) ($_GET['urut'] ?? 'mulai-tercepat');
if (!in_array($urut, BOOTCAMP_URUT, true)) {
    $urut = 'mulai-tercepat';
}
$q = trim((string) ($_GET['q'] ?? ''));
$filterKategori = trim((string) ($_GET['kategori'] ?? ''));
$filterMode = trim((string) ($_GET['mode'] ?? ''));
$filterJenis = trim((string) ($_GET['jenis'] ?? ''));

$program = array_map(function ($p) use ($hariIni) {
    return bootcamp_siapkan($p, $hariIni);
}, bootcamp_urut($semuaProgram, $urut, $hariIni));

$opsiUrut = [
    'mulai-tercepat'    => 'Mulai tercepat',
    'mulai-terjauh'     => 'Mulai terjauh',
    'deadline-terdekat' => 'Deadline daftar terdekat',
    'nama'              => 'Nama A–Z',
];

$nama_user = $_SESSION['username'] ?? ($_SESSION['nama'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Admin Katalog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/dashboard.css">
    <link rel="stylesheet" href="../../public/css/katalog.css?v=6">
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>

        <div class="nav-group" style="margin-top:32px;">
            <div class="nav-title">MENU ADMIN</div>
            <ul class="nav-menu">
                <li><a href="admindashboard.php" class="nav-link">
                    <i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="adminkatalog.php" class="nav-link active">
                    <i class="fa-solid fa-shapes"></i> Katalog Program</a></li>
                <li><a href="../../index.php?page=kategori" class="nav-link">
                    <i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
                <li><a href="../../index.php?page=rating" class="nav-link">
                    <i class="fa-solid fa-star"></i> Rating</a></li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>

<div class="main-area">

    <header class="header">
        <div class="header-actions">
            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff"
                     class="profile-avatar" alt="Avatar admin">
                <div>
                    <div class="profile-name"><?= aman($nama_user); ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="katalog-header">
            <div>
                <span class="label-header"><i class="fa-solid fa-user-shield"></i> ADMINISTRATOR</span>
                <h1>Kelola Katalog Program</h1>
                <p>Tambahkan, ubah, dan hapus program beserta posternya. Perubahan langsung tampil di katalog pengguna.</p>
            </div>

            <div class="admin-header-action">
                <div class="jumlah-program">
                    <span>Total Program</span>
                    <strong id="jumlahHasil"><?= count($program); ?></strong>
                </div>
                <button type="button" class="btn-tambah" data-aksi="tambah">
                    <i class="fa-solid fa-plus"></i> Tambah Program
                </button>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="flash flash-<?= aman($flash['tipe']); ?>" role="alert">
                <i class="fa-solid <?= $flash['tipe'] === 'sukses' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <div>
                    <?php foreach ((array) $flash['pesan'] as $pesan): ?>
                        <div><?= aman($pesan); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <section class="filter-section">
            <div class="filter-title"><i class="fa-solid fa-filter"></i> Cari &amp; Filter</div>

            <label class="katalog-search-box" for="searchBootcamp">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="searchBootcamp" value="<?= aman($q); ?>"
                       placeholder="Cari nama program, penyelenggara..." autocomplete="off">
            </label>

            <select id="filterJenis" aria-label="Filter jenis">
                <option value="">Semua Jenis</option>
                <?php foreach (BOOTCAMP_JENIS as $j): ?>
                    <option value="<?= aman($j); ?>" <?= $filterJenis === $j ? 'selected' : ''; ?>><?= aman($j); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="filterKategori" aria-label="Filter bidang">
                <option value="">Semua Bidang</option>
                <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?= aman($kat['nama_kategori']); ?>"
                        <?= strcasecmp($filterKategori, $kat['nama_kategori']) === 0 ? 'selected' : ''; ?>>
                        <?= aman($kat['nama_kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="filterMode" aria-label="Filter mode">
                <option value="">Semua Mode</option>
                <?php foreach (BOOTCAMP_MODE as $m): ?>
                    <option value="<?= aman($m); ?>" <?= $filterMode === $m ? 'selected' : ''; ?>><?= aman($m); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="sortBootcamp" aria-label="Urutkan program">
                <?php foreach ($opsiUrut as $nilai => $label): ?>
                    <option value="<?= aman($nilai); ?>" <?= $urut === $nilai ? 'selected' : ''; ?>><?= aman($label); ?></option>
                <?php endforeach; ?>
            </select>
        </section>

        <section>
            <div class="section-title">
                <div>
                    <h2>Daftar Program</h2>
                    <p>Kelola seluruh program pelatihan yang tersedia.</p>
                </div>
                <span id="jumlahProgramText"><?= count($program); ?> program</span>
            </div>

            <div class="bootcamp-grid" id="bootcampGrid" data-today="<?= (int) $hariIni; ?>">
                <?php foreach ($program as $data): ?>
                    <?php
                    $teksCari = mb_strtolower(implode(' ', [
                        $data['judul'], $data['penyelenggara'], $data['kategori'], $data['jenis'],
                        $data['mode'], $data['durasi'], $data['deskripsi'],
                    ]));
                    ?>
                    <article class="bootcamp-card"
                        data-id="<?= aman($data['id']); ?>"
                        data-search="<?= aman($teksCari); ?>"
                        data-title="<?= aman(mb_strtolower($data['judul'])); ?>"
                        data-kategori="<?= aman($data['kategori']); ?>"
                        data-jenis="<?= aman($data['jenis']); ?>"
                        data-mode="<?= aman($data['mode']); ?>"
                        data-mulai="<?= (int) $data['mulai_ts']; ?>"
                        data-deadline="<?= (int) $data['deadline_ts']; ?>">

                        <div class="card-image-wrap">
                            <?php if ($data['gambar_src'] !== ''): ?>
                                <img class="program-image" src="<?= aman($data['gambar_src']); ?>"
                                     alt="Poster <?= aman($data['judul']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="program-image-placeholder"><i class="fa-solid fa-image"></i></div>
                            <?php endif; ?>
                            <span class="mode"><?= aman($data['mode']); ?></span>

                            <div class="card-menu">
                                <button type="button" data-aksi="edit" data-id="<?= aman($data['id']); ?>" title="Edit" aria-label="Edit program">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST" class="form-hapus" data-nama="<?= aman($data['judul']); ?>">
                                    <input type="hidden" name="csrf" value="<?= aman($csrf); ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= aman($data['id']); ?>">
                                    <button type="submit" title="Hapus" aria-label="Hapus program">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card-content">
                            <span class="kategori"><?= aman($data['jenis']); ?> · <?= aman($data['kategori']); ?></span>
                            <h3><?= aman($data['judul']); ?></h3>
                            <div class="penyelenggara">
                                <i class="fa-regular fa-building"></i> <?= aman($data['penyelenggara']); ?>
                            </div>
                            <p class="deskripsi"><?= aman($data['deskripsi']); ?></p>
                            <div class="info">
                                <span><i class="fa-regular fa-clock"></i> <?= aman($data['durasi']); ?></span>
                                <span>
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= $data['mulai_tampil'] !== '' ? 'Mulai ' . aman($data['mulai_tampil']) : 'Jadwal belum diisi'; ?>
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
                            <button type="button" class="btn-detail-program" data-aksi="detail" data-id="<?= aman($data['id']); ?>">
                                Lihat Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="katalog-empty" id="katalogEmpty" <?= count($program) ? 'hidden' : ''; ?>>
                <i class="fa-solid fa-magnifying-glass"></i>
                <h3 id="emptyJudul"><?= count($semuaProgram) ? 'Program tidak ditemukan' : 'Belum ada program'; ?></h3>
                <p id="emptyTeks"><?= count($semuaProgram)
                    ? 'Ubah kata kunci atau filter pencarian.'
                    : 'Klik “Tambah Program” untuk menambahkan program pertama.'; ?></p>
            </div>
        </section>

    </main>
</div>


<div class="modal" id="modalForm">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <span>DATA PROGRAM</span>
                <h2 id="judulModal">Tambah Program</h2>
            </div>
            <button type="button" data-aksi="tutup-form" aria-label="Tutup form">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="formBootcamp" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= aman($csrf); ?>">
            <input type="hidden" name="aksi" id="aksi" value="tambah">
            <input type="hidden" name="id" id="id">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) BOOTCAMP_MAX_GAMBAR; ?>">

            <p class="form-error" id="pesanClient" hidden></p>

            <div class="form-grid">
                <div class="form-group full">
                    <label for="judul">Nama Program</label>
                    <input type="text" name="judul" id="judul" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label for="penyelenggara">Penyelenggara</label>
                    <input type="text" name="penyelenggara" id="penyelenggara" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="jenis">Jenis Program</label>
                    <select name="jenis" id="jenis" required>
                        <option value="">Pilih Jenis</option>
                        <?php foreach (BOOTCAMP_JENIS as $j): ?>
                            <option value="<?= aman($j); ?>"><?= aman($j); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kategori">Bidang (Kategori)</label>
                    <select name="kategori" id="kategori" required>
                        <option value="">Pilih Bidang</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= aman($kat['nama_kategori']); ?>"><?= aman($kat['nama_kategori']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mode">Mode</label>
                    <select name="mode" id="mode" required>
                        <option value="">Pilih Mode</option>
                        <?php foreach (BOOTCAMP_MODE as $m): ?>
                            <option value="<?= aman($m); ?>"><?= aman($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="durasi">Durasi</label>
                    <input type="text" name="durasi" id="durasi" maxlength="50" placeholder="Contoh: 4 Minggu" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai Program</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" required>
                </div>

                <div class="form-group">
                    <label for="deadline">Deadline Pendaftaran</label>
                    <input type="date" name="deadline" id="deadline" required>
                </div>

                <div class="form-group">
                    <label for="harga">Harga</label>
                    <input type="text" name="harga" id="harga" maxlength="50" placeholder="Contoh: Gratis atau 50.000" required>
                </div>

                <div class="form-group">
                    <label for="link">Link Pendaftaran</label>
                    <input type="url" name="link" id="link" placeholder="https://..." required>
                </div>

                <div class="form-group full">
                    <label for="gambarFile">Poster Program (opsional)</label>
                    <div class="upload-box" id="uploadBox">
                        <div class="upload-preview" id="uploadPreview" hidden>
                            <img src="" alt="Pratinjau poster" id="uploadPreviewImg">
                        </div>
                        <div class="upload-body">
                            <input type="file" name="gambar" id="gambarFile" accept="image/png,image/jpeg,image/webp">
                            <small>Format JPG, PNG, atau WebP. Maksimal 2 MB. Rasio lebar disarankan 16:9.</small>
                            <label class="cek-hapus" id="hapusGambarWrap" hidden>
                                <input type="checkbox" name="hapus_gambar" id="hapusGambar" value="1">
                                Hapus poster saat ini
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group full">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" maxlength="2000" required></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-batal" data-aksi="tutup-form">Batal</button>
                <button type="submit" class="btn-simpan" id="btnSubmit">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal detail -->
<div class="modal" id="modalDetail" aria-hidden="true">
    <div class="detail-box" role="dialog" aria-modal="true" aria-labelledby="detailJudul">
        <button class="close-detail" type="button" data-aksi="tutup-detail" aria-label="Tutup detail">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="detail-poster" id="detailPoster" hidden><img src="" alt=""></div>
        <div class="detail-icon" id="detailIcon"><i class="fa-solid fa-graduation-cap"></i></div>
        <span class="kategori" id="detailKategori"></span>
        <h2 id="detailJudul"></h2>
        <p id="detailPenyelenggara"></p>
        <div class="detail-info">
            <div><span>Mode</span><strong id="detailMode"></strong></div>
            <div><span>Durasi</span><strong id="detailDurasi"></strong></div>
            <div><span>Mulai</span><strong id="detailMulai"></strong></div>
            <div><span>Deadline daftar</span><strong id="detailDeadline"></strong></div>
            <div><span>Harga</span><strong id="detailHarga"></strong></div>
        </div>
        <h4>Deskripsi Program</h4>
        <p class="detail-deskripsi" id="detailDeskripsi"></p>
        <a href="#" id="detailLink" class="btn-daftar" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link Pendaftaran
        </a>
    </div>
</div>

<script type="application/json" id="programData"><?= bootcamp_json($program); ?></script>
<?php if ($old): ?>
    <script type="application/json" id="oldData"><?= bootcamp_json($old); ?></script>
<?php endif; ?>
<script src="../../public/js/katalog.js?v=6"></script>
</body>
</html>
