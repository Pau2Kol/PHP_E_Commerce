<?php
session_start();
include __DIR__ . '/../database/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Verification que l'email et le username sont tous les deux uniques
        $check = $conn->prepare("SELECT id FROM userdata WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Cet email ou ce nom d'utilisateur est deja utilise.";
        } else {
            $stmt = $conn->prepare("INSERT INTO userdata (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            if ($stmt->execute()) {
                $_SESSION['balance']  = 0;
                $_SESSION['PP']       = "default.png";
                $_SESSION['role']     = "user";
                $_SESSION['email']    = $email;
                $_SESSION['username'] = $username;

                // Recuperation de l'id genere pour la session
                $stmt2 = $conn->prepare("SELECT id FROM userdata WHERE email = ?");
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $stmt2->store_result();
                $stmt2->bind_result($id);
                $stmt2->fetch();
                $_SESSION['user_id'] = $id;
                $stmt2->close();

                header("Location: home");
                exit();
            } else {
                $message = "Erreur lors de l'inscription.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

$title = "Inscription";
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
    <span class="header-brand">Ma Boutique</span>
    <nav><ul>
        <li><a href="home">Accueil</a></li>
        <li><a href="login">Connexion</a></li>
    </ul></nav>
</header>

<main>
<div class="auth-wrapper">
    <h1>Creer un compte</h1>
    <p class="subtitle">Rejoignez la boutique</p>

    <div class="card">
        <?php if (!empty($message)): ?>
            <div class="notification notification-error" style="margin-bottom:1.2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="register" method="post">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" placeholder="VotreNom" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="vous@exemple.com" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Min. 6 caracteres" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Identique au mot de passe" required>
            </div>
            <button type="submit" name="register" class="w-full">S'inscrire</button>
        </form>
    </div>

    <div class="auth-links">
        Deja un compte ? <a href="login">Se connecter</a>
    </div>
</div>
</main>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
