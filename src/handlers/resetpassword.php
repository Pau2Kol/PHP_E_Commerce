<?php
// 1. Logique de traitement
include __DIR__ . '/../database/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $oldPassword = $_POST['old_password'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    //vérifie mdps match et hash le nouveau mdp avant de l'update dans la bdd

    $hashedPassword = $_POST['old_password'];

    $stmt = $conn->prepare("SELECT password FROM userdata WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();
        if (!password_verify($oldPassword, $hashedPassword)) {
            $message = "L'ancien mot de passe est incorrect.";
            return;
        }
    } else {
        $message = "Aucun utilisateur trouve avec cette adresse email.";
        return;
    }

    if ($password === $confirmPassword) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE userdata SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute()) {
            header("Location: login");
        } else {
            $message = "Erreur lors de la mise à jour.";
        }
        $stmt->close();
    } else {
        $message = "Les mots de passe ne correspondent pas.";
    }
}


$title = "Reset Password";
?>

<a href="home">Home</href>
</a>
<body>
    <h2>Réinitialiser le mot de passe</h2>
    
    <?php if ($message) echo "<p>$message</p>"; ?>

    <form action="resetpassword" method="post">
        <input type="email" name="email" placeholder="Votre Email" required>
        <input type="password" name="old_password" placeholder="Ancien mot de passe" required>
        <input type="password" name="password" placeholder="Nouveau mot de passe" required>
        <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
        <button type="submit">Réinitialiser</button>
    </form>
</body>

<?php 
require __DIR__ . '/../../templates/footer.php'; 
?>