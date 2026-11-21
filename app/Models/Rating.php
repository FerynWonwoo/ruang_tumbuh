
<?php

class Rating
{
    private $conn;

    public const STATUS = [
        'pending',
        'approved',
        'rejected'
    ];

    public const MAX_KOMENTAR = 1000;


    public function __construct($conn)
    {
        $this->conn = $conn;
    }


    // =========================================
    // MENGAMBIL SEMUA ULASAN
    // =========================================

    public function semua(): array
    {
        $query = "SELECT * FROM ratings ORDER BY tanggal DESC, id DESC";

        $result = mysqli_query($this->conn, $query);

        if (!$result) {
            error_log(mysqli_error($this->conn));
            return [];
        }

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        mysqli_free_result($result);

        return $data;
    }


    // =========================================
    // MENGAMBIL ULASAN BERDASARKAN ID
    // =========================================

    public function cari(string $id): ?array
    {
        $query = "SELECT * FROM ratings WHERE id = ? LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result) ?: null;

        mysqli_stmt_close($stmt);

        return $data;
    }


    // =========================================
    // ULASAN UNTUK PESERTA
    // =========================================

    public function untukUser(string $username): array
    {
        $query = "SELECT *
                  FROM ratings
                  WHERE status = 'approved'
                     OR username = ?
                  ORDER BY tanggal DESC, id DESC";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return [
                'daftar' => [],
                'publik' => 0,
                'rata' => '—'
            ];
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $daftar = [];
        $total = 0;
        $publik = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $daftar[] = $row;

            if ($row['status'] === 'approved') {
                $total += (int) $row['rating'];
                $publik++;
            }
        }

        mysqli_stmt_close($stmt);

        return [
            'daftar' => $daftar,
            'publik' => $publik,
            'rata' => $publik > 0
                ? number_format($total / $publik, 1)
                : '—'
        ];
    }


    // =========================================
    // MENGAMBIL ULASAN ADMIN
    // =========================================

    public function untukAdmin(string $filter = 'all'): array
    {
        $jumlah = [
            'all' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        $semua = $this->semua();

        foreach ($semua as $u) {
            $status = $u['status'];

            if (isset($jumlah[$status])) {
                $jumlah[$status]++;
            }

            $jumlah['all']++;
        }

        $daftar = [];

        foreach ($semua as $u) {
            if ($filter === 'all' || $u['status'] === $filter) {
                $daftar[] = $u;
            }
        }

        return [
            'daftar' => $daftar,
            'jumlah' => $jumlah
        ];
    }


    // =========================================
    // MENAMBAHKAN ULASAN
    // =========================================

    public function simpan(
        array $form,
        string $username
    ): array {

        $bootcamp = trim($form['bootcamp'] ?? '');
        $rating = (int) ($form['rating'] ?? 0);
        $komentar = trim($form['komentar'] ?? '');

        if ($bootcamp === '' || strlen($bootcamp) > 150) {
            return $this->gagal('Nama bootcamp wajib diisi (maksimal 150 karakter).');
        }
                if (!$this->bootcampAda($bootcamp)) {
            return $this->gagal('Bootcamp tidak ada di katalog. Pilih dari daftar.');
        }

        if ($rating < 1 || $rating > 5) {
            return $this->gagal('Rating harus antara 1 sampai 5.');
        }

        if (
            $komentar === '' ||
            mb_strlen($komentar) > self::MAX_KOMENTAR
        ) {
            return $this->gagal('Komentar wajib diisi dan maksimal 1000 karakter.');
        }

        // Cek apakah pengguna sudah mengulas bootcamp ini
        $query = "SELECT id
                  FROM ratings
                  WHERE username = ?
                  AND bootcamp = ?
                  LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return $this->gagal('Gagal memeriksa data ulasan.');
        }

        mysqli_stmt_bind_param($stmt, "ss", $username, $bootcamp);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $ada = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($ada) {
            return $this->gagal(
                'Kamu sudah mengulas bootcamp ini. Silakan edit ulasan yang sudah ada.'
            );
        }

        $id = bin2hex(random_bytes(8));
        $status = 'pending';

        $query = "INSERT INTO ratings
                  (id, username, bootcamp, rating, komentar, status)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return $this->gagal('Gagal menyiapkan penyimpanan ulasan.');
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssiss",
            $id,
            $username,
            $bootcamp,
            $rating,
            $komentar,
            $status
        );

        $berhasil = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        if (!$berhasil) {
            return $this->gagal('Ulasan gagal disimpan.');
        }

        return [
            'ok' => true,
            'pesan' => 'Ulasan berhasil dikirim dan menunggu persetujuan admin.',
            'error' => ''
        ];
    }


    // =========================================
    // MENGEDIT ULASAN
    // =========================================

    public function update(
        string $id,
        array $form,
        string $username
    ): array {

        $ulasan = $this->cari($id);

        if (!$ulasan || $ulasan['username'] !== $username) {
            return $this->gagal(
                'Ulasan tidak ditemukan atau bukan milikmu.'
            );
        }

        $bootcamp = trim($form['bootcamp'] ?? '');
        $rating = (int) ($form['rating'] ?? 0);
        $komentar = trim($form['komentar'] ?? '');

        if ($bootcamp === '' || strlen($bootcamp) > 150) {
            return $this->gagal('Nama bootcamp tidak valid.');
        }
                if (
            $bootcamp !== $ulasan['bootcamp'] &&
            !$this->bootcampAda($bootcamp)
        ) {
            return $this->gagal('Bootcamp tidak ada di katalog. Pilih dari daftar.');
        }

        if ($rating < 1 || $rating > 5) {
            return $this->gagal('Rating harus antara 1 sampai 5.');
        }

        if (
            $komentar === '' ||
            mb_strlen($komentar) > self::MAX_KOMENTAR
        ) {
            return $this->gagal('Komentar wajib diisi dan maksimal 1000 karakter.');
        }

        // Cek duplikasi bootcamp oleh username yang sama
        $query = "SELECT id FROM ratings
                  WHERE username = ?
                  AND bootcamp = ?
                  AND id != ?
                  LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return $this->gagal('Gagal memeriksa ulasan.');
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $username,
            $bootcamp,
            $id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $duplikat = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($duplikat) {
            return $this->gagal(
                'Kamu sudah mengulas bootcamp tersebut.'
            );
        }

        // Ulasan yang diubah kembali menunggu moderasi,
        // kecuali sebelumnya sudah rejected.
        $status = $ulasan['status'] === 'rejected'
            ? 'rejected'
            : 'pending';

        $query = "UPDATE ratings
                  SET bootcamp = ?,
                      rating = ?,
                      komentar = ?,
                      status = ?
                  WHERE id = ?
                  AND username = ?";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return $this->gagal('Gagal menyiapkan perubahan ulasan.');
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sissss",
            $bootcamp,
            $rating,
            $komentar,
            $status,
            $id,
            $username
        );

        $berhasil = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        if (!$berhasil) {
            return $this->gagal('Ulasan gagal diperbarui.');
        }

        return [
            'ok' => true,
            'pesan' => 'Ulasan berhasil diperbarui dan menunggu moderasi.',
            'error' => ''
        ];
    }


    // =========================================
    // MENARIK / MENGHAPUS ULASAN PESERTA
    // =========================================

    public function tarik(
        string $id,
        string $username
    ): array {

        $query = "DELETE FROM ratings
                  WHERE id = ?
                  AND username = ?";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return $this->gagal('Gagal menyiapkan penghapusan ulasan.');
        }

        mysqli_stmt_bind_param($stmt, "ss", $id, $username);

        $berhasil = mysqli_stmt_execute($stmt);
        $terhapus = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        if (!$berhasil || $terhapus === 0) {
            return $this->gagal(
                'Ulasan tidak ditemukan atau bukan milikmu.'
            );
        }

        return [
            'ok' => true,
            'pesan' => 'Ulasan berhasil ditarik.',
            'error' => ''
        ];
    }


    // =========================================
    // MODERASI ADMIN
    // =========================================

    public function moderasi(
        array $ids,
        string $aksi
    ): int {

        $status = [
            'publish' => 'approved',
            'hide' => 'rejected'
        ];

        if ($aksi === 'hapus') {

            $diproses = 0;

            foreach ($ids as $id) {
                $query = "DELETE FROM ratings WHERE id = ?";

                $stmt = mysqli_prepare($this->conn, $query);

                if (!$stmt) {
                    continue;
                }

                mysqli_stmt_bind_param($stmt, "s", $id);
                mysqli_stmt_execute($stmt);

                $diproses += mysqli_stmt_affected_rows($stmt);

                mysqli_stmt_close($stmt);
            }

            return $diproses;
        }

        if (!isset($status[$aksi])) {
            return 0;
        }

        $statusBaru = $status[$aksi];
        $diproses = 0;

        foreach ($ids as $id) {

            $query = "UPDATE ratings SET status = ? WHERE id = ?";

            $stmt = mysqli_prepare($this->conn, $query);

            if (!$stmt) {
                continue;
            }

            mysqli_stmt_bind_param($stmt, "ss", $statusBaru, $id);

            mysqli_stmt_execute($stmt);

            $diproses += mysqli_stmt_affected_rows($stmt);

            mysqli_stmt_close($stmt);
        }

        return $diproses;
    }


    // =========================================
    // HELPER ERROR
    // =========================================

    private function gagal(string $pesan): array
    {
        return [
            'ok' => false,
            'pesan' => '',
            'error' => $pesan
        ];
    }

        // =========================================
    // DAFTAR BOOTCAMP DARI KATALOG (tabel program)
    // =========================================

    public function daftarBootcamp(): array
    {
        $result = mysqli_query(
            $this->conn,
            "SELECT DISTINCT judul FROM program ORDER BY judul ASC"
        );

        if (!$result) {
            error_log(mysqli_error($this->conn));
            return [];
        }

        $daftar = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $daftar[] = $row['judul'];
        }

        mysqli_free_result($result);

        return $daftar;
    }

    public function bootcampAda(string $judul): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT 1 FROM program WHERE judul = ? LIMIT 1"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $judul);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $ada = (bool) mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $ada;
    }
    
}
