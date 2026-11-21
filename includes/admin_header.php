
<?php
// Session harus sudah dimulai oleh halaman pemanggil.

if (!function_exists('admin_aman')) {
    function admin_aman($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

$adminUsername = $_SESSION['username'] ?? 'Admin';
$adminInisial = strtoupper(mb_substr($adminUsername, 0, 2));

// Judul halaman dapat diatur dari halaman pemanggil.
$adminPageTitle = $adminPageTitle ?? 'Dashboard Admin';
?>

<link rel="stylesheet" href="/ruang_tumbuh/public/css/admin-header.css">

<header class="admin-header">
    <div class="admin-header-title">
        <?= admin_aman($adminPageTitle) ?>
    </div>

    <div class="admin-header-profile">
        <div class="admin-header-avatar">
            <?= admin_aman($adminInisial) ?>
        </div>

        <div class="admin-header-info">
            <div class="admin-header-name">
                <?= admin_aman($adminUsername) ?>
            </div>
            <div class="admin-header-role">
                Administrator
            </div>
        </div>
    </div>
</header>
