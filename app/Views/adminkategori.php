<?php
// View ini hanya boleh dibuka lewat KategoriController (index.php?page=kategori)
if (!defined('APP_KATEGORI')) {
    header('Location: ../../index.php');
    exit;
}

$base = $base ?? '';
$csrf = $csrf ?? '';

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$kategori_list = $kategori_list ?? [];
$error  = $error ?? '';
$sukses = $sukses ?? '';
$old    = $old ?? [];
$editData = $editData ?? null;
$nama_user = $_SESSION['username'] ?? 'Admin';
$totalKategori = count($kategori_list);
$modeEdit = !empty($editData);

// Nilai awal form: data edit, atau input lama kalau validasi gagal
$nilaiNama = $old['nama'] ?? ($editData['nama_kategori'] ?? '');
$nilaiDeskripsi = $old['deskripsi'] ?? ($editData['deskripsi'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Kelola Kategori</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= aman($base); ?>/public/css/rating.css">
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>
        <div class="nav-group first">
            <div class="nav-title">MENU ADMIN</div>
            <ul class="nav-menu">
                <li><a href="<?= aman($base); ?>/app/Views/admindashboard.php" class="nav-link">
                    <i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="<?= aman($base); ?>/app/Views/adminkatalog.php" class="nav-link">
                    <i class="fa-solid fa-shapes"></i> Katalog Bootcamp</a></li>
                <li><a href="<?= aman($base); ?>/index.php?page=kategori" class="nav-link active">
                    <i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
                <li><a href="<?= aman($base); ?>/index.php?page=rating" class="nav-link">
                    <i class="fa-solid fa-star"></i> Rating</a></li>
            </ul>
        </div>
    </div>
    <div class="sidebar-bottom">
        <a href="<?= aman($base); ?>/app/Views/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>

<div class="main-area">
    <header class="header">
        <div class="profile-section">
            <div class="profile-info">
                <div class="profile-name"><?= aman($nama_user); ?></div>
                <div class="profile-role">Administrator</div>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff"
                 class="avatar" alt="Avatar Admin">
        </div>
    </header>

    <main class="page">

        <section class="banner">
            <div>
                <span class="badge-tag"><i class="fa-solid fa-layer-group"></i> MANAJEMEN PROGRAM</span>
                <h1>Kelola Kategori Program</h1>
                <p>Atur kategori program untuk membantu pengguna menemukan bootcamp, beasiswa,
                   dan peluang pengembangan diri sesuai bidang minat mereka.</p>
            </div>
            <div class="banner-stats">
                <div class="stat-pill">
                    <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div>
                        <div class="stat-text"><?= $totalKategori; ?></div>
                        <small>Total Kategori</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pesan dari server -->
        <?php if ($error !== ''): ?>
            <div class="card"><p style="color:#DC2626;margin:0;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= aman($error); ?></p></div>
        <?php endif; ?>
        <?php if ($sukses !== ''): ?>
            <div class="card"><p style="color:#047857;margin:0;">
                <i class="fa-solid fa-circle-check"></i> <?= aman($sukses); ?></p></div>
        <?php endif; ?>

        <!-- Form tambah / ubah -->
        <section class="card form-card">
            <div class="form-head">
                <h2>
                    <i class="fa-solid <?= $modeEdit ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>
                    <?= $modeEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru'; ?>
                </h2>
                <p class="muted">Kategori dipakai untuk mengelompokkan program di katalog RuangTumbuh.</p>
            </div>

            <!-- Pesan validasi client (merah, singkat) -->
            <p id="pesanClient" style="display:none;color:#DC2626;margin:0 0 12px;"></p>

            <form id="formKategori" method="POST" novalidate
                  action="<?= aman($base); ?>/index.php?page=kategori&aksi=<?= $modeEdit ? 'ubah' : 'simpan'; ?>">

                <input type="hidden" name="csrf" value="<?= aman($csrf); ?>">
                <?php if ($modeEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) ($editData['id'] ?? 0); ?>">
                <?php endif; ?>

                <label for="kategoriNama">Nama Kategori</label>
                <input type="text" name="nama" id="kategoriNama" maxlength="100"
                       placeholder="Contoh: Teknologi, Design, Data"
                       value="<?= aman($nilaiNama); ?>">

                <label for="deskripsi">Deskripsi Kategori</label>
                <textarea name="deskripsi" id="deskripsi" rows="4" maxlength="500"
                          placeholder="Jelaskan bidang dan jenis program dalam kategori ini..."><?= aman($nilaiDeskripsi); ?></textarea>

                <div class="actions">
                    <?php if ($modeEdit): ?>
                        <a class="button secondary" href="<?= aman($base); ?>/index.php?page=kategori">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="button secondary">Reset</button>
                    <?php endif; ?>
                    <button type="submit" class="button">
                        <i class="fa-solid fa-save"></i>
                        <?= $modeEdit ? 'Simpan Perubahan' : 'Simpan Kategori'; ?>
                    </button>
                </div>
            </form>
        </section>

        <!-- Daftar kategori -->
        <section class="card">
            <div class="list-heading">
                <div>
                    <h2>Daftar Kategori Program</h2>
                    <p class="muted">Kategori yang tersedia untuk mengelompokkan program.</p>
                </div>
                <span class="count-pill"><i class="fa-solid fa-layer-group"></i>
                    <b><?= $totalKategori; ?></b> Kategori</span>
            </div>

            <div style="overflow-x:auto;margin-top:20px;">
                <table style="width:100%;border-collapse:collapse;text-align:left;">
                    <thead>
                        <tr style="background:#F9FAFB;">
                            <th style="padding:14px;">No.</th>
                            <th style="padding:14px;">Nama Kategori</th>
                            <th style="padding:14px;">Deskripsi</th>
                            <th style="padding:14px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($kategori_list)): ?>
                        <tr><td colspan="4" style="padding:30px;text-align:center;color:#9CA3AF;">
                            Belum ada kategori program.</td></tr>
                    <?php else: foreach ($kategori_list as $i => $kat): ?>
                        <tr style="border-bottom:1px solid #F3F4F6;">
                            <td style="padding:14px;"><?= $i + 1; ?></td>
                            <td style="padding:14px;font-weight:600;">
                                <i class="fa-solid fa-folder" style="color:#10B981;margin-right:8px;"></i>
                                <?= aman($kat['nama_kategori']); ?>
                            </td>
                            <td style="padding:14px;color:#6B7280;"><?= aman($kat['deskripsi'] ?: '-'); ?></td>
                            <td style="padding:14px;white-space:nowrap;">
                                <a class="button secondary"
                                   href="<?= aman($base); ?>/index.php?page=kategori&aksi=edit&id=<?= (int) $kat['id']; ?>">
                                    <i class="fa-solid fa-pen"></i> Ubah</a>

                                <form method="POST" class="form-hapus" style="display:inline;"
                                      data-nama="<?= aman($kat['nama_kategori']); ?>"
                                      action="<?= aman($base); ?>/index.php?page=kategori&aksi=hapus">
                                    <input type="hidden" name="csrf" value="<?= aman($csrf); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $kat['id']; ?>">
                                    <button type="submit" class="button"
                                            style="background:#DC2626;">
                                        <i class="fa-solid fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script src="<?= aman($base); ?>/public/js/adminkategori.js"></script>
</body>
</html>
