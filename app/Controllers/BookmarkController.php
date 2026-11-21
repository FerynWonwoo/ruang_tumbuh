<?php

if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

class BookmarkController
{
    public const JUDUL_MIN = 3;
    public const JUDUL_MAX = 150;
    public const KATEGORI_MAX = 50;
    public const PENYELENGGARA_MAX = 100;
    public const DESKRIPSI_MAX = 1000;
    public const LINK_MAX = 500;

    private Bookmark $bookmarkModel;

    public function __construct(Bookmark $bookmarkModel)
    {
        $this->bookmarkModel = $bookmarkModel;
    }

    public static function aman($teks): string
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function panjang(string $teks): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($teks, 'UTF-8');
        }
        return (int) preg_match_all('/./us', $teks);
    }

    public static function resolvePaths(): array
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/app/Views/bookmark.php');

        if (preg_match('#^(.*?)/app/Views/[^/]+$#i', $scriptName, $matches)) {
            $baseUrl = rtrim($matches[1], '/');
        } else {
            $baseUrl = rtrim(dirname($scriptName), '/');
            if ($baseUrl === '.') {
                $baseUrl = '';
            }
        }

        return [
            'baseUrl'  => $baseUrl,
            'loginUrl' => $baseUrl . '/',
            'assetUrl' => $baseUrl . '/public',
            'viewsUrl' => $baseUrl . '/app/Views',
            'selfUrl'  => $baseUrl . '/app/Views/bookmark.php',
        ];
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    private function requireAuth(string $loginUrl): array
    {
        if (empty($_SESSION['user_id']) && empty($_SESSION['username'])) {
            header('Location: ' . $loginUrl, true, 302);
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $username = trim((string) ($_SESSION['username'] ?? ''));

        if ($userId <= 0 && $username !== '') {
            $userRow = $this->bookmarkModel->findUserByUsername($username);
            if ($userRow) {
                $userId = (int) $userRow['id'];
                $_SESSION['user_id'] = $userId;
                $_SESSION['nama'] = $userRow['nama'] ?? $username;
                $_SESSION['role'] = $userRow['role'] ?? 'user';
            }
        }

        if ($userId <= 0) {
            session_unset();
            session_destroy();
            header('Location: ' . $loginUrl, true, 302);
            exit;
        }

        return [
            'id'       => $userId,
            'username' => $username,
            'nama'     => (string) ($_SESSION['nama'] ?? $username),
            'role'     => (string) ($_SESSION['role'] ?? 'user'),
        ];
    }

    private function validasiInput(array $in): array
    {
        $data = [
            'judul'         => trim((string) ($in['judul'] ?? '')),
            'kategori'      => trim((string) ($in['kategori'] ?? '')),
            'penyelenggara' => trim((string) ($in['penyelenggara'] ?? '')),
            'deadline'      => trim((string) ($in['deadline'] ?? '')),
            'deskripsi'     => trim((string) ($in['deskripsi'] ?? '')),
            'link'          => trim((string) ($in['link'] ?? '')),
        ];

        if ($data['judul'] === '') {
            return ['Judul program wajib diisi.', $data];
        }
        if (self::panjang($data['judul']) < self::JUDUL_MIN) {
            return ['Judul program minimal ' . self::JUDUL_MIN . ' karakter.', $data];
        }
        if (self::panjang($data['judul']) > self::JUDUL_MAX) {
            return ['Judul program maksimal ' . self::JUDUL_MAX . ' karakter.', $data];
        }

        if ($data['kategori'] === '') {
            return ['Kategori program wajib diisi.', $data];
        }
        if (self::panjang($data['kategori']) > self::KATEGORI_MAX) {
            return ['Kategori maksimal ' . self::KATEGORI_MAX . ' karakter.', $data];
        }

        if (self::panjang($data['penyelenggara']) > self::PENYELENGGARA_MAX) {
            return ['Nama penyelenggara maksimal ' . self::PENYELENGGARA_MAX . ' karakter.', $data];
        }

        if ($data['deadline'] !== '') {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data['deadline'], $m)) {
                return ['Format tanggal tenggat tidak valid (gunakan format YYYY-MM-DD).', $data];
            }
            $tahun = (int) $m[1];
            $bulan = (int) $m[2];
            $hari  = (int) $m[3];
            if (!checkdate($bulan, $hari, $tahun)) {
                return ['Tanggal tenggat tidak ditemukan dalam kalender.', $data];
            }
            if ($tahun < 2000 || $tahun > 2100) {
                return ['Tahun pada batas tenggat tidak wajar (harus antara 2000 - 2100).', $data];
            }
        }

        if ($data['link'] !== '') {
            if (self::panjang($data['link']) > self::LINK_MAX) {
                return ['Link pendaftaran maksimal ' . self::LINK_MAX . ' karakter.', $data];
            }
            if (!preg_match('#^https?://#i', $data['link']) || !filter_var($data['link'], FILTER_VALIDATE_URL)) {
                return ['Format link pendaftaran tidak valid. Pastikan diawali http:// atau https://.', $data];
            }
        }

        if (self::panjang($data['deskripsi']) > self::DESKRIPSI_MAX) {
            return ['Deskripsi program maksimal ' . self::DESKRIPSI_MAX . ' karakter.', $data];
        }

        return [null, $data];
    }

    private function formatItem(array $row): array
    {
        $rawDeadline = trim((string) ($row['deadline'] ?? ''));
        $ts = $rawDeadline !== '' ? strtotime($rawDeadline) : false;

        $status = 'Aktif';
        $statusClass = 'active';
        if ($ts !== false) {
            if ($ts < strtotime('today')) {
                $status = 'Berakhir';
                $statusClass = 'expired';
            } elseif ($ts <= strtotime('+7 days')) {
                $status = 'Mendekati Tenggat';
                $statusClass = 'warning';
            }
        }

        $bulan = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $deadlineTampil = '-';
        if ($ts !== false) {
            $deadlineTampil = date('d', $ts) . ' ' . ($bulan[(int) date('n', $ts)] ?? date('M', $ts)) . ' ' . date('Y', $ts);
        }

        $rawLink = trim((string) ($row['link'] ?? ''));
        $safeLink = ($rawLink !== '' && preg_match('#^https?://#i', $rawLink)) ? $rawLink : '';

        return [
            'id'            => (int) $row['id'],
            'program_id'    => $row['program_id'] ?? null,
            'judul'         => (string) ($row['judul'] ?? ''),
            'kategori'      => (string) ($row['kategori'] ?? ''),
            'penyelenggara' => (string) ($row['penyelenggara'] ?? ''),
            'deadline'      => $deadlineTampil,
            'deadline_raw'  => $ts !== false ? date('Y-m-d', $ts) : '',
            'status'        => $status,
            'status_class'  => $statusClass,
            'deskripsi'     => (string) ($row['deskripsi'] ?? ''),
            'link'          => $safeLink,
            'sumber'        => !empty($row['program_id']) ? 'Katalog' : 'Manual',
        ];
    }

    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->sendSecurityHeaders();
        $paths = self::resolvePaths();
        $user = $this->requireAuth($paths['loginUrl']);

        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($user['id'], $paths['selfUrl']);
            return;
        }

        $pesan = $_SESSION['pesan_bookmark'] ?? '';
        $error = $_SESSION['error_bookmark'] ?? '';
        $old   = $_SESSION['old_bookmark'] ?? null;
        unset($_SESSION['pesan_bookmark'], $_SESSION['error_bookmark'], $_SESSION['old_bookmark']);

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $filterKategori = trim((string) ($_GET['kategori'] ?? 'Semua'));

        try {
            $semuaRows = $this->bookmarkModel->getAllByUser($user['id']);
            $filteredRows = ($keyword !== '' || ($filterKategori !== '' && $filterKategori !== 'Semua'))
                ? $this->bookmarkModel->getAllByUser($user['id'], $keyword, $filterKategori)
                : $semuaRows;
        } catch (Throwable $e) {
            error_log('Bookmark load error: ' . $e->getMessage());
            $semuaRows = [];
            $filteredRows = [];
            $error = 'Gagal memuat data bookmark dari database.';
        }

        $daftarBookmark = array_map([$this, 'formatItem'], $filteredRows);
        $semuaItem = array_map([$this, 'formatItem'], $semuaRows);

        $totalTersimpan = count($semuaItem);
        $kategoriFilter = array_values(array_unique(array_filter(array_column($semuaItem, 'kategori'))));

        $kategoriSaran = $kategoriFilter;
        try {
            if (file_exists(__DIR__ . '/../../includes/bootcamp_store.php')) {
                require_once __DIR__ . '/../../includes/bootcamp_store.php';
                foreach (bootcamp_load() as $bc) {
                    if (!empty($bc['kategori'])) {
                        $kategoriSaran[] = $bc['kategori'];
                    }
                }
            }
        } catch (Throwable $e) {
            // Abaikan jika katalog belum tersedia
        }
        $kategoriSaran = array_values(array_unique(array_merge($kategoriSaran, ['Beasiswa', 'Bootcamp', 'Magang', 'Lomba', 'Karier', 'Beasiswa'])));

        if (!defined('BOOKMARK_MVC_RENDER')) {
            define('BOOKMARK_MVC_RENDER', true);
        }

        require __DIR__ . '/../Views/bookmark.php';
    }

    private function handlePost(int $userId, string $redirectUrl): void
    {
        $token = $_POST['csrf'] ?? '';
        $aksi  = trim((string) ($_POST['aksi'] ?? ''));
        $id    = (int) ($_POST['id'] ?? 0);

        if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            $_SESSION['error_bookmark'] = 'Sesi formulir tidak valid atau telah kedaluwarsa. Silakan coba lagi.';
            header('Location: ' . $redirectUrl, true, 303);
            exit;
        }

        try {
            if ($aksi === 'tambah' || $aksi === 'ubah') {
                [$err, $data] = $this->validasiInput($_POST);

                if ($err !== null) {
                    $_SESSION['error_bookmark'] = $err;
                    $_SESSION['old_bookmark'] = $data + ['id' => $id, 'aksi' => $aksi];
                } elseif ($aksi === 'tambah') {
                    if ($this->bookmarkModel->create($userId, $data)) {
                        $_SESSION['pesan_bookmark'] = 'Bookmark baru berhasil ditambahkan ke daftar simpanan.';
                    } else {
                        $_SESSION['error_bookmark'] = 'Gagal menyimpan bookmark ke database.';
                    }
                } else {
                    if ($id <= 0 || !$this->bookmarkModel->findById($id, $userId)) {
                        $_SESSION['error_bookmark'] = 'Data bookmark tidak ditemukan atau bukan milik akun Anda.';
                    } elseif ($this->bookmarkModel->update($id, $userId, $data)) {
                        $_SESSION['pesan_bookmark'] = 'Perubahan bookmark berhasil disimpan.';
                    } else {
                        $_SESSION['error_bookmark'] = 'Gagal memperbarui data bookmark.';
                    }
                }
            } elseif ($aksi === 'hapus') {
                if ($id <= 0 || !$this->bookmarkModel->findById($id, $userId)) {
                    $_SESSION['error_bookmark'] = 'Data bookmark tidak ditemukan atau sudah dihapus.';
                } elseif ($this->bookmarkModel->delete($id, $userId)) {
                    $_SESSION['pesan_bookmark'] = 'Bookmark berhasil dihapus dari daftar simpanan.';
                } else {
                    $_SESSION['error_bookmark'] = 'Gagal menghapus bookmark.';
                }
            } else {
                $_SESSION['error_bookmark'] = 'Aksi tidak dikenali.';
            }
        } catch (Throwable $e) {
            error_log('Bookmark POST error: ' . $e->getMessage());
            $_SESSION['error_bookmark'] = 'Terjadi kesalahan pada server saat memproses data.';
        }

        header('Location: ' . $redirectUrl, true, 303);
        exit;
    }
}