<?php

if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

class Catatan
{
    public const KATEGORI_VALID = [
        'Pribadi',
        'Beasiswa',
        'Bootcamp',
        'Magang',
        'Lomba',
        'Karier',
    ];

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        @mysqli_set_charset($this->conn, 'utf8mb4');
        $this->ensureTable();
    }

    /**
     * Memastikan tabel catatan tersedia di database kelompok (ruangtumbuh).
     */
    public function ensureTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS catatan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            judul VARCHAR(150) NOT NULL,
            kategori VARCHAR(50) NOT NULL,
            isi TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_catatan_user_id (user_id),
            INDEX idx_catatan_kategori (kategori)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Fallback pencarian user berdasarkan username dari tabel users kelompok.
     */
    public function findUserByUsername(string $username): ?array
    {
        $sql = 'SELECT id, nama, username, role FROM users WHERE username = ? LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query pengguna.');
        }

        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        return $user ?: null;
    }

    /**
     * Mengambil semua catatan milik user tertentu dengan filter pencarian dan kategori (prepared statement).
     */
    public function getAllByUser(int $userId, string $keyword = '', string $kategori = ''): array
    {
        $sql = 'SELECT id, user_id, judul, kategori, isi, created_at, updated_at
                FROM catatan
                WHERE user_id = ?';

        $types = 'i';
        $params = [$userId];

        if ($kategori !== '') {
            $sql .= ' AND kategori = ?';
            $types .= 's';
            $params[] = $kategori;
        }

        if ($keyword !== '') {
            $escapedKeyword = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword);
            $like = '%' . $escapedKeyword . '%';
            $sql .= " AND (judul LIKE ? ESCAPE '!' OR isi LIKE ? ESCAPE '!')";
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query daftar catatan.');
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $rows = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return $rows;
    }

    /**
     * Mengambil satu catatan berdasarkan ID milik user yang sedang login.
     */
    public function getByIdForUser(int $id, int $userId): ?array
    {
        $sql = 'SELECT id, user_id, judul, kategori, isi, created_at, updated_at
                FROM catatan
                WHERE id = ? AND user_id = ?
                LIMIT 1';

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query detail catatan.');
        }

        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    /**
     * Mengambil statistik ringkas catatan milik user.
     */
    public function getStatsByUser(int $userId): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total,
                    MAX(updated_at) AS terakhir,
                    COUNT(DISTINCT kategori) AS jumlah_kategori
                FROM catatan
                WHERE user_id = ?';

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query statistik catatan.');
        }

        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        return [
            'total' => (int) ($row['total'] ?? 0),
            'terakhir' => $row['terakhir'] ?? null,
            'jumlah_kategori' => (int) ($row['jumlah_kategori'] ?? 0),
        ];
    }

    /**
     * Menambahkan catatan baru.
     */
    public function create(int $userId, string $judul, string $kategori, string $isi): int
    {
        $sql = 'INSERT INTO catatan (user_id, judul, kategori, isi)
                VALUES (?, ?, ?, ?)';

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query tambah catatan.');
        }

        mysqli_stmt_bind_param($stmt, 'isss', $userId, $judul, $kategori, $isi);
        $ok = mysqli_stmt_execute($stmt);
        $insertId = (int) mysqli_insert_id($this->conn);

        mysqli_stmt_close($stmt);

        if (!$ok) {
            throw new RuntimeException('Gagal menyimpan catatan baru.');
        }

        return $insertId;
    }

    /**
     * Memperbarui catatan yang sudah ada milik user.
     */
    public function update(int $id, int $userId, string $judul, string $kategori, string $isi): bool
    {
        if ($this->getByIdForUser($id, $userId) === null) {
            return false;
        }

        $sql = 'UPDATE catatan
                SET judul = ?, kategori = ?, isi = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?';

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query ubah catatan.');
        }

        mysqli_stmt_bind_param($stmt, 'sssii', $judul, $kategori, $isi, $id, $userId);
        $ok = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        return $ok;
    }

    /**
     * Menghapus catatan milik user.
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = 'DELETE FROM catatan WHERE id = ? AND user_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query hapus catatan.');
        }

        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        mysqli_stmt_execute($stmt);

        $affected = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        return $affected > 0;
    }
}
