<?php
/**
 * Fish Care System - Admin Dashboard
 * User, Pond, and Location Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Only admin can access
if ($user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'অ্যাডমিন ড্যাশবোর্ড';

// Get statistics
try {
    $pdo = getDBConnection();

    // User counts by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
    $userStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Total ponds
    $stmt = $pdo->query("SELECT COUNT(*) FROM ponds");
    $totalPonds = $stmt->fetchColumn();

    // Today's transactions
    $stmt = $pdo->query("SELECT COUNT(*) FROM incomes WHERE DATE(created_at) = CURDATE()");
    $todayIncome = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM expenses WHERE DATE(created_at) = CURDATE()");
    $todayExpense = $stmt->fetchColumn();

    // Recent users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $recentUsers = $stmt->fetchAll();

    // Recent ponds
    $stmt = $pdo->query("
        SELECT p.*, u.full_name_bn as owner_name
        FROM ponds p
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $recentPonds = $stmt->fetchAll();

    // All divisions for location management
    $stmt = $pdo->query("SELECT * FROM divisions ORDER BY name_bn");
    $divisions = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

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
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                ব্যবহারকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="ponds.php" class="sidebar-link">
                <i class="bi bi-water"></i>
                পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="locations.php" class="sidebar-link">
                <i class="bi bi-geo-alt"></i>
                অবস্থান ব্যবস্থাপনা
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="reports.php" class="sidebar-link">
                <i class="bi bi-file-earmark-bar-graph"></i>
                রিপোর্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                সেটিংস
            </a>
        </li>
    </ul>
</aside>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo array_sum($userStats); ?></h3>
                <p>মোট ব্যবহারকারী</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-person-badge"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $userStats['farmer'] ?? 0; ?></h3>
                <p>চাষী</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-cart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $userStats['seller'] ?? 0; ?></h3>
                <p>বিক্রেতা</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-water"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPonds; ?></h3>
                <p>মোট পুকুর</p>
            </div>
        </div>
    </div>

    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সাম্প্রতিক ব্যবহারকারী</h3>
                <a href="users.php" class="btn btn-secondary btn-sm">সব দেখুন</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>নাম</th>
                            <th>ভূমিকা</th>
                            <th>স্ট্যাটাস</th>
                            <th>তারিখ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600;">
                                        <?php echo substr($u['full_name_bn'], 0, 1); ?>
                                    </div>
                                    <?php echo $u['full_name_bn']; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $u['role'] == 'admin' ? 'primary' : ($u['role'] == 'seller' ? 'warning' : 'success'); ?>">
                                    <?php echo getRoleName($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $u['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo $u['status']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($u['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Ponds -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সাম্প্রতিক পুকুর</h3>
                <a href="ponds.php" class="btn btn-secondary btn-sm">সব দেখুন</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>পুকুরের নাম</th>
                            <th>মালিক</th>
                            <th>আকার</th>
                            <th>স্ট্যাটাস</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPonds as $pond): ?>
                        <tr>
                            <td><?php echo $pond['name']; ?></td>
                            <td><?php echo $pond['owner_name']; ?></td>
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
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title">দ্রুত কার্যক্রম</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <a href="users.php?action=add" class="btn btn-primary" style="justify-content: center;">
                <i class="bi bi-person-plus"></i> নতুন ব্যবহারকারী
            </a>
            <a href="ponds.php?action=add" class="btn btn-success" style="justify-content: center;">
                <i class="bi bi-water"></i> নতুন পুকুর
            </a>
            <a href="locations.php?tab=division" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-geo-alt"></i> বিভাগ যোগ
            </a>
            <a href="reports.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="bi bi-file-earmark-bar-graph"></i> রিপোর্ট
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
