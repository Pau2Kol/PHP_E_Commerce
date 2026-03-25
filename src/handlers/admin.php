<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home");
    exit();
}

$title = "Panel Admin";
require __DIR__ . '/../../templates/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['target_id'];
    if ($target_id !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM userdata WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    $article_id = (int)$_POST['article_id'];
    $stmt = $conn->prepare("DELETE FROM article WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $target_id = (int)$_POST['target_id'];
    $new_role  = in_array($_POST['new_role'], ['user', 'admin']) ? $_POST['new_role'] : 'user';
    if ($target_id !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE userdata SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $target_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin"); exit();
}

$nb_users    = $conn->query("SELECT COUNT(*) AS n FROM userdata")->fetch_assoc()['n'];
$nb_articles = $conn->query("SELECT COUNT(*) AS n FROM article")->fetch_assoc()['n'];
$nb_invoices = $conn->query("SELECT COUNT(*) AS n FROM invoice")->fetch_assoc()['n'];
$total_rev   = $conn->query("SELECT IFNULL(SUM(amount), 0) AS s FROM invoice")->fetch_assoc()['s'];

$users    = $conn->query("SELECT id, username, email, balance, role FROM userdata ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$articles = $conn->query("SELECT article.id, article.name, article.price, article.published_at, userdata.username AS author FROM article JOIN userdata ON article.author_id = userdata.id ORDER BY article.published_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
$invoices = $conn->query("SELECT invoice.id, invoice.amount, invoice.transaction_date, userdata.username FROM invoice JOIN userdata ON invoice.user_id = userdata.id ORDER BY invoice.transaction_date DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <h1>Panel Administrateur</h1>
    <p>Bienvenue, <strong style="color:var(--ink);"><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
</div>

<div class="admin-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon"></div>
        <div class="admin-stat-number"><?= $nb_users ?></div>
        <div class="admin-stat-label">Utilisateurs</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon"></div>
        <div class="admin-stat-number"><?= $nb_articles ?></div>
        <div class="admin-stat-label">Articles en vente</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon"></div>
        <div class="admin-stat-number"><?= $nb_invoices ?></div>
        <div class="admin-stat-label">Commandes — <?= number_format($total_rev, 2) ?> €</div>
    </div>
</div>

<div class="admin-tabs">
    <button class="admin-tab active" onclick="showTab('users', this)"> Utilisateurs</button>
    <button class="admin-tab" onclick="showTab('articles', this)"> Articles</button>
    <button class="admin-tab" onclick="showTab('invoices', this)"> Commandes</button>
</div>

<div id="tab-users" class="tab-panel active">
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead><tr>
            <th>#</th><th>Pseudo</th><th>Email</th><th>Solde</th><th>Rôle</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><a href="profil?id=<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= number_format($u['balance'], 2) ?> €</td>
                <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                <td>
                    <div class="action-btns">
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form action="admin" method="POST" class="inline-form">
                            <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                            <button type="submit" name="change_role" class="btn btn-secondary" style="padding:0.28rem 0.75rem;font-size:0.75rem;">
                                <?= $u['role'] === 'admin' ? '↓ user' : '↑ admin' ?>
                            </button>
                        </form>
                        <form action="admin" method="POST" class="inline-form" onsubmit="return confirm('Supprimer <?= htmlspecialchars(addslashes($u['username'])) ?> ?')">
                            <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger" style="padding:0.28rem 0.75rem;font-size:0.75rem;">Supprimer</button>
                        </form>
                        <?php else: ?>
                            <em class="self-label">Vous</em>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-articles" class="tab-panel">
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead><tr>
            <th>#</th><th>Nom</th><th>Prix</th><th>Vendeur</th><th>Date</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($articles as $art): ?>
            <tr>
                <td><?= (int)$art['id'] ?></td>
                <td><?= htmlspecialchars($art['name']) ?></td>
                <td><?= number_format($art['price'], 2) ?> €</td>
                <td><?= htmlspecialchars($art['author']) ?></td>
                <td><?= htmlspecialchars(substr($art['published_at'], 0, 10)) ?></td>
                <td>
                    <form action="admin" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cet article ?')">
                        <input type="hidden" name="article_id" value="<?= (int)$art['id'] ?>">
                        <button type="submit" name="delete_article" class="btn btn-danger" style="padding:0.28rem 0.75rem;font-size:0.75rem;">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-invoices" class="tab-panel">
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead><tr>
            <th>#</th><th>Utilisateur</th><th>Montant</th><th>Date</th>
        </tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><?= (int)$inv['id'] ?></td>
                <td><?= htmlspecialchars($inv['username']) ?></td>
                <td><?= number_format($inv['amount'], 2) ?> €</td>
                <td><?= htmlspecialchars($inv['transaction_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
