<?php
$host = 'localhost';
$db   = 'ruangtumbuh';
$port = 3306;
$user = 'root';
$pass = '';         

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());                 // catat di log server
    die('Koneksi basis data gagal.');            // pesan umum untuk pengguna
}