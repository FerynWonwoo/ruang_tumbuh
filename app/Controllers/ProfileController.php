<?php

class ProfileController
{
    const PILIHAN_BIDANG = [
        'Teknologi Informasi', 'UI/UX Design', 'Data & Analitik',
        'Bisnis & Manajemen', 'Pemasaran Digital', 'Pendidikan',
        'Desain & Kreatif'
    ];

    const PILIHAN_KATEGORI = [
        'Lowongan Kerja', 'Magang', 'Bootcamp', 'Lomba', 'Beasiswa'
    ];

    const PILIHAN_STATUS = [
        'Mahasiswa', 'Pelajar', 'Fresh Graduate',
        'Pencari Kerja', 'Profesional'
    ];

    // Kolom yang dihitung untuk "Kelengkapan profil"
    // (nama dan email selalu ada dari pendaftaran, jadi tidak dihitung)
    const ITEM_KELENGKAPAN = [
        'foto'      => 'Foto profil',
        'telepon'   => 'Nomor handphone',
        'domisili'  => 'Domisili',
        'institusi' => 'Kampus / sekolah / instansi',
        'bio'       => 'Tentang saya',
        'bidang'    => 'Bidang minat',
        'kategori'  => 'Kategori peluang'
    ];

    private $model;

    public function __construct(Profile $model)
    {
        $this->model = $model;
    }

    // ---------------------------------------------------------
    // PROTEKSI HALAMAN (dipanggil sebelum menyentuh database)
    // ---------------------------------------------------------
    public static function pastikanLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Cegah browser menyimpan halaman (tombol Back setelah logout)
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

        if (empty($_SESSION['user_id'])) {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 3)), '/');
            header('Location: ' . $base . '/');
            exit;
        }

        return (int) $_SESSION['user_id'];
    }

    // ---------------------------------------------------------
    // ALUR UTAMA: mengembalikan data untuk ditampilkan view
    // ---------------------------------------------------------
    public function handle($userId)
    {
        if (empty($_SESSION['csrf_profil'])) {
            $_SESSION['csrf_profil'] = bin2hex(random_bytes(32));
        }

        // Sisa versi lama (profil disimpan di sesi) tidak dipakai lagi
        unset($_SESSION['profil']);

        $errors = [];
        $sukses = $_SESSION['pesan_profil'] ?? '';
        unset($_SESSION['pesan_profil']);

        $edit = (($_GET['edit'] ?? '') === '1');

        try {
            $profil = $this->muat($userId);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf'] ?? '';

            if (!is_string($token) || !hash_equals($_SESSION['csrf_profil'], $token)) {
                $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
            } elseif (($_POST['aksi'] ?? '') === 'hapus_profil') {
                $this->hapus($userId);
            } else {
                $edit = true;
                $profil = $this->simpan($userId, $profil, $errors);
            }
        }

        // Kelengkapan dihitung dari data yang tersimpan (bukan dari isian form)
        $tersimpan = $profil;
        if ($errors) {
            try {
                $tersimpan = $this->muat($userId);
            } catch (Throwable $e) {
                $this->galatServer($e);
            }
        }

        $belum = [];
        $terisi = 0;
        foreach (self::ITEM_KELENGKAPAN as $kolom => $label) {
            if (!empty($tersimpan[$kolom])) {
                $terisi++;
            } else {
                $belum[] = $label;
            }
        }
        $kelengkapan = (int) round($terisi / count(self::ITEM_KELENGKAPAN) * 100);

        $fotoUrl = $this->urlFoto($profil['foto']);

        return [
            'profil'          => $profil,
            'errors'          => $errors,
            'sukses'          => $sukses,
            'edit'            => $edit,
            'kelengkapan'     => $kelengkapan,
            'belum'           => $belum,
            'fotoUrl'         => $fotoUrl,
            'pilihanBidang'   => self::PILIHAN_BIDANG,
            'pilihanKategori' => self::PILIHAN_KATEGORI,
            'pilihanStatus'   => self::PILIHAN_STATUS,
        ];
    }

    // ---------------------------------------------------------
    // MEMUAT DATA
    // ---------------------------------------------------------
    private function muat($userId)
    {
        $baris = $this->model->ambil($userId);

        if (!$baris) {
            // Akun sudah tidak ada: akhiri sesi dan kembali ke login
            $_SESSION = [];
            session_destroy();
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 3)), '/');
            header('Location: ' . $base . '/');
            exit;
        }

        return [
            'nama'      => (string) $baris['nama'],
            'email'     => (string) ($baris['email'] ?? ''),
            'telepon'   => (string) ($baris['telepon'] ?? ''),
            'domisili'  => (string) ($baris['domisili'] ?? ''),
            'institusi' => (string) ($baris['institusi'] ?? ''),
            'status'    => ($baris['status'] ?? '') !== '' ? $baris['status'] : 'Mahasiswa',
            'bio'       => (string) ($baris['bio'] ?? ''),
            'foto'      => (string) ($baris['foto'] ?? ''),
            'bidang'    => $this->daftar($baris['bidang'] ?? '', self::PILIHAN_BIDANG),
            'kategori'  => $this->daftar($baris['kategori'] ?? '', self::PILIHAN_KATEGORI),
        ];
    }

    // Mengubah JSON dari database menjadi array pilihan yang valid
    private function daftar($json, array $pilihan)
    {
        $data = json_decode((string) $json, true);

        if (!is_array($data)) {
            return [];
        }

        return array_values(array_filter(
            $pilihan,
            function ($item) use ($data) {
                return in_array($item, $data, true);
            }
        ));
    }

    // ---------------------------------------------------------
    // SIMPAN (create + update) DENGAN VALIDASI
    // ---------------------------------------------------------
    private function simpan($userId, array $profil, array &$errors)
    {
        $fotoLama = $profil['foto'];

        // 1. Ambil isian dari form
        foreach (['nama', 'email', 'telepon', 'domisili', 'institusi', 'status', 'bio'] as $kolom) {
            $profil[$kolom] = is_string($_POST[$kolom] ?? null)
                ? trim($_POST[$kolom])
                : '';
        }

        foreach (['bidang' => self::PILIHAN_BIDANG, 'kategori' => self::PILIHAN_KATEGORI] as $kolom => $pilihan) {
            $nilai = $_POST[$kolom] ?? [];
            $profil[$kolom] = [];

            if (!is_array($nilai)) {
                $errors[] = 'Format pilihan minat tidak valid.';
                continue;
            }

            foreach ($nilai as $item) {
                if (!is_string($item) || !in_array($item, $pilihan, true)) {
                    $errors[] = 'Terdapat pilihan minat yang tidak tersedia.';
                } elseif (!in_array($item, $profil[$kolom], true)) {
                    $profil[$kolom][] = $item;
                }
            }
        }

        // 2. Validasi
        if ($profil['nama'] === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($profil['nama'], 'UTF-8') > 100) {
            $errors[] = 'Nama lengkap maksimal 100 karakter.';
        }

        if ($profil['email'] === '') {
            $errors[] = 'Alamat email wajib diisi.';
        } elseif (
            mb_strlen($profil['email'], 'UTF-8') > 100 ||
            !filter_var($profil['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Format email tidak valid (maksimal 100 karakter).';
        }

        if (
            $profil['telepon'] !== '' &&
            !preg_match('/^\+?[0-9 ()-]{8,20}$/', $profil['telepon'])
        ) {
            $errors[] = 'Nomor handphone harus 8-20 karakter: angka, spasi, +, tanda kurung, atau tanda hubung.';
        }

        if (!in_array($profil['status'], self::PILIHAN_STATUS, true)) {
            $errors[] = 'Pilih status yang tersedia.';
        }

        if (mb_strlen($profil['domisili'], 'UTF-8') > 120) {
            $errors[] = 'Domisili maksimal 120 karakter.';
        }

        if (mb_strlen($profil['institusi'], 'UTF-8') > 150) {
            $errors[] = 'Kampus / sekolah / instansi maksimal 150 karakter.';
        }

        if (mb_strlen($profil['bio'], 'UTF-8') > 500) {
            $errors[] = 'Tentang saya maksimal 500 karakter.';
        }

        // 3. Email tidak boleh sama dengan akun lain
        if (!$errors) {
            try {
                if ($this->model->emailDipakaiLain($profil['email'], $userId)) {
                    $errors[] = 'Email ini sudah dipakai akun lain.';
                }
            } catch (Throwable $e) {
                $this->galatServer($e);
            }
        }

        // 4. Foto (hanya diproses jika isian lain sudah valid)
        $fotoBaru = '';
        if (!$errors && isset($_FILES['foto'])) {
            $fotoBaru = $this->unggahFoto($_FILES['foto'], $errors);

            if ($fotoBaru !== '') {
                $profil['foto'] = $fotoBaru;
            }
        }

        // 5. Simpan ke database
        if (!$errors) {
            try {
                $this->model->simpan($userId, $profil['nama'], $profil['email'], $profil);

                // Foto lama dihapus hanya jika ada foto baru
                if ($fotoBaru !== '' && $fotoLama !== '') {
                    $this->hapusFileFoto($fotoLama);
                }

                // Dashboard dan menu membaca nama dari sesi
                $_SESSION['nama'] = $profil['nama'];

                $_SESSION['pesan_profil'] = 'Profil dan minat kamu berhasil disimpan.';
                header('Location: profile.php');
                exit;
            } catch (mysqli_sql_exception $e) {
                $this->hapusFileFoto($fotoBaru);
                $profil['foto'] = $fotoLama;

                if ((int) $e->getCode() === 1062) {
                    $errors[] = 'Email ini sudah dipakai akun lain.';
                } else {
                    error_log('Profil: ' . $e->getMessage());
                    $errors[] = 'Profil gagal disimpan karena kesalahan server. Silakan coba lagi.';
                }
            } catch (Throwable $e) {
                $this->hapusFileFoto($fotoBaru);
                $profil['foto'] = $fotoLama;

                error_log('Profil: ' . $e->getMessage());
                $errors[] = 'Profil gagal disimpan karena kesalahan server. Silakan coba lagi.';
            }
        } else {
            // Gagal validasi: foto lama tetap dipakai
            $profil['foto'] = $fotoLama;
        }

        return $profil;
    }

    // ---------------------------------------------------------
    // HAPUS DATA PROFIL (akun dan login tetap ada)
    // ---------------------------------------------------------
    private function hapus($userId)
    {
        try {
            $foto = $this->model->hapusData($userId);
        } catch (Throwable $e) {
            $this->galatServer($e);
        }

        $this->hapusFileFoto($foto);

        $_SESSION['pesan_profil'] = 'Data profil berhasil dihapus. Akun dan login kamu tetap aktif.';
        header('Location: profile.php');
        exit;
    }

    // ---------------------------------------------------------
    // FOTO
    // ---------------------------------------------------------
    private function unggahFoto(array $foto, array &$errors)
    {
        if (!isset($foto['error']) || !is_int($foto['error'])) {
            $errors[] = 'Format upload foto tidak valid.';
            return '';
        }

        if ($foto['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($foto['error'] === UPLOAD_ERR_INI_SIZE || $foto['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'Ukuran foto terlalu besar. Maksimal 2 MB.';
            return '';
        }

        if ($foto['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Foto gagal diunggah. Coba lagi.';
            return '';
        }

        if (!is_uploaded_file($foto['tmp_name']) || filesize($foto['tmp_name']) > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
            return '';
        }

        $info = @getimagesize($foto['tmp_name']);
        $format = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!$info || !isset($format[$info['mime']])) {
            $errors[] = 'Gunakan foto berformat JPG, PNG, atau WEBP.';
            return '';
        }

        if ($info[0] > 6000 || $info[1] > 6000) {
            $errors[] = 'Dimensi foto maksimal 6000 x 6000 piksel.';
            return '';
        }

        $folder = __DIR__ . '/../uploads/profile/';

        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            $errors[] = 'Folder penyimpanan foto tidak dapat dibuat.';
            return '';
        }

        $namaFile = bin2hex(random_bytes(16)) . '.' . $format[$info['mime']];

        if (!move_uploaded_file($foto['tmp_name'], $folder . $namaFile)) {
            $errors[] = 'Foto gagal disimpan.';
            return '';
        }

        return $namaFile;
    }

    // Hanya file berpola nama buatan sistem yang boleh dihapus
    private function hapusFileFoto($namaFile)
    {
        $namaFile = basename((string) $namaFile);

        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $namaFile)) {
            return;
        }

        $lokasi = __DIR__ . '/../uploads/profile/' . $namaFile;

        if (is_file($lokasi)) {
            unlink($lokasi);
        }
    }

    private function urlFoto($namaFile)
    {
        $namaFile = basename((string) $namaFile);

        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $namaFile)) {
            return '';
        }

        return '../uploads/profile/' . $namaFile;
    }

    private function galatServer(Throwable $e)
    {
        error_log('Profil: ' . $e->getMessage());

        http_response_code(500);
        echo 'Data profil tidak dapat diproses saat ini. '
            . 'Pastikan migration database sudah dijalankan (php database/migrate.php), lalu muat ulang halaman.';
        exit;
    }
}
