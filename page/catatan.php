<?php
session_start();
header('Cache-Control: no-store');

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['catatan'])) {
    $_SESSION['catatan'] = [
        [
            'id' => 1,
            'judul' => 'Persiapan Bootcamp UI/UX',
            'kategori' => 'Bootcamp',
            'isi' => 'Pelajari kembali design system, user flow, dan prototyping sebelum bootcamp dimulai.',
            'tanggal' => '01 Oktober 2026'
        ],
        [
            'id' => 2,
            'judul' => 'Target Beasiswa',
            'kategori' => 'Beasiswa',
            'isi' => 'Lengkapi CV, sertifikat, dan dokumen pendukung sebelum tanggal pendaftaran.',
            'tanggal' => '30 September 2026'
        ],
        [
            'id' => 3,
            'judul' => 'Rencana Pengembangan Skill',
            'kategori' => 'Pribadi',
            'isi' => 'Fokus belajar PHP, database, UI/UX, dan pengembangan project portfolio.',
            'tanggal' => '28 September 2026'
        ]
    ];
}

function aman($teks)
{
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $judul = trim($_POST['judul'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $isi = trim($_POST['isi'] ?? '');

    if ($judul !== '' && $isi !== '') {
        if ($id !== '') {
            foreach ($_SESSION['catatan'] as &$catatan) {
                if ($catatan['id'] == $id) {
                    $catatan['judul'] = $judul;
                    $catatan['kategori'] = $kategori;
                    $catatan['isi'] = $isi;
                    $catatan['tanggal'] = date('d F Y');
                    break;
                }
            }
            unset($catatan);
        } else {
            $_SESSION['catatan'][] = [
                'id' => time(),
                'judul' => $judul,
                'kategori' => $kategori,
                'isi' => $isi,
                'tanggal' => date('d F Y')
            ];
        }
    }

    header('Location: catatan.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $idHapus = $_GET['hapus'];

    foreach ($_SESSION['catatan'] as $index => $catatan) {
        if ($catatan['id'] == $idHapus) {
            unset($_SESSION['catatan'][$index]);
            $_SESSION['catatan'] = array_values($_SESSION['catatan']);
            break;
        }
    }

    header('Location: catatan.php');
    exit;
}

$editCatatan = null;

if (isset($_GET['edit'])) {
    $idEdit = $_GET['edit'];

    foreach ($_SESSION['catatan'] as $catatan) {
        if ($catatan['id'] == $idEdit) {
            $editCatatan = $catatan;
            break;
        }
    }
}

$keyword = trim($_GET['cari'] ?? '');
$catatanTampil = $_SESSION['catatan'];

if ($keyword !== '') {
    $catatanTampil = array_filter($_SESSION['catatan'], function ($catatan) use ($keyword) {
        return stripos($catatan['judul'], $keyword) !== false ||
               stripos($catatan['isi'], $keyword) !== false;
    });
}

$username = $_SESSION['username'] ?? 'Talenta Muda';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Pribadi | RuangTumbuh</title>
    <link rel="stylesheet" href="../css/catatan.css">
</head>
<body>

<div class="app">
    <aside class="sidebar">
        <a href="dashboard.php" class="logo">
            <span class="logo-dark">Ruang</span><span class="logo-green">Tumbuh</span>
        </a>

        <div class="sidebar-section">
            <p class="sidebar-title">MENU UTAMA</p>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <span class="menu-icon">▦</span>
                    <span>Dashboard</span>
                </a>

                <a href="katalog.php" class="menu-item">
                    <span class="menu-icon">◇</span>
                    <span>Katalog Bootcamp</span>
                </a>

                <a href="kategori.php" class="menu-item">
                    <span class="menu-icon">▦</span>
                    <span>Kategori Program</span>
                </a>

                <a href="bookmark.php" class="menu-item">
                    <span class="menu-icon">♧</span>
                    <span>Bookmark & Tersimpan</span>
                </a>

                <a href="pengingat.php" class="menu-item">
                    <span class="menu-icon">◷</span>
                    <span>Pengingat Tenggat</span>
                </a>

                <a href="catatan.php" class="menu-item active">
                    <span class="menu-icon">≡</span>
                    <span>Catatan Pribadi</span>
                </a>

                <a href="rating.php" class="menu-item">
                    <span class="menu-icon">☆</span>
                    <span>Ulasan & Rating</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-section account-section">
            <p class="sidebar-title">AKUN</p>

            <nav class="sidebar-menu">
                <a href="profile.php" class="menu-item">
                    <span class="menu-icon">♙</span>
                    <span>Profil Pengguna</span>
                </a>

                <a href="pengaturan.php" class="menu-item">
                    <span class="menu-icon">⚙</span>
                    <span>Pengaturan</span>
                </a>
            </nav>
        </div>
    </aside>

    <div class="main-area">
        <header class="navbar">
            <form method="GET" class="search-box">
                <span class="search-icon">⌕</span>
                <input type="text" name="cari" placeholder="Cari catatan pribadi..." value="<?= aman($keyword); ?>">
            </form>

            <div class="navbar-right">
                <a href="#form-catatan" class="save-button">
                    <span>+</span>
                    Tambah Catatan
                </a>

                <a href="profile.php" class="profile-navbar">
                    <div class="avatar">TM</div>

                    <div class="profile-text">
                        <strong><?= aman($username); ?></strong>
                        <span>Explorer</span>
                    </div>
                </a>
            </div>
        </header>

        <main class="content">
            <section class="page-header">
                <div>
                    <div class="header-label">
                        <span class="label-dot"></span>
                        Ruang Catatan Personal
                    </div>

                    <h1>Catatan Pribadi</h1>

                    <p>
                        Simpan ide, rencana, target, dan hal penting dalam perjalanan pengembangan dirimu.
                    </p>
                </div>

                <div class="header-decoration">
                    <div class="note-icon">✎</div>
                </div>
            </section>

            <section class="statistics">
                <div class="stat-card">
                    <div class="stat-top">
                        <span>Total Catatan</span>
                        <div class="stat-icon">≡</div>
                    </div>

                    <strong><?= count($_SESSION['catatan']); ?></strong>
                    <small>Catatan tersimpan</small>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Catatan Terbaru</span>
                        <div class="stat-icon">◷</div>
                    </div>

                    <strong>Hari Ini</strong>
                    <small>Terus catat perkembanganmu</small>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Ruang Ide</span>
                        <div class="stat-icon">✦</div>
                    </div>

                    <strong>Aktif</strong>
                    <small>Jangan biarkan ide terlupakan</small>
                </div>
            </section>

            <section class="note-form-card" id="form-catatan">
                <div class="section-heading">
                    <div>
                        <h2><?= $editCatatan ? 'Edit Catatan' : 'Buat Catatan Baru'; ?></h2>
                        <p>Tuliskan hal penting yang ingin kamu simpan.</p>
                    </div>
                </div>

                <form method="POST" class="note-form">
                    <input type="hidden" name="id" value="<?= aman($editCatatan['id'] ?? ''); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Judul Catatan</label>
                            <input
                                type="text"
                                name="judul"
                                placeholder="Contoh: Persiapan Beasiswa"
                                value="<?= aman($editCatatan['judul'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="form-group category-group">
                            <label>Kategori</label>

                            <select name="kategori">
                                <?php
                                $kategoriAktif = $editCatatan['kategori'] ?? '';

                                $daftarKategori = [
                                    'Pribadi',
                                    'Beasiswa',
                                    'Bootcamp',
                                    'Magang',
                                    'Lomba',
                                    'Karier'
                                ];

                                foreach ($daftarKategori as $kategori):
                                ?>
                                    <option
                                        value="<?= aman($kategori); ?>"
                                        <?= $kategoriAktif === $kategori ? 'selected' : ''; ?>
                                    >
                                        <?= aman($kategori); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Isi Catatan</label>

                        <textarea
                            name="isi"
                            rows="5"
                            placeholder="Tuliskan catatanmu di sini..."
                            required
                        ><?= aman($editCatatan['isi'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <?php if ($editCatatan): ?>
                            <a href="catatan.php" class="button-cancel">Batal</a>
                        <?php endif; ?>

                        <button type="submit" class="button-save">
                            <?= $editCatatan ? 'Simpan Perubahan' : '+ Simpan Catatan'; ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="notes-section">
                <div class="section-heading">
                    <div>
                        <h2>Catatan Saya</h2>
                        <p>Semua catatan yang sudah kamu simpan.</p>
                    </div>

                    <span class="note-count"><?= count($catatanTampil); ?> Catatan</span>
                </div>

                <?php if (count($catatanTampil) > 0): ?>
                    <div class="notes-grid">
                        <?php foreach ($catatanTampil as $catatan): ?>
                            <article class="note-card">
                                <div class="note-card-header">
                                    <span class="category-badge">
                                        <?= aman($catatan['kategori']); ?>
                                    </span>

                                    <div class="note-options">
                                        <a
                                            href="catatan.php?edit=<?= aman($catatan['id']); ?>"
                                            class="edit-button"
                                            title="Edit"
                                        >
                                            ✎
                                        </a>

                                        <a
                                            href="catatan.php?hapus=<?= aman($catatan['id']); ?>"
                                            class="delete-button"
                                            title="Hapus"
                                            onclick="return confirm('Yakin ingin menghapus catatan ini?')"
                                        >
                                            ×
                                        </a>
                                    </div>
                                </div>

                                <h3><?= aman($catatan['judul']); ?></h3>

                                <p class="note-content">
                                    <?= nl2br(aman($catatan['isi'])); ?>
                                </p>

                                <div class="note-footer">
                                    <span>◷</span>
                                    <?= aman($catatan['tanggal']); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">✎</div>

                        <h3>Catatan tidak ditemukan</h3>

                        <p>
                            <?php if ($keyword !== ''): ?>
                                Tidak ada catatan dengan kata kunci
                                "<strong><?= aman($keyword); ?></strong>".
                            <?php else: ?>
                                Kamu belum mempunyai catatan pribadi.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

</body>
</html>