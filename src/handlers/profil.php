<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] == "guest") {
    header("Location: login");
    exit();
}

$title = "Profil";

$target_user_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['user_id'] ?? 0);
if ($target_user_id === 0) { header("Location: login"); exit(); }

$is_own_profile = ($target_user_id === (int)($_SESSION['user_id'] ?? -1));

$stmt = $conn->prepare("SELECT balance, PP, role, username, email FROM userdata WHERE id = ?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { echo "<h1>Utilisateur introuvable</h1>"; exit(); }
$user = $result->fetch_assoc();
$stmt->close();

$username = $user['username'];
$email    = $user['email'];
$balance  = $user['balance'];
$PP       = $user['PP'];
$role     = $user['role'];

require __DIR__ . '/../../templates/header.php';
?>

<div class="profil-header">
    <img src="uploads/<?php echo htmlspecialchars($PP ?: 'default.png'); ?>" alt="Photo de profil" class="profil-avatar">
    <div>
        <div class="profil-name"><?php echo htmlspecialchars($username); ?></div>
        <div class="profil-role"><span class="badge badge-<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($role); ?></span></div>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="notification <?php echo $_SESSION['flash_type'] === 'success' ? 'notification-success' : 'notification-error'; ?>" style="margin-bottom:1.5rem;">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    </div>
<?php endif; ?>

<div class="profil-section">
    <h2>Articles en vente</h2>
    <?php
    $stmt_art = $conn->prepare("SELECT name, price FROM article WHERE author_id = ?");
    $stmt_art->bind_param("i", $target_user_id);
    $stmt_art->execute();
    $result_art = $stmt_art->get_result();
    if ($result_art->num_rows > 0) {
        echo '<ul class="articles-list">';
        while ($article = $result_art->fetch_assoc()) {
            echo '<li><span>' . htmlspecialchars($article['name']) . '</span><span class="article-price">' . number_format($article['price'], 2) . ' €</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Aucun article en vente pour le moment.</p>';
    }
    $stmt_art->close();
    ?>
</div>

<?php if ($is_own_profile): ?>

<div class="profil-section">
    <h2>Mon compte</h2>
    <p style="margin-bottom:0.5rem;">Email : <strong style="color:var(--ink);"><?php echo htmlspecialchars($email); ?></strong></p>
    <p>Solde :</p>
    <div class="balance-badge">
        <span class="balance-amount"><?php echo number_format($balance, 2); ?></span>
        <span class="balance-currency">€</span>
    </div>

    <h3>Mes factures</h3>
    <?php
    $stmt_inv = $conn->prepare("SELECT id, transaction_date, amount FROM invoice WHERE user_id = ?");
    $stmt_inv->bind_param("i", $target_user_id);
    $stmt_inv->execute();
    $result_inv = $stmt_inv->get_result();
    if ($result_inv->num_rows > 0) {
        echo '<ul class="invoice-list">';
        while ($invoice = $result_inv->fetch_assoc()) {
            echo '<li><span>Facture #' . htmlspecialchars($invoice['id']) . ' — ' . htmlspecialchars(substr($invoice['transaction_date'], 0, 10)) . '</span><span class="invoice-amount">' . number_format($invoice['amount'], 2) . ' €</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Aucune facture enregistrée.</p>';
    }
    $stmt_inv->close();
    ?>
</div>

<div class="profil-section">
    <h2>Modifier mes informations</h2>

    <h3>Changer la photo de profil</h3>
    <form action="update_profile" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pp">Nouvelle photo</label>
            <input type="file" name="pp" id="pp" accept="image/*" required>
        </div>
        <button type="submit" name="action" value="update_pp" class="btn-secondary">Mettre à jour</button>
    </form>

    <hr>

    <h3>Changer l'email</h3>
    <form action="update_profile" method="POST">
        <div class="form-group">
            <label for="new_email">Nouvel email</label>
            <input type="email" id="new_email" name="new_email" placeholder="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <button type="submit" name="action" value="update_email" class="btn-secondary">Modifier l'email</button>
    </form>

    <hr>

    <h3>Changer le mot de passe</h3>
    <form action="update_profile" method="POST">
        <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" placeholder="••••••••" required>
        </div>
        <button type="submit" name="action" value="update_password" class="btn-secondary">Modifier le mot de passe</button>
    </form>
</div>

<div class="profil-section">
    <h2>Ajouter des fonds</h2>
    <form action="update_profile" method="POST">
        <div class="form-group">
            <label for="amount">Montant (€)</label>
            <input type="number" id="amount" step="0.01" min="1" name="amount" placeholder="0.00" required>
        </div>
        <button type="submit" name="action" value="add_balance">Créditer mon compte</button>
    </form>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
