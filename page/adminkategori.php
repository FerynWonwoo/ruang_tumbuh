<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Hanya admin yang boleh masuk =====
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

function aman($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function jsonAttr($data)
{
    return json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
}

function buatSlug($teks)
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $teks), '-'));
    return $slug !== '' ? $slug : 'kategori';
}

// Pilihan ikon (class => label)
$daftarIkon = [
    'fa-laptop-code'     => 'Teknologi',
    'fa-code'            => 'Coding',
    'fa-graduation-cap'  => 'Pendidikan',
    'fa-book-open'       => 'Belajar',
    'fa-briefcase'       => 'Karir',
    'fa-handshake'       => 'Kemitraan',
    'fa-trophy'          => 'Lomba',
    'fa-rocket'          => 'Startup',
    'fa-chalkboard-user' => 'Workshop',
    'fa-certificate'     => 'Sertifikat',
    'fa-palette'         => 'Desain',
    'fa-chart-line'      => 'Data',
    'fa-bullhorn'        => 'Marketing',
    'fa-lightbulb'       => 'Inovasi',
    'fa-users'           => 'Komunitas',
    'fa-globe'           => 'Global',
];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Data awal (sementara di session, nanti ganti ke database)
if (!isset($_SESSION['kategori_admin'])) {
    $_SESSION['kategori_admin'] = [
        ['id' => 1, 'nama' => 'Bootcamp & Intensive Class', 'slug' => 'bootcamp',    'ikon' => 'fa-laptop-code',     'jumlah' => 24],
        ['id' => 2, 'nama' => 'Beasiswa & Pendanaan',       'slug' => 'beasiswa',    'ikon' => 'fa-graduation-cap',  'jumlah' => 18],
        ['id' => 3, 'nama' => 'Magang & Karir Pertama',     'slug' => 'magang',      'ikon' => 'fa-briefcase',       'jumlah' => 35],
        ['id' => 4, 'nama' => 'Kompetisi & Lomba',          'slug' => 'lomba',       'ikon' => 'fa-trophy',          'jumlah' => 12],
        ['id' => 5, 'nama' => 'Webinar & Workshop',         'slug' => 'webinar',     'ikon' => 'fa-chalkboard-user', 'jumlah' => 29],
        ['id' => 6, 'nama' => 'Sertifikasi Profesi',        'slug' => 'sertifikasi', 'ikon' => 'fa-certificate',     'jumlah' => 9],
    ];
}

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Sesi formulir tidak valid. Silakan muat ulang halaman.');
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $_SESSION['kategori_admin'] = array_values(array_filter(
            $_SESSION['kategori_admin'],
            fn($k) => (int)$k['id'] !== $id
        ));
        header('Location: adminkategori.php');
        exit;
    }

    if ($aksi === 'simpan') {
        $id   = (int)($_POST['id'] ?? 0);
        $nama = is_string($_POST['nama'] ?? null) ? trim($_POST['nama']) : '';
        $ikon = is_string($_POST['ikon'] ?? null) ? trim($_POST['ikon']) : '';

        if ($nama === '' || $ikon === '') {
            $_SESSION['flash_error'] = 'Nama kategori wajib diisi dan ikon wajib dipilih.';
        } elseif (!isset($daftarIkon[$ikon])) {
            $_SESSION['flash_error'] = 'Ikon tidak valid. Pilih salah satu dari daftar.';
        } else {
            $slug = buatSlug($nama);

            if ($id > 0) {
                foreach ($_SESSION['kategori_admin'] as &$k) {
                    if ((int)$k['id'] === $id) {
                        $k['nama'] = $nama;
                        $k['slug'] = $slug;
                        $k['ikon'] = $ikon;
                        break;
                    }
                }
                unset($k);
            } else {
                $ids = array_column($_SESSION['kategori_admin'], 'id');
                $_SESSION['kategori_admin'][] = [
                    'id'     => ($ids ? max($ids) : 0) + 1,
                    'nama'   => $nama,
                    'slug'   => $slug,
                    'ikon'   => $ikon,
                    'jumlah' => 0,
                ];
            }
        }
        header('Location: adminkategori.php');
        exit;
    }
}

$kategori_list = $_SESSION['kategori_admin'];
$nama_user     = $_SESSION['username'] ?? 'Admin RuangTumbuh';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Kelola Kategori (Admin)</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .admin-card { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.02); }
        .admin-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
        .btn-tambah { background: #10B981; color: #fff; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: .875rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-tambah:hover { background: #059669; }
        .table-wrap { overflow-x: auto; }
        .crud-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .crud-table th, .crud-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
        .crud-table th { background: #f8fafc; color: #475569; font-weight: 700; }
        .action-btns { display: flex; gap: 8px; }
        .action-btns form { margin: 0; }
        .btn-edit, .btn-delete { border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-edit { background: #fef3c7; color: #d97706; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .flash-error { margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; font-size: .9rem; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); justify-content: center; align-items: center; z-index: 1000; padding: 16px; }
        .modal-box { background: #fff; padding: 24px; border-radius: 16px; width: 100%; max-width: 450px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .icon-picker { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .icon-option { position: relative; }
        .icon-option input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
        .icon-option span { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 4px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: .7rem; color: #64748b; background: #fff; }
        .icon-option span i { font-size: 1.2rem; color: #64748b; }
        .icon-option:hover span { border-color: #6ee7b7; }
        .icon-option input:checked + span { border-color: #10B981; background: #ecfdf5; color: #065f46; font-weight: 600; }
        .icon-option input:checked + span i { color: #10B981; }
        .icon-option input:focus-visible + span { outline: 2px solid #10B981; outline-offset: 2px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: .9rem; box-sizing: border-box; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="logo-text">Ruang<span>Tumbuh</span></div>

            <div class="nav-group" style="margin-top: 32px;">
                <div class="nav-title">MENU ADMIN</div>
                <ul class="nav-menu">
                    <li><a href="admindashboard.php" class="nav-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                    <li><a href="adminkatalog.php" class="nav-link"><i class="fa-solid fa-shapes"></i> Katalog Bootcamp</a></li>
                    <li><a href="adminkategori.php" class="nav-link active"><i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
                </ul>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        <header class="header">
            <h3>Manajemen Data Kategori</h3>
            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=F59E0B&color=fff" class="profile-avatar" alt="Avatar">
            </div>
        </header>

        <main class="dashboard-container">
            <?php if ($error !== ''): ?>
                <div class="flash-error" role="alert"><?= aman($error); ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-header-flex">
                    <div>
                        <h2 style="font-size: 1.25rem; color: #0f172a;">Daftar Kategori Program</h2>
                        <p style="color: #64748b; font-size: .85rem;">Tambah, edit, atau hapus kategori minat program.</p>
                    </div>
                    <button type="button" class="btn-tambah" onclick="bukaModal()">
                        <i class="fa-solid fa-plus"></i> Tambah Kategori
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Ikon</th>
                                <th>Nama Kategori</th>
                                <th>Slug URL</th>
                                <th>Jumlah Program</th>
                                <th>Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kategori_list as $index => $kat): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><i class="fa-solid <?= aman($kat['ikon']); ?>" style="color: #10B981; font-size: 1.1rem;"></i></td>
                                <td><strong><?= aman($kat['nama']); ?></strong></td>
                                <td><code><?= aman($kat['slug']); ?></code></td>
                                <td><?= (int)$kat['jumlah']; ?> Program</td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="btn-edit" onclick='bukaModal(<?= jsonAttr($kat); ?>)'>
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']); ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int)$kat['id']; ?>">
                                            <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$kategori_list): ?>
                            <tr><td colspan="6" style="text-align:center; color:#64748b;">Belum ada kategori.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT -->
    <div class="modal-overlay" id="modalForm">
        <div class="modal-box">
            <h3 id="judulModal" style="margin-bottom: 16px;">Tambah Kategori Baru</h3>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']); ?>">
                <input type="hidden" name="aksi" value="simpan">
                <input type="hidden" name="id" id="kategoriId" value="">

                <div class="form-group">
                    <label for="kategoriNama">Nama Kategori</label>
                    <input type="text" name="nama" id="kategoriNama" class="form-control" placeholder="Contoh: Bootcamp & Class" required>
                </div>
                <div class="form-group">
                    <label>Pilih Ikon</label>
                    <div class="icon-picker">
                        <?php foreach ($daftarIkon as $kelas => $label): ?>
                            <label class="icon-option">
                                <input type="radio" name="ikon" value="<?= aman($kelas); ?>" required>
                                <span><i class="fa-solid <?= aman($kelas); ?>"></i><?= aman($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-delete" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn-tambah">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalForm');

        function bukaModal(data) {
            document.getElementById('judulModal').textContent = data ? 'Edit Kategori' : 'Tambah Kategori Baru';
            document.getElementById('kategoriId').value   = data ? data.id : '';
            document.getElementById('kategoriNama').value = data ? data.nama : '';
            const radios = document.querySelectorAll('input[name="ikon"]');
            radios.forEach(r => r.checked = data && r.value === data.ikon);
            modal.style.display = 'flex';
        }

        function tutupModal() {
            modal.style.display = 'none';
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) tutupModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') tutupModal();
        });
    </script>
</body>
</html>
