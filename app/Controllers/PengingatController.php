<?php

class PengingatController
{
    private $pengingatModel;
    private $basePath;

    const MAKS_JUDUL = 150;   
    const MAKS_TAHUN = 5;    

    public function __construct($pengingatModel, $basePath)
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->pengingatModel = $pengingatModel;
        $this->basePath = $basePath;
    }
    private function wajibLogin()
    {
        if (empty($_SESSION['user_id'])) {
            header("Location: {$this->basePath}/");
            exit;
        }

        return (int) $_SESSION['user_id'];
    }
    private function cegahCache()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
    private function kembali()
    {
        header("Location: {$this->basePath}/index.php?page=pengingat");
        exit;
    }
    private function galatServer(Throwable $e)
    {
        error_log('Pengingat: ' . $e->getMessage());

        http_response_code(500);
        echo "Data pengingat tidak dapat diproses saat ini. "
            . "Pastikan migration database sudah dijalankan, lalu coba lagi.";
        exit;
    }
    private function tanggalValid($tanggal)
    {
        if (!is_string($tanggal)) {
            return false;
        }

        $date = DateTime::createFromFormat('!Y-m-d', $tanggal);
        $errors = DateTime::getLastErrors();

        return $date !== false
            && ($errors === false || (
                $errors['warning_count'] === 0 &&
                $errors['error_count'] === 0
            ))
            && $date->format('Y-m-d') === $tanggal;
    }
    private function validasiInput($judul, $tanggal, $tanggalLama = null)
    {
        if ($judul === '') {
            return "Judul pengingat tidak boleh kosong.";
        }

        if (mb_strlen($judul, 'UTF-8') > self::MAKS_JUDUL) {
            return "Judul pengingat maksimal " . self::MAKS_JUDUL . " karakter.";
        }

        if (!$this->tanggalValid($tanggal)) {
            return "Format atau tanggal yang dimasukkan tidak valid.";
        }
        if ($tanggal !== $tanggalLama && $tanggal < date('Y-m-d')) {
            return "Tanggal tenggat sudah lewat. Pilih hari ini atau tanggal setelahnya.";
        }
        $batas = date('Y-m-d', strtotime('+' . self::MAKS_TAHUN . ' years'));
        if ($tanggal > $batas) {
            return "Tanggal tenggat terlalu jauh. Maksimal " . self::MAKS_TAHUN . " tahun dari sekarang.";
        }

        return null;
    }
    private function ambilJudul()
    {
        return trim(
            is_string($_POST['judul'] ?? null)
                ? $_POST['judul']
                : ''
        );
    }
    public function index()
    {
        $userId = $this->wajibLogin();
        $this->cegahCache();

        try {
            $pengingat = $this->pengingatModel->getAll($userId);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        require __DIR__ . '/../Views/pengingat.php';
    }
    public function simpan()
    {
        $userId = $this->wajibLogin();

        $judul = $this->ambilJudul();
        $tanggal = $_POST['tanggal_tenggat'] ?? '';

        $error = $this->validasiInput($judul, $tanggal);

        if ($error !== null) {
            $_SESSION['pengingat_error'] = $error;
            $this->kembali();
        }

        try {
            $berhasil = $this->pengingatModel->tambah($userId, $judul, $tanggal);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        if ($berhasil) {
            $_SESSION['pengingat_sukses'] = "Pengingat berhasil ditambahkan.";
        } else {
            $_SESSION['pengingat_error'] = "Pengingat gagal ditambahkan. Silakan coba lagi.";
        }

        $this->kembali();
    }
    public function edit()
    {
        $userId = $this->wajibLogin();
        $this->cegahCache();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->kembali();
        }

        try {
            $hasil = $this->pengingatModel->getById($id, $userId);
            $data = mysqli_fetch_assoc($hasil);

            if (!$data) {
                $_SESSION['pengingat_error'] = "Pengingat tidak ditemukan.";
                $this->kembali();
            }

            $pengingat = $this->pengingatModel->getAll($userId);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        require __DIR__ . '/../Views/pengingat.php';
    }

    public function ubah()
    {
        $userId = $this->wajibLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $judul = $this->ambilJudul();
        $tanggal = $_POST['tanggal_tenggat'] ?? '';

        if ($id <= 0) {
            $_SESSION['pengingat_error'] = "Data pengingat tidak valid.";
            $this->kembali();
        }

        try {
            $lama = mysqli_fetch_assoc(
                $this->pengingatModel->getById($id, $userId)
            );

            if (!$lama) {
                $_SESSION['pengingat_error'] = "Pengingat tidak ditemukan.";
                $this->kembali();
            }

            $error = $this->validasiInput($judul, $tanggal, $lama['tanggal_tenggat']);

            if ($error !== null) {
                $_SESSION['pengingat_error'] = $error;
                $this->kembali();
            }

            $berhasil = $this->pengingatModel->edit($id, $userId, $judul, $tanggal);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        if ($berhasil) {
            $_SESSION['pengingat_sukses'] = "Pengingat berhasil diperbarui.";
        } else {
            $_SESSION['pengingat_error'] = "Pengingat gagal diperbarui.";
        }

        $this->kembali();
    }
    public function hapus()
    {
        $userId = $this->wajibLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['pengingat_error'] = "ID pengingat tidak valid.";
            $this->kembali();
        }

        try {
            $berhasil = $this->pengingatModel->hapus($id, $userId);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        if ($berhasil) {
            $_SESSION['pengingat_sukses'] = "Pengingat berhasil dihapus.";
        } else {
            $_SESSION['pengingat_error'] = "Pengingat gagal dihapus.";
        }

        $this->kembali();
    }
    public function selesai()
    {
        $userId = $this->wajibLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['selesai'] ?? 1);

        if ($id > 0 && ($status === 0 || $status === 1)) {
            try {
                $this->pengingatModel->ubahStatus($id, $userId, $status);
            } catch (Throwable $e) {
                $this->galatServer($e);
            }
        }

        $this->kembali();
    }
}
