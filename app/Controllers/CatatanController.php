<?php

if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

class CatatanController
{
    public const JUDUL_MIN = 3;
    public const JUDUL_MAX = 150;
    public const ISI_MIN = 5;
    public const ISI_MAX = 5000;
    public const CARI_MAX = 100;

    private Catatan $catatanModel;

    public function __construct(Catatan $catatanModel)
    {
        $this->catatanModel = $catatanModel;
    }

    /**
     * Helper sanitasi output untuk mencegah XSS.
     */
    public static function aman($teks): string
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Menghitung panjang karakter UTF-8 secara akurat.
     */
    public static function panjang(string $teks): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($teks, 'UTF-8');
        }

        return (int) preg_match_all('/./us', $teks);
    }

    /**
     * Format tanggal MySQL ke format Bahasa Indonesia yang rapi.
     */
    public static function formatTanggal(?string $datetime, bool $denganJam = true): string
    {
        if ($datetime === null || trim($datetime) === '') {
            return 'Belum ada';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return self::aman($datetime);
        }

        $bulan = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $hari = date('d', $timestamp);
        $namaBulan = $bulan[(int) date('n', $timestamp)] ?? date('M', $timestamp);
        $tahun = date('Y', $timestamp);

        if ($denganJam) {
            return $hari . ' ' . $namaBulan . ' ' . $tahun . ' · ' . date('H:i', $timestamp);
        }

        return $hari . ' ' . $namaBulan . ' ' . $tahun;
    }

    /**
     * Menghitung URL dasar aplikasi agar aset & link berfungsi di berbagai environment (XAMPP/Laragon/vhost).
     */
    public static function resolvePaths(): array
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/app/Views/catatan.php');

        if (preg_match('#^(.*?)/app/Views/[^/]+$#i', $scriptName, $matches)) {
            $baseUrl = rtrim($matches[1], '/');
        } else {
            $baseUrl = rtrim(dirname($scriptName), '/');
            if ($baseUrl === '.') {
                $baseUrl = '';
            }
        }

        return [
            'baseUrl' => $baseUrl,
            'loginUrl' => $baseUrl . '/',
            'assetUrl' => $baseUrl . '/public',
            'viewsUrl' => $baseUrl . '/app/Views',
            'selfUrl' => $scriptName,
        ];
    }

    /**
     * Entry point utama fitur catatan (GET & POST).
     */
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
            $aksi = is_string($_POST['aksi'] ?? null) ? trim($_POST['aksi']) : 'simpan';

            if ($aksi === 'hapus') {
                $this->hapus($user, $paths);
                return;
            }

            $this->simpan($user, $paths);
            return;
        }

        $this->index($user, $paths);
    }

    /**
     * Menampilkan halaman catatan beserta daftar catatan, pencarian, filter, dan mode edit.
     */
    private function index(
        array $user,
        array $paths,
        array $formOverride = [],
        array $errors = [],
        array $fieldErrors = [],
        bool $isEditOverride = false
    ): void {
        $pesanSukses = '';
        if (!empty($_SESSION['catatan_sukses']) && is_string($_SESSION['catatan_sukses'])) {
            $pesanSukses = $_SESSION['catatan_sukses'];
            unset($_SESSION['catatan_sukses']);
        }

        if (!empty($_SESSION['catatan_error']) && is_string($_SESSION['catatan_error'])) {
            $errors[] = $_SESSION['catatan_error'];
            unset($_SESSION['catatan_error']);
        }

        $form = [
            'id' => '',
            'judul' => '',
            'kategori' => '',
            'isi' => '',
        ];
        $isEditMode = false;

        if (!empty($formOverride)) {
            $form = array_merge($form, $formOverride);
            $isEditMode = $isEditOverride;
        } elseif (isset($_GET['edit'])) {
            $editId = $this->validasiId($_GET['edit']);

            if ($editId === null) {
                $errors[] = 'ID catatan yang ingin diedit tidak valid.';
            } else {
                try {
                    $catatanEdit = $this->catatanModel->getByIdForUser($editId, $user['id']);
                    if ($catatanEdit === null) {
                        $errors[] = 'Catatan yang ingin diedit tidak ditemukan atau bukan milikmu.';
                    } else {
                        $form = [
                            'id' => (string) $catatanEdit['id'],
                            'judul' => (string) $catatanEdit['judul'],
                            'kategori' => (string) $catatanEdit['kategori'],
                            'isi' => (string) $catatanEdit['isi'],
                        ];
                        $isEditMode = true;
                    }
                } catch (Throwable $e) {
                    error_log('Catatan edit load error: ' . $e->getMessage());
                    $errors[] = 'Terjadi gangguan saat memuat data catatan untuk diedit.';
                }
            }
        }

        // Validasi parameter pencarian (GET ?cari=)
        $keyword = '';
        if (isset($_GET['cari'])) {
            if (!is_string($_GET['cari']) || str_contains($_GET['cari'], "\0")) {
                $errors[] = 'Format kata kunci pencarian tidak valid.';
            } else {
                $rawKeyword = trim($_GET['cari']);
                if (self::panjang($rawKeyword) > self::CARI_MAX) {
                    $errors[] = 'Kata kunci pencarian maksimal ' . self::CARI_MAX . ' karakter.';
                    $keyword = function_exists('mb_substr')
                        ? mb_substr($rawKeyword, 0, self::CARI_MAX, 'UTF-8')
                        : substr($rawKeyword, 0, self::CARI_MAX);
                } else {
                    $keyword = $rawKeyword;
                }
            }
        }

        // Validasi parameter filter kategori (GET ?kategori=)
        $filterKategori = '';
        if (isset($_GET['kategori']) && $_GET['kategori'] !== '') {
            if (!is_string($_GET['kategori']) || !in_array($_GET['kategori'], Catatan::KATEGORI_VALID, true)) {
                $errors[] = 'Filter kategori tidak valid.';
            } else {
                $filterKategori = $_GET['kategori'];
            }
        }

        $catatanTampil = [];
        $statistik = [
            'total' => 0,
            'terakhir' => null,
            'jumlah_kategori' => 0,
        ];

        try {
            $catatanTampil = $this->catatanModel->getAllByUser($user['id'], $keyword, $filterKategori);
            $statistik = $this->catatanModel->getStatsByUser($user['id']);
        } catch (Throwable $e) {
            error_log('Catatan list error: ' . $e->getMessage());
            $errors[] = 'Gagal memuat data catatan dari database. Silakan coba beberapa saat lagi.';
        }

        // Siapkan variabel untuk View
        $daftarKategori = Catatan::KATEGORI_VALID;
        $csrfToken = $_SESSION['csrf'];
        $displayName = $user['nama'] !== '' ? $user['nama'] : $user['username'];
        $displayRole = $user['role'] === 'admin' ? 'Admin' : 'Pengguna';
        $avatarInitials = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr(trim($displayName), 0, 2, 'UTF-8'), 'UTF-8')
            : strtoupper(substr(trim($displayName), 0, 2));
        if ($avatarInitials === '') {
            $avatarInitials = 'RT';
        }

        $tanggalTerakhir = self::formatTanggal($statistik['terakhir'], false);
        $baseUrl = $paths['baseUrl'];
        $assetUrl = $paths['assetUrl'];
        $viewsUrl = $paths['viewsUrl'];
        $selfUrl = $paths['selfUrl'];
        $logoutUrl = $viewsUrl . '/logout.php';
        $profileUrl = $viewsUrl . '/profile.php';

        if (!defined('CATATAN_MVC_RENDER')) {
            define('CATATAN_MVC_RENDER', true);
        }

        require __DIR__ . '/../Views/catatan.php';
    }

    /**
     * Memproses Tambah & Ubah Catatan (POST).
     */
    private function simpan(array $user, array $paths): void
    {
        $rawId = is_string($_POST['id'] ?? null) ? trim($_POST['id']) : '';
        $isEditMode = $rawId !== '';

        $formInput = [
            'id' => $rawId,
            'judul' => is_string($_POST['judul'] ?? null) ? trim($_POST['judul']) : '',
            'kategori' => is_string($_POST['kategori'] ?? null) ? trim($_POST['kategori']) : '',
            'isi' => is_string($_POST['isi'] ?? null) ? trim($_POST['isi']) : '',
        ];

        $errors = [];
        $fieldErrors = [];

        if (!$this->validasiCsrf()) {
            $errors[] = 'Sesi formulir tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman dan coba kembali.';
            $this->index($user, $paths, $formInput, $errors, $fieldErrors, $isEditMode);
            return;
        }

        $editId = null;
        if ($isEditMode) {
            $editId = $this->validasiId($rawId);
            if ($editId === null) {
                $errors[] = 'ID catatan tidak valid.';
                $this->index($user, $paths, $formInput, $errors, $fieldErrors, false);
                return;
            }
        }

        // Validasi format tipe data dan null-byte
        foreach (['judul', 'kategori', 'isi'] as $field) {
            if (!isset($_POST[$field]) || !is_string($_POST[$field]) || str_contains($_POST[$field], "\0")) {
                $errors[] = 'Format input ' . $field . ' tidak valid.';
                $fieldErrors[$field] = 'Format input tidak valid.';
            }
        }

        // Normalisasi newline pada isi
        $judul = preg_replace('/\s+/u', ' ', $formInput['judul']) ?? $formInput['judul'];
        $kategori = $formInput['kategori'];
        $isi = str_replace(["\r\n", "\r"], "\n", $formInput['isi']);

        $formInput['judul'] = $judul;
        $formInput['isi'] = $isi;

        // Validasi Judul
        if (!isset($fieldErrors['judul'])) {
            $lenJudul = self::panjang($judul);
            if ($judul === '') {
                $msg = 'Judul catatan wajib diisi.';
                $errors[] = $msg;
                $fieldErrors['judul'] = $msg;
            } elseif ($lenJudul < self::JUDUL_MIN) {
                $msg = 'Judul catatan minimal terdiri dari ' . self::JUDUL_MIN . ' karakter.';
                $errors[] = $msg;
                $fieldErrors['judul'] = $msg;
            } elseif ($lenJudul > self::JUDUL_MAX) {
                $msg = 'Judul catatan maksimal ' . self::JUDUL_MAX . ' karakter.';
                $errors[] = $msg;
                $fieldErrors['judul'] = $msg;
            } elseif (!preg_match('/[\p{L}\p{N}]/u', $judul)) {
                $msg = 'Judul catatan harus mengandung huruf atau angka yang jelas.';
                $errors[] = $msg;
                $fieldErrors['judul'] = $msg;
            }
        }

        // Validasi Kategori (whitelist)
        if (!isset($fieldErrors['kategori'])) {
            if ($kategori === '') {
                $msg = 'Kategori catatan wajib dipilih.';
                $errors[] = $msg;
                $fieldErrors['kategori'] = $msg;
            } elseif (!in_array($kategori, Catatan::KATEGORI_VALID, true)) {
                $msg = 'Kategori yang dipilih tidak valid. Pilih salah satu kategori yang tersedia.';
                $errors[] = $msg;
                $fieldErrors['kategori'] = $msg;
            }
        }

        // Validasi Isi
        if (!isset($fieldErrors['isi'])) {
            $lenIsi = self::panjang($isi);
            if ($isi === '') {
                $msg = 'Isi catatan wajib diisi.';
                $errors[] = $msg;
                $fieldErrors['isi'] = $msg;
            } elseif ($lenIsi < self::ISI_MIN) {
                $msg = 'Isi catatan minimal terdiri dari ' . self::ISI_MIN . ' karakter.';
                $errors[] = $msg;
                $fieldErrors['isi'] = $msg;
            } elseif ($lenIsi > self::ISI_MAX) {
                $msg = 'Isi catatan maksimal ' . number_format(self::ISI_MAX, 0, ',', '.') . ' karakter.';
                $errors[] = $msg;
                $fieldErrors['isi'] = $msg;
            } elseif (!preg_match('/[\p{L}\p{N}]/u', $isi)) {
                $msg = 'Isi catatan harus mengandung teks yang jelas (tidak hanya simbol).';
                $errors[] = $msg;
                $fieldErrors['isi'] = $msg;
            }
        }

        if (!empty($errors)) {
            $this->index($user, $paths, $formInput, $errors, $fieldErrors, $isEditMode);
            return;
        }

        try {
            if ($isEditMode && $editId !== null) {
                $updated = $this->catatanModel->update($editId, $user['id'], $judul, $kategori, $isi);
                if (!$updated) {
                    $errors[] = 'Catatan yang ingin diperbarui tidak ditemukan atau bukan milikmu.';
                    $this->index($user, $paths, $formInput, $errors, $fieldErrors, false);
                    return;
                }
                $_SESSION['catatan_sukses'] = 'Catatan berhasil disimpan.';
            } else {
                $this->catatanModel->create($user['id'], $judul, $kategori, $isi);
                $_SESSION['catatan_sukses'] = 'Catatan baru berhasil ditambahkan.';
            }

            $this->redirect($paths['selfUrl']);
        } catch (Throwable $e) {
            error_log('Catatan save error: ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan saat menyimpan catatan ke database. Silakan coba lagi.';
            $this->index($user, $paths, $formInput, $errors, $fieldErrors, $isEditMode);
        }
    }

    /**
     * Memproses Hapus Catatan (POST + CSRF + Ownership check).
     */
    private function hapus(array $user, array $paths): void
    {
        if (!$this->validasiCsrf()) {
            $_SESSION['catatan_error'] = 'Sesi tidak valid. Muat ulang halaman lalu coba hapus kembali.';
            $this->redirect($paths['selfUrl']);
            return;
        }

        $hapusId = $this->validasiId($_POST['id'] ?? null);
        if ($hapusId === null) {
            $_SESSION['catatan_error'] = 'ID catatan yang akan dihapus tidak valid.';
            $this->redirect($paths['selfUrl']);
            return;
        }

        try {
            $deleted = $this->catatanModel->delete($hapusId, $user['id']);
            if ($deleted) {
                $_SESSION['catatan_sukses'] = 'Catatan berhasil dihapus.';
            } else {
                $_SESSION['catatan_error'] = 'Catatan tidak ditemukan atau sudah dihapus.';
            }
        } catch (Throwable $e) {
            error_log('Catatan delete error: ' . $e->getMessage());
            $_SESSION['catatan_error'] = 'Terjadi kesalahan saat menghapus catatan. Silakan coba lagi.';
        }

        $this->redirect($paths['selfUrl']);
    }

    /**
     * Mengirim header keamanan dan anti-cache agar halaman tidak dapat dibuka kembali setelah logout.
     */
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

    /**
     * Memastikan pengguna benar-benar login melalui session aplikasi kelompok.
     */
    private function requireAuth(string $loginUrl): array
    {
        if (empty($_SESSION['user_id']) && !empty($_SESSION['username']) && is_string($_SESSION['username'])) {
            try {
                $dbUser = $this->catatanModel->findUserByUsername($_SESSION['username']);
                if ($dbUser !== null) {
                    $_SESSION['user_id'] = (int) $dbUser['id'];
                    $_SESSION['nama'] = (string) ($dbUser['nama'] ?? $dbUser['username']);
                    $_SESSION['role'] = (string) ($dbUser['role'] ?? 'user');
                }
            } catch (Throwable $e) {
                error_log('Catatan user sync error: ' . $e->getMessage());
            }
        }

        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['username']) ||
            !is_numeric($_SESSION['user_id']) ||
            (int) $_SESSION['user_id'] <= 0
        ) {
            $this->redirect($loginUrl, 302);
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'username' => (string) $_SESSION['username'],
            'nama' => is_string($_SESSION['nama'] ?? null) ? trim($_SESSION['nama']) : (string) $_SESSION['username'],
            'role' => is_string($_SESSION['role'] ?? null) ? $_SESSION['role'] : 'user',
        ];
    }

    private function validasiCsrf(): bool
    {
        $token = $_POST['csrf'] ?? '';
        return is_string($token)
            && !empty($_SESSION['csrf'])
            && is_string($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }

    private function validasiId($nilai): ?int
    {
        if (!is_string($nilai) && !is_int($nilai)) {
            return null;
        }

        $str = trim((string) $nilai);
        if (!preg_match('/^[1-9][0-9]{0,10}$/', $str)) {
            return null;
        }

        $intVal = filter_var($str, FILTER_VALIDATE_INT);
        return ($intVal !== false && $intVal > 0) ? (int) $intVal : null;
    }

    private function redirect(string $url, int $statusCode = 303): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Location: ' . $url, true, $statusCode);
        exit;
    }
}
