<?php
$title = "Détail de l'article";

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../../templates/header.php';

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id === 0) {
    header("Location: home");
    exit();
}

$stmt = $conn->prepare("
    SELECT article.*, userdata.username AS author_name
    FROM article
    JOIN userdata ON article.author_id = userdata.id
    WHERE article.id = ?
");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<main><h1>Article introuvable</h1><a href='home'>Retour à l'accueil</a></main>";
    require __DIR__ . '/../../templates/footer.php';
    exit();
}

$article = $result->fetch_assoc();
$stmt->close();

$image_src = null;
if ($article['image_data']) {
    $finfo      = new finfo(FILEINFO_MIME_TYPE);
    $image_type = $finfo->buffer($article['image_data']) ?: 'image/jpeg';
    $image_src  = "data:" . $image_type . ";base64," . base64_encode($article['image_data']);
}

$is_author = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$article['author_id'];
$is_admin  = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<div class="detail-container">
    <a href="home" class="back-link">← Retour aux articles</a>

    <div class="detail-card">
        <div class="detail-image-wrap">
            <?php if ($image_src): ?>
                <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($article['name']) ?>" class="detail-image">
            <?php else: ?>
                <img src="uploads/default.png" alt="Image par défaut" class="detail-image">
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($article['name']) ?></h1>

            <div class="detail-meta">
                <span>Vendeur : <a href="profil?id=<?= (int)$article['author_id'] ?>"><?= htmlspecialchars($article['author_name']) ?></a></span>
                <span>Publié le <?= htmlspecialchars(substr($article['published_at'], 0, 10)) ?></span>
            </div>

            <div class="detail-price"><?= number_format($article['price'], 2) ?> €</div>

            <div class="detail-description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($article['description'])) ?></p>
            </div>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'guest'): ?>
                <form action="cart" method="POST" style="margin:0;">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <button type="submit" name="add_to_cart" class="btn-cart-detail">
                        🛒 Ajouter au panier
                    </button>
                </form>
            <?php else: ?>
                <a href="login" class="btn-cart-detail" style="text-align:center;">Se connecter pour acheter</a>
            <?php endif; ?>

            <?php if ($is_author || $is_admin): ?>
                <div style="margin-top:1rem;">
                    <a href="edit?id=<?= (int)$article['id'] ?>" class="btn btn-secondary" style="font-size:0.82rem; padding:0.5rem 1.2rem;">✏️ Modifier l'article</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.detail-container { max-width: 960px; margin: 0 auto; }
.back-link { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.85rem; color:var(--ink-muted); margin-bottom:1.5rem; transition:color var(--transition); }
.back-link:hover { color:var(--accent); }
.detail-card { display:grid; grid-template-columns:1fr 1fr; gap:2.5rem; background:var(--surface); border-radius:var(--radius); border:1px solid var(--border); padding:2rem; box-shadow:var(--shadow); }
.detail-image-wrap { border-radius:var(--radius-sm); overflow:hidden; aspect-ratio:1; }
.detail-image { width:100%; height:100%; object-fit:cover; }
.detail-info { display:flex; flex-direction:column; gap:1rem; }
.detail-meta { display:flex; flex-direction:column; gap:0.2rem; font-size:0.8rem; color:var(--ink-muted); }
.detail-meta a { color:var(--accent-2); }
.detail-price { font-family:var(--font-display); font-size:2.2rem; color:var(--accent); line-height:1; }
.detail-description h3 { font-size:0.85rem; font-weight:600; color:var(--ink-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.5rem; }
.detail-description p { font-size:0.9rem; color:var(--ink-soft); line-height:1.7; }
.btn-cart-detail { display:block; width:100%; padding:0.8rem 1.5rem; background:var(--ink); color:#fff; border:none; border-radius:50px; font-family:var(--font-body); font-size:0.9rem; font-weight:500; cursor:pointer; text-align:center; transition:background var(--transition), transform var(--transition); }
.btn-cart-detail:hover { background:var(--accent); transform:translateY(-1px); color:#fff; }
@media (max-width:680px) { .detail-card { grid-template-columns:1fr; } .detail-image-wrap { aspect-ratio:16/9; } }
</style>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
