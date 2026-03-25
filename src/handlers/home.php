<?php
$title = "Accueil — Ma Boutique";

require __DIR__ . "/../database/db_connection.php";
require __DIR__ . '/../../templates/header.php';

$articles = $conn->query("SELECT id, name, description, price, published_at, author_id, image_data FROM article ORDER BY published_at DESC LIMIT 20");
?>

<div class="page-header">
    <h1>
        <?php if (isset($_SESSION['username'])): ?>
            Bonjour, <?php echo htmlspecialchars($_SESSION['username']); ?> ✦
        <?php else: ?>
            Bienvenue ✦
        <?php endif; ?>
    </h1>
    <p>Découvrez les articles disponibles à la vente.</p>
</div>

<div class="articles-grid">
<?php foreach ($articles as $article):
    $image_type = 'image/jpeg';
    if ($article['image_data']) {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($article['image_data']);
        if ($detected) $image_type = $detected;
    }
?>
    <article>
        <a href="detail?id=<?= (int)$article['id'] ?>" style="display:block;text-decoration:none;color:inherit;">
            <?php if ($article['image_data']): ?>
                <img src="data:<?= $image_type ?>;base64,<?= base64_encode($article['image_data']) ?>" alt="<?= htmlspecialchars($article['name']) ?>">
            <?php else: ?>
                <img src="uploads/default.png" alt="Image par défaut">
            <?php endif; ?>
            <h2><?= htmlspecialchars($article['name']) ?></h2>
            <p><?= htmlspecialchars(mb_strimwidth($article['description'], 0, 80, '…')) ?></p>
        </a>
        <strong><?= number_format($article['price'], 2) ?> €</strong>
        <small>Publié le <?= htmlspecialchars(substr($article['published_at'], 0, 10)) ?></small>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'guest'): ?>
            <form action="cart" method="POST" style="margin:0;">
                <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                <button type="submit" name="add_to_cart" class="btn-cart">🛒 Ajouter au panier</button>
            </form>
        <?php else: ?>
            <a href="login" class="btn-cart">Se connecter pour acheter</a>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
