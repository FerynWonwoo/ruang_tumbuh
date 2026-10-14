<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Cek apakah user sudah login
if (empty($_SESSION['username'])) {
    echo json_encode(['ok' => false, 'pesan' => 'Belum login atau sesi habis']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $judul = $_POST['judul'] ?? 'Program Pilihan';
    $kategori = $_POST['kategori'] ?? 'Bootcamp';
    $penyelenggara = $_POST['penyelenggara'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $link = $_POST['link'] ?? '#';

    if (empty($id)) {
        echo json_encode(['ok' => false, 'pesan' => 'ID kosong']);
        exit;
    }

    if (!isset($_SESSION['bookmarks'])) {
        $_SESSION['bookmarks'] = [];
    }

    // Cek apakah item sudah ada di dalam session bookmarks
    $index_ditemukan = -1;
    foreach ($_SESSION['bookmarks'] as $index => $item) {
        if ($item['id'] === $id) {
            $index_ditemukan = $index;
            break;
        }
    }

    if ($index_ditemukan === -1) {
        array_unshift($_SESSION['bookmarks'], [
            'id' => $id,
            'judul' => $judul,
            'kategori' => $kategori,
            'penyelenggara' => $penyelenggara,
            'deadline' => $deadline,
            'status' => 'Aktif',
            'deskripsi' => $deskripsi,
            'link' => $link
        ]);
        $saved = true;
    } else {
        unset($_SESSION['bookmarks'][$index_ditemukan]);
        $_SESSION['bookmarks'] = array_values($_SESSION['bookmarks']);
        $saved = false;
    }

    echo json_encode(['ok' => true, 'saved' => $saved]);
    exit;
}

echo json_encode(['ok' => false, 'pesan' => 'Metode tidak valid']);