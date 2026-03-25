<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_purchase'])) {
    header("Location: cart");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 1. Récupérer les articles du panier avec leur prix
$stmt = $conn->prepare("
    SELECT article.id AS article_id, article.price, article.name
    FROM cart
    JOIN article ON cart.article_id = article.id
    WHERE cart.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['flash_message'] = "Votre panier est vide.";
    $_SESSION['flash_type'] = "error";
    header("Location: cart");
    exit();
}

$total = array_sum(array_column($cart_items, 'price'));

// 2. Vérifier le solde
$stmt = $conn->prepare("SELECT balance FROM userdata WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$balance = (float)$res['balance'];

if ($balance < $total) {
    $_SESSION['flash_message'] = "Solde insuffisant pour finaliser la commande.";
    $_SESSION['flash_type'] = "error";
    header("Location: cart");
    exit();
}

// 3. Transaction : déduire le solde + créer une facture + vider le panier
$conn->begin_transaction();

try {
    // Déduire le solde
    $stmt = $conn->prepare("UPDATE userdata SET balance = balance - ? WHERE id = ?");
    $stmt->bind_param("di", $total, $user_id);
    $stmt->execute();
    $stmt->close();

    // Créer une facture (adresse factice pour l'instant)
    $billing_address = "Adresse non renseignée";
    $billing_city    = "Ville";
    $billing_zip     = "00000";

    $stmt = $conn->prepare("INSERT INTO invoice (user_id, amount, billing_address, billing_city, billing_zip) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $user_id, $total, $billing_address, $billing_city, $billing_zip);
    $stmt->execute();
    $invoice_id = $conn->insert_id;
    $stmt->close();

    // Vider le panier
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Mettre à jour la session
    $_SESSION['balance'] = $balance - $total;

    $_SESSION['flash_message'] = "✦ Commande validée ! Facture #" . $invoice_id . " — " . number_format($total, 2) . " € débités.";
    $_SESSION['flash_type'] = "success";
    header("Location: profil");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = "Erreur lors de la validation : " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
    header("Location: cart");
    exit();
}
