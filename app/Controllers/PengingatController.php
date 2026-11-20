
<?php

class PengingatController
{
    private $pengingatModel;
    private $basePath;

    public function __construct($pengingatModel, $basePath)
    {
        $this->pengingatModel = $pengingatModel;
        $this->basePath = $basePath;
    }

    // Menampilkan daftar pengingat
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        $pengingat = $this->pengingatModel->getAll($_SESSION['user_id']);

        require __DIR__ . '/../Views/pengingat.php';
    }

    // Menambahkan pengingat
    public function simpan()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        $judul = trim($_POST['judul'] ?? '');
        $tanggal = $_POST['tanggal_tenggat'] ?? '';

        if ($judul !== '' && $tanggal !== '') {
            $this->pengingatModel->tambah(
                $_SESSION['user_id'],
                $judul,
                $tanggal
            );
        }

        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }

    // Menampilkan form edit
    
public function edit()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: {$this->basePath}/");
        exit;
    }

    $id = (int) ($_GET['id'] ?? 0);

    $hasil = $this->pengingatModel->getById(
        $id,
        $_SESSION['user_id']
    );

    $data = mysqli_fetch_assoc($hasil);

    if (!$data) {
        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }

    // Ambil daftar pengingat untuk halaman yang sama
    $pengingat = $this->pengingatModel->getAll($_SESSION['user_id']);

    // Gunakan halaman pengingat yang sama
    require __DIR__ . '/../Views/pengingat.php';
}


    // Menyimpan perubahan
    public function ubah()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $tanggal = $_POST['tanggal_tenggat'] ?? '';

        if ($id > 0 && $judul !== '' && $tanggal !== '') {
            $this->pengingatModel->edit(
                $id,
                $_SESSION['user_id'],
                $judul,
                $tanggal
            );
        }

        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }

    // Menghapus pengingat
    public function hapus()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->pengingatModel->hapus(
                $id,
                $_SESSION['user_id']
            );
        }

        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }

    // Menandai pengingat selesai
    public function selesai()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['selesai'] ?? 1);

        if ($id > 0) {
            $this->pengingatModel->ubahStatus(
                $id,
                $_SESSION['user_id'],
                $status
            );
        }

        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }
}
