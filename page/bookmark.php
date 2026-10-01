<?php
session_start();

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$username = $_SESSION['username'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Data awal bookmark tersimpan (4 Item)
if (!isset($_SESSION['bookmarks']) || count($_SESSION['bookmarks']) < 4) {
    $_SESSION['bookmarks'] = [
        [
            'id' => '1',
            'judul' => 'Beasiswa Akselerasi Talent Digital 2026',
            'kategori' => 'Beasiswa',
            'penyelenggara' => 'Kementerian Kominfo',
            'deadline' => '2026-Oktober-20',
            'status' => 'Aktif',
            'deskripsi' => 'Program beasiswa penuh untuk pelatihan bidang teknologi digital dan sertifikasi internasional.',
            'link' => 'https://digital.kominfo.go.id'
        ],
        [
            'id' => '2',
            'judul' => 'Frontend Web Developer Internship Program',
            'kategori' => 'Magang',
            'penyelenggara' => 'Tech Corp Indonesia',
            'deadline' => '2026-Oktober-30',
            'status' => 'Aktif',
            'deskripsi' => 'Program magang 6 bulan fokus pada pengembangan web modern menggunakan ReactJS dan pemanfaatan REST API.',
            'link' => 'https://techcorp.id/careers'
        ],
        [
            'id' => '3',
            'judul' => 'UI/UX Design Intensive Bootcamp 2026',
            'kategori' => 'Bootcamp',
            'penyelenggara' => 'RuangTumbuh Academy',
            'deadline' => '2026-Oktober-15',
            'status' => 'Mendekati Tenggat',
            'deskripsi' => 'Pelatihan intensif UI/UX design mencakup Design Thinking, Wireframing, Prototyping di Figma, hingga Usability Testing.',
            'link' => 'https://ruangtumbuh.id/bootcamp'
        ],
        [
            'id' => '4',
            'judul' => 'National Cyber Security Hackathon',
            'kategori' => 'Lomba',
            'penyelenggara' => 'Badan Siber Indonesia',
            'deadline' => '2026-November-05',
            'status' => 'Aktif',
            'deskripsi' => 'Kompetisi peretasan etis (Capture The Flag) tingkat nasional untuk menguji kemampuan analisis keamanan jaringan dan web.',
            'link' => 'https://bssn.go.id/hackathon'
        ]
    ];
}

$pesan = $_SESSION['pesan_bookmark'] ?? '';
unset($_SESSION['pesan_bookmark']);

// Proses Operasi Hapus Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $token = $_POST['csrf'] ?? '';

    if (hash_equals($_SESSION['csrf'], $token) && $aksi === 'hapus') {
        $id = $_POST['id'] ?? '';
        $_SESSION['bookmarks'] = array_values(array_filter($_SESSION['bookmarks'], function ($item) use ($id) {
            return $item['id'] !== $id;
        }));
        $_SESSION['pesan_bookmark'] = 'Bookmark berhasil dihapus dari daftar simpanan.';
    }
    header('Location: bookmark.php');
    exit;
}

$daftarBookmark = $_SESSION['bookmarks'];
$totalTersimpan = count($daftarBookmark);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Bookmark & Tersimpan</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/bookmark.css">
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="logo-text">
                Ruang<span>Tumbuh</span>
            </div>

            <div class="nav-group" style="margin-top: 32px;">
                <div class="nav-title">MENU UTAMA</div>

                <ul class="nav-menu">
                    <li>
                        <a href="dashboard.php" class="nav-link">
                            <i class="fa-solid fa-table-cells-large"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="katalog.php" class="nav-link">
                            <i class="fa-solid fa-shapes"></i> Katalog Bootcamp
                        </a>
                    </li>
                    <li>
                        <a href="kategori.php" class="nav-link">
                            <i class="fa-solid fa-layer-group"></i> Kategori Program
                        </a>
                    </li>
                    <li>
                        <a href="bookmark.php" class="nav-link active">
                            <i class="fa-regular fa-bookmark"></i> Bookmark
                        </a>
                    </li>
                    <li>
                        <a href="pengingat.php" class="nav-link">
                            <i class="fa-regular fa-clock"></i> Pengingat Tenggat
                        </a>
                    </li>
                    <li>
                    <a href="catatan.php" class="nav-link">
                        <i class="fa-regular fa-pen-to-square"></i>
                        Catatan Pribadi
                    </a>
                    </li>
                    <li>
                        <a href="rating.php" class="nav-link">
                            <i class="fa-regular fa-star"></i> Ulasan & Rating
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        
        <header class="header">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="search-input" id="searchBookmark" placeholder="Cari dalam bookmark tersimpan...">
            </div>

            <div class="header-actions">
                <div class="profile-section">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($username); ?>&background=10B981&color=fff" alt="Avatar" class="profile-avatar">
                </div>
            </div>
        </header>

        <main class="dashboard-container">
            <!-- BANNER UTAMA -->
            <section class="welcome-banner">
                <div class="banner-content-wrapper">
                    <div>
                        <span class="badge-tag">• Arsip Peluang Pengembangan Diri</span>
                        <h1 class="banner-title">Bookmark & Program Tersimpan</h1>
                        <p class="banner-subtitle">Simpan dan kelola seluruh peluang magang, beasiswa, dan bootcamp favoritmu di satu tempat.</p>
                    </div>

                    <div class="counter-box-main">
                        <div class="counter-icon">
                            <i class="fa-solid fa-bookmark"></i>
                        </div>
                        <div class="counter-info">
                            <span class="counter-number"><?= $totalTersimpan ?> Tersimpan</span>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($pesan): ?>
                <div class="alert-message success">
                    <i class="fa-solid fa-circle-check"></i> <?= aman($pesan) ?>
                </div>
            <?php endif; ?>

            <!-- GRID KARTU BOOKMARK (2 KOLOM) -->
            <div class="bookmark-grid" id="bookmarkGrid">
                <?php if (empty($daftarBookmark)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-bookmark"></i>
                        <h3>Belum ada bookmark tersimpan</h3>
                        <p>Simpan peluang favoritmu melalui katalog program.</p>
                        <a href="dashboard.php" class="empty-state-action">
                            <i class="fa-solid fa-magnifying-glass"></i> Cari Program Sekarang
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftarBookmark as $item): ?>
                        <div class="bookmark-card clickable-card" 
                            data-kategori="<?= aman($item['kategori']) ?>" 
                            data-deadline="<?= aman($item['deadline']) ?>"
                            onclick="openDetailModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">
                            
                            <div class="card-header-tag">
                                <span class="kategori-tag"><?= aman($item['kategori']) ?></span>
                                <span class="status-tag <?= $item['status'] === 'Mendekati Tenggat' ? 'warning' : 'active' ?>">
                                    <span class="badge-status-dot"></span> <?= aman($item['status']) ?>
                                </span>
                            </div>

                            <h3 class="card-title"><?= aman($item['judul']) ?></h3>
                            <p class="penyelenggara"><i class="fa-regular fa-building"></i> <?= aman($item['penyelenggara']) ?></p>

                            <div class="card-footer">
                                <span class="deadline"><i class="fa-regular fa-calendar"></i> <?= aman($item['deadline']) ?></span>

                                <div class="card-actions">
                                    <button type="button" class="btn-action btn-detail">
                                        <i class="fa-regular fa-eye"></i> Detail
                                    </button>
                                    
                                    <form method="post" action="bookmark.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus program ini dari bookmark?');" onclick="event.stopPropagation();">
                                        <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="id" value="<?= aman($item['id']) ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <button type="submit" class="btn-action btn-hapus">
                                            <i class="fa-regular fa-trash-can"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL DETAIL (READ ONLY & DIRECT ACTION) -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Detail Program Favorit</h3>
                <button type="button" class="btn-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="detail-body">
                <div class="card-header-tag" style="margin-bottom: 12px;">
                    <span class="kategori-tag" id="detailKategori">-</span>
                    <span class="status-tag active" id="detailStatus">
                        <span class="badge-status-dot"></span> <span id="detailStatusText">-</span>
                    </span>
                </div>
                <h2 id="detailJudul" style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">-</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 16px;">
                    <i class="fa-regular fa-building"></i> Penyelenggara: <strong id="detailPenyelenggara" style="color: #334155;">-</strong>
                </p>
                
                <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 12px 0;">

                <div class="detail-section">
                    <label style="font-weight: 600; font-size: 13px; color: #475569;">Deskripsi & Persyaratan Program:</label>
                    <p id="detailDeskripsi" style="color: #334155; font-size: 14px; line-height: 1.6; margin-top: 6px;">-</p>
                </div>

                <div class="detail-section" style="margin-top: 16px; font-size: 13px; color: #64748b;">
                    <i class="fa-regular fa-calendar"></i> Batas Tenggat: <strong id="detailDeadline" style="color: #0f172a;">-</strong>
                </div>
            </div>
            
            <div class="modal-footer" style="margin-top: 24px; display: flex; gap: 8px; justify-content: flex-end;">
                <a href="pengingat.php" class="btn-action" style="padding: 8px 12px; background: #f1f5f9; color: #334155; border-radius: 6px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-regular fa-bell"></i> Pasang Pengingat
                </a>
                <a id="detailLink" href="#" target="_blank" class="btn-action" style="padding: 8px 16px; background: #10B981; color: #fff; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Daftar Sekarang
                </a>
            </div>
        </div>
    </div>

    <script src="../js/bookmark.js"></script>
</body>
</html>