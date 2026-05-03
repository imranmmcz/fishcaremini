<?php
/**
 * Fish Care System - Seller Accounting Page
 * Complete income-expense tracking for sellers
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'হিসাব-নিকাশ';

// Get accounting data
try {
    $pdo = getDBConnection();
    $sellerId = $user['id'];

    // This month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE seller_id = ? AND MONTH(invoice_date) = MONTH(CURDATE())
    ");
    $stmt->execute([$sellerId]);
    $monthlySales = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM purchases
        WHERE seller_id = ? AND MONTH(purchase_date) = MONTH(CURDATE())
    ");
    $stmt->execute([$sellerId]);
    $monthlyPurchases = $stmt->fetchColumn();

    // Fish sales this month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM fish_sales
        WHERE seller_id = ? AND MONTH(sale_date) = MONTH(CURDATE())
    ");
    $stmt->execute([$sellerId]);
    $monthlyFishSales = $stmt->fetchColumn();

    // Total
    $totalIncome = $monthlySales + $monthlyFishSales;
    $totalExpense = $monthlyPurchases;
    $netProfit = $totalIncome - $totalExpense;

    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT 'sale' as type, id, total_amount as amount, invoice_date as date, 'বিক্রয়' as description
        FROM invoices WHERE seller_id = ?
        UNION ALL
        SELECT 'purchase' as type, id, total_amount as amount, purchase_date as date, 'ক্রয়' as description
        FROM purchases WHERE seller_id = ?
        UNION ALL
        SELECT 'fish_sale' as type, id, total_amount as amount, sale_date as date, 'মাছ বিক্রয়' as description
        FROM fish_sales WHERE seller_id = ?
        ORDER BY date DESC LIMIT 20
    ");
    $stmt->execute([$sellerId, $sellerId, $sellerId]);
    $transactions = $stmt->fetchAll();

    // Customer balances
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM customers WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $customerReceivables = $stmt->fetchColumn();

    // Supplier payables
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM suppliers WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $supplierPayables = $stmt->fetchColumn();

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
            <a href="suppliers.php" class="sidebar-link">
                <i class="bi bi-truck"></i> সরবরাহকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="accounting.php" class="sidebar-link active">
                <i class="bi bi-calculator"></i> হিসাব-নিকাশ
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-arrow-down-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalIncome); ?></h3>
                <p>এই মাসের মোট আয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalExpense); ?></h3>
                <p>এই মাসের মোট ব্যয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $netProfit >= 0 ? 'success' : 'danger'; ?>">
                <i class="bi bi-<?php echo $netProfit >= 0 ? 'graph-up-arrow' : 'graph-down-arrow'; ?>"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency(abs($netProfit)); ?></h3>
                <p><?php echo $netProfit >= 0 ? 'নিট লাভ' : 'নিট ক্ষতি'; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo ($customerReceivables - $supplierPayables) >= 0 ? 'primary' : 'warning'; ?>">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency(abs($customerReceivables - $supplierPayables)); ?></h3>
                <p><?php echo ($customerReceivables - $supplierPayables) >= 0 ? 'নেট প্রাপ্তি' : 'নেট পাওনা'; ?></p>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Income Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-arrow-down-circle"></i> আয়ের বিবরণ</h3>
            </div>
            <div style="padding: 10px 0;">
                <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <span>পণ্য বিক্রয়</span>
                    <span style="font-weight: 600; color: var(--secondary-color);"><?php echo formatCurrency($monthlySales); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <span>মাছ বিক্রয়</span>
                    <span style="font-weight: 600; color: var(--secondary-color);"><?php echo formatCurrency($monthlyFishSales); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 12px 0; font-weight: 600;">
                    <span>মোট আয়</span>
                    <span style="color: var(--secondary-color);"><?php echo formatCurrency($totalIncome); ?></span>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-arrow-up-circle"></i> ব্যয়ের বিবরণ</h3>
            </div>
            <div style="padding: 10px 0;">
                <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <span>পণ্য ক্রয়</span>
                    <span style="font-weight: 600; color: var(--danger-color);"><?php echo formatCurrency($monthlyPurchases); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 12px 0; font-weight: 600;">
                    <span>মোট ব্যয়</span>
                    <span style="color: var(--danger-color);"><?php echo formatCurrency($totalExpense); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Receivables and Payables -->
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-people"></i> গ্রাহক পাওনা</h3>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 36px; font-weight: 700; color: <?php echo $customerReceivables > 0 ? 'var(--danger-color)' : 'var(--secondary-color)'; ?>;">
                    <?php echo formatCurrency($customerReceivables); ?>
                </div>
                <p style="color: var(--text-secondary); margin-top: 10px;">গ্রাহকদের কাছ থেকে পাওনা</p>
                <a href="customers.php" class="btn btn-secondary btn-sm" style="margin-top: 15px;">গ্রাহক দেখুন</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-truck"></i> সরবরাহকারী পাওনা</h3>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 36px; font-weight: 700; color: <?php echo $supplierPayables > 0 ? 'var(--danger-color)' : 'var(--secondary-color)'; ?>;">
                    <?php echo formatCurrency($supplierPayables); ?>
                </div>
                <p style="color: var(--text-secondary); margin-top: 10px;">সরবরাহকারীদের পাওনা</p>
                <a href="suppliers.php" class="btn btn-secondary btn-sm" style="margin-top: 15px;">সরবরাহকারী দেখুন</a>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-clock-history"></i> সাম্প্রতিক লেনদেন</h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>ধরন</th>
                        <th>বিবরণ</th>
                        <th>পরিমাণ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?php echo formatDate($trans['date']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo in_array($trans['type'], ['sale', 'fish_sale']) ? 'success' : 'danger'; ?>">
                                <?php echo $trans['description']; ?>
                            </span>
                        </td>
                        <td><?php echo $trans['type']; ?></td>
                        <td style="color: <?php echo in_array($trans['type'], ['sale', 'fish_sale']) ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                            <?php echo in_array($trans['type'], ['sale', 'fish_sale']) ? '+' : '-'; ?><?php echo formatCurrency($trans['amount']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            কোনো লেনদেন নেই
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
