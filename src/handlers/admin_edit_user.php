<?php
session_start();
require __DIR__ . '/../database/db_connection.php';

// Acces reserve aux administrateurs
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home");
    exit();
}

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($target_id === 0) {
    header("Location: admin");
    exit();
}

// Recuperation de l'utilisateur cible
$stmt = $conn->prepare("SELECT id, username, email, balance, role FROM userdata WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: admin");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

$message      = "";
$message_type = "error";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $new_username = trim($_POST['username']);
    $new_email    = trim($_POST['email']);
    $new_balance  = (float)$_POST['balance'];
    $new_role     = in_array($_POST['role'], ['user', 'admin']) ? $_POST['role'] : 'user';

    // Verification que l'email ou le username ne sont pas deja pris par un autre utilisateur
    $check = $conn->prepare("SELECT id FROM userdata WHERE (email = ? OR username = ?) AND id != ?");
    $check->bind_param("ssi", $new_email, $new_username, $target_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Ce nom d'utilisateur ou cet email est deja utilise.";
    } else {
        // Mise a jour du mot de passe si renseigne
        if (!empty($_POST['new_password'])) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE userdata SET username=?, email=?, balance=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("ssdssi", $new_username, $new_email, $new_balance, $new_role, $hashed, $target_id);
        } else {
            $stmt = $conn->prepare("UPDATE userdata SET username=?, email=?, balance=?, role=? WHERE id=?");
            $stmt->bind_param("ssdsi", $new_username, $new_email, $new_balance, $new_role, $target_id);
        }

        if ($stmt->execute()) {
            $message      = "Utilisateur mis a jour avec succes.";
            $message_type = "success";
            $user['username'] = $new_username;
            $user['email']    = $new_email;
            $user['balance']  = $new_balance;
            $user['role']     = $new_role;
        } else {
            $message = "Erreur lors de la mise a jour.";
        }
        $stmt->close();
    }
    $check->close();
}

$title = "Modifier l'utilisateur";
require __DIR__ . '/../../templates/header.php';
?>

<div class="sell-container">
    <a href="admin" class="back-link" style="font-size:0.85rem; color:var(--ink-muted); display:inline-flex; align-items:center; gap:0.3rem; margin-bottom:1rem;">
        &#8592; Retour au panel admin
    </a>
    <h1>Modifier l'utilisateur</h1>
    <p style="margin-bottom:1.5rem;">
        Modification du compte <strong style="color:var(--ink);"><?= htmlspecialchars($user['username']) ?></strong>
    </p>

    <?php if ($message): ?>
        <div class="notification notification-<?= $message_type ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="admin_edit_user?id=<?= $target_id ?>" method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="balance">Solde (EUR)</label>
                <input type="number" id="balance" name="balance" step="0.01" min="0" value="<?= number_format($user['balance'], 2, '.', '') ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="user"  <?= $user['role'] === 'user'  ? 'selected' : '' ?>>user</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                <input type="password" id="new_password" name="new_password" placeholder="Nouveau mot de passe">
            </div>
            <div class="form-actions">
                <button type="submit" name="update_user">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
