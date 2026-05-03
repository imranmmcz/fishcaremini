<?php
/**
 * Fish Care System - Customer Purchases Page
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'customer') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'মাছ ক্রয়';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'অবৈধ অনুরোধ।';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            try {
                $pdo = getDBConnection();

                $stmt = $pdo->prepare("
                    INSERT INTO sales (customer_id, seller_id, fish_type, quantity_kg, price_per_kg, total_amount, sale_date, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    intval($_POST['seller_id']),
                    sanitize($_POST['fish_type']),
                    floatval($_POST['quantity_kg']),
                    floatval($_POST['price_per_kg']),
                    floatval($_POST['total_amount']),
                    sanitize($_POST['sale_date']),
                    sanitize($_POST['notes'] ?? '')
                ]);

                $success = 'ক্রয় সফলভাবে সংরক্ষিত হয়েছে।';
                logActivity('purchase_add', 'মাছ ক্রয় করেছেন: ' . $_POST['fish_type']);
            } catch (Exception $e) {
                $error = 'ত্রুটি: ' . $e->getMessage();
            }
        }
    }
}

$csrfToken = generateCSRFToken();

try {
    $pdo = getDBConnection();

    // Get purchases
    $stmt = $pdo->prepare("
        SELECT s.*, u.name_bn as seller_name
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        WHERE s.customer_id = ?
        ORDER BY s.sale_date DESC
    ");
    $stmt->execute([$user['id']]);
    $purchases = $stmt->fetchAll();

    // Get sellers for dropdown
    $stmt = $pdo->query("SELECT id, name_bn FROM users WHERE role = 'seller' AND status = 'active' ORDER BY name_bn");
    $sellers = $stmt->fetchAll();

    // Get fish species
    $stmt = $pdo->query("SELECT name_bn FROM fish_species ORDER BY name_bn");
    $fishTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    $error = $e->getMessage();
}

include __DIR__ . '/../../../includes/header.php';
?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="ponds.php" class="sidebar-link">
                <i class="bi bi-water"></i>
                আমার পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="purchases.php" class="sidebar-link active">
                <i class="bi bi-cart-check"></i>
                মাছ ক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="payments.php" class="sidebar-link">
                <i class="bi bi-wallet2"></i>
                পেমেন্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                প্রোফাইল
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">মাছ ক্রয় তালিকা</h3>
            <button type="button" class="btn btn-primary" onclick="openModal('addPurchaseModal')">
                <i class="bi bi-cart-plus"></i> নতুন ক্রয়
            </button>
        </div>

        <?php if (empty($purchases)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            এখনো কোনো মাছ ক্রয় করা হয়নি।
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>মাছের ধরন</th>
                        <th>পরিমাণ (কেজি)</th>
                        <th>দাম/কেজি</th>
                        <th>মোট দাম</th>
                        <th>বিক্রেতা</th>
                        <th>নোট</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo formatDate($purchase['sale_date']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['fish_type']); ?></td>
                        <td><?php echo $purchase['quantity_kg']; ?></td>
                        <td><?php echo formatCurrency($purchase['price_per_kg']); ?></td>
                        <td><?php echo formatCurrency($purchase['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['seller_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($purchase['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Purchase Modal -->
<div class="modal" id="addPurchaseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">মাছ ক্রয় যোগ করুন</h3>
            <button type="button" class="modal-close" onclick="closeModal('addPurchaseModal')">&times;</button>
        </div>
        <form method="POST" onsubmit="calculateTotal()">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label">বিক্রেতা *</label>
                    <select name="seller_id" class="form-control" required>
                        <option value="">বিক্রেতা নির্বাচন করুন</option>
                        <?php foreach ($sellers as $seller): ?>
                        <option value="<?php echo $seller['id']; ?>"><?php echo $seller['name_bn']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">মাছের ধরন *</label>
                    <input type="text" name="fish_type" id="fish_type" class="form-control" required list="fishTypes" placeholder="মাছের নাম লিখুন">
                    <datalist id="fishTypes">
                        <?php foreach ($fishTypes as $type): ?>
                        <option value="<?php echo $type; ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">পরিমাণ (কেজি) *</label>
                        <input type="number" name="quantity_kg" id="quantity_kg" class="form-control" step="0.1" required oninput="calculateTotal()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">দাম/কেজি (৳) *</label>
                        <input type="number" name="price_per_kg" id="price_per_kg" class="form-control" step="0.01" required oninput="calculateTotal()">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">মোট দাম (৳)</label>
                    <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" required readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">তারিখ *</label>
                    <input type="date" name="sale_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">নোট</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="অতিরিক্ত তথ্য"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPurchaseModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function calculateTotal() {
    const qty = parseFloat(document.getElementById('quantity_kg').value) || 0;
    const price = parseFloat(document.getElementById('price_per_kg').value) || 0;
    document.getElementById('total_amount').value = (qty * price).toFixed(2);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
