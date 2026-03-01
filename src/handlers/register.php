<?php
session_start();

include __DIR__ . '/../database/db_connection.php';

$message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    //check pswd
    if ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        //l'algo par default c bcrypt
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        //prépare query évite sql injection
        $check = $conn->prepare("SELECT email FROM userdata WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        //check si le mail existe déjà dans la bdd
        if ($check->num_rows > 0) {
            $message = "Cet email est déjà utilisé.";
        } else {

            $stmt = $conn->prepare("INSERT INTO userdata (username, email, password) VALUES (?, ?, ?)");
            //type sss dit qu'on accepte que des strings en values
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            //si tout vas bien execute query et envoi login
            if ($stmt->execute()) {
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $username;
            header("Location: home");
            exit();
            } else {
                $message = "Erreur lors de l'insertion.";
            }
            $stmt->close();
        }
        $check->close();
    }
}


$title = "Register - Ma Boutique";
?>

<body>
    <div class="container">
        <h2>Inscription</h2>
        <a href="home">Home</href>
        </a>

        <?php if (!empty($message)): ?>
            <p class="notification"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="register" method="post">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button type="submit" name="register">S'inscrire</button>
        </form>
    </div>
</body>

<?php 
require __DIR__ . '/../../templates/footer.php'; 
?>