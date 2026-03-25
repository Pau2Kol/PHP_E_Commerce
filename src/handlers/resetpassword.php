<?php
include __DIR__ . '/../database/db_connection.php';

$message = "";
$message_type = "error";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email           = $_POST['email'];
    $oldPassword     = $_POST['old_password'];
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $hashedPassword  = $_POST['old_password'];

    $stmt = $conn->prepare("SELECT password FROM userdata WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();
        if (!password_verify($oldPassword, $hashedPassword)) {
            $message = "Mot de passe actuel incorrect.";
        }
    } else {
        $message = "Aucun utilisateur trouvé avec cette adresse email.";
    }

    if (empty($message)) {
        if ($password === $confirmPassword) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE userdata SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);
            if ($stmt->execute()) {
                header("Location: login");
                exit();
            } else {
                $message = "Erreur lors de la mise à jour.";
            }
            $stmt->close();
        } else {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
        }
    }
}

$title = "Réinitialiser le mot de passe";
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
    <h1>Nouveau mot de passe</h1>
    <p class="subtitle">Renseignez votre email et votre mot de passe actuel</p>

    <div class="card">
        <?php if ($message): ?>
            <div class="notification notification-error" style="margin-bottom:1.2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="resetpassword" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="vous@exemple.com" required>
            </div>
            <div class="form-group">
                <label for="old_password">Mot de passe actuel</label>
                <input type="password" id="old_password" name="old_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="w-full">Réinitialiser</button>
        </form>
    </div>

    <div class="auth-links">
        <a href="login">Retour à la connexion</a>
    </div>
</div>
</main>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
