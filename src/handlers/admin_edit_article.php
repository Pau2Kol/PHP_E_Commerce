<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

// Acces reserve aux administrateurs
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home");
    exit();
}

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($article_id === 0) {
    header("Location: admin");
    exit();
}

// Recuperation de l'article et de son stock
$stmt = $conn->prepare("
    SELECT article.*, COALESCE(stock.quantity, 0) AS stock_quantity
    FROM article
    LEFT JOIN stock ON stock.article_id = article.id
    WHERE article.id = ?
");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: admin");
    exit();
}
$article = $result->fetch_assoc();
$stmt->close();

$message      = "";
$message_type = "error";

// Suppression de l'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    $stmt = $conn->prepare("DELETE FROM article WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin");
    exit();
}

// Mise a jour de l'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_article'])) {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $quantity    = max(0, (int)$_POST['quantity']);

    $conn->begin_transaction();
    try {
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $image_data = file_get_contents($_FILES['product_image']['tmp_name']);
            $stmt = $conn->prepare("UPDATE article SET name=?, description=?, price=?, image_data=? WHERE id=?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $image_data, $article_id);
        } else {
            $stmt = $conn->prepare("UPDATE article SET name=?, description=?, price=? WHERE id=?");
            $stmt->bind_param("ssdi", $name, $description, $price, $article_id);
        }
        $stmt->execute();
        $stmt->close();

        // Mise a jour du stock (insertion ou mise a jour si la ligne existe deja)
        $stmt = $conn->prepare("INSERT INTO stock (article_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = ?");
        $stmt->bind_param("iii", $article_id, $quantity, $quantity);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $message              = "Article mis a jour avec succes.";
        $message_type         = "success";
        $article['name']        = $name;
        $article['description'] = $description;
        $article['price']       = $price;
        $article['stock_quantity'] = $quantity;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Erreur lors de la mise a jour : " . $e->getMessage();
    }
}

$title = "Modifier l'article (admin)";
require __DIR__ . '/../../templates/header.php';
?>

<div class="sell-container">
    <a href="admin" class="back-link" style="font-size:0.85rem; color:var(--ink-muted); display:inline-flex; align-items:center; gap:0.3rem; margin-bottom:1rem;">
        &#8592; Retour au panel admin
    </a>

    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <h1>Modifier l'article</h1>
        <form action="admin_edit_article?id=<?= $article_id ?>" method="POST" onsubmit="return confirm('Supprimer definitivement cet article ?')" style="margin:0;">
            <button type="submit" name="delete_article" class="btn btn-danger">Supprimer l'article</button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="notification notification-<?= $message_type ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="admin_edit_article?id=<?= $article_id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Nom du produit</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($article['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($article['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="price">Prix unitaire (EUR)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?= number_format($article['price'], 2, '.', '') ?>" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantite en stock</label>
                <input type="number" id="quantity" name="quantity" min="0" value="<?= (int)$article['stock_quantity'] ?>" required>
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
