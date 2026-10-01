<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback session username jika belum didefinisikan
$nama_user = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Pengguna';
$halamanAktif = basename($_SERVER['PHP_SELF']);

// Data Kategori Program
$kategori_list = [
    [
        'id' => 'bootcamp',
        'nama' => 'Bootcamp & Intensive Class',
        'ikon' => 'fa-laptop-code',
        'warna' => '#10B981',
        'jumlah' => 24,
        'deskripsi' => 'Program pelatihan terstruktur untuk menguasai keterampilan digital secara intensif dari dasar hingga siap kerja.'
    ],
    [
        'id' => 'beasiswa',
        'nama' => 'Beasiswa & Pendanaan',
        'ikon' => 'fa-graduation-cap',
        'warna' => '#3B82F6',
        'jumlah' => 18,
        'deskripsi' => 'Peluang bantuan dana pendidikan, beasiswa studi lanjut, dan program pertukaran pelajar nasional maupun internasional.'
    ],
    [
        'id' => 'magang',
        'nama' => 'Magang & Karir Pertama',
        'ikon' => 'fa-briefcase',
        'warna' => '#8B5CF6',
        'jumlah' => 35,
        'deskripsi' => 'Akses ke berbagai kesempatan internship di startup, BUMN, dan perusahaan multinasional untuk mengawali karirmu.'
    ],
    [
        'id' => 'lomba',
        'nama' => 'Kompetisi & Lomba',
        'ikon' => 'fa-trophy',
        'warna' => '#F59E0B',
        'jumlah' => 12,
        'deskripsi' => 'Asah kemampuan dan bangun portofolio melalui kompetisi UI/UX, hackathon, essay, dan ide bisnis.'
    ],
    [
        'id' => 'webinar',
        'nama' => 'Webinar & Workshop',
        'ikon' => 'fa-chalkboard-user',
        'warna' => '#EC4899',
        'jumlah' => 29,
        'deskripsi' => 'Sesi berbagi ilmu bersama praktisi industri untuk memperluas wawasan dan jaringan profesional.'
    ],
    [
        'id' => 'sertifikasi',
        'nama' => 'Sertifikasi Profesi',
        'ikon' => 'fa-certificate',
        'warna' => '#06B6D4',
        'jumlah' => 9,
        'deskripsi' => 'Program sertifikasi resmi untuk memvalidasi keahlian teknis dan non-teknis agar dilirik oleh perekrut.'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Kategori Program</title>

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS File External -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/kategori.css">
</head>

<body>

    <aside class="sidebar">

        <div>
            <div class="logo-text">
                Ruang<span>Tumbuh</span>
            </div>

            <div class="nav-group" style="margin-top: 32px;">
                <div class="nav-title">MENU UTAMA</div>

                <ul class="nav-menu">
                    <li>
                        <a href="dashboard.php" class="nav-link">
                            <i class="fa-solid fa-table-cells-large"></i>
                            Dashboard
                        </a>
                    </li>

                    <li>
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-shapes"></i>
                            Katalog Bootcamp
                        </a>
                    </li>

                    <li>
                        <a href="kategori.php" class="nav-link <?= $halamanAktif === 'kategori.php' ? 'active' : '' ?>">
                            <i class="fa-solid fa-layer-group"></i>
                            Kategori Program
                        </a>
                    </li>

                    <li>
                        <a href="bookmark.php" class="nav-link">
                            <i class="fa-regular fa-bookmark"></i>
                            Bookmark
                        </a>
                    </li>

                    <li>
                        <a href="pengingat.php" class="nav-link">
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


<<<<<<< HEAD
            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="katalog.php" class="nav-link">
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
                    <a href="bookmark.php" class="nav-link">
                        <i class="fa-regular fa-bookmark"></i>
                        Bookmark
                    </a>
                </li>

                <li>
                    <a href="pengingat.php" class="nav-link">
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
=======
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
>>>>>>> 55113c3136b6e3504294e58a3fd89ae41bca1a18
        </div>

    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">

        <!-- HEADER -->
        <header class="header">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchKategori" class="search-input" placeholder="Cari kategori program...">
            </div>

            <div class="header-actions">
                <button class="btn-simpan">
                    <i class="fa-solid fa-plus"></i> Simpan Peluang
                </button>

                <div class="profile-section">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff" alt="Avatar" class="profile-avatar">
                </div>
            </div>
        </header>

        <!-- DASHBOARD CONTAINER -->
        <main class="dashboard-container">

            <!-- HERO CONTAINER KUNING UTUH (Membungkus Judul & Kartu) -->
            <div class="kategori-hero-card">

                <!-- HEADER JUDUL -->
                <div class="kategori-header">
                    <span class="badge-tag"><i class="fa-solid fa-layer-group"></i> Eksplorasi Program</span>
                    <h1>Kategori Program</h1>
                    <p>Pantau dan jelajahi berbagai jalur pengembangan potensi sesuai dengan tujuan karirmu.</p>
                </div>

                <!-- GRID KATEGORI DI DALAM BOX KUNING -->
                <div class="kategori-grid">
                    <?php foreach ($kategori_list as $kat): ?>
                        <a href="katalog.php?kategori=<?= $kat['id']; ?>" class="kategori-card">
                            <div>
                                <div class="kategori-icon-wrapper" style="background-color: <?= $kat['warna']; ?>18; color: <?= $kat['warna']; ?>;">
                                    <i class="fa-solid <?= $kat['ikon']; ?>"></i>
                                </div>
                                <h2 class="kategori-title"><?= $kat['nama']; ?></h2>
                                <p class="kategori-desc"><?= $kat['deskripsi']; ?></p>
                            </div>
                            <div class="kategori-footer">
                                <span class="badge-count"><?= $kat['jumlah']; ?> Program</span>
                                <span>Jelajahi <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>

        </main>
    </div>

    <!-- JS File External -->
    <script src="../js/kategori.js"></script>
</body>

</html>