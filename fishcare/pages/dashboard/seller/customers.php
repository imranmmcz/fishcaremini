<?php
/**
 * Fish Care System - Seller Customer Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'গ্রাহক ব্যবস্থাপনা';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $sellerId = $user['id'];

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_customer') {
                $name = sanitize($_POST['name']);
                $phone = sanitize($_POST['phone']);
                $email = sanitize($_POST['email']);
                $address = sanitize($_POST['address']);
                $notes = sanitize($_POST['notes']);

                $stmt = $pdo->prepare("
                    INSERT INTO customers (seller_id, name, phone, email, address, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$sellerId, $name, $phone, $email, $address, $notes]);
                $message = 'গ্রাহক সফলভাবে যোগ করা হয়েছে';
                $messageType = 'success';
            }

            if ($_POST['action'] === 'update_balance') {
                $customerId = intval($_POST['customer_id']);
                $amount = floatval($_POST['amount']);
                $type = $_POST['type']; // payment or due

                if ($type === 'payment') {
                    $stmt = $pdo->prepare("UPDATE customers SET balance = balance - ? WHERE id = ? AND seller_id = ?");
                    $stmt->execute([$amount, $customerId, $sellerId]);
                    $message = 'পেমেন্ট রেকর্ড করা হয়েছে';
                } else {
                    $stmt = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ? AND seller_id = ?");
                    $stmt->execute([$amount, $customerId, $sellerId]);
                    $message = 'বকেয়া যোগ করা হয়েছে';
                }
                $messageType = 'success';
            }

            if ($_POST['action'] === 'delete_customer') {
                $customerId = intval($_POST['customer_id']);
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ? AND seller_id = ?");
                $stmt->execute([$customerId, $sellerId]);
                $message = 'গ্রাহক মুছে ফেলা হয়েছে';
                $messageType = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'ত্রুটি: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get customers
try {
    $pdo = getDBConnection();
    $sellerId = $user['id'];

    $stmt = $pdo->prepare("
        SELECT c.*,
            (SELECT COUNT(*) FROM invoices WHERE customer_id = c.id) as total_orders,
            (SELECT SUM(total_amount) FROM invoices WHERE customer_id = c.id) as total_purchase
        FROM customers c
        WHERE c.seller_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$sellerId]);
    $customers = $stmt->fetchAll();

    // Get total due
    $totalDue = array_sum(array_column($customers, 'balance'));

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
            <a href="customers.php" class="sidebar-link active">
                <i class="bi bi-people"></i> গ্রাহক
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="suppliers.php" class="sidebar-link">
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
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($customers); ?></h3>
                <p>মোট গ্রাহক</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $totalDue > 0 ? 'danger' : 'success'; ?>">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalDue); ?></h3>
                <p>মোট বকেয়া</p>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">সকল গ্রাহক</h3>
            <button class="btn btn-primary" onclick="openModal('addCustomerModal')">
                <i class="bi bi-plus-lg"></i> নতুন গ্রাহক
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>গ্রাহকের নাম</th>
                        <th>ফোন</th>
                        <th>মোট ক্রয়</th>
                        <th>বর্তমান বকেয়া</th>
                        <th>স্ট্যাটাস</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $cust): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?php echo $cust['name']; ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $cust['email']; ?></div>
                        </td>
                        <td><?php echo $cust['phone'] ?? '-'; ?></td>
                        <td><?php echo formatCurrency($cust['total_purchase'] ?? 0); ?></td>
                        <td>
                            <span style="color: <?php echo $cust['balance'] > 0 ? 'var(--danger-color)' : 'var(--secondary-color)'; ?>; font-weight: 600;">
                                <?php echo formatCurrency($cust['balance']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($cust['balance'] > 0): ?>
                                <span class="badge badge-danger">বকেয়া আছে</span>
                            <?php else: ?>
                                <span class="badge badge-success">সব পরিশোধিত</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-secondary" onclick="paymentModal(<?php echo $cust['id']; ?>, '<?php echo $cust['name']; ?>', <?php echo $cust['balance']; ?>)">
                                    <i class="bi bi-cash"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('মুছে ফেলতে চান?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="customer_id" value="<?php echo $cust['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="bi bi-people" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>কোনো গ্রাহক নেই। নতুন গ্রাহক যোগ করুন।</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal" id="addCustomerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন গ্রাহক যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_customer">
                <div class="form-group">
                    <label class="form-label">গ্রাহকের নাম <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ফোন নম্বর</label>
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCustomerModal')">বাতিল</button>
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
                <input type="hidden" name="action" value="update_balance">
                <input type="hidden" name="customer_id" id="payment_customer_id">

                <div class="form-group">
                    <label class="form-label">গ্রাহক</label>
                    <input type="text" id="payment_customer_name" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">বর্তমান বকেয়া</label>
                    <input type="text" id="payment_current_due" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">লেনদেনের ধরন</label>
                    <select name="type" class="form-control" id="payment_type" onchange="togglePaymentAmount()">
                        <option value="payment">পেমেন্ট (বকেয়া পরিশোধ)</option>
                        <option value="due">নতুন বকেয়া</option>
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

function paymentModal(customerId, customerName, balance) {
    document.getElementById('payment_customer_id').value = customerId;
    document.getElementById('payment_customer_name').value = customerName;
    document.getElementById('payment_current_due').value = balance + ' টাকা';
    openModal('paymentModal');
}

function togglePaymentAmount() {
    const type = document.getElementById('payment_type').value;
    const amountInput = document.querySelector('input[name="amount"]');
    if (type === 'payment') {
        amountInput.max = document.getElementById('payment_current_due').value.replace(' টাকা', '');
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
