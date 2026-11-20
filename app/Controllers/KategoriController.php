<?php
class KategoriController
{
    private $model;
    private $base;

    public function __construct($model, $base)
    {
        $this->model = $model;
        $this->base  = $base;
    }

    // ---------- Helper ----------

    private function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    private function kembali()
    {
        $this->redirect($this->base . '/index.php?page=kategori');
    }

    // Wajib login + wajib admin, dan halaman tidak boleh di-cache (tombol back)
    private function jaga()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (empty($_SESSION['user_id'])) {
            $this->redirect($this->base . '/index.php');
        }
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect($this->base . '/app/Views/dashboard.php');
        }
    }

    private function cekCsrf()
    {
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            $_SESSION['flash_error'] = 'Sesi form tidak valid. Silakan coba lagi.';
            $this->kembali();
        }
    }

    private function validasi($nama, $deskripsi, $kecualiId = 0)
    {
        if ($nama === '') {
            return 'Nama kategori wajib diisi.';
        }
        if (mb_strlen($nama) < 3) {
            return 'Nama kategori minimal 3 karakter.';
        }
        if (mb_strlen($nama) > 100) {
            return 'Nama kategori maksimal 100 karakter.';
        }
        if (mb_strlen($deskripsi) > 500) {
            return 'Deskripsi maksimal 500 karakter.';
        }
        if ($this->model->namaSudahAda($nama, $kecualiId)) {
            return 'Nama kategori sudah digunakan.';
        }
        return null;
    }

    private function gagal($pesan, $nama = '', $deskripsi = '')
    {
        $_SESSION['flash_error'] = $pesan;
        $_SESSION['flash_old']   = ['nama' => $nama, 'deskripsi' => $deskripsi];
    }

    private function render($editData = null)
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        try {
    $kategori_list = $this->model->semua();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $kategori_list = [];
    $_SESSION['flash_error'] = 'Data kategori belum dapat dimuat.';
}
        $error  = $_SESSION['flash_error']  ?? '';
        $sukses = $_SESSION['flash_sukses'] ?? '';
        $old    = $_SESSION['flash_old']    ?? [];
        unset($_SESSION['flash_error'], $_SESSION['flash_sukses'], $_SESSION['flash_old']);

        $csrf = $_SESSION['csrf'];
        $base = $this->base;

        define('APP_KATEGORI', true);
        require __DIR__ . '/../Views/adminkategori.php';
    }

    // ---------- Aksi ----------

    public function index()
    {
        $this->jaga();
        $this->render(null);
    }

    public function edit()
    {
        $this->jaga();
        $id = (int) ($_GET['id'] ?? 0);
        $data = $id > 0 ? $this->model->cari($id) : null;

        if (!$data) {
            $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
            $this->kembali();
        }
        $this->render($data);
    }

    public function simpan()
    {
        $this->jaga();
        $this->cekCsrf();

        $nama = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        try {
            $err = $this->validasi($nama, $deskripsi);
            if ($err) {
                $this->gagal($err, $nama, $deskripsi);
            } elseif ($this->model->tambah($nama, $deskripsi, (int) $_SESSION['user_id'])) {
                $_SESSION['flash_sukses'] = 'Kategori berhasil ditambahkan.';
            } else {
                $this->gagal('Kategori gagal disimpan.', $nama, $deskripsi);
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->gagal('Terjadi kesalahan pada server. Coba lagi.', $nama, $deskripsi);
        }
        $this->kembali();
    }

    public function ubah()
    {
        $this->jaga();
        $this->cekCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        try {
            if ($id <= 0 || !$this->model->cari($id)) {
                $this->gagal('Kategori tidak ditemukan.');
            } elseif ($err = $this->validasi($nama, $deskripsi, $id)) {
                $this->gagal($err);
                $this->redirect($this->base . '/index.php?page=kategori&aksi=edit&id=' . $id);
            } elseif ($this->model->ubah($id, $nama, $deskripsi)) {
                $_SESSION['flash_sukses'] = 'Kategori berhasil diubah.';
            } else {
                $this->gagal('Kategori gagal diubah.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->gagal('Terjadi kesalahan pada server. Coba lagi.');
        }
        $this->kembali();
    }

    public function hapus()
    {
        $this->jaga();
        $this->cekCsrf();

        $id = (int) ($_POST['id'] ?? 0);

        try {
            if ($id <= 0 || !$this->model->cari($id)) {
                $this->gagal('Kategori tidak ditemukan.');
            } elseif ($this->model->hapus($id)) {
                $_SESSION['flash_sukses'] = 'Kategori berhasil dihapus.';
            } else {
                $this->gagal('Kategori gagal dihapus.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->gagal('Kategori tidak dapat dihapus karena masih digunakan.');
        }
        $this->kembali();
    }
}
