<?php
// Nama file halaman yang sedang dibuka
$halaman_aktif = basename($_SERVER['PHP_SELF']);
$halaman_pengajuan = $_GET['page'] ?? '';

$menu = [
    'dashboard.php' => ['fa-solid fa-table-cells-large', 'Dashboard'],
    'katalog.php'   => ['fa-solid fa-shapes', 'Katalog Program'],
    'kategori.php'  => ['fa-solid fa-layer-group', 'Kategori Program'],
    'bookmark.php'  => ['fa-regular fa-bookmark', 'Bookmark'],
    'pengingat.php' => ['fa-regular fa-clock', 'Pengingat Tenggat'],
    'catatan.php'   => ['fa-regular fa-pen-to-square', 'Catatan Pribadi'],
    'rating.php'    => ['fa-regular fa-star', 'Ulasan & Rating'],
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/ruang_tumbuh/public/css/sidebar.css">

<aside class="sidebar">
    <div>
        <div class="logo-text">
            Ruang<span>Tumbuh</span>
        </div>

        <div class="nav-group nav-group-spaced">
            <div class="nav-title">MENU UTAMA</div>

            <ul class="nav-menu">
                <?php foreach ($menu as $file => [$ikon, $label]): ?>
                    <?php
                    if ($file === 'pengingat.php') {
                        $url = '/ruang_tumbuh/index.php?page=pengingat';
                        $aktif = ($halaman_pengajuan === 'pengingat');
                        } else {
                        $url = '/ruang_tumbuh/app/Views/' . $file;
                        $aktif = ($halaman_aktif === $file);
                        }
                    ?>

                    <li>
                        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                            class="nav-link <?= $aktif ? 'active' : ''; ?>">
                            <i class="<?= htmlspecialchars($ikon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
    <a href="/ruang_tumbuh/app/Views/logout.php" class="logout-link">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>
</div>
</aside>