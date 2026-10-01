<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['bootcamp'])) {
    $_SESSION['bootcamp'] = [
        [
            'id' => 1,
            'judul' => 'UI/UX Design Bootcamp',
            'penyelenggara' => 'Ruang Tumbuh Academy',
            'kategori' => 'Design',
            'mode' => 'Online',
            'durasi' => '4 Minggu',
            'deadline' => '2026-10-15',
            'harga' => 'Gratis',
            'link' => '#',
            'deskripsi' => 'Pelatihan dasar hingga lanjutan UI/UX Design untuk mahasiswa.'
        ],
        [
            'id' => 2,
            'judul' => 'Web Development Bootcamp',
            'penyelenggara' => 'Ruang Tumbuh Academy',
            'kategori' => 'Teknologi',
            'mode' => 'Online',
            'durasi' => '6 Minggu',
            'deadline' => '2026-10-20',
            'harga' => 'Rp150.000',
            'link' => '#',
            'deskripsi' => 'Belajar membangun website modern menggunakan HTML, CSS, JavaScript, dan PHP.'
        ],
        [
            'id' => 3,
            'judul' => 'Digital Marketing Bootcamp',
            'penyelenggara' => 'SkillUp Indonesia',
            'kategori' => 'Marketing',
            'mode' => 'Offline',
            'durasi' => '3 Minggu',
            'deadline' => '2026-10-25',
            'harga' => 'Rp100.000',
            'link' => '#',
            'deskripsi' => 'Mempelajari strategi digital marketing, content planning, dan social media marketing.'
        ],
        [
            'id' => 4,
            'judul' => 'Data Analytics Bootcamp',
            'penyelenggara' => 'DataCamp Indonesia',
            'kategori' => 'Data',
            'mode' => 'Online',
            'durasi' => '5 Minggu',
            'deadline' => '2026-11-01',
            'harga' => 'Rp200.000',
            'link' => '#',
            'deskripsi' => 'Pelatihan analisis data menggunakan spreadsheet dan tools data analytics.'
        ],
        [
            'id' => 5,
            'judul' => 'Graphic Design Bootcamp',
            'penyelenggara' => 'Creative Academy',
            'kategori' => 'Design',
            'mode' => 'Offline',
            'durasi' => '4 Minggu',
            'deadline' => '2026-11-05',
            'harga' => 'Rp175.000',
            'link' => '#',
            'deskripsi' => 'Belajar prinsip desain grafis, layout, warna, dan pembuatan visual digital.'
        ],
        [
            'id' => 6,
            'judul' => 'Python Programming Bootcamp',
            'penyelenggara' => 'TechSkill Academy',
            'kategori' => 'Teknologi',
            'mode' => 'Online',
            'durasi' => '8 Minggu',
            'deadline' => '2026-11-10',
            'harga' => 'Rp250.000',
            'link' => '#',
            'deskripsi' => 'Belajar pemrograman Python dari dasar hingga pembuatan project sederhana.'
        ]
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

<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>

        <div class="nav-group" style="margin-top:32px;">
            <div class="nav-title">MENU UTAMA</div>

            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="katalog.php" class="nav-link active">
                        <i class="fa-solid fa-shapes"></i>
                        Katalog Bootcamp
                    </a>
                </li>

                <li>
                    <a href="kategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i>
                        Kategori Program
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-bookmark"></i>
                        Bookmark 
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-clock"></i>
                        Pengingat Tenggat
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-pen-to-square"></i>
                        Catatan Pribadi
                    </a>
                </li>

                <li>
                    <a href="rating.php" class="nav-link">
                        <i class="fa-regular fa-star"></i>
                        Ulasan & Rating
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <div class="nav-title">AKUN</div>

            <ul class="nav-menu">
                <li>
                    <a href="profile.php" class="nav-link">
                        <i class="fa-regular fa-user"></i>
                        Profil Pengguna
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