<?php
$title = "Modifier l'article";

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../../templates/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: login");
    exit();
}

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($article_id === 0) { header("Location: home"); exit(); }

$stmt = $conn->prepare("SELECT * FROM article WHERE id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: home");
    exit();
}

$article = $result->fetch_assoc();
$stmt->close();

$is_author = (int)$_SESSION['user_id'] === (int)$article['author_id'];
$is_admin  = $_SESSION['role'] === 'admin';

if (!$is_author && !$is_admin) {
    header("Location: home");
    exit();
}

$message      = "";
$message_type = "error";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    $stmt = $conn->prepare("DELETE FROM article WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();
    header("Location: home");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_article'])) {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $image_data = file_get_contents($_FILES['product_image']['tmp_name']);
        $stmt = $conn->prepare("UPDATE article SET name=?, description=?, price=?, image_data=? WHERE id=?");
        $stmt->bind_param("ssdsi", $name, $description, $price, $image_data, $article_id);
    } else {
        $stmt = $conn->prepare("UPDATE article SET name=?, description=?, price=? WHERE id=?");
        $stmt->bind_param("ssdi", $name, $description, $price, $article_id);
    }

    if ($stmt->execute()) {
        $message      = "Article mis à jour avec succès.";
        $message_type = "success";
        $article['name']        = $name;
        $article['description'] = $description;
        $article['price']       = $price;
    } else {
        $message = "Erreur lors de la mise à jour.";
    }
    $stmt->close();
}
?>

<div class="sell-container">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <a href="detail?id=<?= $article_id ?>" class="back-link" style="font-size:0.85rem; color:var(--ink-muted); display:inline-flex; align-items:center; gap:0.3rem;">← Retour à l'article</a>
            <h1 style="margin-top:0.3rem;">Modifier l'article</h1>
        </div>

        <form action="edit?id=<?= $article_id ?>" method="POST" onsubmit="return confirm('Supprimer définitivement cet article ?')" style="margin:0;">
            <button type="submit" name="delete_article" class="btn btn-danger">🗑 Supprimer l'article</button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="notification notification-<?= $message_type ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="edit?id=<?= $article_id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Nom du produit</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($article['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($article['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="price">Prix (€)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?= number_format($article['price'], 2, '.', '') ?>" required>
            </div>
            <div class="form-group">
                <label for="product_image">Nouvelle image (optionnel)</label>
                <input type="file" id="product_image" name="product_image" accept="image/*">
            </div>
            <div class="form-actions">
                <button type="submit" name="update_article">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
