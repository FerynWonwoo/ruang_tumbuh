<?php
session_start();
header('Cache-Control: no-store');

// Menggunakan session login dari index.php (versi tanpa database).
if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

function aman($teks)
{
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

function panjang($teks)
{
    return preg_match_all('/./us', $teks);
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$pilihanBidang = ['Teknologi Informasi', 'UI/UX Design', 'Data & Analitik', 'Bisnis & Manajemen', 'Pemasaran Digital', 'Pendidikan', 'Desain & Kreatif'];
$pilihanKategori = ['Lowongan Kerja', 'Magang', 'Bootcamp', 'Lomba', 'Beasiswa'];
$profil = $_SESSION['profil'] ?? [
    'foto' => '',
    'nama' => $_SESSION['username'],
    'email' => '',
    'telepon' => '',
    'domisili' => '',
    'institusi' => '',
    'status' => 'Mahasiswa',
    'bio' => '',
    'bidang' => [],
    'kategori' => []
];
$profil['foto'] = $profil['foto'] ?? '';

$pilihanStatus = ['Mahasiswa', 'Pelajar', 'Fresh Graduate', 'Pencari Kerja', 'Profesional'];
$edit = isset($_GET['edit']) && $_GET['edit'] === '1';
$errors = [];
$sukses = $_SESSION['pesan_profil'] ?? '';
unset($_SESSION['pesan_profil']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit = true;
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        foreach (['nama', 'email', 'telepon', 'domisili', 'institusi', 'status', 'bio'] as $kolom) {
            $profil[$kolom] = is_string($_POST[$kolom] ?? null) ? trim($_POST[$kolom]) : '';
        }
        // Hanya pilihan yang tersedia yang dapat disimpan.
        foreach (['bidang' => $pilihanBidang, 'kategori' => $pilihanKategori] as $kolom => $pilihan) {
            $nilai = $_POST[$kolom] ?? [];
            $profil[$kolom] = [];
            if (!is_array($nilai)) {
                $errors[] = 'Format pilihan minat tidak valid.';
                continue;
            }
            foreach ($nilai as $item) {
                if (!is_string($item) || !in_array($item, $pilihan, true)) {
                    $errors[] = 'Terdapat pilihan minat yang tidak tersedia.';
                } elseif (!in_array($item, $profil[$kolom], true)) {
                    $profil[$kolom][] = $item;
                }
            }
        }
        if ($profil['nama'] === '' || panjang($profil['nama']) > 120) {
            $errors[] = 'Isi nama lengkap, maksimal 120 karakter.';
        }
        if (strlen($profil['email']) > 254 || !filter_var($profil['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Masukkan alamat email yang valid.';
        }
        if ($profil['telepon'] !== '' && !preg_match('/^\+?[0-9 ()-]{8,20}$/', $profil['telepon'])) {
            $errors[] = 'Nomor handphone harus berisi 8–20 karakter berupa angka, spasi, +, tanda kurung, atau tanda hubung.';
        }
        if (!in_array($profil['status'], $pilihanStatus, true)) {
            $errors[] = 'Pilih status yang tersedia.';
        }
        if (panjang($profil['domisili']) > 120 || panjang($profil['institusi']) > 150 || panjang($profil['bio']) > 500) {
            $errors[] = 'Isian terlalu panjang. Singkat domisili, institusi, atau bio kamu.';
        }
        // Upload foto hanya diproses jika data profil sudah valid.
        if (!$errors && isset($_FILES['foto'])) {
            $foto = $_FILES['foto'];

            if (!isset($foto['error']) || !is_int($foto['error'])) {
                $errors[] = 'Format upload foto tidak valid.';
            } elseif ($foto['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($foto['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Foto gagal diunggah. Coba lagi.';
                } elseif (
                    !is_uploaded_file($foto['tmp_name']) ||
                    filesize($foto['tmp_name']) > 2 * 1024 * 1024
                ) {
                    $errors[] = 'Ukuran foto maksimal 2 MB.';
                } else {
                    $info = @getimagesize($foto['tmp_name']);

                    $format = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!$info || !isset($format[$info['mime']])) {
                        $errors[] = 'Gunakan foto berformat JPG, PNG, atau WEBP.';
                    } elseif ($info[0] > 6000 || $info[1] > 6000) {
                        $errors[] = 'Dimensi foto maksimal 6000 × 6000 piksel.';
                    } else {
                        $folder = __DIR__ . '/../uploads/profile/';

                        if (
                            !is_dir($folder) &&
                            !mkdir($folder, 0755, true) &&
                            !is_dir($folder)
                        ) {
                            $errors[] = 'Folder penyimpanan foto tidak dapat dibuat.';
                        } else {
                            $namaFile = bin2hex(random_bytes(16))
                                . '.' . $format[$info['mime']];

                            if (
                                move_uploaded_file(
                                    $foto['tmp_name'],
                                    $folder . $namaFile
                                )
                            ) {
                                $profil['foto'] = '../uploads/profile/' . $namaFile;
                            } else {
                                $errors[] = 'Foto gagal disimpan.';
                            }
                        }
                    }
                }
            }
        }
        if (!$errors) {
            $_SESSION['profil'] = $profil;
            $_SESSION['pesan_profil'] = 'Profil dan minat kamu berhasil disimpan.';
            header('Location: profile.php');
            exit;
        }
    }
}

$terisi = 0;
foreach (['nama', 'email', 'telepon', 'domisili', 'institusi', 'bio', 'bidang', 'kategori'] as $kolom) {
    if (!empty($profil[$kolom]))
        $terisi++;
}
$kelengkapan = (int) round($terisi / 8 * 100);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | Ruang Tumbuh</title>
    <link rel="stylesheet" href="../css/profile.css">
</head>

<body>
    
    <header class="header">
        <div class="header-inner">
            <a class="brand" href="dashboard.php">
                Ruang<span>Tumbuh</span>
            </a>

        </div>
    </header>
    <main>
        <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span aria-hidden="true">/</span><span>Profil Saya</span>
        </div>
        <div class="page-heading">
            <div>
                <p class="eyebrow">RUANG UNTUK MENGENAL DIRIMU</p>
                <h1>Profil Saya<span>.</span></h1>
                <p>Kenali potensimu, tentukan minatmu, dan mulai langkah berikutnya.</p>
            </div><span class="heading-label">Tumbuh dengan caramu ✦</span>
        </div>

        <?php if ($sukses !== ''): ?>
            <div class="notice success" role="status"><?= aman($sukses) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="notice error" role="alert"><strong>Profil belum disimpan.</strong>
                <ul><?php foreach ($errors as $error): ?>
                        <li><?= aman($error) ?></li><?php endforeach; ?>
                </ul>
            </div><?php endif; ?>

        <section class="profile-banner" aria-label="Ringkasan profil">
            <div class="avatar">
    <?php if ($profil['foto'] !== ''): ?>
        <img
            src="<?= aman($profil['foto']) ?>"
            alt="Foto profil <?= aman($profil['nama']) ?>"
        >
    <?php else: ?>
        <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
            <circle cx="32" cy="22" r="12" />
            <path d="M9 61v-9a23 23 0 0 1 46 0v9Z" />
        </svg>
    <?php endif; ?>
</div>
            <div class="identity"><span class="member">AKUN RUANG TUMBUH</span>
                <h2><?= aman($profil['nama']) ?></h2>
                <p><?= aman($profil['status']) ?><?= $profil['institusi'] !== '' ? ' · ' . aman($profil['institusi']) : '' ?>
                </p>
            </div>
            <?php if (!$edit): ?><a class="button yellow" href="profile.php?edit=1#form-profil">Edit Profil <span
                        aria-hidden="true">↗</span></a><?php else: ?><span class="editing-label">Sedang mengedit
                    profil</span><?php endif; ?>
        </section>

        <?php if ($edit): ?>
            <form id="form-profil" method="post" action="profile.php" class="card edit-card" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                <div class="section-heading"><span class="number">01</span>
                    <div>
                        <h2>Informasi Pribadi</h2>
                        <p>Ceritakan sedikit tentang dirimu. Kolom bertanda * wajib diisi.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="full">
                        <label for="foto">Foto profil</label>

                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"
                            aria-describedby="foto-hint">

                        <p class="hint" id="foto-hint">
                            JPG, PNG, atau WEBP. Maksimal 2 MB.
                            Kosongkan jika tidak ingin mengganti foto.
                        </p>
                    </div>
                    <div><label for="nama">Nama lengkap *</label><input id="nama" name="nama"
                            value="<?= aman($profil['nama']) ?>" maxlength="120" autocomplete="name" required></div>
                    <div><label for="email">Alamat email *</label><input type="email" id="email" name="email"
                            value="<?= aman($profil['email']) ?>" maxlength="254" autocomplete="email" required></div>
                    <div><label for="telepon">Nomor handphone</label><input type="tel" id="telepon" name="telepon"
                            value="<?= aman($profil['telepon']) ?>" maxlength="20" autocomplete="tel"
                            placeholder="Contoh: 081234567890"></div>
                    <div><label for="domisili">Domisili</label><input id="domisili" name="domisili"
                            value="<?= aman($profil['domisili']) ?>" maxlength="120" autocomplete="address-level2"
                            placeholder="Contoh: Malang, Jawa Timur"></div>
                    <div><label for="status">Status saat ini</label><select id="status"
                            name="status"><?php foreach ($pilihanStatus as $status): ?>
                                <option value="<?= aman($status) ?>" <?= $profil['status'] === $status ? 'selected' : '' ?>>
                                    <?= aman($status) ?>
                                </option><?php endforeach; ?>
                        </select></div>
                    <div><label for="institusi">Kampus / sekolah / instansi</label><input id="institusi" name="institusi"
                            value="<?= aman($profil['institusi']) ?>" maxlength="150" autocomplete="organization"
                            placeholder="Contoh: Universitas Brawijaya"></div>
                    <div class="full"><label for="bio">Tentang saya</label><textarea id="bio" name="bio" rows="4"
                            maxlength="500"
                            placeholder="Ceritakan kesibukan, ketertarikan, atau tujuan yang ingin kamu capai."><?= aman($profil['bio']) ?></textarea>
                        <p class="hint">Maksimal 500 karakter.</p>
                    </div>
                </div>
                <div class="section-heading interest-heading"><span class="number">02</span>
                    <div>
                        <h2>Minat & Peluang</h2>
                        <p>Boleh pilih lebih dari satu. Hapus centang untuk menghapus minat.</p>
                    </div>
                </div>
                <fieldset>
                    <legend>Bidang yang diminati</legend>
                    <div class="choices"><?php foreach ($pilihanBidang as $i => $bidang): ?><label class="choice"
                                for="bidang-<?= $i ?>"><input type="checkbox" id="bidang-<?= $i ?>" name="bidang[]"
                                    value="<?= aman($bidang) ?>" <?= in_array($bidang, $profil['bidang'], true) ? 'checked' : '' ?>><span><?= aman($bidang) ?></span></label><?php endforeach; ?></div>
                </fieldset>
                <fieldset>
                    <legend>Kategori peluang yang dicari</legend>
                    <div class="choices"><?php foreach ($pilihanKategori as $i => $kategori): ?><label class="choice"
                                for="kategori-<?= $i ?>"><input type="checkbox" id="kategori-<?= $i ?>" name="kategori[]"
                                    value="<?= aman($kategori) ?>" <?= in_array($kategori, $profil['kategori'], true) ? 'checked' : '' ?>><span><?= aman($kategori) ?></span></label><?php endforeach; ?></div>
                </fieldset>
                <div class="form-footer">
                    <p>Sesuaikan minatmu kapan saja.</p>
                    <div class="actions"><a class="button secondary" href="profile.php">Batal</a><button
                            class="button primary" type="submit">Simpan Perubahan <span aria-hidden="true">✓</span></button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="profile-grid">
                <aside class="left-column">
                    <section class="card">
                        <div class="section-heading"><span class="section-icon" aria-hidden="true">◉</span>
                            <h2>Informasi Pribadi & Kontak</h2>
                        </div>
                        <dl class="contact-list">
                            <?php foreach (['email' => 'Alamat email', 'telepon' => 'Nomor handphone', 'domisili' => 'Domisili', 'institusi' => 'Kampus / sekolah / instansi'] as $kolom => $label): ?>
                                <div>
                                    <dt><?= aman($label) ?></dt>
                                    <dd class="<?= $profil[$kolom] === '' ? 'empty' : '' ?>">
                                        <?= aman($profil[$kolom] !== '' ? $profil[$kolom] : 'Belum diisi') ?>
                                    </dd>
                                </div><?php endforeach; ?>
                        </dl>
                    </section>
                    <section class="growth-card">
                        <div class="progress-heading">
                            <h2>Kelengkapan profil</h2><strong><?= $kelengkapan ?>%</strong>
                        </div><progress value="<?= $kelengkapan ?>" max="100"
                            aria-label="Kelengkapan profil"><?= $kelengkapan ?>%</progress>
                        <p>Langkah kecil untuk mengenali potensi dan tujuanmu.</p><a
                            href="profile.php?edit=1#form-profil">Lengkapi profil <span aria-hidden="true">→</span></a>
                    </section>
                </aside>
                <div class="right-column">
                    <section class="card">
                        <div class="section-heading"><span class="section-icon" aria-hidden="true">✎</span>
                            <h2>Tentang Saya</h2><span class="mini-label">BIO PROFIL</span>
                        </div>
                        <p class="bio <?= $profil['bio'] === '' ? 'empty' : '' ?>">
                            <?= $profil['bio'] !== '' ? nl2br(aman($profil['bio'])) : 'Belum ada cerita tentangmu. Tambahkan perkenalan singkat, aktivitas, dan tujuan yang ingin kamu capai.' ?>
                        </p>
                    </section>
                    <section class="card">
                        <div class="section-heading"><span class="section-icon" aria-hidden="true">✳</span>
                            <h2>Minat & Peluang</h2><a class="small-link" href="profile.php?edit=1#bidang-0">Ubah minat
                                ↗</a>
                        </div>
                        <p class="section-description">Pilihan yang mencerminkan arah pertumbuhanmu.</p>
                        <h3>Bidang yang diminati</h3>
                        <div class="tags"><?php if (!$profil['bidang']): ?>
                                <p class="empty">Kamu belum memilih bidang minat.</p>
                            <?php endif; ?>     <?php foreach ($profil['bidang'] as $bidang): ?><span
                                    class="tag green"><?= aman($bidang) ?></span><?php endforeach; ?>
                        </div>
                        <div class="divider"></div>
                        <h3>Kategori peluang yang dicari</h3>
                        <div class="tags"><?php if (!$profil['kategori']): ?>
                                <p class="empty">Pilih lowongan kerja, magang, bootcamp, lomba, atau beasiswa.</p>
                            <?php endif; ?>     <?php foreach ($profil['kategori'] as $kategori): ?><span
                                    class="tag gold"><?= aman($kategori) ?></span><?php endforeach; ?>
                        </div>
                    </section>
                    <div class="quote"><span aria-hidden="true">✦</span>
                        <p>Setiap orang punya jalannya sendiri.<br><strong>Ini ruangmu untuk terus bertumbuh.</strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <footer class="footer">© <?= date('Y') ?> Ruang Tumbuh <span>Satu langkah kecil untuk masa depan yang lebih
                besar.</span></footer>
    </main>
</body>

</html>