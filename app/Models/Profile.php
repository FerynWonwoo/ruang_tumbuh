<?php

class Profile
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Mengambil data akun (users) digabung data profil tambahan
    public function ambil($userId)
    {
        $query = "SELECT u.id, u.nama, u.username, u.email,
                         p.telepon, p.domisili, p.institusi, p.status,
                         p.bio, p.bidang, p.kategori, p.foto
                  FROM users u
                  LEFT JOIN profil p ON p.user_id = u.id
                  WHERE u.id = ?
                  LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        return $data ?: null;
    }

    // Apakah email sudah dipakai akun lain?
    public function emailDipakaiLain($email, $userId)
    {
        $query = "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $email, $userId);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $ada = mysqli_fetch_assoc($hasil) !== null;

        mysqli_stmt_close($stmt);

        return $ada;
    }

    // Menyimpan nama + email (users) dan data profil (profil) sekaligus.
    // Memakai transaksi: kalau salah satu gagal, keduanya dibatalkan.
    public function simpan($userId, $nama, $email, array $p)
    {
        mysqli_begin_transaction($this->conn);

        try {
            $stmt = mysqli_prepare(
                $this->conn,
                "UPDATE users SET nama = ?, email = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $email, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $query = "INSERT INTO profil
                        (user_id, telepon, domisili, institusi, status,
                         bio, bidang, kategori, foto)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                        telepon   = VALUES(telepon),
                        domisili  = VALUES(domisili),
                        institusi = VALUES(institusi),
                        status    = VALUES(status),
                        bio       = VALUES(bio),
                        bidang    = VALUES(bidang),
                        kategori  = VALUES(kategori),
                        foto      = VALUES(foto)";

            $stmt = mysqli_prepare($this->conn, $query);

            $bidang = json_encode($p['bidang'], JSON_UNESCAPED_UNICODE);
            $kategori = json_encode($p['kategori'], JSON_UNESCAPED_UNICODE);

            mysqli_stmt_bind_param(
                $stmt,
                "issssssss",
                $userId,
                $p['telepon'],
                $p['domisili'],
                $p['institusi'],
                $p['status'],
                $p['bio'],
                $bidang,
                $kategori,
                $p['foto']
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($this->conn);
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            throw $e;
        }
    }

    // Menghapus data profil tambahan. Akun (users) TIDAK dihapus.
    // Mengembalikan nama file foto lama agar file-nya ikut dihapus.
    public function hapusData($userId)
    {
        $stmt = mysqli_prepare($this->conn, "SELECT foto FROM profil WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($this->conn, "DELETE FROM profil WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $baris['foto'] ?? '';
    }
}
