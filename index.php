<?php

//remplace index.php par la bonne route grâce au switch + escape les char chelous
$base_path = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . ltrim(str_replace($base_path, '', $request_uri), '/');

//quand tu fait une nouvelle page faut indiquer le chemin ici
$routes = [
    '/'         => 'home.php',
    '/home'    => 'home.php',
    '/login'    => 'login.php',
    '/register' => 'register.php',
    '/logout'   => 'logout.php',
    '/profil'   => 'profil.php',
    '/resetpassword' => 'resetpassword.php'
];

//vérifie si route existe
if (array_key_exists($path, $routes)) {
    
    $file_path = __DIR__ . '/src/handlers/' . $routes[$path];

    if (file_exists($file_path)) {
        require $file_path;
    } else {
        http_response_code(500);
        
        echo "Erreur : Fichier introuvable dans " . $file_path;
    }
    
} else {
        http_response_code(404);
        $error_page = __DIR__ . '/src/templates/404.php';
        if (file_exists($error_page)) {
            include $error_page;
        } else {
            echo "404 - Page non trouvée";
        }
    }