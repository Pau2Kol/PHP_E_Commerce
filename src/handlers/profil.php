<?php
session_start();
$title = "Profil";
require __DIR__ . '/../database/db_connection.php';

if ($_SESSION['role'] == "guest") {
    header("Location: login.php");
    exit();
}

$target_user_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['user_id'];
$is_own_profile = ($target_user_id === (int)$_SESSION['user_id']);

$stmt = $conn->prepare("SELECT balance, PP, role, username, email FROM userdata WHERE id = ?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "<h1>Utilisateur introuvable</h1>";
    exit();
}

$stmt->bind_result($balance, $PP, $role, $username, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?php echo htmlspecialchars($username); ?></title>
</head>
<body>

<nav>
    <a href="home">Accueil</a> 
</nav>

<h1><?php echo htmlspecialchars($username); ?></h1>
<img src="../uploads/<?php echo htmlspecialchars($PP ?: 'default.png'); ?>" alt="Photo de profil">

<section>
    <h2>Articles en vente</h2>
    <?php
    $stmt_art = $conn->prepare("SELECT name, price FROM Article WHERE author_id = ?");
    $stmt_art->bind_param("i", $target_user_id);
    $stmt_art->execute();
    $result_art = $stmt_art->get_result();

    if ($result_art->num_rows > 0) {
        echo "<ul>";
        while ($article = $result_art->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($article['name']) . " — " . htmlspecialchars($article['price']) . " €</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun article en vente.</p>";
    }
    $stmt_art->close();
    ?>
</section>

<?php if ($is_own_profile): ?>

<hr>

<section>
    <h2>Mon compte</h2>
    <p>Email : <?php echo htmlspecialchars($email); ?></p>
    <p>Solde : <?php echo htmlspecialchars($balance); ?> €</p>

    <h3>Mes factures</h3>
    <?php
    $stmt_inv = $conn->prepare("SELECT id, transaction_date, amount FROM Invoice WHERE user_id = ?");
    $stmt_inv->bind_param("i", $target_user_id);
    $stmt_inv->execute();
    $result_inv = $stmt_inv->get_result();

    if ($result_inv->num_rows > 0) {
        echo "<ul>";
        while ($invoice = $result_inv->fetch_assoc()) {
            echo "<li>Facture #" . htmlspecialchars($invoice['id']) . " — " . htmlspecialchars($invoice['amount']) . " € le " . htmlspecialchars($invoice['transaction_date']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucune facture.</p>";
    }
    $stmt_inv->close();
    ?>

    <h3>Modifier mes informations</h3>
    <form action="update_profile.php" method="POST">
        <label>Nouvel email :</label>
        <input type="email" name="new_email" required>
        <button type="submit" name="action" value="update_email">Modifier</button>
    </form>

    <form action="update_profile.php" method="POST">
        <label>Nouveau mot de passe :</label>
        <input type="password" name="new_password" required>
        <button type="submit" name="action" value="update_password">Modifier</button>
    </form>

    <h3>Ajouter des fonds</h3>
    <form action="update_profile.php" method="POST">
        <label>Montant (€) :</label>
        <input type="number" step="0.01" name="amount" required>
        <button type="submit" name="action" value="add_balance">Créditer</button>
    </form>
</section>

<?php endif; ?>

</body>
</html>