<?php
session_start();
header('Cache-Control: no-store');

function aman($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$pesanError = '';
$pesanSukses = '';
$namaLengkap = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    $aksi = $_POST['aksi'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $pesanError = 'Sesi formulir tidak valid. Silakan coba kembali.';
    } elseif ($aksi === 'register') {
        $namaLengkap = is_string($_POST['nama_lengkap'] ?? null) ? trim($_POST['nama_lengkap']) : '';
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $konfirmasiPassword = is_string($_POST['konfirmasi_password'] ?? null) ? $_POST['konfirmasi_password'] : '';

        if ($namaLengkap === '' || $username === '' || $email === '' || $password === '') {
            $pesanError = 'Semua kolom wajib diisi.';
        } elseif ($password !== $konfirmasiPassword) {
            $pesanError = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            // Logika simpan akun baru bisa ditambahkan di sini
            header('Location: index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | Ruang Tumbuh</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/script.js" defer></script>
</head>
<body>

    <video class="background-video" autoplay muted loop playsinline aria-hidden="true" tabindex="-1">
        <source src="../assets/background.mp4" type="video/mp4">
    </video>
    <div class="video-overlay" aria-hidden="true"></div>

    <main class="login-card" id="halaman-register">
        <aside class="promotion">
            <div class="badge">● &nbsp; Portal Peluang Mahasiswa &amp; Karir Muda</div>
            <h1>Bergabung Bersama<br><span>Ruang Tumbuh</span></h1>
            <p>Buat akun sekarang untuk mengakses ribuan informasi karir, magang, dan beasiswa.</p>
            <img class="illustration" src="../assets/illustration.png" alt="Ilustrasi seseorang menaiki tangga menuju bintang" width="340" height="280">
            <div class="benefits"><span>✓ Akses Informasi</span><span>✓ Peluang Terbuka</span><span>✓ Ruang Bertumbuh</span></div>
        </aside>

        <section class="login-panel" aria-labelledby="register-title">
            <div class="brand"> Ruang<span>Tumbuh</span></div>
            <div class="form-area">
                <h2 id="register-title">Daftar Akun Baru.</h2>
                <p class="subtitle">Lengkapi data diri Anda di bawah ini untuk mendaftar.</p>

                <?php if ($pesanError !== ''): ?>
                    <div class="alert" id="pesan-error" role="alert"><?= aman($pesanError) ?></div>
                <?php endif; ?>

                <?php if ($pesanSukses !== ''): ?>
                    <div class="alert" style="background: #e7f5ef; border-color: #bce3d2; color: #236353;" role="status">
                        <?= aman($pesanSukses) ?>
                    </div>
                <?php endif; ?>

                <form id="form-register" method="post" action="register.php">
                    <input type="hidden" name="aksi" value="register">
                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">

                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input id="nama_lengkap" name="nama_lengkap" type="text" value="<?= aman($namaLengkap) ?>" placeholder="Masukkan nama lengkap..." required>

                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= aman($username) ?>" placeholder="Buat username..." autocomplete="username" required>

                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= aman($email) ?>" placeholder="contoh@email.com" autocomplete="email" required>

                    <label for="password">Kata Sandi</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" placeholder="Buat kata sandi..." autocomplete="new-password" required>
                        <button class="toggle-password" id="lihat-password" type="button" aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <path id="garis-mata" d="M3 3 21 21" hidden/>
                            </svg>
                        </button>
                    </div>

                    <label for="konfirmasi_password">Konfirmasi Kata Sandi</label>
                    <input id="konfirmasi_password" name="konfirmasi_password" type="password" placeholder="Ulangi kata sandi..." autocomplete="new-password" required>

                    <button class="submit-button" type="submit">DAFTAR AKUN SEKARANG <span aria-hidden="true">→</span></button>
                </form>

                <p class="register-prompt">Sudah punya akun? <a href="index.php" class="register-link">Masuk di sini</a></p>
            </div>
            <footer class="panel-footer">Satu langkah kecil untuk masa depan yang lebih besar.</footer>
        </section>
    </main>

    <p class="copyright">© <?= date('Y') ?> Ruang Tumbuh. All rights reserved.</p>
</body>
</html>