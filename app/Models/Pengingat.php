<?php

require_once __DIR__ . '/../../config/koneksimvc.php';

class Pengingat
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Mengambil semua pengingat milik pengguna
    public function getAll($user_id)
    {
        $query = "SELECT * FROM pengingat WHERE user_id = ? ORDER BY tanggal_tenggat ASC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        return mysqli_stmt_get_result($stmt);
    }

    // Menambahkan pengingat
    public function tambah($user_id, $judul, $tanggal_tenggat)
    {
        $query = "INSERT INTO pengingat (user_id, judul, tanggal_tenggat) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $judul, $tanggal_tenggat);

        return mysqli_stmt_execute($stmt);
    }

    // Mengambil satu pengingat berdasarkan ID
    public function getById($id, $user_id)
    {
        $query = "SELECT * FROM pengingat WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);

        return mysqli_stmt_get_result($stmt);
    }

    // Mengubah pengingat
    public function edit($id, $user_id, $judul, $tanggal_tenggat)
    {
        $query = "UPDATE pengingat SET judul = ?, tanggal_tenggat = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssii", $judul, $tanggal_tenggat, $id, $user_id);

        return mysqli_stmt_execute($stmt);
    }

    // Menghapus pengingat
    public function hapus($id, $user_id)
    {
        $query = "DELETE FROM pengingat WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

        return mysqli_stmt_execute($stmt);
    }

    // Mengubah status pengingat
    public function ubahStatus($id, $user_id, $selesai)
    {
        $query = "UPDATE pengingat SET selesai = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iii", $selesai, $id, $user_id);

        return mysqli_stmt_execute($stmt);
    }
}

