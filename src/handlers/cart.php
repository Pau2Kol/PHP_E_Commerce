<?php
session_start();
require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../../templates/header.php';

$title = "Mon Panier";

// Rediriger si non connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: login");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ── Action : Ajouter au panier ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $article_id = (int)$_POST['article_id'];

    // Vérifier que l'article n'est pas déjà dans le panier
    $check = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND article_id = ?");
    $check->bind_param("ii", $user_id, $article_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, article_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $article_id);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    header("Location: cart");
    exit();
}

// ── Action : Supprimer du panier ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $cart_id = (int)$_POST['cart_id'];

    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: cart");
    exit();
}

// ── Récupérer les articles du panier ──
$stmt = $conn->prepare("
    SELECT cart.id AS cart_id, article.id AS article_id,
           article.name, article.description, article.price, article.image_data
    FROM cart
    JOIN article ON cart.article_id = article.id
    WHERE cart.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculer le total
$total = array_sum(array_column($cart_items, 'price'));
$balance = (float)$_SESSION['balance'];
?>

<div class="cart-container">
    <h1>Mon Panier</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="notification <?= $_SESSION['flash_type'] === 'success' ? 'notification-success' : 'notification-error' ?>" style="margin-bottom:1rem;">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="cart-empty">
            <span>🛍️</span>
            <h2>Votre panier est vide</h2>
            <p>Explorez notre boutique et ajoutez des articles !</p>
            <a href="home" class="btn btn-primary" style="margin-top:1.5rem; display:inline-block;">Voir les articles</a>
        </div>
    <?php else: ?>

        <?php foreach ($cart_items as $item): 
            $image_src = null;
            if ($item['image_data']) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $image_type = $finfo->buffer($item['image_data']) ?: 'image/jpeg';
                $image_src = "data:" . $image_type . ";base64," . base64_encode($item['image_data']);
            }
        ?>
        <div class="cart-item">
            <?php if ($image_src): ?>
                <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            <?php else: ?>
                <img src="uploads/default.png" alt="Image par défaut">
            <?php endif; ?>

            <div class="cart-item-info">
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <p><?= htmlspecialchars(mb_strimwidth($item['description'], 0, 80, '…')) ?></p>
            </div>

            <div class="cart-item-price">
                <?= number_format($item['price'], 2) ?> €
            </div>

            <form action="cart" method="POST" style="margin:0;">
                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                <button type="submit" name="remove_from_cart" class="btn btn-danger" style="padding:0.4rem 0.9rem; font-size:0.8rem;" title="Retirer">✕</button>
            </form>
        </div>
        <?php endforeach; ?>

        <div class="cart-summary">
            <div>
                <div style="font-size:0.85rem; color:var(--texte-doux);">Total</div>
                <div class="cart-total"><?= number_format($total, 2) ?> €</div>
                <div style="font-size:0.82rem; color:var(--texte-doux); margin-top:0.3rem;">
                    Votre solde : <strong><?= number_format($balance, 2) ?> €</strong>
                </div>
            </div>

            <?php if ($balance >= $total): ?>
                <form action="validate" method="POST" style="margin:0;">
                    <button type="submit" name="confirm_purchase" class="btn btn-primary">
                        ✦ Valider la commande
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align:right;">
                    <p style="color:#c0534a; font-size:0.85rem; margin-bottom:0.75rem;">
                        Solde insuffisant (manque <?= number_format($total - $balance, 2) ?> €)
                    </p>
                    <a href="profil" class="btn btn-secondary">Recharger mon compte</a>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
