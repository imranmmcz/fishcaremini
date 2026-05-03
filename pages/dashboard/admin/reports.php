<?php
/**
 * Fish Care System - Admin Reports Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'রিপোর্ট';

// Get report data
try {
    $pdo = getDBConnection();

    // User statistics
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $userStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $activeUsers = $stmt->fetchColumn();

    // Total ponds
    $stmt = $pdo->query("SELECT COUNT(*) FROM ponds");
    $totalPonds = $stmt->fetchColumn();

    // Active ponds
    $stmt = $pdo->query("SELECT COUNT(*) FROM ponds WHERE status = 'active'");
    $activePonds = $stmt->fetchColumn();

    // System activity (recent logs)
    $stmt = $pdo->query("
        SELECT sl.*, u.full_name_bn
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        ORDER BY sl.created_at DESC
        LIMIT 20
    ");
    $recentActivity = $stmt->fetchAll();

    // Income/Expense summary
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM incomes");
    $totalIncome = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses");
    $totalExpense = $stmt->fetchColumn();

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
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i> ব্যবহারকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="ponds.php" class="sidebar-link">
                <i class="bi bi-water"></i> পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="locations.php" class="sidebar-link">
                <i class="bi bi-geo-alt"></i> অবস্থান ব্যবস্থাপনা
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="reports.php" class="sidebar-link active">
                <i class="bi bi-file-earmark-bar-graph"></i> রিপোর্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i> সেটিংস
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <!-- Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalUsers; ?></h3>
                <p>মোট ব্যবহারকারী</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $activeUsers; ?></h3>
                <p>সক্রিয় ব্যবহারকারী</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-water"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPonds; ?></h3>
                <p>মোট পুকুর</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo ($totalIncome - $totalExpense) >= 0 ? 'success' : 'danger'; ?>">
                <i class="bi bi-calculator"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency(abs($totalIncome - $totalExpense)); ?></h3>
                <p><?php echo ($totalIncome - $totalExpense) >= 0 ? 'মোট লাভ' : 'মোট ক্ষতি'; ?></p>
            </div>
        </div>
    </div>

    <!-- User Distribution -->
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pie-chart"></i> ব্যবহারকারী বিভাজন</h3>
            </div>
            <div style="padding: 20px 0;">
                <?php foreach ($userStats as $role => $count): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge badge-<?php echo $role == 'admin' ? 'primary' : ($role == 'seller' ? 'warning' : 'success'); ?>">
                            <?php echo getRoleName($role); ?>
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 600; font-size: 18px;"><?php echo $count; ?></span>
                        <span style="color: var(--text-secondary); font-size: 12px; margin-left: 8px;">
                            (<?php echo round(($count / $totalUsers) * 100); ?>%)
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-cash-stack"></i> আর্থিক সারাংশ</h3>
            </div>
            <div style="padding: 20px 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <span>মোট আয়</span>
                    <span style="font-weight: 600; color: var(--secondary-color);"><?php echo formatCurrency($totalIncome); ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <span>মোট ব্যয়</span>
                    <span style="font-weight: 600; color: var(--danger-color);"><?php echo formatCurrency($totalExpense); ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; font-weight: 600; font-size: 18px;">
                    <span>নিট ফলাফল</span>
                    <span style="color: <?php echo ($totalIncome - $totalExpense) >= 0 ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>;">
                        <?php echo formatCurrency(abs($totalIncome - $totalExpense)); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- System Activity -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-activity"></i> সাম্প্রতিক সিস্টেম কার্যক্রম</h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>ব্যবহারকারী</th>
                        <th>অ্যাকশন</th>
                        <th>বিবরণ</th>
                        <th>আইপি</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivity as $activity): ?>
                    <tr>
                        <td><?php echo formatDate($activity['created_at']); ?></td>
                        <td><?php echo $activity['full_name_bn'] ?? 'সিস্টেম'; ?></td>
                        <td>
                            <span class="badge badge-primary"><?php echo $activity['action']; ?></span>
                        </td>
                        <td><?php echo $activity['description'] ?? '-'; ?></td>
                        <td><?php echo $activity['ip_address'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivity)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            কোনো কার্যক্রম নেই
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Export -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-download"></i> রপ্তানি বিকল্প</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <a href="#" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-file-earmark-spreadsheet"></i> সকল ইউজার
            </a>
            <a href="#" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-file-earmark-spreadsheet"></i> সকল পুকুর
            </a>
            <a href="#" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-file-earmark-spreadsheet"></i> আয়-ব্যয় রিপোর্ট
            </a>
            <a href="#" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-file-earmark-spreadsheet"></i> সিস্টেম লগ
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
