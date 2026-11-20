<?php
class Kategori
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function semua()
    {
        $hasil = $this->conn->query(
            "SELECT * FROM kategori ORDER BY id DESC"
        );

        return $hasil->fetch_all(MYSQLI_ASSOC);
    }

    public function cari($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM kategori WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function namaSudahAda($nama, $kecualiId = 0)
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM kategori
             WHERE nama_kategori = ? AND id <> ?"
        );

        $stmt->bind_param("si", $nama, $kecualiId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function tambah($nama, $deskripsi, $userId)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO kategori
             (nama_kategori, deskripsi, user_id)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("ssi", $nama, $deskripsi, $userId);

        return $stmt->execute();
    }

    public function ubah($id, $nama, $deskripsi)
    {
        $stmt = $this->conn->prepare(
            "UPDATE kategori
             SET nama_kategori = ?, deskripsi = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $nama, $deskripsi, $id);

        return $stmt->execute();
    }

    public function hapus($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM kategori WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
