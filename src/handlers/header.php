<?php
session_start();
$_SESSION['role'] = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Ma Boutique'; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <picture>
            <a href="profil">
                <img src="uploads/<?php echo htmlspecialchars($_SESSION['PP'] ?? 'default.png'); ?>" alt="Photo de profil">
            </a>
        </picture>

        <nav>
            <ul>
                <li><a href="home">Accueil</a></li>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'guest'): ?>
                    <li><a href="sell">Vendre</a></li>
                    <li><a href="cart">🛒 Panier</a></li>
                    <li><a href="profil">Profil</a></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin">⚙️ Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login">Connexion</a></li>
                    <li><a href="register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
