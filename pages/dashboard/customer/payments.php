<?php
/**
 * Fish Care System - Customer Payments Page
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'customer') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'পেমেন্ট';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'অবৈধ অনুরোধ।';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'pay') {
            try {
                $pdo = getDBConnection();

                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, type, category, amount, description, transaction_date)
                    VALUES (?, 'expense', 'fish_purchase', ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    floatval($_POST['amount']),
                    sanitize($_POST['description'] ?? 'মাছ ক্রয় পেমেন্ট'),
                    sanitize($_POST['payment_date'])
                ]);

                $success = 'পেমেন্ট সফলভাবে সংরক্ষিত হয়েছে।';
                logActivity('payment', 'পেমেন্ট করেছেন: ' . $_POST['amount']);
            } catch (Exception $e) {
                $error = 'ত্রুটি: ' . $e->getMessage();
            }
        }
    }
}

$csrfToken = generateCSRFToken();

try {
    $pdo = getDBConnection();

    // Get payments
    $stmt = $pdo->prepare("
        SELECT * FROM transactions
        WHERE user_id = ? AND type = 'expense'
        ORDER BY transaction_date DESC
    ");
    $stmt->execute([$user['id']]);
    $payments = $stmt->fetchAll();

    // Get total paid
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type = 'expense'");
    $stmt->execute([$user['id']]);
    $totalPaid = $stmt->fetchColumn();

    // Get total purchase amount
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE customer_id = ?");
    $stmt->execute([$user['id']]);
    $totalPurchase = $stmt->fetchColumn();

    // Due amount
    $dueAmount = $totalPurchase - $totalPaid;

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
            <a href="purchases.php" class="sidebar-link">
                <i class="bi bi-cart-check"></i>
                মাছ ক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="payments.php" class="sidebar-link active">
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

    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalPurchase); ?></h3>
                <p>মোট ক্রয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-currency-taka"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalPaid); ?></h3>
                <p>মোট পেমেন্ট</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $dueAmount > 0 ? 'danger' : 'success'; ?>">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($dueAmount); ?></h3>
                <p><?php echo $dueAmount > 0 ? 'বকেয়া' : 'পরিশোধিত'; ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">পেমেন্ট ইতিহাস</h3>
            <?php if ($dueAmount > 0): ?>
            <button type="button" class="btn btn-primary" onclick="openModal('addPaymentModal')">
                <i class="bi bi-credit-card"></i> পেমেন্ট করুন
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($payments)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            এখনো কোনো পেমেন্ট করা হয়নি।
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>বিবরণ</th>
                        <th>পরিমাণ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo formatDate($payment['transaction_date']); ?></td>
                        <td><?php echo htmlspecialchars($payment['description']); ?></td>
                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal" id="addPaymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">পেমেন্ট করুন</h3>
            <button type="button" class="modal-close" onclick="closeModal('addPaymentModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="pay">

                <div class="form-group">
                    <label class="form-label">পরিমাণ (৳) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" required
                           placeholder="পেমেন্ট পরিমাণ" value="<?php echo round($dueAmount, 2); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">তারিখ *</label>
                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">বিবরণ</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="পেমেন্ট বিবরণ">মাছ ক্রয় পেমেন্ট</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPaymentModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">পেমেন্ট করুন</button>
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
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
