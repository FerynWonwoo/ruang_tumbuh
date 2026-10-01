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
    <meta na    me="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Tenggat - RuangTumbuh</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/pengingat.css">
</head>

<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

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