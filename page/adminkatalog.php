<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['bootcamp'])) {
    $_SESSION['bootcamp'] = [];
}

if (isset($_POST['tambah'])) {
    $data = [
        'id' => time(),
        'judul' => $_POST['judul'],
        'penyelenggara' => $_POST['penyelenggara'],
        'kategori' => $_POST['kategori'],
        'mode' => $_POST['mode'],
        'durasi' => $_POST['durasi'],
        'deadline' => $_POST['deadline'],
        'harga' => $_POST['harga'],
        'link' => $_POST['link'],
        'deskripsi' => $_POST['deskripsi']
    ];

    $_SESSION['bootcamp'][] = $data;
    header('Location: adminkatalog.php');
    exit;
}

if (isset($_POST['edit'])) {
    foreach ($_SESSION['bootcamp'] as &$data) {
        if ($data['id'] == $_POST['id']) {
            $data['judul'] = $_POST['judul'];
            $data['penyelenggara'] = $_POST['penyelenggara'];
            $data['kategori'] = $_POST['kategori'];
            $data['mode'] = $_POST['mode'];
            $data['durasi'] = $_POST['durasi'];
            $data['deadline'] = $_POST['deadline'];
            $data['harga'] = $_POST['harga'];
            $data['link'] = $_POST['link'];
            $data['deskripsi'] = $_POST['deskripsi'];
            break;
        }
    }

    unset($data);
    header('Location: adminkatalog.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    $_SESSION['bootcamp'] = array_values(
        array_filter($_SESSION['bootcamp'], function ($data) use ($id) {
            return $data['id'] != $id;
        })
    );

    header('Location: adminkatalog.php');
    exit;
}

$bootcamp = $_SESSION['bootcamp'];

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'])
    : 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Admin Katalog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/katalog.css">
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>

        <div class="nav-group" style="margin-top:32px;">
            <div class="nav-title">MENU ADMIN</div>

            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="adminkatalog.php" class="nav-link active">
                        <i class="fa-solid fa-shapes"></i>
                        Katalog Bootcamp
                    </a>
                </li>

                <li>
                    <a href="adminkategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i>
                        Kategori Program
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>

<div class="main-area">

    <header class="header">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="search-input" placeholder="Cari program...">
        </div>

        <div class="header-actions">
            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff" class="profile-avatar">

                <div>
                    <div class="profile-name"><?= $nama_user; ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="katalog-header">
            <div>
                <span class="label-header">
                    <i class="fa-solid fa-user-shield"></i>
                    ADMINISTRATOR
                </span>

                <h1>Kelola Katalog Bootcamp</h1>

                <p>
                    Tambahkan, ubah, dan hapus informasi program bootcamp
                    yang tersedia pada RuangTumbuh.
                </p>
            </div>

            <div class="admin-header-action">
                <div class="jumlah-program">
                    <span>Total Program</span>
                    <strong id="jumlahHasil"><?= count($bootcamp); ?></strong>
                </div>

                <button class="btn-tambah" onclick="bukaModalTambah()">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Bootcamp
                </button>
            </div>
        </section>

        <section class="filter-section">
            <div class="filter-title">
                <i class="fa-solid fa-filter"></i>
                Filter Program
            </div>

            <div class="katalog-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchBootcamp" placeholder="Cari bootcamp...">
            </div>

            <select id="filterKategori">
                <option value="">Semua Kategori</option>
                <option value="Teknologi">Teknologi</option>
                <option value="Design">Design</option>
                <option value="Data">Data</option>
                <option value="Marketing">Marketing</option>
            </select>

            <select id="filterMode">
                <option value="">Semua Mode</option>
                <option value="Online">Online</option>
                <option value="Offline">Offline</option>
            </select>
        </section>

        <section>
            <div class="section-title">
                <div>
                    <h2>Daftar Bootcamp</h2>
                    <p>Kelola seluruh program pelatihan yang tersedia.</p>
                </div>

                <span id="jumlahProgramText"><?= count($bootcamp); ?> program</span>
            </div>

            <div class="bootcamp-grid">

                <?php foreach ($bootcamp as $data): ?>
                    <article class="bootcamp-card"
                        data-search="<?= htmlspecialchars(strtolower($data['judul'].' '.$data['penyelenggara'].' '.$data['kategori'])); ?>"
                        data-kategori="<?= htmlspecialchars($data['kategori']); ?>"
                        data-mode="<?= htmlspecialchars($data['mode']); ?>">

                        <div class="card-top">

                            <div class="program-icon">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>

                            <span class="mode"><?= htmlspecialchars($data['mode']); ?></span>

                            <div class="card-menu">

                                <button onclick='bukaModalEdit(<?= json_encode($data); ?>)' title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <a href="adminkatalog.php?hapus=<?= $data['id']; ?>"
                                   onclick="return confirm('Yakin ingin menghapus bootcamp ini?')"
                                   title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>

                            </div>
                        </div>

                        <div class="card-content">

                            <span class="kategori">
                                <?= htmlspecialchars($data['kategori']); ?>
                            </span>

                            <h3><?= htmlspecialchars($data['judul']); ?></h3>

                            <div class="penyelenggara">
                                <i class="fa-regular fa-building"></i>
                                <?= htmlspecialchars($data['penyelenggara']); ?>
                            </div>

                            <p class="deskripsi">
                                <?= htmlspecialchars($data['deskripsi']); ?>
                            </p>

                            <div class="info">
                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?= htmlspecialchars($data['durasi']); ?>
                                </span>

                                <span>
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= date('d M Y', strtotime($data['deadline'])); ?>
                                </span>
                            </div>

                        </div>

                        <div class="card-footer">

                            <strong><?= htmlspecialchars($data['harga']); ?></strong>

                            <button onclick='lihatDetail(<?= json_encode($data); ?>)'>
                                Lihat Detail
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>

                        </div>

                    </article>
                <?php endforeach; ?>

            </div>
        </section>

    </main>
</div>

<div class="modal" id="modalForm">

    <div class="modal-box">

        <div class="modal-header">

            <div>
                <span>DATA BOOTCAMP</span>
                <h2 id="judulModal">Tambah Bootcamp</h2>
            </div>

            <button onclick="tutupModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form method="POST" id="formBootcamp">

            <input type="hidden" name="id" id="id">

            <div class="form-grid">

                <div class="form-group full">
                    <label>Nama Bootcamp</label>
                    <input type="text" name="judul" id="judul" required>
                </div>

                <div class="form-group">
                    <label>Penyelenggara</label>
                    <input type="text" name="penyelenggara" id="penyelenggara" required>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" id="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Teknologi">Teknologi</option>
                        <option value="Design">Design</option>
                        <option value="Data">Data</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Mode</label>
                    <select name="mode" id="mode" required>
                        <option value="">Pilih Mode</option>
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Durasi</label>
                    <input type="text" name="durasi" id="durasi" placeholder="Contoh: 4 Minggu" required>
                </div>

                <div class="form-group">
                    <label>Deadline</label>
                    <input type="date" name="deadline" id="deadline" required>
                </div>

                <div class="form-group">
                    <label>Harga</label>
                    <input type="text" name="harga" id="harga" placeholder="Contoh: Gratis" required>
                </div>

                <div class="form-group">
                    <label>Link Pendaftaran</label>
                    <input type="url" name="link" id="link" placeholder="https://..." required>
                </div>

                <div class="form-group full">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" required></textarea>
                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn-batal" onclick="tutupModal()">
                    Batal
                </button>

                <button type="submit" class="btn-simpan" id="btnSubmit" name="tambah">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan
                </button>

            </div>

        </form>

    </div>

</div>

<div class="modal" id="modalDetail">

    <div class="detail-box">

        <button class="close-detail" onclick="tutupDetail()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="detail-icon">
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
                <span>Deadline</span>
                <strong id="detailDeadline"></strong>
            </div>

            <div>
                <span>Harga</span>
                <strong id="detailHarga"></strong>
            </div>

        </div>

        <h4>Deskripsi Program</h4>

        <p class="detail-deskripsi" id="detailDeskripsi"></p>

        <a href="#" id="detailLink" class="btn-daftar" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            Daftar Program
        </a>

    </div>

</div>

<script src="../js/katalog.js"></script>
</body>
</html>