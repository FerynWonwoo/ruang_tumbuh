<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pengingat)) {
    header('Location: /ruang_tumbuh/index.php?page=pengingat');
    exit;
}

$namaAsli = $_SESSION['nama']
    ?? $_SESSION['user']['nama']
    ?? 'ruangtumbuh';

$nama = htmlspecialchars($namaAsli, ENT_QUOTES, 'UTF-8');

$inisialAsli = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr(trim($namaAsli), 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr(trim($namaAsli), 0, 1));

$inisial = htmlspecialchars($inisialAsli ?: 'P', ENT_QUOTES, 'UTF-8');

$modeEdit = isset($data) && is_array($data);

$pesanError = $_SESSION['pengingat_error'] ?? '';
$pesanSukses = $_SESSION['pengingat_sukses'] ?? '';

unset($_SESSION['pengingat_error'], $_SESSION['pengingat_sukses']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Tenggat - RuangTumbuh</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/ruang_tumbuh/public/css/sidebar.css">
    <link rel="stylesheet" href="/ruang_tumbuh/public/css/header.css">
    <link rel="stylesheet" href="/ruang_tumbuh/public/css/pengingat.css">
</head>

<body>
<?php
ob_start();
include __DIR__ . '/../../includes/sidebar.php';
echo str_replace(
    'href="logout.php"',
    'href="/ruang_tumbuh/app/Views/logout.php"',
    ob_get_clean()
);
?>

<main class="main-area">

    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <div class="pengingat-container">

        <?php if ($pesanError !== ''): ?>
            <div class="pesan-feedback pesan-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>
                    <?= htmlspecialchars($pesanError, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($pesanSukses !== ''): ?>
            <div class="pesan-feedback pesan-sukses" role="status">
                <i class="fa-solid fa-circle-check"></i>
                <span>
                    <?= htmlspecialchars($pesanSukses, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        <?php endif; ?>

        <section class="welcome-banner">

            <div class="badge-tag">
                <i class="fa-regular fa-clock"></i>
                PENGINGAT TENGGAT
            </div>

            <h1 class="banner-title">
                Jangan lewatkan tenggat pentingmu.
            </h1>

            <p class="banner-subtitle">
                Catat aktivitas dan tanggal tenggat agar tugas dan peluangmu tetap terpantau.
            </p>

        </section>

        <section class="reminder-card">

            <div class="card-header">

                <div class="card-title-group">
                    <i class="fa-regular fa-calendar-plus"></i>

                    <div>
                        <h2 class="card-title">
                            <?= $modeEdit ? 'Edit Pengingat' : 'Tambah Pengingat'; ?>
                        </h2>

                        <p class="card-description">
                            <?= $modeEdit
                                ? 'Perbarui aktivitas dan tanggal tenggatmu.'
                                : 'Tambahkan aktivitas yang memiliki tenggat waktu.'; ?>
                        </p>
                    </div>
                </div>

            </div>

            <form
                id="formPengingat"
                class="reminder-form"
                action="index.php?page=pengingat&aksi=<?= $modeEdit ? 'ubah' : 'simpan'; ?>"
                method="POST"
            >

                <?php if ($modeEdit): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $data['id']; ?>"
                    >
                <?php endif; ?>

                <div class="form-group">

                    <label for="judul">Nama Aktivitas</label>

                    <div class="input-wrapper">
                        <i class="fa-regular fa-pen-to-square"></i>

                        <input
                            type="text"
                            id="judul"
                            name="judul"
                            placeholder="Nama aktivitas"
                            maxlength="150"
                            value="<?= $modeEdit
                                ? htmlspecialchars($data['judul'], ENT_QUOTES, 'UTF-8')
                                : ''; ?>"
                            required
                        >
                    </div>

                </div>

                <div class="form-group">

                    <label for="tanggal">Tanggal Tenggat</label>

                    <div class="input-wrapper">
                        <i class="fa-regular fa-calendar"></i>

                        <input
                            type="text"
                            id="tanggal"
                            name="tanggal_tenggat"
                            placeholder="dd/mm/yyyy"
                            maxlength="10"
                            autocomplete="off"
                            value="<?= $modeEdit
                                ? date('d/m/Y', strtotime($data['tanggal_tenggat']))
                                : ''; ?>"
                            required
                        >
                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn-tambah">
                        <i class="fa-solid fa-<?= $modeEdit ? 'save' : 'plus'; ?>"></i>

                        <?= $modeEdit
                            ? 'Simpan Perubahan'
                            : 'Tambah Pengingat'; ?>
                    </button>

                    <?php if ($modeEdit): ?>
                        <a href="index.php?page=pengingat" class="btn-batal">
                            Batal
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        </section>

        <section class="reminder-card">

            <div class="list-header">

                <div class="card-title-group">
                    <i class="fa-regular fa-calendar-check"></i>

                    <div>
                        <h2 class="card-title">Daftar Tenggat</h2>

                        <p class="card-description">
                            Aktivitas yang sudah kamu simpan.
                        </p>
                    </div>
                </div>

                <div class="total-reminder">
                    <span><?= mysqli_num_rows($pengingat); ?></span>
                    Pengingat
                </div>

            </div>

            <div class="reminder-list">

                <?php if (mysqli_num_rows($pengingat) > 0): ?>

                    <?php while ($item = mysqli_fetch_assoc($pengingat)): ?>

                        <div class="reminder-item <?= $item['selesai'] ? 'completed' : ''; ?>">

                            <div class="reminder-info">

                                <div class="reminder-icon">
                                    <i class="fa-regular fa-clock"></i>
                                </div>

                                <div class="reminder-details">

                                    <div class="reminder-name">
                                        <?= htmlspecialchars(
                                            $item['judul'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </div>

                                    <div class="reminder-date">
                                        <i class="fa-regular fa-calendar"></i>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($item['tanggal_tenggat'])
                                        ); ?>
                                    </div>

                                </div>

                            </div>

                            <div class="reminder-actions">

                                <form
                                    method="POST"
                                    action="index.php?page=pengingat&aksi=selesai"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $item['id']; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="selesai"
                                        value="<?= $item['selesai'] ? 0 : 1; ?>"
                                    >

                                    <button type="submit" class="btn-selesai">
                                        <i class="fa-solid fa-check"></i>

                                        <?= $item['selesai']
                                            ? 'Batalkan'
                                            : 'Selesai'; ?>
                                    </button>
                                </form>
                                 <button
                                 type="button"
                                 class="btn-edit"
                                 data-id="<?= (int) $item['id']; ?>"
                                 data-judul="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8'); ?>"
                                 data-tanggal="<?= date('d/m/Y', strtotime($item['tanggal_tenggat'])); ?>" >
                                 <i class="fa-solid fa-pen"></i>
                                 Edit
                                </button>

                                <form
                                    method="POST"
                                    action="index.php?page=pengingat&aksi=hapus"
                                    onsubmit="return confirm('Yakin ingin menghapus pengingat ini?')"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $item['id']; ?>"
                                    >

                                    <button type="submit" class="btn-hapus">
                                        <i class="fa-solid fa-trash"></i>
                                        Hapus
                                    </button>

                                </form>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fa-regular fa-calendar-check"></i>

                        <h3>Belum ada pengingat</h3>

                        <p>
                            Tambahkan aktivitas dan tanggal tenggatmu.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </div>

<div class="modal-overlay" id="modalEditPengingat">

    <div class="modal-content">

        <div class="modal-header">
            <h2>Edit Pengingat</h2>

            <button type="button" class="modal-close" onclick="tutupModalEdit()">
                &times;
            </button>
        </div>

        <p class="modal-description">
            Perbarui aktivitas dan tanggal tenggatmu.
        </p>

        <form
            id="formEditPengingat"
            action="index.php?page=pengingat&aksi=ubah"
            method="POST"
        >

            <input type="hidden" name="id" id="editId">

            <div class="form-group">
                <label for="editJudul">Nama Aktivitas</label>

                <input
                    type="text"
                    name="judul"
                    id="editJudul"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="editTanggal">Tanggal Tenggat</label>

                <input
                    type="text"
                    name="tanggal_tenggat"
                    id="editTanggal"
                    placeholder="dd/mm/yyyy"
                    maxlength="10"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="btn-batal"
                    onclick="tutupModalEdit()"
                >
                    Batal
                </button>

                <button type="submit" class="btn-tambah">
                    <i class="fa-solid fa-save"></i>
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>
</div>
</main>

<script src="/ruang_tumbuh/public/js/pengingat.js"></script>

</body>
</html>
