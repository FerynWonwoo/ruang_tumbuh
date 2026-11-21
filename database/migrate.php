
<?php
// Jalankan dari root proyek:
// php database/migrate.php

require_once __DIR__ . '/../config/koneksimvc.php';

// Membuat tabel pencatat migration dan seeder.
$conn->query("
    CREATE TABLE IF NOT EXISTS migrations (
        nama VARCHAR(255) PRIMARY KEY,
        dijalankan_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Mengambil daftar file yang sudah dijalankan.
$sudah = [];

$res = $conn->query("SELECT nama FROM migrations");

while ($r = $res->fetch_assoc()) {
    $sudah[] = $r['nama'];
}

$res->free();

/**
 * Menjalankan SQL multi-query dan menghabiskan
 * seluruh hasil query agar tidak terjadi
 * error Commands out of sync.
 */
function jalankanSql(mysqli $conn, string $isi): void
{
    if (!$conn->multi_query($isi)) {
        throw new RuntimeException($conn->error);
    }

    do {
        $hasil = $conn->store_result();

        if ($hasil instanceof mysqli_result) {
            $hasil->free();
        }

        if (!$conn->more_results()) {
            break;
        }

        if (!$conn->next_result()) {
            throw new RuntimeException($conn->error);
        }

    } while (true);
}

/**
 * Menjalankan file SQL berdasarkan folder.
 */
function prosesFolder(
    mysqli $conn,
    string $folder,
    string $prefix,
    array &$sudah,
    string $label
): void {

    $files = glob($folder . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $namaFile = basename($file);
        $namaCatatan = $prefix . $namaFile;

        if (in_array($namaCatatan, $sudah, true)) {
            echo "LEWATI $label $namaFile" . PHP_EOL;
            continue;
        }

        try {
            $isi = file_get_contents($file);

            if ($isi === false) {
                throw new RuntimeException('File SQL tidak dapat dibaca.');
            }

            jalankanSql($conn, $isi);

            $stmt = $conn->prepare(
                "INSERT INTO migrations (nama) VALUES (?)"
            );

            $stmt->bind_param("s", $namaCatatan);
            $stmt->execute();
            $stmt->close();

            $sudah[] = $namaCatatan;

            echo "OK $label $namaFile" . PHP_EOL;

        } catch (Throwable $e) {
            echo "GAGAL $label $namaFile: "
                . $e->getMessage() . PHP_EOL;

            exit(1);
        }
    }
}

// 1. Jalankan seluruh migration terlebih dahulu.
echo "=== MIGRATION ===" . PHP_EOL;

prosesFolder(
    $conn,
    __DIR__ . '/migrations',
    '',
    $sudah,
    'MIGRATION'
);

// 2. Jalankan seeder setelah migration selesai.
echo PHP_EOL . "=== SEEDER ===" . PHP_EOL;

prosesFolder(
    $conn,
    __DIR__ . '/seeders',
    'seeder:',
    $sudah,
    'SEEDER'
);

echo PHP_EOL . "Semua proses selesai." . PHP_EOL;
