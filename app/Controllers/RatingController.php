
<?php

class RatingController
{
    private $ratingModel;

    public function __construct($ratingModel)
    {
        $this->ratingModel = $ratingModel;
    }


    // =========================================
    // VALIDASI PESERTA
    // =========================================

    private function cekLogin(): string
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (empty($_SESSION['username'])) {
        header('Location: /ruang_tumbuh/index.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: /ruang_tumbuh/app/Views/adminrating.php');
        exit;
    }

    return $_SESSION['username'];
}


    // =========================================
    // VALIDASI CSRF
    // =========================================

    private function cekCSRF(): bool
    {
        $token = $_POST['csrf'] ?? '';

        return is_string($token)
            && !empty($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }


    // =========================================
    // MENAMPILKAN RATING
    // =========================================

    public function tampilkan()
    {
        $username = $this->cekLogin();

        // Membuat CSRF baru jika belum tersedia
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Menentukan base path untuk CSS dan JS
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$scriptName = $_SERVER['SCRIPT_NAME'];

if (basename($scriptName) === 'index.php') {
    $basePath = rtrim(dirname($scriptName), '/\\');
} else {
    $basePath = dirname(dirname(dirname($scriptName)));
}

if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

        $data = $this->ratingModel->untukUser($username);

        $daftarUlasan = $data['daftar'];
        $totalPublik = $data['publik'];
        $rataRating = $data['rata'];

        $statusLabel = [
            'pending' => 'Menunggu persetujuan',
            'approved' => 'Dipublikasikan',
            'rejected' => 'Disembunyikan admin'
        ];

        $pesan = $_SESSION['rating_pesan'] ?? '';
        $error = $_SESSION['rating_error'] ?? '';

        unset($_SESSION['rating_pesan'], $_SESSION['rating_error']);

        $formBaru = [
            'bootcamp' => '',
            'rating' => 0,
            'komentar' => ''
        ];

        $formEdit = [
            'id' => '',
            'bootcamp' => '',
            'rating' => 0,
            'komentar' => ''
        ];

        $modalBuka = false;

        // Isi ulang form jika penyimpanan sebelumnya gagal
        $lama = $_SESSION['rating_old'] ?? null;
        unset($_SESSION['rating_old']);

        if (is_array($lama)) {
            $isian = [
                'bootcamp' => (string) ($lama['bootcamp'] ?? ''),
                'rating' => (int) ($lama['rating'] ?? 0),
                'komentar' => (string) ($lama['komentar'] ?? '')
            ];

            if (($lama['id'] ?? '') !== '') {
                $formEdit = ['id' => (string) $lama['id']] + $isian;
                $modalBuka = true;
            } else {
                $formBaru = $isian;
            }
        }

                // Pilihan bootcamp diambil dari katalog (tabel program)
        $daftarBootcamp = $this->ratingModel->daftarBootcamp();
                // Datang dari tombol "Done" di katalog: isi bootcamp otomatis
        $pilihan = trim((string) ($_GET['bootcamp'] ?? ''));

        if ($pilihan !== '' && !$modalBuka && in_array($pilihan, $daftarBootcamp, true)) {

            $sudahAda = false;

            foreach ($daftarUlasan as $u) {
                if ($u['username'] === $username && $u['bootcamp'] === $pilihan) {
                    $formEdit = [
                        'id' => (string) $u['id'],
                        'bootcamp' => (string) $u['bootcamp'],
                        'rating' => (int) $u['rating'],
                        'komentar' => (string) $u['komentar']
                    ];
                    $modalBuka = true;
                    $sudahAda = true;
                    break;
                }
            }

            if (!$sudahAda && $formBaru['bootcamp'] === '') {
                $formBaru['bootcamp'] = $pilihan;
            }
        }
        $maxKomentar = Rating::MAX_KOMENTAR;

        require __DIR__ . '/../Views/rating.php';
    }


    // =========================================
    // MENYIMPAN ULASAN
    // =========================================

    public function simpan()
    {
        $username = $this->cekLogin();

        if (!$this->cekCSRF()) {
            $_SESSION['rating_error'] = 'Sesi tidak valid. Silakan coba kembali.';
            header('Location: ?page=rating');
            exit;
        }

        $id = trim($_POST['id'] ?? '');

        if ($id !== '') {
            $hasil = $this->ratingModel->update(
                $id,
                $_POST,
                $username
            );
        } else {
            $hasil = $this->ratingModel->simpan(
                $_POST,
                $username
            );
        }

        if ($hasil['ok']) {
            $_SESSION['rating_pesan'] = $hasil['pesan'];
        } else {
            $_SESSION['rating_error'] = $hasil['error'];
            $_SESSION['rating_old'] = [
                'id' => $id,
                'bootcamp' => (string) ($_POST['bootcamp'] ?? ''),
                'rating' => (int) ($_POST['rating'] ?? 0),
                'komentar' => (string) ($_POST['komentar'] ?? '')
            ];
        }

        header('Location: /ruang_tumbuh/app/Views/rating.php');
exit;
    }


    // =========================================
    // MENARIK ULASAN
    // =========================================

    public function tarik()
    {
        $username = $this->cekLogin();

        if (!$this->cekCSRF()) {
            $_SESSION['rating_error'] = 'Sesi tidak valid.';
            header('Location: ?page=rating');
            exit;
        }

        $id = trim($_POST['id'] ?? '');

        $hasil = $this->ratingModel->tarik(
            $id,
            $username
        );

        if ($hasil['ok']) {
            $_SESSION['rating_pesan'] = $hasil['pesan'];
        } else {
            $_SESSION['rating_error'] = $hasil['error'];
        }

header('Location: /ruang_tumbuh/app/Views/rating.php');
exit;
    }

        // Endpoint pencarian untuk combobox bootcamp (JSON)
    public function cari()
    {
        $this->cekLogin();

        header('Content-Type: application/json; charset=utf-8');

        $kata = trim((string) ($_GET['q'] ?? ''));

        echo json_encode(
            $this->ratingModel->cariBootcamp($kata),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
