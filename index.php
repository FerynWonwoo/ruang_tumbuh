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

// 6. ROUTER
$router = new Router();

// 7. GET
$getHandler = function () use ($authController, $kategoriController) {

    $page = $_GET['page'] ?? 'login';

    if ($page === 'register') {
        $authController->registerForm();
    } elseif ($page === 'kategori') {
        if (($_GET['aksi'] ?? '') === 'edit') {
            $kategoriController->edit();
        } else {
            $kategoriController->index();
        }
    } else {
        $authController->index();
    }
};

$router->get($basePath . '/', $getHandler);
$router->get($basePath, $getHandler);
$router->get($basePath . '/index.php', $getHandler);

// 8. POST
$postHandler = function () use ($authController, $kategoriController) {

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
    } else {
        $authController->login();
    }
};

$router->post($basePath . '/', $postHandler);
$router->post($basePath, $postHandler);
$router->post($basePath . '/index.php', $postHandler);

// 9. JALANKAN
$router->dispatch();
