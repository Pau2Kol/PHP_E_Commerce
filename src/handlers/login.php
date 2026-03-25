<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT id, username, password, balance, PP, role FROM userdata WHERE email = ?";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $email;
                $_SESSION['balance']  = $user['balance'];
                $_SESSION['PP']       = $user['PP'];
                $_SESSION['role']     = $user['role'];
                header("Location: home");
                exit();
            } else {
                $message = "Identifiants incorrects.";
            }
        } else {
            $message = "Identifiants incorrects.";
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $message = "Erreur de base de données : la table 'userdata' semble absente.";
    }
}

$title = "Connexion";
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
        <li><a href="register">Inscription</a></li>
    </ul></nav>
</header>

<main>
<div class="auth-wrapper">
    <h1>Bon retour</h1>
    <p class="subtitle">Connectez-vous à votre compte</p>

    <div class="card">
        <?php if ($message): ?>
            <div class="notification notification-error" style="margin-bottom:1.2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="login" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="vous@exemple.com" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="w-full">Se connecter</button>
        </form>
    </div>

    <div class="auth-links">
        <a href="resetpassword">Mot de passe oublié ?</a>
        &nbsp;·&nbsp;
        <a href="register">Créer un compte</a>
    </div>
</div>
</main>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
