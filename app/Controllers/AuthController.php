
<?php

class AuthController
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    // Menampilkan halaman login
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['role']);
            return;
        }

        $pesanError = '';
        $pesanSukses = '';
        $username = '';

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        if (isset($_GET['registered'])) {
            $pesanSukses = 'Pendaftaran berhasil. Silakan masuk dengan akun Anda.';
        }

        require __DIR__ . '/../Views/index.php';
    }

    // Memproses login
    public function login()
    {
        $pesanError = '';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $token = $_POST['csrf'] ?? '';

        if (
            !is_string($token) ||
            empty($_SESSION['csrf']) ||
            !hash_equals($_SESSION['csrf'], $token)
        ) {
            $pesanError = 'Sesi formulir tidak valid. Silakan coba kembali.';
        } else {
            $user = $this->userModel->login($username);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Perbarui hash jika diperlukan
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $this->userModel->updatePassword(
                        $user['id'],
                        password_hash($password, PASSWORD_DEFAULT)
                    );
                }

                unset($_SESSION['csrf']);

                $this->redirectByRole($user['role']);
                return;
            }

            $pesanError = 'Username atau password salah.';
        }

        $pesanSukses = '';
        require __DIR__ . '/../Views/index.php';
    }

    // Mengarahkan pengguna berdasarkan role
    private function redirectByRole($role)
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        if ($role === 'admin') {
            header("Location: $basePath/app/Views/admindashboard.php");
        } else {
            header("Location: $basePath/app/Views/dashboard.php");
        }

        exit;
    }


    // =====================================
    // MENAMPILKAN HALAMAN REGISTER
    // =====================================

    
public function registerForm()
{
    if (isset($_SESSION['user_id'])) {
        $this->redirectByRole($_SESSION['role']);
        return;
    }

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    // Inisialisasi variabel untuk View
    $pesanError = '';
    $pesanSukses = '';
    $namaLengkap = '';
    $username = '';
    $email = '';

    require __DIR__ . '/../Views/register.php';
}


    // =====================================
    // MEMPROSES REGISTER
    // =====================================

    public function register()
    {
        $pesanError = '';
        $pesanSukses = '';

        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $password = $_POST['password'] ?? '';
        $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

        $token = $_POST['csrf'] ?? '';

        if (
            !is_string($token) ||
            empty($_SESSION['csrf']) ||
            !hash_equals($_SESSION['csrf'], $token)
        ) {
            $pesanError = 'Sesi formulir tidak valid. Silakan coba kembali.';
        } elseif (
            $namaLengkap === '' ||
            $username === '' ||
            $email === '' ||
            $password === ''
        ) {
            $pesanError = 'Semua kolom wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)) {
            $pesanError = 'Username 3-50 karakter: huruf, angka, titik, atau garis bawah.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesanError = 'Format email tidak valid.';
        } elseif (strlen($password) < 8) {
            $pesanError = 'Kata sandi minimal 8 karakter.';
        } elseif ($password !== $konfirmasiPassword) {
            $pesanError = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            try {
                $hasil = $this->userModel->register(
                    $namaLengkap,
                    $username,
                    $email,
                    $password
                );

                if ($hasil === 'duplicate') {
                    $pesanError = 'Username atau email sudah terdaftar.';
                } elseif ($hasil) {
                    unset($_SESSION['csrf']);

                    header('Location: ' .
                        rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') .
                        '/?registered=1');
                    exit;
                } else {
                    $pesanError = 'Pendaftaran gagal. Silakan coba lagi.';
                }

            } catch (Throwable $e) {
                error_log($e->getMessage());
                $pesanError = 'Terjadi kesalahan pada server. Silakan coba lagi.';
            }
        }

        require __DIR__ . '/../Views/register.php';
    }

}
