<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek Keamanan: Pastikan hanya Admin yang bisa akses
// if ($_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$nama_user = $_SESSION['username'] ?? 'Admin RuangTumbuh';

// Sample Data Kategori (Nanti tinggal diganti dengan query Database PHP/MySQL)
$kategori_list = [
    ['id' => 1, 'nama' => 'Bootcamp & Intensive Class', 'slug' => 'bootcamp', 'ikon' => 'fa-laptop-code', 'jumlah' => 24],
    ['id' => 2, 'nama' => 'Beasiswa & Pendanaan', 'slug' => 'beasiswa', 'ikon' => 'fa-graduation-cap', 'jumlah' => 18],
    ['id' => 3, 'nama' => 'Magang & Karir Pertama', 'slug' => 'magang', 'ikon' => 'fa-briefcase', 'jumlah' => 35],
    ['id' => 4, 'nama' => 'Kompetisi & Lomba', 'slug' => 'lomba', 'ikon' => 'fa-trophy', 'jumlah' => 12],
    ['id' => 5, 'nama' => 'Webinar & Workshop', 'slug' => 'webinar', 'ikon' => 'fa-chalkboard-user', 'jumlah' => 29],
    ['id' => 6, 'nama' => 'Sertifikasi Profesi', 'slug' => 'sertifikasi', 'ikon' => 'fa-certificate', 'jumlah' => 9],
];
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
        .admin-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .admin-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-tambah {
            background-color: #10B981;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-tambah:hover { background-color: #059669; }

        /* Style Tabel Data */
        .crud-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .crud-table th, .crud-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        .crud-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            background: #fef3c7;
            color: #d97706;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Modal Popup Form */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-box {
            background: white;
            padding: 24px;
            border-radius: 16px;
            width: 100%;
            max-width: 450px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="logo-text">Ruang<span>Tumbuh</span></div>
            
            <div class="nav-group" style="margin-top: 32px;">
                <div class="nav-title">PANEL ADMIN</div>
                <ul class="nav-menu">
                    <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                    <li><a href="adminkategori.php" class="nav-link active"><i class="fa-solid fa-layer-group"></i> Kelola Kategori</a></li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        <header class="header">
            <h3>Manajemen Data Kategori</h3>
            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=F59E0B&color=fff" class="profile-avatar">
            </div>
        </header>

        <main class="dashboard-container">
            <div class="admin-card">
                <div class="admin-header-flex">
                    <div>
                        <h2 style="font-size: 1.25rem; color: #0f172a;">Daftar Kategori Program</h2>
                        <p style="color: #64748b; font-size: 0.85rem;">Tambah, edit, atau hapus kategori minat program.</p>
                    </div>
                    <button class="btn-tambah" onclick="toggleModal(true)">
                        <i class="fa-solid fa-plus"></i> Tambah Kategori
                    </button>
                </div>

                <!-- TABEL CRUD DATA -->
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
                            <td><i class="fa-solid <?= $kat['ikon']; ?>" style="color: #10B981; font-size: 1.1rem;"></i></td>
                            <td><strong><?= $kat['nama']; ?></strong></td>
                            <td><code><?= $kat['slug']; ?></code></td>
                            <td><?= $kat['jumlah']; ?> Program</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="alert('Edit kategori ID: <?= $kat['id']; ?>')"><i class="fa-solid fa-pen"></i> Edit</button>
                                    <button class="btn-delete" onclick="if(confirm('Hapus kategori ini?')) alert('Dihapus!')"><i class="fa-solid fa-trash"></i> Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL POPUP FORM TAMBAH -->
    <div class="modal-overlay" id="modalForm">
        <div class="modal-box">
            <h3 style="margin-bottom: 16px;">Tambah Kategori Baru</h3>
            <form action="#" method="POST">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Bootcamp & Class" required>
                </div>
                <div class="form-group">
                    <label>Class Ikon (FontAwesome)</label>
                    <input type="text" name="ikon" class="form-control" placeholder="Contoh: fa-laptop-code" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-delete" onclick="toggleModal(false)">Batal</button>
                    <button type="submit" class="btn-tambah">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(show) {
            document.getElementById('modalForm').style.display = show ? 'flex' : 'none';
        }
    </script>
</body>
</html>