<?php

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Nilai default jika variabel belum dikirim Controller
$pesanError = $pesanError ?? '';
$pesanSukses = $pesanSukses ?? '';
$namaLengkap = $namaLengkap ?? '';
$username = $username ?? '';
$email = $email ?? '';

?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | Ruang Tumbuh</title>
    <style>
        /* ==========================================================
   style.css - Halaman Login & Register Ruang Tumbuh
   (CSS dashboard dipisah ke dashboard.css)
   ========================================================== */

        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --hijau: #468b7c;
            --hijau-tua: #357262;
            --hijau-teks: #3f8f7c;
            --oranye: #f2a516;
            --oranye-tua: #e39a12;
            --navy: #141c3a;
            --teks: #1d2433;
            --teks-lembut: #6b7684;
            --garis: #e6e9ec;
            --krem: #fffbee;
            --krem-garis: #f4eed3;
        }

        /* atribut hidden wajib menyembunyikan elemen (termasuk SVG) */
        [hidden] {
            display: none !important;
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px 20px 16px;
            font-family: 'Rubik', 'Segoe UI', Arial, sans-serif;
            color: var(--teks);
            background: #8a7a45;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        /* ---------- Video latar ---------- */
        .background-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .video-overlay {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: rgba(58, 50, 20, 0.52);
        }

        /* ---------- Kartu utama ---------- */
        .login-card {
            width: min(1180px, 100%);
            min-height: 700px;
            display: grid;
            grid-template-columns: 42% 58%;
            overflow: hidden;
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.28);
        }

        /* ---------- Panel kiri ---------- */
        .promotion {
            padding: 44px 40px 26px;
            display: flex;
            flex-direction: column;
            background: linear-gradient(160deg, #fff0a0 0%, #ffe67c 52%, #f8c64c 100%);
        }

        .badge {
            width: fit-content;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.88);
            border-radius: 100px;
            color: #2f7f6d;
            font-size: 11px;
            font-weight: 500;
        }

        .promotion h1 {
            margin-top: 40px;
            margin-bottom: 16px;
            color: var(--navy);
            font-size: 40px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -1px;
        }

        .promotion h1 span {
            color: var(--hijau-teks);
        }

        .promotion>p {
            max-width: 380px;
            color: #3b3a2c;
            font-size: 15px;
            line-height: 1.65;
        }

        .illustration {
            display: block;
            width: 100%;
            max-width: 330px;
            height: auto;
            margin: auto;
            padding-top: 24px;
        }

        .benefits {
            margin-top: 10px;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            color: #2e2d22;
            font-size: 11px;
        }

        /* ---------- Panel kanan ---------- */
        .login-panel {
            padding: 40px 34px 22px;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .brand {
            padding-left: 2px;
            color: var(--navy);
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand span {
            color: var(--hijau-teks);
        }

        /* Kartu krem berisi form */
        .form-area {
            margin-top: 18px;
            padding: 30px 34px 26px;
            background: var(--krem);
            border: 2px solid var(--krem-garis);
            border-radius: 20px;
            box-shadow: 0 6px 18px rgba(176, 148, 40, 0.08);
        }

        .form-area h2 {
            margin-bottom: 10px;
            color: var(--oranye);
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.8px;
        }

        .subtitle {
            margin-bottom: 24px;
            color: var(--teks-lembut);
            font-size: 12px;
        }

        /* ---------- Pesan ---------- */
        .alert {
            margin-bottom: 18px;
            padding: 11px 14px;
            background: #fdeceb;
            border: 1px solid #f3c5c0;
            border-radius: 10px;
            color: #a3372c;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ---------- Form ---------- */
        form label {
            display: block;
            margin-bottom: 8px;
            color: #232a3a;
            font-size: 12px;
            font-weight: 500;
        }

        form input {
            width: 100%;
            height: 46px;
            margin-bottom: 18px;
            padding: 0 16px;
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: 12px;
            outline: none;
            color: var(--teks);
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(20, 30, 50, 0.03);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        form input::placeholder {
            color: #99a2ad;
        }

        form input:focus {
            border-color: var(--hijau);
            box-shadow: 0 0 0 4px rgba(70, 139, 124, 0.13);
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 52px;
        }

        .toggle-password {
            position: absolute;
            top: 23px;
            right: 12px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            background: none;
            border: none;
            border-radius: 8px;
            color: #66707d;
            cursor: pointer;
        }

        .toggle-password:hover {
            background: #f1f4f5;
            color: var(--hijau-tua);
        }

        .forgot-row {
            margin: -6px 0 18px;
            display: flex;
            justify-content: flex-end;
        }

        .text-button {
            background: none;
            border: none;
            color: var(--oranye-tua);
            font-size: 11px;
            cursor: pointer;
        }

        .text-button:hover {
            text-decoration: underline;
        }

        .submit-button {
            width: 100%;
            height: 52px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--hijau);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(70, 139, 124, 0.28);
            transition: background 0.2s, transform 0.2s;
        }

        .submit-button:hover {
            background: var(--hijau-tua);
            transform: translateY(-1px);
        }

        .submit-button span {
            font-size: 17px;
        }

        .register-prompt {
            margin-top: 18px;
            text-align: center;
            color: var(--teks-lembut);
            font-size: 12px;
        }

        .register-link {
            color: var(--hijau-teks);
            font-weight: 600;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        .panel-footer {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #edf0f2;
            color: var(--teks-lembut);
            font-size: 11px;
        }

        .login-panel .form-area+.panel-footer {
            margin-top: auto;
        }

        .copyright {
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 12px;
            text-align: center;
        }

        /* ---------- Dialog "Lupa kata sandi" ---------- */
        .info-dialog {
            width: min(420px, 90vw);
            margin: auto;
            padding: 30px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.3);
        }

        .info-dialog::backdrop {
            background: rgba(20, 20, 10, 0.55);
        }

        .info-dialog h2 {
            margin-bottom: 10px;
            color: var(--navy);
            font-size: 20px;
        }

        .info-dialog p {
            margin-bottom: 22px;
            color: var(--teks-lembut);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ---------- Responsif ---------- */
        @media (max-width: 960px) {
            .login-card {
                grid-template-columns: 1fr;
                min-height: 0;
            }

            .promotion {
                padding: 32px 28px 24px;
            }

            .promotion h1 {
                margin-top: 26px;
                font-size: 32px;
            }

            .illustration {
                display: none;
            }

            .benefits {
                margin-top: 26px;
                flex-wrap: wrap;
            }

            .login-panel {
                padding: 30px 24px 22px;
            }

            .panel-footer {
                margin-top: 24px;
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 14px 12px 10px;
            }

            .login-card {
                border-radius: 22px;
            }

            .promotion {
                padding-left: 22px;
                padding-right: 22px;
            }

            .login-panel {
                padding-left: 14px;
                padding-right: 14px;
            }

            .form-area {
                padding: 24px 20px 22px;
            }

            .form-area h2 {
                font-size: 24px;
            }
        }
    </style>
    <script src="../js/script.js" defer></script>
</head>

<script>
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('.toggle-password');
        if (!tombol) return;

        e.stopPropagation(); // cegah script.js ikut menangani klik ini

        const input = document.getElementById(tombol.getAttribute('aria-controls'));
        const garis = tombol.querySelector('.garis-mata');
        const tampil = input.type === 'password';

        input.type = tampil ? 'text' : 'password';
        garis.hidden = !tampil; // password terlihat → garis muncul
        tombol.setAttribute('aria-pressed', tampil);
    }, true);
</script>

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
            <img class="illustration" src="../assets/illustration.png"
                alt="Ilustrasi seseorang menaiki tangga menuju bintang" width="340" height="280">
            <div class="benefits"><span>✓ Akses Informasi</span><span>✓ Peluang Terbuka</span><span>✓ Ruang
                    Bertumbuh</span></div>
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


                <form id="form-register" method="POST" action="<?= $basePath ?>/?page=register">

                    <input type="hidden" name="aksi" value="register">

                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf'] ?? '') ?>">

                    <label for="nama_lengkap">Nama Lengkap</label>

                    <input id="nama_lengkap" name="nama_lengkap" type="text" value="<?= aman($namaLengkap) ?>"
                        placeholder="Masukkan nama lengkap..." required>

                    <label for="username">Username</label>

                    <input id="username" name="username" type="text" value="<?= aman($username) ?>"
                        placeholder="Buat username..." autocomplete="username" required>

                    <label for="email">Email</label>

                    <input id="email" name="email" type="email" value="<?= aman($email) ?>"
                        placeholder="contoh@email.com" autocomplete="email" required>

                    <label for="password">Kata Sandi</label>

                    <div class="password-field">

                        <input id="password" name="password" type="password"
                            placeholder="Buat kata sandi (min. 8 karakter)..." autocomplete="new-password" minlength="8"
                            required>

                        <button class="toggle-password" type="button" aria-controls="password"
                            aria-label="Tampilkan kata sandi">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                                <path class="garis-mata" d="M3 3 21 21" hidden />
                            </svg>
                        </button>

                    </div>

                    <label for="konfirmasi_password">
                        Konfirmasi Kata Sandi
                    </label>

                    <div class="password-field">

                        <input id="konfirmasi_password" name="konfirmasi_password" type="password"
                            placeholder="Ulangi kata sandi..." autocomplete="new-password" required>

                        <button class="toggle-password" type="button" aria-controls="konfirmasi_password"
                            aria-label="Tampilkan konfirmasi kata sandi">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                                <path class="garis-mata" d="M3 3 21 21" hidden />
                            </svg>
                        </button>

                    </div>

                    <button class="submit-button" type="submit">
                        DAFTAR SEKARANG <span>→</span>
                    </button>

                </form>


                <p class="register-prompt">Sudah punya akun? <a href="<?= $basePath ?>/" class="register-link">
                        Masuk di sini
                    </a>
            </div>
            <footer class="panel-footer">Satu langkah kecil untuk masa depan yang lebih besar.</footer>
        </section>
    </main>

    <p class="copyright">© <?= date('Y') ?> Ruang Tumbuh. All rights reserved.</p>
</body>

</html>