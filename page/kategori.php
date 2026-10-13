
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8')
    : (isset($_SESSION['user']['nama'])
        ? htmlspecialchars($_SESSION['user']['nama'], ENT_QUOTES, 'UTF-8')
        : 'Pengguna');

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

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/kategori.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- AREA UTAMA: JANGAN DIBUKA DUA KALI -->
    <div class="main-area">

        <!-- HEADER BERSAMA -->
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <!-- KONTEN HALAMAN -->
        <main class="dashboard-container">

            <!-- BANNER: MENGGUNAKAN CLASS DARI DASHBOARD -->
            <section class="welcome-banner">

                <span class="badge-tag">
                    <i class="fa-solid fa-layer-group"></i>
                    Eksplorasi Program
                </span>

                <h1 class="banner-title">
                    Kategori Program
                </h1>

                <p class="banner-subtitle">
                    Pantau dan jelajahi berbagai jalur pengembangan
                    potensi sesuai dengan tujuan karirmu.
                </p>

            </section>

            <!-- SEARCH KATEGORI -->
            <div class="kategori-search-section">
                <label for="searchKategori" class="kategori-search-label">
                    Cari kategori program
                </label>

                <div class="search-box kategori-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchKategori"
                        class="search-input"
                        placeholder="Cari kategori, misalnya Bootcamp..."
                        autocomplete="off"
                    >
                </div>
            </div>

            <!-- GRID KATEGORI -->
            <div class="kategori-grid">

                <?php foreach ($kategori_list as $kat): ?>

                    <a
                        href="katalog.php?kategori=<?= urlencode($kat['id']); ?>"
                        class="kategori-card"
                    >

                        <div>
                            <div
                                class="kategori-icon-wrapper"
                                style="
                                    background-color: <?= $kat['warna']; ?>18;
                                    color: <?= $kat['warna']; ?>;
                                "
                            >
                                <i class="fa-solid <?= $kat['ikon']; ?>"></i>
                            </div>

                            <h2 class="kategori-title">
                                <?= htmlspecialchars(
                                    $kat['nama'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </h2>

                            <p class="kategori-desc">
                                <?= htmlspecialchars(
                                    $kat['deskripsi'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>
                        </div>

                        <div class="kategori-footer">

                            <span class="badge-count">
                                <?= (int) $kat['jumlah']; ?> Program
                            </span>

                            <span class="kategori-link">
                                Jelajahi
                                <i class="fa-solid fa-arrow-right"></i>
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        </main>

    </div>

    <!-- JAVASCRIPT -->
    <script src="../js/kategori.js"></script>

</body>
</html>
