<?php

date_default_timezone_set('Asia/Jakarta');

define('BOOTCAMP_UPLOAD_DIR', __DIR__ . '/../public/uploads/program/');
define('BOOTCAMP_UPLOAD_URL', 'public/uploads/program/'); // relatif dari root project
define('BOOTCAMP_MAX_GAMBAR', 2 * 1024 * 1024);          // 2 MB

const BOOTCAMP_JENIS = ['Lomba', 'Bootcamp', 'Magang', 'Karier', 'Beasiswa'];
const BOOTCAMP_MODE = ['Online', 'Offline', 'Hybrid'];
const BOOTCAMP_URUT = ['mulai-tercepat', 'mulai-terjauh', 'deadline-terdekat', 'nama'];
const BOOTCAMP_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];


function bootcamp_normalize(array $d): array
{
    $hasil = [];

    foreach ([
        'id',
        'judul',
        'penyelenggara',
        'kategori',
        'jenis',
        'mode',
        'durasi',
        'tanggal_mulai',
        'deadline',
        'harga',
        'link',
        'deskripsi',
        'gambar'
    ] as $kunci) {
        $hasil[$kunci] = trim((string) ($d[$kunci] ?? ''));
    }

    $hasil['kategori_id'] = (int) ($d['kategori_id'] ?? 0);

    $hasil['jenis'] = bootcamp_jenis_cocok($hasil['jenis']) ?? 'Bootcamp';

    return $hasil;
}

// Nama jenis baku (Lomba/Bootcamp/Magang/Karier) dari teks bebas, atau null.
function bootcamp_jenis_cocok(string $teks): ?string
{
    foreach (BOOTCAMP_JENIS as $j) {
        if (strcasecmp($j, trim($teks)) === 0) {
            return $j;
        }
    }
    return null;
}

function bootcamp_load(): array
{
    global $conn;

    if (!($conn instanceof mysqli)) {
        return [];
    }

    $sql = "
        SELECT
            p.id,
            p.judul,
            p.penyelenggara,
            k.nama_kategori AS kategori,
            p.jenis,
            p.mode,
            p.durasi,
            p.tanggal_mulai,
            p.deadline,
            p.harga,
            p.link,
            p.deskripsi,
            p.gambar
        FROM program p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        ORDER BY p.judul ASC
    ";

    $hasil = $conn->query($sql);

    if (!$hasil) {
        error_log('Gagal mengambil data program: ' . $conn->error);
        return [];
    }

    $program = [];

    while ($row = $hasil->fetch_assoc()) {
        $program[] = bootcamp_normalize($row);
    }

    return $program;
}

function bootcamp_save(array $data): bool
{
    global $conn;

    if (!($conn instanceof mysqli)) {
        return false;
    }

    $conn->begin_transaction();

    try {
        // Ambil daftar kategori dari database
        $kategoriMap = [];

        $hasilKategori = $conn->query(
            "SELECT id, nama_kategori FROM kategori"
        );

        if (!$hasilKategori) {
            throw new Exception($conn->error);
        }

        while ($kategori = $hasilKategori->fetch_assoc()) {
            $nama = strtolower(trim($kategori['nama_kategori']));
            $kategoriMap[$nama] = (int) $kategori['id'];
        }

        $stmt = $conn->prepare("
            INSERT INTO program (
                id, judul, penyelenggara, kategori_id, jenis,
                mode, durasi, tanggal_mulai, deadline,
                harga, link, deskripsi, gambar
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                judul = VALUES(judul),
                penyelenggara = VALUES(penyelenggara),
                kategori_id = VALUES(kategori_id),
                jenis = VALUES(jenis),
                mode = VALUES(mode),
                durasi = VALUES(durasi),
                tanggal_mulai = VALUES(tanggal_mulai),
                deadline = VALUES(deadline),
                harga = VALUES(harga),
                link = VALUES(link),
                deskripsi = VALUES(deskripsi),
                gambar = VALUES(gambar)
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        foreach ($data as $program) {
            $id = (string) ($program['id'] ?? '');

            $namaKategori = strtolower(
                trim((string) ($program['kategori'] ?? ''))
            );

            $kategoriId = (int) ($program['kategori_id'] ?? 0);

            // Jika kategori_id belum tersedia, cari berdasarkan nama kategori
            if ($kategoriId <= 0) {
                $kategoriId = $kategoriMap[$namaKategori] ?? 0;
            }

            if ($id === '' || $kategoriId <= 0) {
                throw new Exception('ID program atau kategori tidak valid.');
            }

            $judul = (string) ($program['judul'] ?? '');
            $penyelenggara = (string) ($program['penyelenggara'] ?? '');
            $jenis = (string) ($program['jenis'] ?? 'Bootcamp');
            $mode = (string) ($program['mode'] ?? '');
            $durasi = (string) ($program['durasi'] ?? '');

            $tanggalMulai = !empty($program['tanggal_mulai'])
                ? $program['tanggal_mulai']
                : null;

            $deadline = (string) ($program['deadline'] ?? '');
            $harga = (string) ($program['harga'] ?? '');
            $link = (string) ($program['link'] ?? '');
            $deskripsi = (string) ($program['deskripsi'] ?? '');
            $gambar = (string) ($program['gambar'] ?? '');

            $stmt->bind_param(
                "sssisssssssss",
                $id,
                $judul,
                $penyelenggara,
                $kategoriId,
                $jenis,
                $mode,
                $durasi,
                $tanggalMulai,
                $deadline,
                $harga,
                $link,
                $deskripsi,
                $gambar
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
        }

        $stmt->close();

        $conn->commit();
        return true;

    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Gagal menyimpan program: ' . $e->getMessage());
        return false;
    }
}
function bootcamp_hapus(string $id): bool
{
    global $conn;

    if (!($conn instanceof mysqli)) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM program WHERE id = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $id);
    $berhasil = $stmt->execute();

    $stmt->close();

    return $berhasil;
}
// Timestamp awal hari untuk 'YYYY-MM-DD'. 0 jika kosong / tidak valid.
function bootcamp_ts(string $tgl): int
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tgl, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return 0;
    }
    $ts = strtotime($tgl . ' 00:00:00');
    return $ts === false ? 0 : $ts;
}

function bootcamp_tanggal_tampil(string $tgl): string
{
    $ts = bootcamp_ts($tgl);
    if ($ts === 0) {
        return '';
    }
    return date('j', $ts) . ' ' . BOOTCAMP_BULAN[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function bootcamp_harga_tampil(string $harga): string
{
    $harga = trim($harga);
    if ($harga === '') {
        return 'Hubungi penyelenggara';
    }
    // Angka saja (mis. 50.000) diberi awalan Rp
    if (preg_match('/^[0-9][0-9.,]*$/', $harga) && (int) preg_replace('/\D/', '', $harga) > 0) {
        return 'Rp ' . $harga;
    }
    return $harga;
}

// Path/URL gambar yang aman dipakai di atribut src. '' jika tidak ada.
function bootcamp_gambar_src(string $gambar, string $prefix = '../../'): string
{
    $gambar = trim($gambar);
    if ($gambar === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $gambar) && filter_var($gambar, FILTER_VALIDATE_URL)) {
        return $gambar; // data lama yang memakai URL
    }
    if (strpos($gambar, BOOTCAMP_UPLOAD_URL) === 0 && strpos($gambar, '..') === false) {
        return $prefix . $gambar;
    }
    return '';
}

/**
 * Simpan poster yang diunggah. Mengembalikan path relatif, atau null
 * (jika tidak ada file atau gagal; cek $error untuk membedakan).
 */
function bootcamp_upload_gambar(array $file, ?string &$error = null): ?string
{
    $error = null;

    if (!isset($file['error']) || is_array($file['error'])) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Ukuran poster terlalu besar (maksimal 2 MB).';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Poster gagal diunggah. Silakan coba lagi.';
        return null;
    }
    if ($file['size'] > BOOTCAMP_MAX_GAMBAR) {
        $error = 'Ukuran poster terlalu besar (maksimal 2 MB).';
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        $error = 'Upload poster tidak valid.';
        return null;
    }

    $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($izin[$info['mime']])) {
        $error = 'Format poster harus JPG, PNG, atau WebP.';
        return null;
    }

    if (!is_dir(BOOTCAMP_UPLOAD_DIR) && !mkdir(BOOTCAMP_UPLOAD_DIR, 0755, true)) {
        $error = 'Folder penyimpanan poster tidak bisa dibuat.';
        return null;
    }

    $nama = bin2hex(random_bytes(16)) . '.' . $izin[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], BOOTCAMP_UPLOAD_DIR . $nama)) {
        $error = 'Poster gagal disimpan di server.';
        return null;
    }
    return BOOTCAMP_UPLOAD_URL . $nama;
}

// Hapus file poster lokal (URL eksternal diabaikan).
function bootcamp_hapus_gambar(string $gambar): void
{
    if (strpos($gambar, BOOTCAMP_UPLOAD_URL) !== 0) {
        return;
    }
    $path = BOOTCAMP_UPLOAD_DIR . basename($gambar);
    if (is_file($path)) {
        @unlink($path);
    }
}


function bootcamp_kategori_list($conn, array $program = []): array
{
    $hasil = [];

    if ($conn instanceof mysqli) {
        try {
            $q = $conn->query('SELECT id, nama_kategori, deskripsi, ikon FROM kategori ORDER BY nama_kategori ASC');
            if ($q) {
                while ($row = $q->fetch_assoc()) {
                    $hasil[] = [
                        'id'            => (int) $row['id'],
                        'nama_kategori' => (string) $row['nama_kategori'],
                        'deskripsi'     => (string) ($row['deskripsi'] ?? ''),
                        'ikon'          => (string) ($row['ikon'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            $hasil = [];
        }
    }

    if (!$hasil) {
        $nama = array_unique(array_filter(array_column($program, 'kategori'), 'strlen'));
        sort($nama, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($nama as $n) {
            $hasil[] = ['id' => 0, 'nama_kategori' => $n, 'deskripsi' => '', 'ikon' => ''];
        }
    }
    return $hasil;
}

// Cari kategori dari parameter URL: angka = id, selain itu = nama.
function bootcamp_kategori_cari(array $kategoriList, string $param): ?array
{
    $param = trim($param);
    if ($param === '') {
        return null;
    }
    foreach ($kategoriList as $k) {
        if (ctype_digit($param) && $k['id'] > 0 && $k['id'] === (int) $param) {
            return $k;
        }
        if (strcasecmp($k['nama_kategori'], $param) === 0) {
            return $k;
        }
    }
    return null;
}

// Nilai parameter ?kategori= untuk sebuah kategori.
function bootcamp_kategori_param(array $kategori): string
{
    return $kategori['id'] > 0 ? (string) $kategori['id'] : $kategori['nama_kategori'];
}

// Jumlah program per jenis.
function bootcamp_hitung_jenis(array $program): array
{
    $jumlah = array_fill_keys(BOOTCAMP_JENIS, 0);
    foreach ($program as $p) {
        if (isset($jumlah[$p['jenis']])) {
            $jumlah[$p['jenis']]++;
        }
    }
    return $jumlah;
}

// Jumlah program per nama kategori (huruf kecil sebagai kunci).
function bootcamp_hitung_kategori(array $program): array
{
    $jumlah = [];
    foreach ($program as $p) {
        $kunci = mb_strtolower($p['kategori']);
        $jumlah[$kunci] = ($jumlah[$kunci] ?? 0) + 1;
    }
    return $jumlah;
}


function bootcamp_kunci_urut(array $d, string $mode, int $hariIni): array
{
    $mulai = bootcamp_ts($d['tanggal_mulai']);
    $batas = bootcamp_ts($d['deadline']);

    switch ($mode) {
        case 'nama':
            return [0, 0];
        case 'mulai-terjauh':
            if ($mulai === 0) return [1, 0];
            return $mulai >= $hariIni ? [0, -$mulai] : [2, -$mulai];
        case 'deadline-terdekat':
            if ($batas === 0) return [1, 0];
            return $batas >= $hariIni ? [0, $batas] : [2, -$batas];
        default: // mulai-tercepat
            if ($mulai === 0) return [1, 0];
            return $mulai >= $hariIni ? [0, $mulai] : [2, -$mulai];
    }
}

function bootcamp_urut(array $program, string $mode, int $hariIni): array
{
    if (!in_array($mode, BOOTCAMP_URUT, true)) {
        $mode = 'mulai-tercepat';
    }
    usort($program, function (array $a, array $b) use ($mode, $hariIni): int {
        $ka = bootcamp_kunci_urut($a, $mode, $hariIni);
        $kb = bootcamp_kunci_urut($b, $mode, $hariIni);
        return ($ka[0] <=> $kb[0])
            ?: ($ka[1] <=> $kb[1])
            ?: strcasecmp($a['judul'], $b['judul']);
    });
    return $program;
}

// Tambahkan field turunan untuk tampilan dan JavaScript.
function bootcamp_siapkan(array $d, int $hariIni): array
{
    $mulai = bootcamp_ts($d['tanggal_mulai']);
    $batas = bootcamp_ts($d['deadline']);

    $d['mulai_ts']        = $mulai;
    $d['deadline_ts']     = $batas;
    $d['mulai_tampil']    = bootcamp_tanggal_tampil($d['tanggal_mulai']);
    $d['deadline_tampil'] = bootcamp_tanggal_tampil($d['deadline']);
    $d['harga_tampil']    = bootcamp_harga_tampil($d['harga']);
    $d['gambar_src']      = bootcamp_gambar_src($d['gambar']);
    $d['pendaftaran_tutup'] = $batas > 0 && $batas < $hariIni;
    $d['sudah_berjalan']    = $mulai > 0 && $mulai < $hariIni;
    return $d;
}

// JSON aman untuk ditaruh di <script type="application/json">.
function bootcamp_json($data): string
{
    return json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}
