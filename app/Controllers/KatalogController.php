<?php
/**
 * Controller Katalog: menerima request, memeriksa login/role, memvalidasi input,
 * memanggil Model Katalog, lalu memilih template di app/Views/katalog/.
 */
require_once __DIR__ . '/../Models/Katalog.php';

class KatalogController
{
    private $model;

    private const URUT_LABEL = [
        'mulai-tercepat'    => 'Mulai tercepat',
        'mulai-terjauh'     => 'Mulai terjauh',
        'deadline-terdekat' => 'Deadline daftar terdekat',
        'nama'              => 'Nama A–Z',
    ];

    public function __construct(Katalog $model)
    {
        $this->model = $model;
    }

    /* ---------------- Helper ---------------- */

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function jagaLogin(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (empty($_SESSION['user_id'])) {
            $this->redirect('../../index.php');
        }
    }

    private function jagaAdmin(): void
    {
        $this->jagaLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('dashboard.php');
        }
    }

    private function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    private function render(string $tampilan, array $data): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/katalog.php';
    }

    private function urutDariUrl(): string
    {
        $urut = (string) ($_GET['urut'] ?? 'mulai-tercepat');
        return in_array($urut, BOOTCAMP_URUT, true) ? $urut : 'mulai-tercepat';
    }

    // Ambil data; jika database bermasalah halaman tetap tampil (kosong) dan error dicatat
    private function muatData(): array
    {
        try {
            return [$this->model->semua(), $this->model->daftarKategori()];
        } catch (Throwable $e) {
            error_log('Katalog: ' . $e->getMessage());
            return [[], []];
        }
    }

    /* ---------------- Halaman pengguna ---------------- */

    public function index(): void
    {
        $this->jagaLogin();

        $hariIni = strtotime('today');
        [$semuaProgram, $kategoriList] = $this->muatData();

        // State dari URL: ?kategori=...&jenis=...&q=...&urut=...
        $paramKategori = trim((string) ($_GET['kategori'] ?? ''));
        $kategoriAktif = bootcamp_kategori_cari($kategoriList, $paramKategori);
        $kategoriTidakAda = $paramKategori !== '' && $kategoriAktif === null;
        $urut = $this->urutDariUrl();
        $q = trim((string) ($_GET['q'] ?? ''));

        // 1) filter bidang (dari halaman kategori)
        if ($kategoriAktif !== null) {
            $programBidang = array_values(array_filter($semuaProgram, function ($p) use ($kategoriAktif) {
                return $p['kategori_id'] === $kategoriAktif['id'];
            }));
        } elseif ($kategoriTidakAda) {
            $programBidang = [];
        } else {
            $programBidang = $semuaProgram;
        }
        $paramBidangUrl = $kategoriAktif ? bootcamp_kategori_param($kategoriAktif) : '';

        // 2) filter jenis (chip Lomba / Bootcamp / Magang / Karier / Beasiswa)
        $jumlahPerJenis = bootcamp_hitung_jenis($programBidang);
        $jenisAktif = bootcamp_jenis_cocok((string) ($_GET['jenis'] ?? ''));
        $program = $jenisAktif === null
            ? $programBidang
            : array_values(array_filter($programBidang, function ($p) use ($jenisAktif) {
                return $p['jenis'] === $jenisAktif;
            }));

        // 3) urutkan lalu siapkan field tampilan
        $program = array_map(function ($p) use ($hariIni) {
            return bootcamp_siapkan($p, $hariIni);
        }, bootcamp_urut($program, $urut, $hariIni));

        $tersimpan = array_map('strval', array_column($_SESSION['bookmarks'] ?? [], 'id'));

        $judulHalaman = $kategoriAktif ? $kategoriAktif['nama_kategori'] : 'Katalog Program';
        $subJudul = $kategoriAktif
            ? ($kategoriAktif['deskripsi'] !== '' ? $kategoriAktif['deskripsi'] : 'Daftar program pada kategori ini.')
            : 'Cari dan temukan program pengembangan diri yang sesuai dengan kebutuhanmu.';
        $opsiUrut = self::URUT_LABEL;

        $this->render('index', compact(
            'hariIni', 'semuaProgram', 'kategoriList', 'kategoriAktif', 'kategoriTidakAda',
            'programBidang', 'paramBidangUrl', 'jumlahPerJenis', 'jenisAktif', 'program',
            'urut', 'q', 'tersimpan', 'judulHalaman', 'subJudul', 'opsiUrut'
        ));
    }

    /* ---------------- Halaman admin ---------------- */

    public function admin(): void
    {
        $this->jagaAdmin();
        $csrf = $this->tokenCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->proses($csrf);   // selalu berakhir dengan redirect
        }

        $hariIni = strtotime('today');
        [$semuaProgram, $kategoriList] = $this->muatData();

        $flash = $_SESSION['flash'] ?? null;
        $old = $_SESSION['katalog_old'] ?? null;
        unset($_SESSION['flash'], $_SESSION['katalog_old']);

        $urut = $this->urutDariUrl();
        $q = trim((string) ($_GET['q'] ?? ''));
        $filterKategori = trim((string) ($_GET['kategori'] ?? ''));
        $filterMode = trim((string) ($_GET['mode'] ?? ''));
        $filterJenis = trim((string) ($_GET['jenis'] ?? ''));

        $program = array_map(function ($p) use ($hariIni) {
            return bootcamp_siapkan($p, $hariIni);
        }, bootcamp_urut($semuaProgram, $urut, $hariIni));

        $opsiUrut = self::URUT_LABEL;
        $nama_user = $_SESSION['username'] ?? ($_SESSION['nama'] ?? 'Admin');

        $this->render('admin', compact(
            'csrf', 'hariIni', 'semuaProgram', 'kategoriList', 'flash', 'old', 'urut', 'q',
            'filterKategori', 'filterMode', 'filterJenis', 'program', 'opsiUrut', 'nama_user'
        ));
    }

    private function pesan(string $tipe, $pesan): void
    {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => (array) $pesan];
    }

    private function kembaliKeAdmin(): void
    {
        $this->redirect('adminkatalog.php');
    }

    // Validasi form tambah/edit. Mengembalikan [data, daftar error]
    private function validasi(array $post, array $kategoriList): array
    {
        $d = [];
        foreach (['judul', 'penyelenggara', 'kategori', 'jenis', 'mode', 'durasi',
                  'tanggal_mulai', 'deadline', 'harga', 'link', 'deskripsi'] as $k) {
            $d[$k] = trim((string) ($post[$k] ?? ''));
        }

        $err = [];
        if ($d['judul'] === '' || mb_strlen($d['judul']) > 150) {
            $err[] = 'Nama program wajib diisi (maksimal 150 karakter).';
        }
        if ($d['penyelenggara'] === '' || mb_strlen($d['penyelenggara']) > 100) {
            $err[] = 'Penyelenggara wajib diisi (maksimal 100 karakter).';
        }

        // Bidang harus ada di tabel kategori; dicocokkan lewat nama
        $d['kategori_id'] = 0;
        foreach ($kategoriList as $k) {
            if ($k['nama_kategori'] === $d['kategori']) {
                $d['kategori_id'] = $k['id'];
                break;
            }
        }
        if ($d['kategori_id'] <= 0) {
            $err[] = 'Pilih bidang yang tersedia.';
        }

        if (!in_array($d['jenis'], BOOTCAMP_JENIS, true)) {
            $err[] = 'Pilih jenis program (Lomba, Bootcamp, Magang, Karier, atau Beasiswa).';        }
        if (!in_array($d['mode'], BOOTCAMP_MODE, true)) {
            $err[] = 'Pilih mode program.';
        }
        if ($d['durasi'] === '' || mb_strlen($d['durasi']) > 50) {
            $err[] = 'Durasi wajib diisi (maksimal 50 karakter).';
        }

        $mulai = bootcamp_ts($d['tanggal_mulai']);
        $batas = bootcamp_ts($d['deadline']);
        if ($mulai === 0) {
            $err[] = 'Tanggal mulai program wajib diisi dengan tanggal yang valid.';
        }
        if ($batas === 0) {
            $err[] = 'Deadline pendaftaran wajib diisi dengan tanggal yang valid.';
        }
        if ($mulai > 0 && $batas > 0 && $batas > $mulai) {
            $err[] = 'Deadline pendaftaran tidak boleh setelah tanggal mulai program.';
        }

        if ($d['harga'] === '' || mb_strlen($d['harga']) > 50) {
            $err[] = 'Harga wajib diisi (contoh: Gratis atau 50.000).';
        }
        if (!filter_var($d['link'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $d['link'])) {
            $err[] = 'Link pendaftaran harus berupa URL http:// atau https://.';
        }
        if ($d['deskripsi'] === '' || mb_strlen($d['deskripsi']) > 2000) {
            $err[] = 'Deskripsi wajib diisi (maksimal 2000 karakter).';
        }
        return [$d, $err];
    }

    // Menangani POST: tambah / edit / hapus, lalu redirect (Post-Redirect-Get)
    private function proses(string $csrf): void
    {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            $this->pesan('error', 'Sesi form tidak valid. Muat ulang halaman lalu coba lagi.');
            $this->kembaliKeAdmin();
        }

        $aksi = (string) ($_POST['aksi'] ?? '');
        $id = (string) ($_POST['id'] ?? '');

        try {
            if ($aksi === 'hapus') {
                $this->aksiHapus($id);
            } elseif ($aksi === 'tambah' || $aksi === 'edit') {
                $this->aksiSimpan($aksi, $id);
            }
        } catch (Throwable $e) {
            error_log('Katalog admin: ' . $e->getMessage());
            $this->pesan('error', 'Terjadi kesalahan pada server. Periksa log error.');
        }
        $this->kembaliKeAdmin();
    }

    private function aksiHapus(string $id): void
    {
        $lama = $this->model->cari($id);
        if ($lama === null) {
            $this->pesan('error', 'Program tidak ditemukan.');
            return;
        }

        if ($this->model->hapus($id)) {
            bootcamp_hapus_gambar($lama['gambar']);
            $this->pesan('sukses', 'Program berhasil dihapus.');
        } else {
            $this->pesan('error', 'Gagal menghapus program dari database.');
        }
    }

    private function aksiSimpan(string $aksi, string $id): void
    {
        $kategoriList = $this->model->daftarKategori();
        [$input, $errors] = $this->validasi($_POST, $kategoriList);

        $lama = null;
        if ($aksi === 'edit') {
            $lama = $this->model->cari($id);
            if ($lama === null) {
                $errors[] = 'Program yang diedit tidak ditemukan.';
            }
        }

        // Poster (opsional)
        $gambarBaru = null;
        if (!$errors) {
            $errUpload = null;
            $gambarBaru = bootcamp_upload_gambar($_FILES['gambar'] ?? [], $errUpload);
            if ($errUpload !== null) {
                $errors[] = $errUpload;
            }
        }

        if ($errors) {
            $input['id'] = $id;
            $this->pesan('error', $errors);
            $_SESSION['katalog_old'] = ['aksi' => $aksi, 'data' => $input];
            return;
        }

        $gambarLamaHapus = '';
        if ($aksi === 'tambah') {
            $input['id'] = uniqid();
            $input['gambar'] = $gambarBaru ?? '';
        } else {
            $input['id'] = $id;
            if ($gambarBaru !== null) {
                $input['gambar'] = $gambarBaru;
                $gambarLamaHapus = $lama['gambar'];
            } elseif (!empty($_POST['hapus_gambar'])) {
                $input['gambar'] = '';
                $gambarLamaHapus = $lama['gambar'];
            } else {
                $input['gambar'] = $lama['gambar'];
            }
        }

        $ok = $aksi === 'tambah' ? $this->model->tambah($input) : $this->model->ubah($input);

        if ($ok) {
            if ($gambarLamaHapus !== '') {
                bootcamp_hapus_gambar($gambarLamaHapus);
            }
            $this->pesan('sukses', $aksi === 'tambah'
                ? 'Program berhasil ditambahkan.'
                : 'Perubahan program berhasil disimpan.');
        } else {
            if ($gambarBaru !== null) {
                bootcamp_hapus_gambar($gambarBaru);   // batalkan file yang sudah terunggah
            }
            $this->pesan('error', 'Gagal menyimpan data program ke database.');
        }
    }
}
