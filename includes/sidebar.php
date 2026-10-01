<?php
// Nama file halaman yang sedang dibuka, misal "dashboard.php"
$halaman_aktif = basename($_SERVER['PHP_SELF']);

// Daftar menu: file => [ikon, label]
$menu = [
    'dashboard.php' => ['fa-solid fa-table-cells-large', 'Dashboard'],
    'katalog.php'   => ['fa-solid fa-shapes',            'Katalog Bootcamp'],
    'kategori.php'  => ['fa-solid fa-layer-group',       'Kategori Program'],
    'bookmark.php'  => ['fa-regular fa-bookmark',        'Bookmark'],
    'pengingat.php' => ['fa-regular fa-clock',           'Pengingat Tenggat'],
    'catatan.php'   => ['fa-regular fa-pen-to-square',   'Catatan Pribadi'],
    'rating.php'    => ['fa-regular fa-star',            'Ulasan & Rating'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>

<aside class="sidebar">
    <div>
        <div class="logo-text">
            Ruang<span>Tumbuh</span>
        </div>

        <div class="nav-group nav-group-spaced">
            <div class="nav-title">MENU UTAMA</div>

            <ul class="nav-menu">
                <?php foreach ($menu as $file => [$ikon, $label]): ?>
                    <li>
                        <a href="<?= $file; ?>"
                           class="nav-link <?= ($halaman_aktif === $file) ? 'active' : ''; ?>">
                            <i class="<?= $ikon; ?>"></i>
                            <?= $label; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>
    
</body>
</html>