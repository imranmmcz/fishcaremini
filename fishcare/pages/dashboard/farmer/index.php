<?php
/**
 * Fish Care System - Farmer/Customer Dashboard
 * Pond Management, Fish Stocking, Income-Expense
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Only farmer and customer can access
if (!in_array($user['role'], ['farmer', 'customer'])) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'ড্যাশবোর্ড';

// Get farmer statistics
try {
    $pdo = getDBConnection();
    $userId = $user['id'];

    // Total ponds
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ponds WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalPonds = $stmt->fetchColumn();

    // Active ponds
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ponds WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $activePonds = $stmt->fetchColumn();

    // Total income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM incomes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalIncome = $stmt->fetchColumn();

    // Total expense
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalExpense = $stmt->fetchColumn();

    $netProfit = $totalIncome - $totalExpense;

    // This month's income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM incomes WHERE user_id = ? AND MONTH(transaction_date) = MONTH(CURDATE())");
    $stmt->execute([$userId]);
    $monthlyIncome = $stmt->fetchColumn();

    // This month's expense
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND MONTH(transaction_date) = MONTH(CURDATE())");
    $stmt->execute([$userId]);
    $monthlyExpense = $stmt->fetchColumn();

    // Recent ponds
    $stmt = $pdo->prepare("SELECT * FROM ponds WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $ponds = $stmt->fetchAll();

    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT 'income' as type, id, amount, description, transaction_date as date FROM incomes WHERE user_id = ?
        UNION ALL
        SELECT 'expense' as type, id, amount, description, transaction_date as date FROM expenses WHERE user_id = ?
        ORDER BY date DESC LIMIT 10
    ");
    $stmt->execute([$userId, $userId]);
    $recentTransactions = $stmt->fetchAll();

    // Fish species
    $stmt = $pdo->query("SELECT * FROM fish_species ORDER BY name_bn");
    $fishSpecies = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

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
            <a href="fish-stock.php" class="sidebar-link">
                <i class="bi bi-layer-group"></i>
                মাছ স্টকিং
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="income.php" class="sidebar-link">
                <i class="bi bi-arrow-down-circle"></i>
                আয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="expense.php" class="sidebar-link">
                <i class="bi bi-arrow-up-circle"></i>
                ব্যয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="accounting.php" class="sidebar-link">
                <i class="bi bi-calculator"></i>
                হিসাব-নিকাশ
            </a>
        </li>
    </ul>
</aside>

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
                <i class="bi bi-arrow-down-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($monthlyIncome); ?></h3>
                <p>এই মাসের আয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($monthlyExpense); ?></h3>
                <p>এই মাসের ব্যয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $netProfit >= 0 ? 'success' : 'danger'; ?>">
                <i class="bi bi-<?php echo $netProfit >= 0 ? 'graph-up-arrow' : 'graph-down-arrow'; ?>"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency(abs($netProfit)); ?></h3>
                <p><?php echo $netProfit >= 0 ? 'মোট লাভ' : 'মোট ক্ষতি'; ?></p>
            </div>
        </div>
    </div>

    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Quick Transaction -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-plus-circle"></i> দ্রুত লেনদেন</h3>
            </div>

            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#income" type="button">আয়</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#expense" type="button">ব্যয়</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Income Form -->
                <div class="tab-pane fade show active" id="income">
                    <form id="incomeForm">
                        <div class="form-group">
                            <label class="form-label">পুকুর নির্বাচন</label>
                            <select class="form-control" name="pond_id" required>
                                <option value="">পুকুর নির্বাচন করুন</option>
                                <?php foreach ($ponds as $pond): ?>
                                    <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">আয়ের ধরন</label>
                            <select class="form-control" name="category_id" required>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM income_categories WHERE is_active = 1");
                                $incomeCategories = $stmt->fetchAll();
                                foreach ($incomeCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_bn']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">পরিমাণ (টাকা)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">বিবরণ</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">
                            <i class="bi bi-check-circle"></i> আয় যোগ করুন
                        </button>
                    </form>
                </div>

                <!-- Expense Form -->
                <div class="tab-pane fade" id="expense">
                    <form id="expenseForm">
                        <div class="form-group">
                            <label class="form-label">পুকুর নির্বাচন</label>
                            <select class="form-control" name="pond_id" required>
                                <option value="">পুকুর নির্বাচন করুন</option>
                                <?php foreach ($ponds as $pond): ?>
                                    <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ব্যয়ের ধরন</label>
                            <select class="form-control" name="category_id" required>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM expense_categories WHERE is_active = 1");
                                $expenseCategories = $stmt->fetchAll();
                                foreach ($expenseCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_bn']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">পরিমাণ (টাকা)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">বিক্রেতার নাম</label>
                            <input type="text" name="vendor_name" class="form-control" placeholder="সরবরাহকারী/দোকান">
                        </div>

                        <div class="form-group">
                            <label class="form-label">বিবরণ</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="bi bi-check-circle"></i> ব্যয় যোগ করুন
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
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
                        <?php foreach ($recentTransactions as $trans): ?>
                        <tr>
                            <td><?php echo formatDate($trans['date']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $trans['type'] == 'income' ? 'success' : 'danger'; ?>">
                                    <?php echo $trans['type'] == 'income' ? 'আয়' : 'ব্যয়'; ?>
                                </span>
                            </td>
                            <td><?php echo $trans['description'] ? substr($trans['description'], 0, 30) : '-'; ?></td>
                            <td style="color: <?php echo $trans['type'] == 'income' ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                                <?php echo $trans['type'] == 'income' ? '+' : '-'; ?><?php echo formatCurrency($trans['amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">কোনো লেনদেন নেই</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- My Ponds -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-water"></i> আমার পুকুর</h3>
            <a href="ponds.php?action=add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> নতুন পুকুর
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($ponds as $pond): ?>
            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-water" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 18px; color: white;"><?php echo $pond['name']; ?></h4>
                        <span class="badge badge-<?php echo $pond['status'] == 'active' ? 'success' : 'warning'; ?>">
                            <?php echo $pond['status'] == 'active' ? 'সক্রিয়' : 'অপেক্ষায়'; ?>
                        </span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px; color: var(--text-secondary);">
                    <div>
                        <i class="bi bi-rulers"></i> <?php echo $pond['size_decimal']; ?> ডেসিমাল
                    </div>
                    <div>
                        <i class="bi bi-droplet"></i> <?php echo ucfirst($pond['water_type']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($ponds)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary); grid-column: 1 / -1;">
                <i class="bi bi-water" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>আপনার কোনো পুকুর নেই। নতুন পুকুর যোগ করুন।</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('incomeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_income');

    fetch('<?php echo SITE_URL; ?>/api/accounting.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
});

document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_expense');

    fetch('<?php echo SITE_URL; ?>/api/accounting.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
