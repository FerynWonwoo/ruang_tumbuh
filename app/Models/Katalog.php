<?php

require_once __DIR__ . '/../../includes/bootcamp_store.php';

class Katalog
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function dasarSql(): string
    {
        return "SELECT p.id, p.judul, p.penyelenggara, p.kategori_id,
                       k.nama_kategori AS kategori, p.jenis, p.mode, p.durasi,
                       p.tanggal_mulai, p.deadline, p.harga, p.link, p.deskripsi, p.gambar
                FROM program p
                LEFT JOIN kategori k ON k.id = p.kategori_id";
    }

    public function semua(): array
    {
        $hasil = $this->conn->query($this->dasarSql() . ' ORDER BY p.judul ASC');
        if (!$hasil) {
            throw new RuntimeException($this->conn->error);
        }

        $data = [];
        while ($row = $hasil->fetch_assoc()) {
            $data[] = bootcamp_normalize($row);
        }
        return $data;
    }

    public function cari(string $id): ?array
    {
        $stmt = $this->conn->prepare($this->dasarSql() . ' WHERE p.id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? bootcamp_normalize($row) : null;
    }

    public function tambah(array $d): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO program
                (id, judul, penyelenggara, kategori_id, jenis, mode, durasi,
                 tanggal_mulai, deadline, harga, link, deskripsi, gambar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $mulai = $d['tanggal_mulai'] !== '' ? $d['tanggal_mulai'] : null;
        $batas = $d['deadline'] !== '' ? $d['deadline'] : null;

        $stmt->bind_param(
            'sssisssssssss',
            $d['id'], $d['judul'], $d['penyelenggara'], $d['kategori_id'], $d['jenis'],
            $d['mode'], $d['durasi'], $mulai, $batas, $d['harga'], $d['link'],
            $d['deskripsi'], $d['gambar']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function ubah(array $d): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE program
                SET judul = ?, penyelenggara = ?, kategori_id = ?, jenis = ?, mode = ?,
                    durasi = ?, tanggal_mulai = ?, deadline = ?, harga = ?, link = ?,
                    deskripsi = ?, gambar = ?
              WHERE id = ?'
        );

        $mulai = $d['tanggal_mulai'] !== '' ? $d['tanggal_mulai'] : null;
        $batas = $d['deadline'] !== '' ? $d['deadline'] : null;

        $stmt->bind_param(
            'ssissssssssss',
            $d['judul'], $d['penyelenggara'], $d['kategori_id'], $d['jenis'], $d['mode'],
            $d['durasi'], $mulai, $batas, $d['harga'], $d['link'],
            $d['deskripsi'], $d['gambar'], $d['id']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function hapus(string $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM program WHERE id = ?');
        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Daftar bidang 
    public function daftarKategori(): array
    {
        $hasil = $this->conn->query('SELECT * FROM kategori ORDER BY nama_kategori ASC');
        if (!$hasil) {
            throw new RuntimeException($this->conn->error);
        }

        $data = [];
        while ($row = $hasil->fetch_assoc()) {
            $data[] = [
                'id'            => (int) $row['id'],
                'nama_kategori' => (string) $row['nama_kategori'],
                'deskripsi'     => (string) ($row['deskripsi'] ?? ''),
                'ikon'          => (string) ($row['ikon'] ?? ''),
            ];
        }
        return $data;
    }
}
