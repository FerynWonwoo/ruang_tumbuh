
<?php

session_start();

// ==========================================
// 1. KONEKSI DATABASE
// ==========================================

require_once __DIR__ . '/config/koneksimvc.php';


// ==========================================
// 2. MEMANGGIL ROUTER
// ==========================================

require_once __DIR__ . '/app/Router.php';


// ==========================================
// 3. MEMANGGIL MODEL DAN CONTROLLER
// ==========================================

require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';


// ==========================================
// 4. MEMBUAT OBJECT
// ==========================================

$userModel = new User($conn);

$authController = new AuthController($userModel);


// ==========================================
// 5. INISIALISASI ROUTER
// ==========================================

$router = new Router();


// ==========================================
// 6. BASE PATH
// ==========================================

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

if ($basePath === '/') {
    $basePath = '';
}


// ==========================================
// 7. ROUTING GET
// ==========================================

$getHandler = function () use ($authController) {

    $page = $_GET['page'] ?? 'login';

    if ($page === 'register') {
        $authController->registerForm();
    } else {
        $authController->index();
    }

};

$router->get($basePath . '/', $getHandler);
$router->get($basePath, $getHandler);


// ==========================================
// 8. ROUTING POST
// ==========================================

$postHandler = function () use ($authController) {

    $page = $_GET['page'] ?? 'login';

    if ($page === 'register') {
        $authController->register();
    } else {
        $authController->login();
    }

};

$router->post($basePath . '/', $postHandler);
$router->post($basePath, $postHandler);


// ==========================================
// 9. MENJALANKAN ROUTER
// ==========================================

$router->dispatch();

?>
