<?php
// Membuat akun contoh untuk dua role. Jalankan dari root proyek:
//   php database/seed_users.php
// Akun yang sudah ada tidak diubah.
require_once __DIR__ . '/../config/koneksimvc.php';

$akun = [
    ['Administrator', 'admin',  'admin@ruangtumbuh.test',  'admin12345', 'admin'],
    ['Pengguna Demo', 'user',   'user@ruangtumbuh.test',   'user12345',  'user'],
];

foreach ($akun as [$nama, $username, $email, $password, $role]) {
    $cek = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $cek->bind_param("s", $username);
    $cek->execute();

    if ($cek->get_result()->fetch_assoc()) {
        echo "ADA   $username" . PHP_EOL;
        continue;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare(
        "INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)"
    );
    $ins->bind_param("sssss", $nama, $username, $email, $hash, $role);
    $ins->execute();

    echo "OK    $username / $password ($role)" . PHP_EOL;
}
