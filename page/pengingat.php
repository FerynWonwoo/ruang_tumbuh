<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'])
    : (isset($_SESSION['user']['nama'])
        ? htmlspecialchars($_SESSION['user']['nama'])
        : 'Pengguna');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Tenggat - RuangTumbuh</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/pengingat.css">
</head>

<body>

<aside class="sidebar">

    <div>
        <div class="logo-text">
            Ruang<span>Tumbuh</span>
        </div>

        <div class="sidebar-content">

            <div class="nav-group">
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
                        <a href="kategori.php" class="nav-link">
                            <i class="fa-solid fa-layer-group"></i>
                            Kategori Program
                        </a>
                    </li>

                    <li>
                        <a href="#" class="nav-link">
                            <i class="fa-regular fa-bookmark"></i>
                            Bookmark &amp;<br>Tersimpan
                        </a>
                    </li>

                    <li>
                        <a href="pengingat.php" class="nav-link active">
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
                            Ulasan &amp; Rating
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
    </div>

</aside>


<main class="main-area">

    <div class="pengingat-container">

        <!-- BANNER -->
        <section class="welcome-banner">

            <div class="badge-tag">
                <i class="fa-regular fa-clock"></i>
                PENGINGAT TENGGAT
            </div>

            <h1 class="banner-title">
                Jangan lewatkan tenggat pentingmu.
            </h1>

            <p class="banner-subtitle">
                Catat aktivitas dan tanggal tenggat agar tugas,
                pendaftaran, dan peluangmu tetap terpantau.
            </p>

        </section>


        <!-- TAMBAH PENGINGAT -->
        <section class="reminder-card">

            <div class="card-header">

                <div class="card-title-group">

                    <i class="fa-regular fa-calendar-plus"></i>

                    <div>
                        <h2 class="card-title">
                            Tambah Pengingat
                        </h2>

                        <p class="card-description">
                            Tambahkan aktivitas yang memiliki tenggat waktu.
                        </p>
                    </div>

                </div>

            </div>


            <form id="formPengingat" class="reminder-form">

                <div class="form-group">

                    <label for="judul">
                        Nama Aktivitas
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-regular fa-pen-to-square"></i>

                        <input
                            type="text"
                            id="judul"
                            placeholder="Contoh: Pengumpulan tugas"
                            required>

                    </div>

                </div>


                <div class="form-group">

                    <label for="tanggal">
                        Tanggal Tenggat
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-regular fa-calendar"></i>

                        <input
                            type="text"
                            id="tanggal"
                            placeholder="dd/mm/yyyy"
                            maxlength="10"
                            required>

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn-tambah">

                    <i class="fa-solid fa-plus"></i>
                    Tambah Pengingat

                </button>

            </form>

        </section>


        <!-- DAFTAR TENGGAT -->
        <section class="reminder-card">

            <div class="list-header">

                <div class="card-title-group">

                    <i class="fa-regular fa-calendar-check"></i>

                    <div>
                        <h2 class="card-title">
                            Daftar Tenggat
                        </h2>

                        <p class="card-description">
                            Aktivitas yang sudah kamu simpan.
                        </p>
                    </div>

                </div>

                <div class="total-reminder">
                    <span id="jumlah">0</span>
                    Pengingat
                </div>

            </div>


            <div
                id="daftarPengingat"
                class="reminder-list">
            </div>

        </section>

    </div>

</main>


<script src="../js/pengingat.js"></script>

</body>
</html>