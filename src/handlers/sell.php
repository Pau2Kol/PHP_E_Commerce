<?php
$title = "Vendre un produit";

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../../templates/header.php';

// Acces reserve aux utilisateurs connectes
if (!isset($_SESSION['role']) || $_SESSION['role'] == "guest") {
    header("Location: login");
    exit();
}

$sell_message = "";
$sell_type    = "error";

if (isset($_POST["submit_sell"])) {
    $product_name = trim($_POST["product_name"]);
    $description  = trim($_POST["description"]);
    $price        = (float)$_POST["price"];
    $quantity     = max(1, (int)$_POST["quantity"]);
    $author_id    = (int)$_SESSION['user_id'];

    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] === UPLOAD_ERR_OK) {
        $image_data = file_get_contents($_FILES["product_image"]["tmp_name"]);

        // Transaction : creation de l'article et de son stock en une seule operation atomique
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO article (name, description, price, author_id, image_data) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdis", $product_name, $description, $price, $author_id, $image_data);
            $stmt->execute();
            $new_article_id = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO stock (article_id, quantity) VALUES (?, ?)");
            $stmt->bind_param("ii", $new_article_id, $quantity);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $sell_message = "Produit mis en vente avec succes.";
            $sell_type    = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $sell_message = "Erreur lors de la mise en vente : " . $e->getMessage();
        }
    } else {
        $sell_message = "Veuillez selectionner une image valide.";
    }
}
?>

<div class="sell-container">
    <h1>Mettre en vente</h1>

    <?php if ($sell_message): ?>
        <div class="notification notification-<?php echo $sell_type; ?>" style="margin-bottom:1.5rem;">
            <?php echo htmlspecialchars($sell_message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="sell" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="product_name">Nom du produit</label>
                <input type="text" id="product_name" name="product_name" placeholder="Ex : iPhone 15 Pro" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Decrivez l'etat et les caracteristiques..." required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Prix unitaire (EUR)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantite disponible</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label for="product_image">Image du produit</label>
                <input type="file" id="product_image" name="product_image" accept="image/*" required>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit_sell">Publier l'annonce</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
