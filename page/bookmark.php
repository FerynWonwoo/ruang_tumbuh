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

// Simulasi penyimpanan data bookmark di session
if (!isset($_SESSION['bookmarks'])) {
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

// Contoh fungsi opsional jika nanti menerima data kiriman (tambah bookmark dari halaman lain via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $token = $_POST['csrf'] ?? '';

    if (hash_equals($_SESSION['csrf'], $token)) {
        if ($aksi === 'hapus') {
            $id = $_POST['id'] ?? '';
            $_SESSION['bookmarks'] = array_values(array_filter($_SESSION['bookmarks'], function ($item) use ($id) {
                return $item['id'] !== $id;
            }));
            $_SESSION['pesan_bookmark'] = 'Bookmark berhasil dihapus dari daftar simpanan.';
        } elseif ($aksi === 'tambah') {
            // Logika otomatis ketika diklik dari halaman lain (Katalog/Dashboard)
            $newItem = [
                'id' => $_POST['id'] ?? uniqid(),
                'judul' => $_POST['judul'] ?? 'Program Pilihan',
                'kategori' => $_POST['kategori'] ?? 'Umum',
                'penyelenggara' => $_POST['penyelenggara'] ?? 'Penyelenggara',
                'deadline' => $_POST['deadline'] ?? '2026-Desember-31',
                'status' => 'Aktif',
                'deskripsi' => $_POST['deskripsi'] ?? 'Deskripsi program.',
                'link' => $_POST['link'] ?? '#'
            ];
            // Cek agar tidak duplikat
            $exists = false;
            foreach ($_SESSION['bookmarks'] as $b) {
                if ($b['judul'] === $newItem['judul']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                array_unshift($_SESSION['bookmarks'], $newItem);
                $_SESSION['pesan_bookmark'] = 'Program berhasil ditambahkan ke bookmark!';
            }
        }
    }
    header('Location: bookmark.php');
    exit;
}

$pesan = $_SESSION['pesan_bookmark'] ?? '';
unset($_SESSION['pesan_bookmark']);

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
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/bookmark.css">
</head>
<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <div class="main-area">
        
        <!-- HEADER GLOBAL -->
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <main class="dashboard-container">
            <!-- BANNER UTAMA -->
            <section class="welcome-banner">
                <div class="banner-content-wrapper">
                    <div>
                        <span class="badge-tag">• Arsip Peluang Pengembangan Diri</span>
                        <h1 class="banner-title">Bookmark & Program Tersimpan</h1>
                        <p class="banner-subtitle">Simpan dan kelola seluruh peluang magang, beasiswa, dan bootcamp favoritmu di satu tempat.</p>
                    </div>

                    <div class="counter-box-main" style="margin: 0;">
                        <div class="counter-icon">
                            <i class="fa-solid fa-bookmark"></i>
                        </div>
                        <div class="counter-info">
                            <span class="counter-number"><?= $totalTersimpan ?> Tersimpan</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KOTAK PENCARIAN & TOMBOL FILTER KATEGORI -->
            <div style="width: 100%; margin: 0px 0; display: flex; flex-direction: column; gap: 12px;">
                <input type="text" id="searchBookmark" placeholder="Cari dalam bookmark tersimpan..." style="width: 100%; height: 46px; padding: 0 20px; background-color: #FFFFFF; border: 1px solid #D1D5DB; border-radius: 12px; font-size: 14px; color: #1F2937; outline: none; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
                
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="filter-btn active" data-filter="Semua" style="padding: 8px 16px; border-radius: 9999px; border: 1px solid #D1D5DB; background: #10B981; color: #FFFFFF; font-size: 13px; font-weight: 500; cursor: pointer;">Semua</button>
                    <button type="button" class="filter-btn" data-filter="Beasiswa" style="padding: 8px 16px; border-radius: 9999px; border: 1px solid #D1D5DB; background: #FFFFFF; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer;">Beasiswa</button>
                    <button type="button" class="filter-btn" data-filter="Magang" style="padding: 8px 16px; border-radius: 9999px; border: 1px solid #D1D5DB; background: #FFFFFF; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer;">Magang</button>
                    <button type="button" class="filter-btn" data-filter="Bootcamp" style="padding: 8px 16px; border-radius: 9999px; border: 1px solid #D1D5DB; background: #FFFFFF; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer;">Bootcamp</button>
                    <button type="button" class="filter-btn" data-filter="Lomba" style="padding: 8px 16px; border-radius: 9999px; border: 1px solid #D1D5DB; background: #FFFFFF; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer;">Lomba</button>
                </div>
            </div>

            <?php if ($pesan): ?>
                <div class="alert-message success" style="margin-bottom: 20px; padding: 12px 16px; background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-check"></i> <?= aman($pesan) ?>
                </div>
            <?php endif; ?>

            <!-- GRID KARTU BOOKMARK -->
            <div class="bookmark-grid" id="bookmarkGrid">
                <?php if (empty($daftarBookmark)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-bookmark"></i>
                        <h3>Belum ada bookmark tersimpan</h3>
                        <p>Silakan klik ikon bookmark pada program di halaman katalog atau dashboard.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftarBookmark as $item): ?>
                        <div class="bookmark-card clickable-card" 
                            data-kategori="<?= aman($item['kategori']) ?>" 
                            data-judul="<?= strtolower(aman($item['judul'])) ?>"
                            data-penyelenggara="<?= strtolower(aman($item['penyelenggara'])) ?>"
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

    <!-- MODAL DETAIL -->
    <div class="modal-overlay" id="detailModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
        <div class="modal-card" style="background: #fff; width: 100%; max-width: 500px; padding: 24px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #111827;">Detail Program Favorit</h3>
                <button type="button" onclick="closeModal('detailModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6B7280;">&times;</button>
            </div>
            <div class="detail-body">
                <div class="card-header-tag" style="margin-bottom: 12px;">
                    <span class="kategori-tag" id="detailKategori">-</span>
                    <span class="status-tag active">
                        <span class="badge-status-dot"></span> <span id="detailStatusText">Aktif</span>
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
                <a id="detailLink" href="#" target="_blank" class="btn-action" style="padding: 8px 16px; background: #10B981; color: #fff; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Daftar Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- SCRIPT (MODAL, SEARCH, & FILTER) -->
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openDetailModal(item) {
            document.getElementById('detailKategori').innerText = item.kategori;
            document.getElementById('detailJudul').innerText = item.judul;
            document.getElementById('detailPenyelenggara').innerText = item.penyelenggara;
            document.getElementById('detailDeskripsi').innerText = item.deskripsi;
            document.getElementById('detailDeadline').innerText = item.deadline;
            document.getElementById('detailLink').href = item.link;
            openModal('detailModal');
        }

        // Live Search & Filter
        const searchInput = document.getElementById('searchBookmark');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.bookmark-card');

        let currentFilter = 'Semua';
        let currentKeyword = '';

        function filterCards() {
            cards.forEach(card => {
                const kategori = card.getAttribute('data-kategori');
                const judul = card.getAttribute('data-judul');
                const penyelenggara = card.getAttribute('data-penyelenggara');

                const matchCategory = (currentFilter === 'Semua' || kategori === currentFilter);
                const matchKeyword = (judul.includes(currentKeyword) || penyelenggara.includes(currentKeyword));

                if (matchCategory && matchKeyword) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                currentKeyword = e.target.value.toLowerCase().trim();
                filterCards();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.style.background = '#FFFFFF';
                    b.style.color = '#374151';
                });
                btn.style.background = '#10B981';
                btn.style.color = '#FFFFFF';

                currentFilter = btn.getAttribute('data-filter');
                filterCards();
            });
        });
    </script>
</body>
</html>