<?php
// Contoh login sederhana tanpa database.
session_start();
header('Cache-Control: no-store');

function aman($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$pesanError = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    $aksi = $_POST['aksi'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $pesanError = 'Sesi formulir tidak valid. Silakan coba kembali.';
    } elseif ($aksi === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: index.php');
        exit;
    } elseif ($aksi === 'login') {
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $pesanError = 'Username dan kata sandi wajib diisi.';
        } elseif ($username === 'ruangtumbuh' && $password === 'semogajaya') {
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        } else {
            $pesanError = 'Username atau kata sandi salah.';
        }
    }
}

$sudahLogin = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Ruang Tumbuh</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/script.js" defer></script>
</head>
<body>

    <video class="background-video" autoplay muted loop playsinline aria-hidden="true" tabindex="-1">
        <source src="../assets/background.mp4" type="video/mp4">
    </video>
    <div class="video-overlay" aria-hidden="true"></div>
    <?php if (!$sudahLogin): ?>
    <main class="login-card" id="halaman-login">
        <aside class="promotion">
            <div class="badge">● &nbsp; Portal Peluang Mahasiswa &amp; Karir Muda</div>
            <h1>Raih Peluang<br><span>Impianmu</span> Sekarang.</h1>
            <p>Temukan jalan karir, magang, kompetisi, dan beasiswa terkurasi dalam satu ekosistem terintegrasi.</p>
            <img class="illustration" src="../assets/illustration.png" alt="Ilustrasi seseorang menaiki tangga menuju bintang" width="340" height="280">
            <div class="benefits"><span>✓ Akses Informasi</span><span>✓ Peluang Terbuka</span><span>✓ Ruang Bertumbuh</span></div>
        </aside>
        <section class="login-panel" aria-labelledby="login-title">
            <div class="brand"> Ruang<span>Tumbuh</span></div>
            <div class="form-area">
                <h2 id="login-title">Masuk ke Ruang Tumbuh.</h2>
                <p class="subtitle">Masukkan akun dan kata sandi Anda untuk melanjutkan.</p>
                <?php if ($pesanError !== ''): ?>
                <div class="alert" id="pesan-error" role="alert"><?= aman($pesanError) ?></div>
                <?php endif; ?>
                <form id="form-login" method="post" action="index.php">
                    <input type="hidden" name="aksi" value="login">
                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= aman($username) ?>" placeholder="Masukkan username Anda..." autocomplete="username" required>
                    <label for="password">Kata Sandi</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi Anda..." autocomplete="current-password" required>
                        <button class="toggle-password" id="lihat-password" type="button" aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <path id="garis-mata" d="M3 3 21 21" hidden/>
                            </svg>
                        </button>
                    </div>
                    <div class="forgot-row"><button class="text-button forgot-password" id="lupa-password" type="button">Lupa Kata Sandi?</button></div>
                    <button class="submit-button" type="submit">MASUK KE AKUN <span aria-hidden="true">→</span></button>
                </form>
                <p class="register-prompt">Belum punya akun? <button class="text-button register-link" id="daftar-akun" type="button">Daftar sekarang!</button></p>
            </div>
            <footer class="panel-footer">Satu langkah kecil untuk masa depan yang lebih besar.</footer>
        </section>
    </main>
    <?php else: ?>
    <main class="success-card" id="halaman-berhasil">
        <div class="success-icon" aria-hidden="true">✓</div>
        <h1 id="judul-berhasil" tabindex="-1">Login berhasil!</h1>
        <p>Selamat datang, <strong id="nama-user"><?= aman($_SESSION['username']) ?></strong>.</p>
        <p>Kamu sudah masuk ke Ruang Tumbuh.</p>
        <form method="post" action="index.php">
            <input type="hidden" name="aksi" value="logout">
            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
            <button class="submit-button" id="tombol-logout" type="submit">Keluar dari akun →</button>
        </form>
    </main>
    <?php endif; ?>
    <dialog class="info-dialog" id="info-akun" aria-labelledby="judul-info" aria-describedby="isi-info">
        <h2 id="judul-info"></h2>
        <p id="isi-info"></p>
        <form method="dialog">
            <button class="submit-button" type="submit">Kembali ke login</button>
        </form>
    </dialog>
    <p class="copyright">© <?= date('Y') ?> Ruang Tumbuh. All rights reserved.</p>
</body>
</html>
