<?php
// Halaman Profil. Alur: view -> ProfileController -> model Profile -> database.

// 1. Wajib login (sebelum menyentuh database)
require_once __DIR__ . '/../Controllers/ProfileController.php';
$userId = ProfileController::pastikanLogin();

// 2. Koneksi database kelompok (output kosong dari file koneksi dibuang)
ob_start();
require_once __DIR__ . '/../../config/koneksimvc.php';
ob_end_clean();

// 3. Model dan controller
require_once __DIR__ . '/../Models/Profile.php';

$controller = new ProfileController(new Profile($conn));
extract($controller->handle($userId));

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | Ruang Tumbuh</title>
    <link rel="stylesheet" href="../../public/css/profile.css?v=<?= (int) @filemtime(__DIR__ . '/../../public/css/profile.css') ?>">
</head>

<body>
    <div class="app-shell">

        <div class="app-content">
            <main>
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span aria-hidden="true">/</span>
                    <span>Profil Saya</span>
                </div>

                <div class="page-heading">
                    <div>
                        <p class="eyebrow">RUANG UNTUK MENGENAL DIRIMU</p>
                        <h1>Profil Saya<span>.</span></h1>
                        <p>Kenali potensimu, tentukan minatmu, dan mulai langkah berikutnya.</p>
                    </div>
                    <span class="heading-label">Tumbuh dengan caramu ✦</span>
                </div>

                <?php if ($sukses !== ''): ?>
                    <div class="notice success" role="status"><?= aman($sukses) ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="notice error" role="alert">
                        <strong>Profil belum disimpan.</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= aman($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <section class="profile-banner" aria-label="Ringkasan profil">
                    <div class="avatar">
                        <?php if ($fotoUrl !== ''): ?>
                            <img src="<?= aman($fotoUrl) ?>"
                                alt="Foto profil <?= aman($profil['nama']) ?>">
                        <?php else: ?>
                            <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                <circle cx="32" cy="22" r="12" />
                                <path d="M9 61v-9a23 23 0 0 1 46 0v9Z" />
                            </svg>
                        <?php endif; ?>
                    </div>

                    <div class="identity">
                        <span class="member">AKUN RUANG TUMBUH</span>
                        <h2><?= aman($profil['nama']) ?></h2>
                        <p>
                            <?= aman($profil['status']) ?>
                            <?= $profil['institusi'] !== '' ? ' · ' . aman($profil['institusi']) : '' ?>
                        </p>
                    </div>

                    <?php if (!$edit): ?>
                        <a class="button yellow" href="profile.php?edit=1#form-profil">
                            Edit Profil <span aria-hidden="true">↗</span>
                        </a>

                        <form method="post" action="profile.php"
                            onsubmit="return confirm('Hapus data profil (telepon, domisili, institusi, bio, minat, dan foto)? Akun dan login kamu tetap ada.');">
                            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf_profil']) ?>">
                            <input type="hidden" name="aksi" value="hapus_profil">
                            <button type="submit" class="button danger">Hapus Profil</button>
                        </form>
                    <?php else: ?>
                        <span class="editing-label">Sedang mengedit profil</span>
                    <?php endif; ?>
                </section>

                <?php if ($edit): ?>
                    <form id="form-profil" method="post" action="profile.php"
                        class="card edit-card" enctype="multipart/form-data">

                        <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf_profil']) ?>">

                        <div class="section-heading">
                            <span class="number">01</span>
                            <div>
                                <h2>Informasi Pribadi</h2>
                                <p>Ceritakan sedikit tentang dirimu. Kolom bertanda * wajib diisi.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="full">
                                <label for="foto">Foto profil</label>
                                <input type="file" id="foto" name="foto"
                                    accept="image/jpeg,image/png,image/webp"
                                    aria-describedby="foto-hint">
                                <p class="hint" id="foto-hint">
                                    JPG, PNG, atau WEBP. Maksimal 2 MB.
                                    Kosongkan jika tidak ingin mengganti foto.
                                </p>
                            </div>

                            <div>
                                <label for="nama">Nama lengkap *</label>
                                <input id="nama" name="nama"
                                    value="<?= aman($profil['nama']) ?>"
                                    maxlength="100" autocomplete="name" required>
                            </div>

                            <div>
                                <label for="email">Alamat email *</label>
                                <input type="email" id="email" name="email"
                                    value="<?= aman($profil['email']) ?>"
                                    maxlength="100" autocomplete="email" required>
                            </div>

                            <div>
                                <label for="telepon">Nomor handphone</label>
                                <input type="tel" id="telepon" name="telepon"
                                    value="<?= aman($profil['telepon']) ?>"
                                    maxlength="20" autocomplete="tel"
                                    placeholder="Contoh: 081234567890">
                            </div>

                            <div>
                                <label for="domisili">Domisili</label>
                                <input id="domisili" name="domisili"
                                    value="<?= aman($profil['domisili']) ?>"
                                    maxlength="120" autocomplete="address-level2"
                                    placeholder="Contoh: Malang, Jawa Timur">
                            </div>

                            <div>
                                <label for="status">Status saat ini</label>
                                <select id="status" name="status">
                                    <?php foreach ($pilihanStatus as $status): ?>
                                        <option value="<?= aman($status) ?>"
                                            <?= $profil['status'] === $status ? 'selected' : '' ?>>
                                            <?= aman($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="institusi">Kampus / sekolah / instansi</label>
                                <input id="institusi" name="institusi"
                                    value="<?= aman($profil['institusi']) ?>"
                                    maxlength="150" autocomplete="organization"
                                    placeholder="Contoh: Universitas Brawijaya">
                            </div>

                            <div class="full">
                                <label for="bio">Tentang saya</label>
                                <textarea id="bio" name="bio" rows="4" maxlength="500"
                                    placeholder="Ceritakan kesibukan, ketertarikan, atau tujuan yang ingin kamu capai."><?= aman($profil['bio']) ?></textarea>
                                <p class="hint">Maksimal 500 karakter.</p>
                            </div>
                        </div>

                        <div class="section-heading interest-heading">
                            <span class="number">02</span>
                            <div>
                                <h2>Minat & Peluang</h2>
                                <p>Boleh pilih lebih dari satu. Hapus centang untuk menghapus minat.</p>
                            </div>
                        </div>

                        <fieldset>
                            <legend>Bidang yang diminati</legend>
                            <div class="choices">
                                <?php foreach ($pilihanBidang as $i => $bidang): ?>
                                    <label class="choice" for="bidang-<?= $i ?>">
                                        <input type="checkbox" id="bidang-<?= $i ?>"
                                            name="bidang[]" value="<?= aman($bidang) ?>"
                                            <?= in_array($bidang, $profil['bidang'], true) ? 'checked' : '' ?>>
                                        <span><?= aman($bidang) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Kategori peluang yang dicari</legend>
                            <div class="choices">
                                <?php foreach ($pilihanKategori as $i => $kategori): ?>
                                    <label class="choice" for="kategori-<?= $i ?>">
                                        <input type="checkbox" id="kategori-<?= $i ?>"
                                            name="kategori[]" value="<?= aman($kategori) ?>"
                                            <?= in_array($kategori, $profil['kategori'], true) ? 'checked' : '' ?>>
                                        <span><?= aman($kategori) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <div class="form-footer">
                            <p>Sesuaikan minatmu kapan saja.</p>
                            <div class="actions">
                                <a class="button secondary" href="profile.php">Batal</a>
                                <button class="button primary" type="submit">
                                    Simpan Perubahan <span aria-hidden="true">✓</span>
                                </button>
                            </div>
                        </div>
                    </form>

                <?php else: ?>
                    <div class="profile-grid">
                        <aside class="left-column">
                            <section class="card">
                                <div class="section-heading">
                                    <span class="section-icon" aria-hidden="true">◉</span>
                                    <h2>Informasi Pribadi & Kontak</h2>
                                </div>

                                <dl class="contact-list">
                                    <?php foreach ([
                                        'email' => 'Alamat email',
                                        'telepon' => 'Nomor handphone',
                                        'domisili' => 'Domisili',
                                        'institusi' => 'Kampus / sekolah / instansi'
                                    ] as $kolom => $label): ?>
                                        <div>
                                            <dt><?= aman($label) ?></dt>
                                            <dd class="<?= $profil[$kolom] === '' ? 'empty' : '' ?>">
                                                <?= aman($profil[$kolom] !== '' ? $profil[$kolom] : 'Belum diisi') ?>
                                            </dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            </section>

                            <section class="growth-card">
                                <div class="progress-heading">
                                    <h2>Kelengkapan profil</h2>
                                    <strong><?= (int) $kelengkapan ?>%</strong>
                                </div>
                                <progress value="<?= (int) $kelengkapan ?>" max="100"
                                    aria-label="Kelengkapan profil"><?= (int) $kelengkapan ?>%</progress>

                                <?php if ($belum): ?>
                                    <p>Belum dilengkapi: <?= aman(implode(', ', $belum)) ?>.</p>
                                    <a href="profile.php?edit=1#form-profil">
                                        Lengkapi profil <span aria-hidden="true">→</span>
                                    </a>
                                <?php else: ?>
                                    <p>Profilmu sudah lengkap. Keren!</p>
                                <?php endif; ?>
                            </section>
                        </aside>

                        <div class="right-column">
                            <section class="card">
                                <div class="section-heading">
                                    <span class="section-icon" aria-hidden="true">✎</span>
                                    <h2>Tentang Saya</h2>
                                    <span class="mini-label">BIO PROFIL</span>
                                </div>
                                <p class="bio <?= $profil['bio'] === '' ? 'empty' : '' ?>">
                                    <?= $profil['bio'] !== ''
                                        ? nl2br(aman($profil['bio']))
                                        : 'Belum ada cerita tentangmu. Tambahkan perkenalan singkat, aktivitas, dan tujuan yang ingin kamu capai.' ?>
                                </p>
                            </section>

                            <section class="card">
                                <div class="section-heading">
                                    <span class="section-icon" aria-hidden="true">✳</span>
                                    <h2>Minat & Peluang</h2>
                                    <a class="small-link" href="profile.php?edit=1#bidang-0">
                                        Ubah minat ↗
                                    </a>
                                </div>

                                <p class="section-description">
                                    Pilihan yang mencerminkan arah pertumbuhanmu.
                                </p>

                                <h3>Bidang yang diminati</h3>
                                <div class="tags">
                                    <?php if (!$profil['bidang']): ?>
                                        <p class="empty">Kamu belum memilih bidang minat.</p>
                                    <?php endif; ?>

                                    <?php foreach ($profil['bidang'] as $bidang): ?>
                                        <span class="tag green"><?= aman($bidang) ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="divider"></div>

                                <h3>Kategori peluang yang dicari</h3>
                                <div class="tags">
                                    <?php if (!$profil['kategori']): ?>
                                        <p class="empty">
                                            Pilih lowongan kerja, magang, bootcamp, lomba, atau beasiswa.
                                        </p>
                                    <?php endif; ?>

                                    <?php foreach ($profil['kategori'] as $kategori): ?>
                                        <span class="tag gold"><?= aman($kategori) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <div class="quote">
                                <span aria-hidden="true">✦</span>
                                <p>
                                    Setiap orang punya jalannya sendiri.<br>
                                    <strong>Ini ruangmu untuk terus bertumbuh.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <footer class="footer">
                    © <?= date('Y') ?> Ruang Tumbuh
                    <span>Satu langkah kecil untuk masa depan yang lebih besar.</span>
                </footer>
            </main>
        </div>
    </div>
</body>

</html>
