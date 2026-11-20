<?php

if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

class Bookmark
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        @mysqli_set_charset($this->conn, 'utf8mb4');
        $this->ensureTable();
    }

    /**
     * Otomatis membuat tabel bookmarks di database kelompok (ruangtumbuh) jika belum ada.
     */
    public function ensureTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS bookmarks (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            program_id VARCHAR(50) NULL,
            judul VARCHAR(150) NOT NULL,
            kategori VARCHAR(50) NOT NULL,
            penyelenggara VARCHAR(100) NOT NULL DEFAULT '',
            deadline DATE NULL,
            deskripsi TEXT NULL,
            link TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_bookmarks_user (user_id),
            UNIQUE KEY uq_bookmarks_user_program (user_id, program_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Fallback pencarian user_id berdasarkan username pada tabel users kelompok.
     */
    public function findUserByUsername(string $username): ?array
    {
        $sql = 'SELECT id, nama, username, role FROM users WHERE username = ? LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * READ: Ambil seluruh bookmark milik user tertentu (prepared statement).
     */
    public function getAllByUser(int $userId, string $keyword = '', string $kategori = ''): array
    {
        $sql = 'SELECT id, user_id, program_id, judul, kategori, penyelenggara, deadline, deskripsi, link, created_at, updated_at
                FROM bookmarks
                WHERE user_id = ?';

        $types = 'i';
        $params = [$userId];

        if ($kategori !== '' && $kategori !== 'Semua') {
            $sql .= ' AND kategori = ?';
            $types .= 's';
            $params[] = $kategori;
        }

        if ($keyword !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword);
            $like = '%' . $escaped . '%';
            $sql .= " AND (judul LIKE ? ESCAPE '!' OR penyelenggara LIKE ? ESCAPE '!' OR deskripsi LIKE ? ESCAPE '!')";
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query daftar bookmark.');
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
     * READ ONE: Ambil satu bookmark milik user tertentu berdasarkan ID.
     */
    public function findById(int $id, int $userId): ?array
    {
        $sql = 'SELECT id, user_id, program_id, judul, kategori, penyelenggara, deadline, deskripsi, link, created_at, updated_at
                FROM bookmarks
                WHERE id = ? AND user_id = ?
                LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    /**
     * Daftar program_id yang sudah di-bookmark user (sinkronisasi ikon di Katalog).
     */
    public function getSavedProgramIds(int $userId): array
    {
        $sql = 'SELECT program_id FROM bookmarks WHERE user_id = ? AND program_id IS NOT NULL';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $ids = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                if (!empty($row['program_id'])) {
                    $ids[] = (string) $row['program_id'];
                }
            }
        }
        mysqli_stmt_close($stmt);
        return $ids;
    }

    /**
     * CREATE: Simpan bookmark baru dengan prepared statement.
     */
    public function create(int $userId, array $data): bool
    {
        $sql = 'INSERT INTO bookmarks (user_id, program_id, judul, kategori, penyelenggara, deadline, deskripsi, link)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query simpan bookmark.');
        }

        $programId = !empty($data['program_id']) ? (string) $data['program_id'] : null;
        $judul = (string) ($data['judul'] ?? '');
        $kategori = (string) ($data['kategori'] ?? '');
        $penyelenggara = (string) ($data['penyelenggara'] ?? '');
        $deadline = !empty($data['deadline']) ? (string) $data['deadline'] : null;
        $deskripsi = (string) ($data['deskripsi'] ?? '');
        $link = (string) ($data['link'] ?? '');

        mysqli_stmt_bind_param(
            $stmt,
            'isssssss',
            $userId,
            $programId,
            $judul,
            $kategori,
            $penyelenggara,
            $deadline,
            $deskripsi,
            $link
        );

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * UPDATE: Perbarui bookmark milik user tertentu.
     */
    public function update(int $id, int $userId, array $data): bool
    {
        $sql = 'UPDATE bookmarks
                SET judul = ?, kategori = ?, penyelenggara = ?, deadline = ?, deskripsi = ?, link = ?
                WHERE id = ? AND user_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query ubah bookmark.');
        }

        $judul = (string) ($data['judul'] ?? '');
        $kategori = (string) ($data['kategori'] ?? '');
        $penyelenggara = (string) ($data['penyelenggara'] ?? '');
        $deadline = !empty($data['deadline']) ? (string) $data['deadline'] : null;
        $deskripsi = (string) ($data['deskripsi'] ?? '');
        $link = (string) ($data['link'] ?? '');

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssii',
            $judul,
            $kategori,
            $penyelenggara,
            $deadline,
            $deskripsi,
            $link,
            $id,
            $userId
        );

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * DELETE: Hapus bookmark milik user tertentu.
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = 'DELETE FROM bookmarks WHERE id = ? AND user_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan query hapus bookmark.');
        }

        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        $ok = mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $ok && $affected > 0;
    }

    /**
     * TOGGLE dari Katalog: Jika sudah ada -> hapus; jika belum -> simpan dari tabel program.
     */
    public function toggleFromKatalog(int $userId, string $programId, array $fallbackData = []): bool
    {
        $checkSql = 'SELECT id FROM bookmarks WHERE user_id = ? AND program_id = ? LIMIT 1';
        $checkStmt = mysqli_prepare($this->conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, 'is', $userId, $programId);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        $existing = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($checkStmt);

        if ($existing) {
            $this->delete((int) $existing['id'], $userId);
            return false;
        }

        $progSql = 'SELECT p.id, p.judul, p.penyelenggara, k.nama_kategori AS kategori, p.deadline, p.deskripsi, p.link
                    FROM program p
                    LEFT JOIN kategori k ON p.kategori_id = k.id
                    WHERE p.id = ? LIMIT 1';
        $progStmt = mysqli_prepare($this->conn, $progSql);
        $progData = null;
        if ($progStmt) {
            mysqli_stmt_bind_param($progStmt, 's', $programId);
            mysqli_stmt_execute($progStmt);
            $progRes = mysqli_stmt_get_result($progStmt);
            $progData = $progRes ? mysqli_fetch_assoc($progRes) : null;
            mysqli_stmt_close($progStmt);
        }

        $source = $progData ?: $fallbackData;
        $judul = trim((string) ($source['judul'] ?? 'Program Tersimpan'));
        $kategori = trim((string) ($source['kategori'] ?? 'Umum'));
        $penyelenggara = trim((string) ($source['penyelenggara'] ?? ''));
        $deskripsi = trim((string) ($source['deskripsi'] ?? ''));
        $link = trim((string) ($source['link'] ?? ''));

        $rawDeadline = trim((string) ($source['deadline'] ?? ''));
        $deadline = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDeadline) ? $rawDeadline : null;

        $this->create($userId, [
            'program_id'    => $programId,
            'judul'         => mb_substr($judul, 0, 150),
            'kategori'      => mb_substr($kategori !== '' ? $kategori : 'Umum', 0, 50),
            'penyelenggara' => mb_substr($penyelenggara, 0, 100),
            'deadline'      => $deadline,
            'deskripsi'     => mb_substr($deskripsi, 0, 1000),
            'link'          => $link,
        ]);

        return true;
    }

    /**
     * Ringkasan untuk kartu statistik Dashboard (total bookmark & mendekati tenggat <= 7 hari).
     */
    public function getSummaryByUser(int $userId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN deadline IS NOT NULL AND deadline >= CURDATE() AND deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS mendekati
                FROM bookmarks
                WHERE user_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['total' => 0, 'mendekati' => 0];
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'mendekati' => (int) ($row['mendekati'] ?? 0),
        ];
    }
}