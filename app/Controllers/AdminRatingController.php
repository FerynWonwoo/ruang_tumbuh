<?php

class AdminRatingController
{
    private $ratingModel;

    public function __construct($ratingModel)
    {
        $this->ratingModel = $ratingModel;
    }

    private function cekAdmin()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (empty($_SESSION['username'])) {
            header('Location: ./');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /ruang_tumbuh/index.php');
            exit;
        }
    }

    private function cekCSRF(): bool
    {
        $token = $_POST['csrf'] ?? '';

        return is_string($token)
            && !empty($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }

    public function tampilkan()
    {
        $this->cekAdmin();

        $filter = $_GET['status'] ?? 'all';

        if (!in_array($filter, Rating::STATUS, true) && $filter !== 'all') {
            $filter = 'all';
        }

        $data = $this->ratingModel->untukAdmin($filter);

        $daftar = $data['daftar'];
        $jumlah = $data['jumlah'];

        $tab = [
            'all' => 'Semua',
            'pending' => 'Menunggu',
            'approved' => 'Dipublikasikan',
            'rejected' => 'Disembunyikan'
        ];

        $statusLabel = [
            'pending' => 'Menunggu',
            'approved' => 'Dipublikasikan',
            'rejected' => 'Disembunyikan'
        ];

        $nama_user = $_SESSION['nama'] ?? $_SESSION['username'];

        $pesan = $_SESSION['admin_rating_pesan'] ?? '';
        $error = $_SESSION['admin_rating_error'] ?? '';

        unset(
            $_SESSION['admin_rating_pesan'],
            $_SESSION['admin_rating_error']
        );

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        $scriptName = $_SERVER['SCRIPT_NAME'];

        if (basename($scriptName) === 'index.php') {
            $basePath = rtrim(dirname($scriptName), '/\\');
        } else {
            $basePath = dirname(dirname(dirname($scriptName)));
        }

        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        require __DIR__ . '/../Views/adminrating.php';
    }

    public function moderasi()
    {
        $this->cekAdmin();

        if (!$this->cekCSRF()) {
            $_SESSION['admin_rating_error'] = 'Sesi tidak valid.';
            header('Location: /ruang_tumbuh/app/Views/rating.php');
            exit;
        }

        $ids = [];
        $aksi = '';
        $filter = $_POST['filter'] ?? 'all';
        $kembali = in_array($filter, Rating::STATUS, true)
            ? '?page=admin-rating&status=' . $filter
            : '?page=admin-rating';

        if (!empty($_POST['satu'])) {
            $bagian = explode(':', $_POST['satu'], 2);

            if (count($bagian) === 2) {
                $aksi = $bagian[0];
                $ids[] = $bagian[1];
            }
        } else {
            $aksi = $_POST['massa'] ?? '';
            $ids = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
        }

        $aksiValid = ['publish', 'hide', 'hapus'];

        if (!in_array($aksi, $aksiValid, true)) {
            $_SESSION['admin_rating_error'] = 'Aksi moderasi tidak valid.';
            header('Location: ' . $kembali);
            exit;
        }

        $ids = array_values(array_unique(array_filter(
            $ids,
            function ($id) {
                return is_string($id)
                    && preg_match('/^[a-f0-9]{16}$/', $id);
            }
        )));

        if (empty($ids)) {
            $_SESSION['admin_rating_error'] = 'Pilih ulasan terlebih dahulu.';
            header('Location: ' . $kembali);
            exit;
        }

        $jumlah = $this->ratingModel->moderasi($ids, $aksi);

        $_SESSION['admin_rating_pesan'] =
            $jumlah . ' ulasan berhasil diproses.';

        header('Location: ' . $kembali);
        exit;
    }
}