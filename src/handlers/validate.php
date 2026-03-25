<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

// Acces reserve aux utilisateurs connectes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_purchase'])) {
    header("Location: cart");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Recuperation du panier avec le stock disponible pour chaque article
$stmt = $conn->prepare("
    SELECT article.id AS article_id, article.price, article.name,
           COALESCE(stock.quantity, 0) AS stock_quantity
    FROM cart
    JOIN article ON cart.article_id = article.id
    LEFT JOIN stock ON stock.article_id = article.id
    WHERE cart.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result     = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['flash_message'] = "Votre panier est vide.";
    $_SESSION['flash_type']    = "error";
    header("Location: cart");
    exit();
}

// Verification du stock avant tout traitement
foreach ($cart_items as $item) {
    if ((int)$item['stock_quantity'] <= 0) {
        $_SESSION['flash_message'] = "L'article \"" . $item['name'] . "\" n'est plus disponible en stock.";
        $_SESSION['flash_type']    = "error";
        header("Location: cart");
        exit();
    }
}

$total = array_sum(array_column($cart_items, 'price'));

$stmt = $conn->prepare("SELECT balance FROM userdata WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res     = $stmt->get_result()->fetch_assoc();
$stmt->close();
$balance = (float)$res['balance'];

if ($balance < $total) {
    $_SESSION['flash_message'] = "Solde insuffisant pour finaliser la commande.";
    $_SESSION['flash_type']    = "error";
    header("Location: cart");
    exit();
}

$title = "Informations de facturation";
require __DIR__ . '/../../templates/header.php';

$billing_error = "";

if (isset($_POST['finalize_order'])) {
    $billing_address = trim($_POST['billing_address']);
    $billing_city    = trim($_POST['billing_city']);
    $billing_zip     = trim($_POST['billing_zip']);

    if (empty($billing_address) || empty($billing_city) || empty($billing_zip)) {
        $billing_error = "Veuillez remplir tous les champs de facturation.";
    } else {
        $conn->begin_transaction();
        try {
            // Debit du solde utilisateur
            $stmt = $conn->prepare("UPDATE userdata SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $total, $user_id);
            $stmt->execute();
            $stmt->close();

            // Creation de la facture
            $stmt = $conn->prepare("INSERT INTO invoice (user_id, amount, billing_address, billing_city, billing_zip) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $user_id, $total, $billing_address, $billing_city, $billing_zip);
            $stmt->execute();
            $invoice_id = $conn->insert_id;
            $stmt->close();

            // Decrementation du stock pour chaque article achete
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("UPDATE stock SET quantity = quantity - 1 WHERE article_id = ? AND quantity > 0");
                $stmt->bind_param("i", $item['article_id']);
                $stmt->execute();
                $stmt->close();
            }

            // Vidage du panier
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $_SESSION['balance']       = $balance - $total;
            $_SESSION['flash_message'] = "Commande validee. Facture #" . $invoice_id . " - " . number_format($total, 2) . " EUR debites.";
            $_SESSION['flash_type']    = "success";
            header("Location: profil");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $billing_error = "Erreur lors de la validation : " . $e->getMessage();
        }
    }
}
?>

<div class="validate-container">
    <a href="cart" class="back-link">&#8592; Retour au panier</a>
    <h1>Finaliser la commande</h1>

    <div class="validate-grid">
        <div>
            <div class="card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Recapitulatif</h2>
                <ul class="invoice-list">
                    <?php foreach ($cart_items as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['name']) ?></span>
                            <span class="invoice-amount"><?= number_format($item['price'], 2) ?> EUR</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border);">
                    <span style="font-size:0.8rem; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em;">Total</span>
                    <span style="font-family:var(--font-display); font-size:1.8rem; color:var(--accent);"><?= number_format($total, 2) ?> EUR</span>
                </div>
                <p style="font-size:0.8rem; margin-top:0.5rem;">Solde apres achat : <strong style="color:var(--ink);"><?= number_format($balance - $total, 2) ?> EUR</strong></p>
            </div>
        </div>

        <div class="card">
            <h2 style="font-size:1.1rem; margin-bottom:1.2rem;">Adresse de facturation</h2>

            <?php if ($billing_error): ?>
                <div class="notification notification-error" style="margin-bottom:1.2rem;">
                    <?= htmlspecialchars($billing_error) ?>
                </div>
            <?php endif; ?>

            <form action="validate" method="POST">
                <input type="hidden" name="confirm_purchase" value="1">

                <div class="form-group">
                    <label for="billing_address">Adresse</label>
                    <input type="text" id="billing_address" name="billing_address" placeholder="12 rue de la Paix" required value="<?= htmlspecialchars($_POST['billing_address'] ?? '') ?>">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label for="billing_city">Ville</label>
                        <input type="text" id="billing_city" name="billing_city" placeholder="Paris" required value="<?= htmlspecialchars($_POST['billing_city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="billing_zip">Code postal</label>
                        <input type="text" id="billing_zip" name="billing_zip" placeholder="75001" required value="<?= htmlspecialchars($_POST['billing_zip'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" name="finalize_order" class="w-full" style="margin-top:0.5rem;">
                    Confirmer la commande
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.validate-container { max-width:860px; margin:0 auto; }
.validate-container h1 { margin:0.5rem 0 1.5rem; }
.validate-grid { display:grid; grid-template-columns:1fr 1.4fr; gap:1.5rem; }
.back-link { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.85rem; color:var(--ink-muted); margin-bottom:1rem; transition:color var(--transition); }
.back-link:hover { color:var(--accent); }
@media (max-width:640px) { .validate-grid { grid-template-columns:1fr; } }
</style>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
