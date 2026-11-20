<?php

session_start();

// 1. KONEKSI DATABASE
require_once __DIR__ . '/config/koneksimvc.php';

// 2. ROUTER
require_once __DIR__ . '/app/Router.php';

// 3. MODEL DAN CONTROLLER
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/Kategori.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/KategoriController.php';
require_once __DIR__ . '/app/Models/Pengingat.php';
require_once __DIR__ . '/app/Controllers/PengingatController.php';

// 4. BASE PATH
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($basePath === '/') {
    $basePath = '';
}

// 5. OBJECT
$userModel = new User($conn);
$authController = new AuthController($userModel);

$kategoriModel = new Kategori($conn);
$kategoriController = new KategoriController($kategoriModel, $basePath);

$pengingatModel = new Pengingat();
$pengingatController = new PengingatController($pengingatModel, $basePath);

// 6. ROUTER
$router = new Router();

// 7. GET
$getHandler = function () use ($authController, $kategoriController, $pengingatController) {

    $page = $_GET['page'] ?? 'login';

    if ($page === 'register') {
        $authController->registerForm();
    } elseif ($page === 'kategori') {
        if (($_GET['aksi'] ?? '') === 'edit') {
            $kategoriController->edit();
        } else {
            $kategoriController->index();
        }
    } elseif ($page === 'pengingat') {
    if (($_GET['aksi'] ?? '') === 'edit') {
        $pengingatController->edit();
    } else {
        $pengingatController->index();
    }
    } else {
        $authController->index();
    }

};

$router->get($basePath . '/', $getHandler);
$router->get($basePath, $getHandler);
$router->get($basePath . '/index.php', $getHandler);

// 8. POST
$postHandler = function () use ($authController, $kategoriController, $pengingatController) {

    $page = $_GET['page'] ?? 'login';

    if ($page === 'register') {
        $authController->register();
    } elseif ($page === 'kategori') {
        $aksi = $_GET['aksi'] ?? '';
        if ($aksi === 'simpan') {
            $kategoriController->simpan();
        } elseif ($aksi === 'ubah') {
            $kategoriController->ubah();
        } elseif ($aksi === 'hapus') {
            $kategoriController->hapus();
        } else {
            http_response_code(404);
            echo "Halaman tidak ditemukan.";
        }
    } elseif ($page === 'pengingat') {
    $aksi = $_GET['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $pengingatController->simpan();
    } elseif ($aksi === 'ubah') {
        $pengingatController->ubah();
    } elseif ($aksi === 'hapus') {
        $pengingatController->hapus();
    } elseif ($aksi === 'selesai') {
        $pengingatController->selesai();
    } else {
        http_response_code(404);
        echo "Aksi tidak ditemukan.";
    }
    } else {
        $authController->login();
    }
};

$router->post($basePath . '/', $postHandler);
$router->post($basePath, $postHandler);
$router->post($basePath . '/index.php', $postHandler);

// 9. JALANKAN
$router->dispatch();
