<?php
session_start();
include __DIR__ . '/../database/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM userdata WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Cet email ou ce nom d'utilisateur est déjà utilisé.";
        } else {
            $stmt = $conn->prepare("INSERT INTO userdata (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            if ($stmt->execute()) {
                $_SESSION['balance']  = 0;
                $_SESSION['PP']       = "default.png";
                $_SESSION['role']     = "user";
                $_SESSION['email']    = $email;
                $_SESSION['username'] = $username;

                $stmt = $conn->prepare("SELECT id FROM userdata WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($id);
                $stmt->fetch();
                $_SESSION['user_id'] = $id;

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
    <span class="header-brand">✦ Ma Boutique</span>
    <nav><ul>
        <li><a href="home">Accueil</a></li>
        <li><a href="login">Connexion</a></li>
    </ul></nav>
</header>

<main>
<div class="auth-wrapper">
    <h1>Créer un compte</h1>
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
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="register" class="w-full">S'inscrire</button>
        </form>
    </div>

    <div class="auth-links">
        Déjà un compte ? <a href="login">Se connecter</a>
    </div>
</div>
</main>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
