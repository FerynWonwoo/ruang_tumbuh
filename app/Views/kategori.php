<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8')
    : (isset($_SESSION['user']['nama'])
        ? htmlspecialchars($_SESSION['user']['nama'], ENT_QUOTES, 'UTF-8')
        : 'Pengguna');

$halamanAktif = basename($_SERVER['PHP_SELF']);

// Koneksi database
require_once __DIR__ . '/../../config/koneksimvc.php';

// Mengambil kategori dari database
$kategori_list = [];

$query = "SELECT
            id,
            nama_kategori,
            deskripsi,
            ikon
          FROM kategori
          ORDER BY id DESC";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kategori_list[] = $row;
    }
}

// Warna dan ikon default untuk tampilan
$warnaDefault = '#10B981';
$ikonDefault = 'fa-layer-group';
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
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../public/css/dashboard.css">
    <link rel="stylesheet" href="../../public/css/kategori.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- AREA UTAMA: JANGAN DIBUKA DUA KALI -->
    <div class="main-area">

        <!-- HEADER BERSAMA -->
        <?php include __DIR__ . '/../../includes/header.php'; ?>

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
                        autocomplete="off">
                </div>
            </div>

            <!-- GRID KATEGORI -->

            <div class="kategori-grid">

                <?php if (empty($kategori_list)): ?>

                    <p>Belum ada kategori program yang tersedia.</p>

                <?php else: ?>

                    <?php foreach ($kategori_list as $kat): ?>

                        <?php
                        $warna = $warnaDefault;
                        $ikon = !empty($kat['ikon'])
                            ? $kat['ikon']
                            : $ikonDefault;
                        ?>

                        <a
                            href="katalog.php?kategori=<?= (int) $kat['id']; ?>"
                            class="kategori-card">

                            <div>
                                <div
                                    class="kategori-icon-wrapper"
                                    style="
                            background-color: <?= $warna; ?>18;
                            color: <?= $warna; ?>;
                        ">
                                    <i class="fa-solid <?= htmlspecialchars($ikon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                                </div>

                                <h2 class="kategori-title">
                                    <?= htmlspecialchars(
                                        $kat['nama_kategori'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </h2>

                                <p class="kategori-desc">
                                    <?= htmlspecialchars(
                                        $kat['deskripsi'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>
                            </div>

                            <div class="kategori-footer">

                                <span class="badge-count">
                                    0 Program
                                </span>

                                <span class="kategori-link">
                                    Jelajahi
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


        </main>

    </div>

    <!-- JAVASCRIPT -->
    <script src="../../public/js/kategori.js"></script>

</body>

</html>