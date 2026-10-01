<?php
session_start();

$pesanError = '';
$username = '';

// Fungsi keamanan output
function aman($teks)
{
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

// Buat CSRF token jika belum ada
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// PROSES LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // LOGIN ADMIN
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header('Location: adminrating.php');
            exit;
        }
        // LOGIN USER BAWAAN
        elseif ($username === 'ruangtumbuh' && $password === 'semogajaya') {
            $_SESSION['username'] = 'ruangtumbuh';
            $_SESSION['role'] = 'user';
            header('Location: dashboard.php');
            exit;
        }
        // LOGIN USER HASIL REGISTER
        elseif (
            isset($_SESSION['registered_user']) &&
            $username === ($_SESSION['registered_user']['username'] ?? '') &&
            $password === ($_SESSION['registered_user']['password'] ?? '')
        ) {
            $_SESSION['username'] = $_SESSION['registered_user']['username'];
            $_SESSION['role'] = 'user';
            header('Location: dashboard.php');
            exit;
        } else {
            $pesanError = 'Username atau password salah.';
        }
    }
}
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

    <!-- Background Video -->
    <video class="background-video" autoplay muted loop playsinline aria-hidden="true">
        <source src="../assets/background.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <!-- LOGIN CARD -->
    <main class="login-card">

        <!-- BAGIAN KIRI -->
        <aside class="promotion">
            <div class="badge">
                ● &nbsp; Portal Peluang Mahasiswa &amp; Karir Muda
            </div>

            <h1>Raih Peluang <br><span>Impianmu</span> Sekarang.</h1>

            <p>
                Temukan jalan karir, magang, kompetisi, dan beasiswa terkurasi dalam satu ekosistem terintegrasi.
            </p>

            <img class="illustration" src="../assets/illustration.png" alt="Ilustrasi Ruang Tumbuh">

            <div class="benefits">
                <span>✓ Akses Informasi</span>
                <span>✓ Peluang Terbuka</span>
                <span>✓ Ruang Bertumbuh</span>
            </div>
        </aside>

        <!-- BAGIAN KANAN -->
        <section class="login-panel">
            <div class="brand">
                Ruang<span>Tumbuh</span>
            </div>

            <div class="form-area">
                <h2>Masuk ke Ruang Tumbuh.</h2>
                <p class="subtitle">Masukkan akun dan kata sandi Anda untuk melanjutkan.</p>

                <!-- ERROR LOGIN -->
                <?php if ($pesanError !== ''): ?>
                    <div class="alert">
                        <?= aman($pesanError) ?>
                    </div>
                <?php endif; ?>

                <!-- FORM LOGIN -->
                <form id="form-login" method="post" action="index.php">
                    <input type="hidden" name="aksi" value="login">
                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">

                    <!-- USERNAME -->
                    <label for="username">Username</label>
                    <input 
                        id="username" 
                        name="username" 
                        type="text" 
                        value="<?= aman($username) ?>" 
                        placeholder="Masukkan username Anda..." 
                        autocomplete="username" 
                        required
                    >

                    <!-- PASSWORD -->
                    <label for="password">Kata Sandi</label>
                    <div class="password-field">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            placeholder="Masukkan kata sandi Anda..." 
                            autocomplete="current-password" 
                            required
                        >
                        <button class="toggle-password" id="lihat-password" type="button" aria-label="Tampilkan kata sandi">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                                <path id="garis-mata" d="M3 3 21 21" hidden />
                            </svg>
                        </button>
                    </div>

                    <!-- LUPA PASSWORD -->
                    <div class="forgot-row">
                        <button class="text-button forgot-password" id="lupa-password" type="button">
                            Lupa Kata Sandi?
                        </button>
                    </div>

                    <!-- BUTTON LOGIN -->
                    <button class="submit-button" type="submit">
                        MASUK KE AKUN <span>→</span>
                    </button>
                </form>

                <!-- REGISTER -->
                <p class="register-prompt">
                    Belum punya akun? <a href="register.php" class="register-link">Daftar sekarang!</a>
                </p>
            </div>

            <footer class="panel-footer">
                Satu langkah kecil untuk masa depan yang lebih besar.
            </footer>
        </section>

    </main>

    <!-- DIALOG LUPA PASSWORD -->
    <dialog class="info-dialog" id="info-akun">
        <h2 id="judul-info"></h2>
        <p id="isi-info"></p>
        <form method="dialog">
            <button class="submit-button" type="submit">Kembali ke login</button>
        </form>
    </dialog>

    <p class="copyright">
        © <?= date('Y') ?> Ruang Tumbuh. All rights reserved.
    </p>

</body>

</html>