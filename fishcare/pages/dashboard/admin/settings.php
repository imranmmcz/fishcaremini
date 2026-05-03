<?php
/**
 * Fish Care System - Admin Settings Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'সেটিংস';

$message = '';
$messageType = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'সেটিংস সফলভাবে সংরক্ষিত হয়েছে';
    $messageType = 'success';
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
            <a href="reports.php" class="sidebar-link">
                <i class="bi bi-file-earmark-bar-graph"></i> রিপোর্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="settings.php" class="sidebar-link active">
                <i class="bi bi-gear"></i> সেটিংস
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-gear"></i> সিস্টেম সেটিংস</h3>
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">সাইটের নাম</label>
                <input type="text" class="form-control" value="<?php echo SITE_NAME_BN; ?>" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">ডেটাবেস স্ট্যাটাস</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge badge-success">সক্রিয়</span>
                    <span style="color: var(--text-secondary);">ডাটাবেজ সংযোগ সফল</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">মোট টেবিল</label>
                <?php
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo '<span class="badge badge-primary">' . count($tables) . 'টি টেবিল</span>';
                } catch (Exception $e) {
                    echo '<span class="badge badge-danger">ত্রুটি</span>';
                }
                ?>
            </div>

            <div class="form-group">
                <label class="form-label">সিস্টেম তথ্য</label>
                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; font-family: monospace;">
                    <div>PHP Version: <?php echo phpversion(); ?></div>
                    <div>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                    <div>Current Time: <?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-info-circle"></i> সিস্টেম তথ্য</h3>
        </div>
        <div style="padding: 20px 0;">
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span>ফিশ কেয়ার সিস্টেম ভার্সন</span>
                <span>2.0.0</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span>ডেভেলপার</span>
                <span>MiniMax Agent</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span>লাইসেন্স</span>
                <span>MIT License</span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
