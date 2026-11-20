<link rel="stylesheet" href="../../public/css/header.css">
<link rel="stylesheet" href="../../public/css/rating.css">

<?php
// Session harus sudah dimulai oleh halaman pemanggil.

if (!function_exists('aman')) {
    function aman($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

$headerUsername = $_SESSION['username'] ?? '';
$headerAdmin = ($_SESSION['role'] ?? 'user') === 'admin';
?>

<header class="header">
    <a href="/ruang_tumbuh/app/Views/profile.php"
       class="profile-section"
       title="Lihat profil">

        <div class="profile-info">
            <div class="profile-name"><?= aman($headerUsername) ?></div>
            <div class="profile-role">
                <?= $headerAdmin ? 'Admin' : 'Pengguna' ?>
            </div>
        </div>

        <div class="avatar">
            <?= aman(strtoupper(mb_substr($headerUsername, 0, 2))) ?>
        </div>
    </a>
</header>
