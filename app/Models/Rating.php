<?php
/**
 * Model Ulasan: seluruh akses data (data/ulasan.json) dan aturan data.
 * Tidak ada HTML / session di sini.
 */
class Ulasan
{
    public const STATUS = ['pending', 'approved', 'rejected'];

    public static function idValid(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }

    // ---------- Baca ----------
    public function semua(): array
    {
        if (!is_file(DATA_FILE)) {
            return [];
        }
        $data = json_decode((string) file_get_contents(DATA_FILE), true);
        return is_array($data) ? $data : [];
    }

    public function cari(string $id): ?array
    {
        return $this->semua()[$id] ?? null;
    }

    // Data lama tanpa status dianggap tayang
    private function normalisasi(array $u): array
    {
        $u['status'] = in_array($u['status'] ?? '', self::STATUS, true) ? $u['status'] : 'approved';
        return $u;
    }

    /** Untuk halaman peserta: ulasan tayang + ulasan milik sendiri. */
    public function untukUser(string $username): array
    {
        $daftar = [];
        $total  = 0;
        $publik = 0;

        foreach (array_reverse($this->semua(), true) as $u) {
            $u = $this->normalisasi($u);

            if ($u['status'] === 'approved') {
                $total += (int) $u['rating'];
                $publik++;
            }
            if ($u['status'] === 'approved' || $u['username'] === $username) {
                $daftar[] = $u;
            }
        }

        return [
            'daftar' => $daftar,
            'publik' => $publik,
            'rata'   => $publik > 0 ? number_format($total / $publik, 1) : '—',
        ];
    }

    /** Untuk halaman admin: daftar sesuai filter + jumlah per status. */
    public function untukAdmin(string $filter): array
    {
        $jumlah = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        $daftar = [];

        foreach (array_reverse($this->semua(), true) as $u) {
            $u = $this->normalisasi($u);
            $jumlah['all']++;
            $jumlah[$u['status']]++;

            if ($filter === 'all' || $u['status'] === $filter) {
                $daftar[] = $u;
            }
        }

        return ['daftar' => $daftar, 'jumlah' => $jumlah];
    }

    // ---------- Tulis (peserta) ----------
    /** @return array{ok:bool,error:string,pesan:string} */
    public function simpan(array $form, string $id, string $username): array
    {
        $hasil = ['ok' => true, 'error' => '', 'pesan' => ''];

        $tersimpan = $this->ubah(function ($data) use (&$hasil, $form, $id, $username) {
            $lama = $data[$id] ?? null;

            if ($id !== '' && (!$lama || $lama['username'] !== $username)) {
                $hasil = ['ok' => false, 'error' => 'Ulasan tidak ditemukan atau bukan milikmu.', 'pesan' => ''];
                return $data;
            }

            // Satu ulasan per user per bootcamp (berlaku juga saat edit)
            foreach ($data as $kunci => $u) {
                if ($kunci !== $id
                    && ($u['username'] ?? '') === $username
                    && ($u['bootcamp'] ?? '') === $form['bootcamp']) {
                    $hasil = ['ok' => false, 'error' => 'Kamu sudah mengulas bootcamp ini. Edit ulasan yang sudah ada.', 'pesan' => ''];
                    return $data;
                }
            }

            $idBaru = $id !== '' ? $id : bin2hex(random_bytes(8));

            // Baru / hasil edit menunggu persetujuan admin; yang disembunyikan tetap tersembunyi.
            $status = ($lama['status'] ?? '') === 'rejected' ? 'rejected' : 'pending';

            $data[$idBaru] = [
                'id'       => $idBaru,
                'username' => $username,
                'bootcamp' => $form['bootcamp'],
                'rating'   => $form['rating'],
                'komentar' => $form['komentar'],
                'status'   => $status,
                'tanggal'  => $lama['tanggal'] ?? date('d/m/Y'),
            ];

            $hasil['pesan'] = $status === 'rejected'
                ? 'Ulasan diperbarui, tetapi sedang disembunyikan admin.'
                : ($id !== ''
                    ? 'Ulasan diperbarui dan menunggu persetujuan admin.'
                    : 'Ulasan terkirim dan menunggu persetujuan admin.');
            return $data;
        });

        return $tersimpan ? $hasil : $this->gagalSimpan('Data tidak bisa disimpan. Hubungi admin.');
    }

    /** @return array{ok:bool,error:string,pesan:string} */
    public function tarik(string $id, string $username): array
    {
        $hasil = ['ok' => true, 'error' => '', 'pesan' => 'Ulasan berhasil ditarik.'];

        $tersimpan = $this->ubah(function ($data) use (&$hasil, $id, $username) {
            if (!isset($data[$id]) || $data[$id]['username'] !== $username) {
                $hasil = ['ok' => false, 'error' => 'Ulasan tidak ditemukan atau bukan milikmu.', 'pesan' => ''];
                return $data;
            }
            unset($data[$id]);
            return $data;
        });

        return $tersimpan ? $hasil : $this->gagalSimpan('Data tidak bisa disimpan. Hubungi admin.');
    }

    // ---------- Tulis (admin) ----------
    /**
     * @param string[] $ids
     * @param string   $aksi publish|hide|hapus
     * @return int|null jumlah ulasan diproses, null jika gagal menyimpan
     */
    public function moderasi(array $ids, string $aksi): ?int
    {
        $diproses = 0;

        $tersimpan = $this->ubah(function ($data) use ($ids, $aksi, &$diproses) {
            foreach ($ids as $id) {
                if (!isset($data[$id])) {
                    continue;
                }
                if ($aksi === 'hapus') {
                    unset($data[$id]);
                } else {
                    $data[$id]['status'] = $aksi === 'publish' ? 'approved' : 'rejected';
                }
                $diproses++;
            }
            return $data;
        });

        return $tersimpan ? $diproses : null;
    }

    // ---------- Internal ----------
    private function gagalSimpan(string $error): array
    {
        return ['ok' => false, 'error' => $error, 'pesan' => ''];
    }

    // Baca-ubah-tulis dengan file lock supaya aman dari tabrakan.
    private function ubah(callable $fn): bool
    {
        $dir = dirname(DATA_FILE);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        // Blokir akses langsung ke folder data lewat browser (Apache)
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents(
                $htaccess,
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
                "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
            );
        }

        $fp = fopen(DATA_FILE, 'c+');
        if (!$fp) {
            return false;
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        $data = json_decode((string) stream_get_contents($fp), true);
        $data = $fn(is_array($data) ? $data : []);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
}
