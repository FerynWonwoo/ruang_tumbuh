<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// Hanya admin yang boleh masuk
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}
 
function aman($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
 
$nama_user = $_SESSION['username'] ?? 'Admin';
 
// ===== Data (sementara dari session, nanti ganti query database) =====
$bootcamp = $_SESSION['bootcamp'] ?? [];
// Jika halaman Kelola Kategori belum pernah dibuka, datanya belum ada: pakai 6 kategori bawaan
$kategori = $_SESSION['kategori_admin'] ?? null;
$totalKategori = $kategori !== null ? count($kategori) : 6;
 
$totalBootcamp = count($bootcamp);
$totalOnline   = count(array_filter($bootcamp, fn($b) => $b['mode'] === 'Online'));
$totalOffline  = count(array_filter($bootcamp, fn($b) => $b['mode'] === 'Offline'));
 
// Bootcamp per kategori
$perKategori = ['Teknologi' => 0, 'Design' => 0, 'Data' => 0, 'Marketing' => 0];
foreach ($bootcamp as $b) {
    if (isset($perKategori[$b['kategori']])) {
        $perKategori[$b['kategori']]++;
    }
}
$maks = max(1, max($perKategori));
 
// Deadline terdekat (yang belum lewat)
$hariIni = date('Y-m-d');
$mendatang = array_filter($bootcamp, fn($b) => $b['deadline'] >= $hariIni);
usort($mendatang, fn($a, $b) => strcmp($a['deadline'], $b['deadline']));
$mendatang = array_slice($mendatang, 0, 5);
 
$jam = (int)date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .welcome { background: #fef9c3; border: 1px solid #fef08a; border-radius: 20px; padding: 24px; margin-bottom: 20px; }
        .welcome .tag { display: inline-block; background: #0d9488; color: #fff; font-size: .75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; margin-bottom: 12px; }
        .welcome h1 { font-size: 1.5rem; color: #0f172a; font-weight: 800; margin: 0 0 6px; }
        .welcome p { color: #475569; font-size: .9rem; margin: 0; }
 
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 14px; }
        .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .stat-card span { display: block; color: #64748b; font-size: .8rem; }
        .stat-card strong { font-size: 1.6rem; color: #0f172a; line-height: 1.2; }
 
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; }
        .panel h2 { font-size: 1.05rem; color: #0f172a; margin: 0 0 4px; }
        .panel .sub { color: #64748b; font-size: .82rem; margin: 0 0 16px; }
 
        .bar-row { margin-bottom: 14px; }
        .bar-label { display: flex; justify-content: space-between; font-size: .85rem; color: #334155; margin-bottom: 6px; }
        .bar-track { height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
        .bar-fill { height: 100%; background: #10B981; border-radius: 99px; }
 
        .deadline-item { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .deadline-item:last-child { border-bottom: none; }
        .deadline-item strong { display: block; font-size: .9rem; color: #0f172a; }
        .deadline-item small { color: #64748b; }
        .deadline-date { background: #fef3c7; color: #b45309; font-size: .75rem; font-weight: 600; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
        .empty { color: #94a3b8; font-size: .9rem; padding: 12px 0; }
 
        .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .quick-card { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; text-decoration: none; color: inherit; transition: transform .2s, box-shadow .2s; }
        .quick-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.06); }
        .quick-card strong { display: block; color: #0f172a; font-size: .95rem; }
        .quick-card small { color: #64748b; }
 
        @media (max-width: 800px) { .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
 
<aside class="sidebar">
    <div>
        <div class="logo-text">Ruang<span>Tumbuh</span></div>
 
        <div class="nav-group" style="margin-top:32px;">
            <div class="nav-title">MENU ADMIN</div>
            <ul class="nav-menu">
                <li><a href="admindashboard.php" class="nav-link active"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="adminkatalog.php" class="nav-link"><i class="fa-solid fa-shapes"></i> Katalog Bootcamp</a></li>
                <li><a href="adminkategori.php" class="nav-link"><i class="fa-solid fa-layer-group"></i> Kategori Program</a></li>
            </ul>
        </div>
    </div>
 
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>
 
<div class="main-area">
    <header class="header">
        <h3>Dashboard Admin</h3>
        <div class="header-actions">
            <div class="profile-section">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=F59E0B&color=fff" class="profile-avatar" alt="Avatar">
                <div>
                    <div class="profile-name"><?= aman($nama_user); ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>
 
    <main class="dashboard-container">
 
        <section class="welcome">
            <span class="tag"><i class="fa-solid fa-user-shield"></i> ADMINISTRATOR</span>
            <h1><?= $sapaan; ?>, <?= aman($nama_user); ?>!</h1>
            <p>Ringkasan data RuangTumbuh hari ini, <?= date('d M Y'); ?>.</p>
        </section>
 
        <section class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-shapes"></i></div>
                <div><span>Total Bootcamp</span><strong><?= $totalBootcamp; ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-layer-group"></i></div>
                <div><span>Total Kategori</span><strong><?= $totalKategori; ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-wifi"></i></div>
                <div><span>Program Online</span><strong><?= $totalOnline; ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-location-dot"></i></div>
                <div><span>Program Offline</span><strong><?= $totalOffline; ?></strong></div>
            </div>
        </section>
 
        <section class="two-col">
            <div class="panel">
                <h2>Bootcamp per Kategori</h2>
                <p class="sub">Sebaran program pada katalog saat ini.</p>
 
                <?php foreach ($perKategori as $nama => $jumlah): ?>
                    <div class="bar-row">
                        <div class="bar-label"><span><?= aman($nama); ?></span><strong><?= $jumlah; ?></strong></div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= round($jumlah / $maks * 100); ?>%;"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
 
            <div class="panel">
                <h2>Deadline Terdekat</h2>
                <p class="sub">Program yang pendaftarannya segera ditutup.</p>
 
                <?php if (!$mendatang): ?>
                    <div class="empty">Belum ada program dengan deadline mendatang.</div>
                <?php endif; ?>
 
                <?php foreach ($mendatang as $b): ?>
                    <div class="deadline-item">
                        <div>
                            <strong><?= aman($b['judul']); ?></strong>
                            <small><?= aman($b['penyelenggara']); ?></small>
                        </div>
                        <span class="deadline-date"><?= aman(date('d M Y', strtotime($b['deadline']))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
 
        <section class="quick-grid">
            <a href="adminkatalog.php" class="quick-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-plus"></i></div>
                <div><strong>Kelola Katalog Bootcamp</strong><small>Tambah, ubah, atau hapus program</small></div>
            </a>
            <a href="adminkategori.php" class="quick-card">
                <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-layer-group"></i></div>
                <div><strong>Kelola Kategori Program</strong><small>Atur kategori dan ikonnya</small></div>
            </a>
        </section>
 
    </main>
</div>
 
</body>
</html>
 
