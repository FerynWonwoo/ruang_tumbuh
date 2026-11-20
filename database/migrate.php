<?php
// Jalankan dari root proyek: php database/migrate.php
require_once __DIR__ . '/../config/koneksimvc.php';

$conn->query("CREATE TABLE IF NOT EXISTS migrations (
    nama VARCHAR(255) PRIMARY KEY,
    dijalankan_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sudah = [];
$res = $conn->query("SELECT nama FROM migrations");
while ($r = $res->fetch_assoc()) {
    $sudah[] = $r['nama'];
}

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $nama = basename($file);
    if (in_array($nama, $sudah)) {
        continue;
    }

    try {
        $conn->multi_query(file_get_contents($file));
        while ($conn->more_results() && $conn->next_result()) { }
    } catch (Throwable $e) {
        echo "GAGAL $nama: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }

    $stmt = $conn->prepare("INSERT INTO migrations (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    echo "OK    $nama" . PHP_EOL;
}
echo "Selesai." . PHP_EOL;
