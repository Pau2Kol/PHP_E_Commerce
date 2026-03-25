<?php
session_start();
$_SESSION['role'] = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <picture>
            <a href="profil">
            <img src="uploads/<?php  //echo $_SESSION['PP'] ?? "default.png" ; ?>" alt="Image de profil">
            </a>
        </picture>

        <nav>
            <ul>
            <!-- Faudra faire un truc pour les paths j'ai la flemme normalement le .htaccess il redirige tout vers index.php il sert de handler mais jsp pourquoi la il veut pas dcp j'ai ça en attendant  -->
                <li><a href="home">Accueil</a></li>
                <li><a href="login">Connexion</a></li>
                <li><a href="register">Inscription</a></li>
                <li><a href="logout">Deconnexion</a></li>
                <li><a href="profil">Profil</a></li>
                <li><a href="sell">Vendre</a></li>
            </ul>
        </nav>
    </header>
    <main></main>