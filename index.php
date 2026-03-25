<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path        = parse_url($request_uri, PHP_URL_PATH);

// Correspondance entre les routes et les fichiers handlers
$routes = [
    '/'                   => 'home.php',
    '/home'               => 'home.php',
    '/login'              => 'login.php',
    '/register'           => 'register.php',
    '/logout'             => 'logout.php',
    '/profil'             => 'profil.php',
    '/account'            => 'profil.php',
    '/resetpassword'      => 'resetpassword.php',
    '/update_profile'     => 'update_profile.php',
    '/sell'               => 'sell.php',
    '/cart'               => 'cart.php',
    '/validate'           => 'validate.php',
    '/detail'             => 'detail.php',
    '/edit'               => 'edit.php',
    '/admin'              => 'admin.php',
    '/admin_edit_user'    => 'admin_edit_user.php',
    '/admin_edit_article' => 'admin_edit_article.php',
];

if (array_key_exists($path, $routes)) {
    $file_path = __DIR__ . '/src/handlers/' . $routes[$path];
    if (file_exists($file_path)) {
        require $file_path;
    } else {
        http_response_code(500);
        echo "Erreur : fichier introuvable -> " . htmlspecialchars($file_path);
    }
} else {
    http_response_code(404);
    $error_page = __DIR__ . '/src/templates/404.php';
    if (file_exists($error_page)) {
        include $error_page;
    } else {
        echo "404 - Page non trouvee";
    }
}
