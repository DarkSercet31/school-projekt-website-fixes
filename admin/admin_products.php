<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php'); exit;
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../user/dashboard.php'); exit;
}

$lang    = $_SESSION['lang'] ?? 'en';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $stock = (int)($_POST['stock'] ?? 0);
        $img   = trim($_POST['image_url'] ?? '');

        if ($name === '' || $price <= 0) {
            $error = ($lang === 'de') ? 'Name und gültiger Preis erforderlich.' : 'Name and valid price required.';
        } else {
            $pdo->prepare(
                "INSERT INTO product (name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?)"
            )->execute([$name, $desc ?: null, $price, $stock, $img ?: null]);
            $success = ($lang === 'de') ? 'Produkt erstellt.' : 'Product created.';
        }
    }

    if ($action === 'update') {
        $id    = (int)($_POST['product_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $stock = (int)($_POST['stock'] ?? 0);
        $img   = trim($_POST['image_url'] ?? '');

        if ($id > 0 && $name !== '' && $price > 0) {
            $pdo->prepare(
                "UPDATE product SET name=?, description=?, price=?, stock=?, image_url=? WHERE pk_product_id=?"
            )->execute([$name, $desc ?: null, $price, $stock, $img ?: null, $id]);
            $success = ($lang === 'de') ? 'Produkt aktualisiert.' : 'Product updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['product_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM product WHERE pk_product_id = ?")->execute([$id]);
            $success = ($lang === 'de') ? 'Produkt gelöscht.' : 'Product deleted.';
        }
    }
}

$products = $pdo->query("SELECT * FROM product ORDER BY pk_product_id")->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3 py-4">

        <?php if ($success): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Produkte verwalten' : 'Manage products'; ?>
                    </h1>
                </div>
            </div>
        </section>

        <!-- Create product -->
        <section class="glass-card mb-3">
            <div class="glass-card-body">
                <h2 class="h6 mb-3"><?php echo ($lang === 'de') ? 'Neues Produkt' : 'New product'; ?></h2>
                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-3">
                        <label class="form-label"><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?>
                        </label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php echo ($lang === 'de') ? 'Preis (€)' : 'Price (€)'; ?></label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" min="0" value="99">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Image URL</label>
                        <input type="text" name="image_url" class="form-control">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary-soft">
                            <?php echo ($lang === 'de') ? 'Erstellen' : 'Create'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Product list -->
        <section class="glass-card">
            <div class="glass-card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Preis' : 'Price'; ?></th>
                                <th>Stock</th>
                                <th class="text-end">
                                    <?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <form method="post">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id"
                                           value="<?php echo (int)$p['pk_product_id']; ?>">
                                    <td><?php echo (int)$p['pk_product_id']; ?></td>
                                    <td>
                                        <input type="text" name="name" class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($p['name']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="description" class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($p['description'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="price" class="form-control form-control-sm"
                                               style="width:90px;" step="0.01" min="0.01"
                                               value="<?php echo number_format($p['price'], 2, '.', ''); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" name="stock" class="form-control form-control-sm"
                                               style="width:70px;" min="0"
                                               value="<?php echo (int)$p['stock']; ?>">
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="submit" class="btn btn-success">
                                                <?php echo ($lang === 'de') ? 'Speichern' : 'Save'; ?>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                                <td>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id"
                                               value="<?php echo (int)$p['pk_product_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
