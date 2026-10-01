<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['bootcamp']) || empty($_SESSION['bootcamp'])) {
    $_SESSION['bootcamp'] = [
        
    ];
}

$bootcamp = $_SESSION['bootcamp'];

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'])
    : (isset($_SESSION['user']['nama']) ? htmlspecialchars($_SESSION['user']['nama']) : 'Pengguna');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Katalog Bootcamp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/katalog.css">
</head>
<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-area">

    <header class="header">
        <div class="search-box">
           
        </div>

        <div class="header-actions">
            <button class="btn-simpan">
                <i class="fa-solid fa-plus"></i>
                Simpan Peluang
            </button>

            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff" class="profile-avatar">
            </div>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="katalog-header">
            <div>
                <span class="label-header">
                    <i class="fa-solid fa-graduation-cap"></i>
                    PENGEMBANGAN DIRI
                </span>

                <h1>Katalog Bootcamp</h1>

                <p>
                    Temukan berbagai program pelatihan dan bootcamp
                    untuk meningkatkan skill dan mendukung perkembangan karirmu.
                </p>
            </div>

            <div class="jumlah-program">
                <span>Total Program</span>
                <strong id="jumlahHasil"><?= count($bootcamp); ?> program</strong>
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
                    <h2>Program Bootcamp</h2>
                    <p>Pilih program yang sesuai dengan kebutuhanmu.</p>
                </div>

                <span id="jumlahProgramText"><?= count($bootcamp); ?> program tersedia</span>
            </div>

            <div class="bootcamp-grid">

                <?php foreach ($bootcamp as $data): ?>
                    <article class="bootcamp-card"
                        data-search="<?= htmlspecialchars(strtolower($data['judul'].' '.$data['penyelenggara'].' '.$data['kategori'])); ?>"
                        data-kategori="<?= htmlspecialchars($data['kategori']); ?>"
                        data-mode="<?= htmlspecialchars($data['mode']); ?>">

                        <div class="card-top">
                            <div class="program-icon">
                                <?php
                                $icon = 'fa-graduation-cap';

                                if ($data['kategori'] === 'Design') {
                                    $icon = 'fa-pen-nib';
                                } elseif ($data['kategori'] === 'Teknologi') {
                                    $icon = 'fa-code';
                                } elseif ($data['kategori'] === 'Data') {
                                    $icon = 'fa-chart-column';
                                } elseif ($data['kategori'] === 'Marketing') {
                                    $icon = 'fa-bullhorn';
                                }
                                ?>

                                <i class="fa-solid <?= $icon; ?>"></i>
                            </div>

                            <span class="mode"><?= htmlspecialchars($data['mode']); ?></span>
                        </div>

                        <div class="card-content">
                            <span class="kategori"><?= htmlspecialchars($data['kategori']); ?></span>

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