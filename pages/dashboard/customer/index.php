<?php
/**
 * Fish Care System - Customer Dashboard
 * Pond Management and Fish Trading
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Only customer can access
if ($user['role'] !== 'customer') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'গ্রাহক ড্যাশবোর্ড';

// Get statistics
try {
    $pdo = getDBConnection();

    // Customer's ponds
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ponds WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalPonds = $stmt->fetchColumn();

    // Fish purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
    $stmt->execute([$user['id']]);
    $totalPurchases = $stmt->fetchColumn();

    // Total spending
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE customer_id = ?");
    $stmt->execute([$user['id']]);
    $totalSpending = $stmt->fetchColumn();

    // Recent purchases
    $stmt = $pdo->prepare("
        SELECT s.*, u.name_bn as seller_name
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        WHERE s.customer_id = ?
        ORDER BY s.sale_date DESC
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $recentPurchases = $stmt->fetchAll();

    // Recent ponds
    $stmt = $pdo->prepare("SELECT * FROM ponds WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $recentPonds = $stmt->fetchAll();

    // Fish species
    $stmt = $pdo->query("SELECT * FROM fish_species ORDER BY name_bn");
    $fishSpecies = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../../includes/header.php'; ?>

<!-- Sidebar -->
<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link active">
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

<!-- Main Content -->
<div class="content-wrapper">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-water"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPonds; ?></h3>
                <p>মোট পুকুর</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPurchases; ?></h3>
                <p>মাছ ক্রয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-currency-taka"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalSpending); ?></h3>
                <p>মোট ব্যয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency(0); ?></h3>
                <p>বকেয়া</p>
            </div>
        </div>
    </div>

    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Recent Purchases -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সাম্প্রতিক ক্রয়</h3>
                <a href="purchases.php" class="btn btn-secondary btn-sm">সব দেখুন</a>
            </div>
            <?php if (empty($recentPurchases)): ?>
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
                            <th>মাছের নাম</th>
                            <th>পরিমাণ</th>
                            <th>দাম</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPurchases as $purchase): ?>
                        <tr>
                            <td><?php echo formatDate($purchase['sale_date']); ?></td>
                            <td><?php echo $purchase['fish_type']; ?></td>
                            <td><?php echo $purchase['quantity_kg']; ?> কেজি</td>
                            <td><?php echo formatCurrency($purchase['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- My Ponds -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">আমার পুকুর</h3>
                <a href="ponds.php" class="btn btn-secondary btn-sm">সব দেখুন</a>
            </div>
            <?php if (empty($recentPonds)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    এখনো কোনো পুকুর যোগ করা হয়নি।
                </div>
            <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>পুকুরের নাম</th>
                            <th>আকার</th>
                            <th>স্ট্যাটাস</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPonds as $pond): ?>
                        <tr>
                            <td><?php echo $pond['name']; ?></td>
                            <td><?php echo $pond['size_decimal']; ?> ডেসিমাল</td>
                            <td>
                                <span class="badge badge-<?php echo $pond['status'] == 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo $pond['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title">দ্রুত কার্যক্রম</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <a href="ponds.php?action=add" class="btn btn-primary" style="justify-content: center;">
                <i class="bi bi-plus-circle"></i> নতুন পুকুর
            </a>
            <a href="purchases.php?action=add" class="btn btn-success" style="justify-content: center;">
                <i class="bi bi-cart-plus"></i> মাছ ক্রয়
            </a>
            <a href="payments.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-credit-card"></i> পেমেন্ট
            </a>
            <a href="profile.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-person-gear"></i> প্রোফাইল
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
