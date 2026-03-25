<?php
$title = "Accueil - Ma Boutique";

require __DIR__ . "/../database/db_connection.php";
require __DIR__ . '/../../templates/header.php';

// Recherche par mot-cle dans le nom ou la description
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("
        SELECT article.id, article.name, article.description, article.price,
               article.published_at, article.author_id, article.image_data,
               COALESCE(stock.quantity, 0) AS stock_quantity
        FROM article
        LEFT JOIN stock ON stock.article_id = article.id
        WHERE article.name LIKE ? OR article.description LIKE ?
        ORDER BY article.published_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $articles = $stmt->get_result();
    $stmt->close();
} else {
    $articles = $conn->query("
        SELECT article.id, article.name, article.description, article.price,
               article.published_at, article.author_id, article.image_data,
               COALESCE(stock.quantity, 0) AS stock_quantity
        FROM article
        LEFT JOIN stock ON stock.article_id = article.id
        ORDER BY article.published_at DESC
        LIMIT 20
    ");
}
?>

<div class="page-header">
    <h1>
        <?php if (isset($_SESSION['username'])): ?>
            Bonjour, <?php echo htmlspecialchars($_SESSION['username']); ?>
        <?php else: ?>
            Bienvenue
        <?php endif; ?>
    </h1>
    <p>Decouvrez les articles disponibles a la vente.</p>
</div>

<!-- Barre de recherche -->
<form action="home" method="GET" class="search-form">
    <div class="search-wrap">
        <input
            type="text"
            name="q"
            placeholder="Rechercher un article..."
            value="<?= htmlspecialchars($search) ?>"
            class="search-input"
        >
        <button type="submit" class="search-btn">Rechercher</button>
        <?php if ($search !== ''): ?>
            <a href="home" class="search-clear">Effacer</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($search !== ''): ?>
    <p style="margin-bottom:1rem; font-size:0.85rem; color:var(--ink-muted);">
        Resultats pour : <strong style="color:var(--ink);"><?= htmlspecialchars($search) ?></strong>
    </p>
<?php endif; ?>

<div class="articles-grid">
<?php
$found = false;
foreach ($articles as $article):
    $found = true;
    $image_type = 'image/jpeg';
    if ($article['image_data']) {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($article['image_data']);
        if ($detected) $image_type = $detected;
    }
    $stock = (int)$article['stock_quantity'];
?>
    <article>
        <a href="detail?id=<?= (int)$article['id'] ?>" style="display:block; text-decoration:none; color:inherit;">
            <?php if ($article['image_data']): ?>
                <img src="data:<?= $image_type ?>;base64,<?= base64_encode($article['image_data']) ?>" alt="<?= htmlspecialchars($article['name']) ?>">
            <?php else: ?>
                <img src="uploads/default.png" alt="Image par defaut">
            <?php endif; ?>
            <h2><?= htmlspecialchars($article['name']) ?></h2>
            <p><?= htmlspecialchars(mb_strimwidth($article['description'], 0, 80, '...')) ?></p>
        </a>
        <strong><?= number_format($article['price'], 2) ?> EUR</strong>
        <small>Publie le <?= htmlspecialchars(substr($article['published_at'], 0, 10)) ?></small>

        <!-- Indicateur de stock discret -->
        <?php if ($stock <= 0): ?>
            <span class="card-stock card-stock-empty">Stock epuise</span>
        <?php elseif ($stock <= 3): ?>
            <span class="card-stock card-stock-low">Plus que <?= $stock ?></span>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'guest'): ?>
            <?php if ($stock <= 0): ?>
                <button class="btn-cart" disabled style="opacity:0.4; cursor:not-allowed;">Stock epuise</button>
            <?php else: ?>
                <form action="cart" method="POST" style="margin:0;">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <button type="submit" name="add_to_cart" class="btn-cart">Ajouter au panier</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <a href="login" class="btn-cart">Se connecter pour acheter</a>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
</div>

<?php if (!$found): ?>
    <div class="cart-empty">
        <div class="empty-icon">&#9679;</div>
        <h2>Aucun article trouve</h2>
        <p>Essayez un autre mot-cle ou <a href="home">voir tous les articles</a>.</p>
    </div>
<?php endif; ?>

<style>
.search-form { margin-bottom:1.5rem; }
.search-wrap { display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap; }
.search-input { flex:1; min-width:200px; padding:0.65rem 1rem; border:1.5px solid var(--border-mid); border-radius:50px; font-family:var(--font-body); font-size:0.9rem; color:var(--ink); background:var(--surface); outline:none; transition:border-color var(--transition), box-shadow var(--transition); }
.search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(124,92,191,0.12); }
.search-btn { padding:0.65rem 1.4rem; background:var(--accent); color:#fff; border:none; border-radius:50px; font-family:var(--font-body); font-size:0.88rem; font-weight:500; cursor:pointer; transition:background var(--transition); }
.search-btn:hover { background:#6a4dab; }
.search-clear { font-size:0.82rem; color:var(--ink-muted); padding:0.3rem 0.6rem; border-radius:50px; border:1px solid var(--border-mid); transition:color var(--transition); }
.search-clear:hover { color:var(--error-fg); }
.card-stock { display:inline-block; font-size:0.72rem; font-weight:600; padding:0.15rem 0.6rem; border-radius:50px; }
.card-stock-empty { background:rgba(140,46,46,0.1); color:#7a2a2a; }
.card-stock-low   { background:rgba(180,110,20,0.1); color:#8a5a10; }
</style>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
