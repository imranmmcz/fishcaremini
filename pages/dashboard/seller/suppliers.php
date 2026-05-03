<?php
/**
 * Fish Care System - Seller Supplier Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'সরবরাহকারী ব্যবস্থাপনা';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $sellerId = $user['id'];

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_supplier') {
                $name = sanitize($_POST['name']);
                $phone = sanitize($_POST['phone']);
                $email = sanitize($_POST['email']);
                $companyName = sanitize($_POST['company_name']);
                $address = sanitize($_POST['address']);
                $notes = sanitize($_POST['notes']);

                $stmt = $pdo->prepare("
                    INSERT INTO suppliers (seller_id, name, phone, email, company_name, address, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$sellerId, $name, $phone, $email, $companyName, $address, $notes]);
                $message = 'সরবরাহকারী সফলভাবে যোগ করা হয়েছে';
                $messageType = 'success';
            }

            if ($_POST['action'] === 'update_payment') {
                $supplierId = intval($_POST['supplier_id']);
                $amount = floatval($_POST['amount']);
                $type = $_POST['type'];

                if ($type === 'payment') {
                    $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ? AND seller_id = ?");
                    $stmt->execute([$amount, $supplierId, $sellerId]);
                    $message = 'পেমেন্ট রেকর্ড করা হয়েছে';
                } else {
                    $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ? AND seller_id = ?");
                    $stmt->execute([$amount, $supplierId, $sellerId]);
                    $message = 'নতুন ক্রয় যোগ করা হয়েছে';
                }
                $messageType = 'success';
            }

            if ($_POST['action'] === 'delete_supplier') {
                $supplierId = intval($_POST['supplier_id']);
                $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ? AND seller_id = ?");
                $stmt->execute([$supplierId, $sellerId]);
                $message = 'সরবরাহকারী মুছে ফেলা হয়েছে';
                $messageType = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'ত্রুটি: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get suppliers
try {
    $pdo = getDBConnection();
    $sellerId = $user['id'];

    $stmt = $pdo->prepare("
        SELECT s.*,
            (SELECT COUNT(*) FROM purchases WHERE supplier_id = s.id) as total_purchases,
            (SELECT SUM(total_amount) FROM purchases WHERE supplier_id = s.id) as total_spent
        FROM suppliers s
        WHERE s.seller_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$sellerId]);
    $suppliers = $stmt->fetchAll();

    $totalPayable = array_sum(array_column($suppliers, 'balance'));

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i> ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="products.php" class="sidebar-link">
                <i class="bi bi-box-seam"></i> পণ্য ম্যানেজমেন্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="invoices.php" class="sidebar-link">
                <i class="bi bi-receipt"></i> চালান
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="fish-sales.php" class="sidebar-link">
                <i class="bi bi-water"></i> মাছ বিক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="customers.php" class="sidebar-link">
                <i class="bi bi-people"></i> গ্রাহক
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="suppliers.php" class="sidebar-link active">
                <i class="bi bi-truck"></i> সরবরাহকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="accounting.php" class="sidebar-link">
                <i class="bi bi-calculator"></i> হিসাব-নিকাশ
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-truck"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($suppliers); ?></h3>
                <p>মোট সরবরাহকারী</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $totalPayable > 0 ? 'danger' : 'success'; ?>">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalPayable); ?></h3>
                <p>মোট পাওনা</p>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">সকল সরবরাহকারী</h3>
            <button class="btn btn-primary" onclick="openModal('addSupplierModal')">
                <i class="bi bi-plus-lg"></i> নতুন সরবরাহকারী
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>সরবরাহকারীর নাম</th>
                        <th>কোম্পানি</th>
                        <th>ফোন</th>
                        <th>মোট ক্রয়</th>
                        <th>বর্তমান পাওনা</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supp): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?php echo $supp['name']; ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $supp['email']; ?></div>
                        </td>
                        <td><?php echo $supp['company_name'] ?? '-'; ?></td>
                        <td><?php echo $supp['phone'] ?? '-'; ?></td>
                        <td><?php echo formatCurrency($supp['total_spent'] ?? 0); ?></td>
                        <td>
                            <span style="color: <?php echo $supp['balance'] > 0 ? 'var(--danger-color)' : 'var(--secondary-color)'; ?>; font-weight: 600;">
                                <?php echo formatCurrency($supp['balance']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-secondary" onclick="paymentModal(<?php echo $supp['id']; ?>, '<?php echo $supp['name']; ?>', <?php echo $supp['balance']; ?>)">
                                    <i class="bi bi-cash"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('মুছে ফেলতে চান?');">
                                    <input type="hidden" name="action" value="delete_supplier">
                                    <input type="hidden" name="supplier_id" value="<?php echo $supp['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="bi bi-truck" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>কোনো সরবরাহকারী নেই। নতুন সরবরাহকারী যোগ করুন।</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal" id="addSupplierModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন সরবরাহকারী যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addSupplierModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_supplier">
                <div class="form-group">
                    <label class="form-label">নাম <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">কোম্পানির নাম</label>
                    <input type="text" name="company_name" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">ফোন</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">ইমেইল</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">নোট</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSupplierModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">লেনদেন</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="supplier_id" id="payment_supplier_id">
                <div class="form-group">
                    <label class="form-label">সরবরাহকারী</label>
                    <input type="text" id="payment_supplier_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">বর্তমান পাওনা</label>
                    <input type="text" id="payment_current_balance" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">লেনদেনের ধরন</label>
                    <select name="type" class="form-control">
                        <option value="payment">পেমেন্ট (পাওনা পরিশোধ)</option>
                        <option value="purchase">নতুন ক্রয়</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">পরিমাণ</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function paymentModal(supplierId, supplierName, balance) {
    document.getElementById('payment_supplier_id').value = supplierId;
    document.getElementById('payment_supplier_name').value = supplierName;
    document.getElementById('payment_current_balance').value = balance + ' টাকা';
    openModal('paymentModal');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
