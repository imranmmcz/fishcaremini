<?php
/**
 * Fish Care System - Seller Products Management
 * Medicine, Feed, Equipment Inventory
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'পণ্য ম্যানেজমেন্ট';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $sellerId = $user['id'];

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_product') {
                $categoryId = intval($_POST['category_id']);
                $nameBn = sanitize($_POST['name_bn']);
                $nameEn = sanitize($_POST['name_en']);
                $brand = sanitize($_POST['brand']);
                $unit = sanitize($_POST['unit']);
                $buyPrice = floatval($_POST['buy_price']);
                $sellPrice = floatval($_POST['sell_price']);
                $stockQty = floatval($_POST['stock_quantity']);
                $minStock = floatval($_POST['min_stock_alert']);
                $description = sanitize($_POST['description']);

                $stmt = $pdo->prepare("
                    INSERT INTO products (seller_id, category_id, name_bn, name_en, brand, unit, buy_price, sell_price, stock_quantity, min_stock_alert, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$sellerId, $categoryId, $nameBn, $nameEn, $brand, $unit, $buyPrice, $sellPrice, $stockQty, $minStock, $description]);
                $message = 'পণ্য সফলভাবে যোগ করা হয়েছে';
                $messageType = 'success';
                logActivity('add_product', 'নতুন পণ্য যোগ করেছেন: ' . $nameBn);
            }

            if ($_POST['action'] === 'update_stock') {
                $productId = intval($_POST['product_id']);
                $newStock = floatval($_POST['new_stock']);
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ? AND seller_id = ?");
                $stmt->execute([$newStock, $productId, $sellerId]);
                $message = 'স্টক আপডেট করা হয়েছে';
                $messageType = 'success';
            }

            if ($_POST['action'] === 'delete_product') {
                $productId = intval($_POST['product_id']);
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
                $stmt->execute([$productId, $sellerId]);
                $message = 'পণ্য মুছে ফেলা হয়েছে';
                $messageType = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'ত্রুটি: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get products
try {
    $pdo = getDBConnection();
    $sellerId = $user['id'];

    $stmt = $pdo->prepare("
        SELECT p.*, c.name_bn as category_name
        FROM products p
        LEFT JOIN product_categories c ON p.category_id = c.id
        WHERE p.seller_id = ?
        ORDER BY p.name_bn
    ");
    $stmt->execute([$sellerId]);
    $products = $stmt->fetchAll();

    // Get categories
    $stmt = $pdo->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY name_bn");
    $categories = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="products.php" class="sidebar-link active">
                <i class="bi bi-box-seam"></i>
                পণ্য ম্যানেজমেন্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="invoices.php" class="sidebar-link">
                <i class="bi bi-receipt"></i>
                চালান
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="fish-sales.php" class="sidebar-link">
                <i class="bi bi-water"></i>
                মাছ বিক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="customers.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                গ্রাহক
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="suppliers.php" class="sidebar-link">
                <i class="bi bi-truck"></i>
                সরবরাহকারী
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
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <?php
        $totalProducts = count($products);
        $lowStock = count(array_filter($products, function($p) { return $p['stock_quantity'] <= $p['min_stock_alert']; }));
        $totalValue = array_sum(array_map(function($p) { return $p['stock_quantity'] * $p['buy_price']; }, $products));
        $potentialProfit = array_sum(array_map(function($p) { return $p['stock_quantity'] * ($p['sell_price'] - $p['buy_price']); }, $products));
        ?>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalProducts; ?></h3>
                <p>মোট পণ্য</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $lowStock > 0 ? 'danger' : 'success'; ?>">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $lowStock; ?></h3>
                <p>কম স্টক</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($totalValue); ?></h3>
                <p>মোট মূল্য</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($potentialProfit); ?></h3>
                <p>সম্ভাব্য লাভ</p>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">সকল পণ্য</h3>
            <button class="btn btn-primary" onclick="openModal('addProductModal')">
                <i class="bi bi-plus-lg"></i> নতুন পণ্য
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>পণ্যের নাম</th>
                        <th>ব্র্যান্ড</th>
                        <th>ক্যাটাগরি</th>
                        <th>ক্রয় দাম</th>
                        <th>বিক্রয় দাম</th>
                        <th>স্টক</th>
                        <th>স্ট্যাটাস</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?php echo $prod['name_bn']; ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo $prod['name_en']; ?></div>
                        </td>
                        <td><?php echo $prod['brand'] ?? '-'; ?></td>
                        <td><span class="badge badge-primary"><?php echo $prod['category_name']; ?></span></td>
                        <td><?php echo formatCurrency($prod['buy_price']); ?>/<?php echo $prod['unit']; ?></td>
                        <td><?php echo formatCurrency($prod['sell_price']); ?>/<?php echo $prod['unit']; ?></td>
                        <td>
                            <span style="color: <?php echo $prod['stock_quantity'] <= $prod['min_stock_alert'] ? 'var(--danger-color)' : 'white'; ?>; font-weight: 600;">
                                <?php echo $prod['stock_quantity']; ?> <?php echo $prod['unit']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($prod['stock_quantity'] <= $prod['min_stock_alert']): ?>
                                <span class="badge badge-danger">কম স্টক</span>
                            <?php elseif ($prod['stock_quantity'] > 50): ?>
                                <span class="badge badge-success">ভালো</span>
                            <?php else: ?>
                                <span class="badge badge-warning">মাঝারি</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-secondary" onclick="editStock(<?php echo $prod['id']; ?>, <?php echo $prod['stock_quantity']; ?>)">
                                    <i class="bi bi-plus-slash-minus"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('মুছে ফেলতে চান?');">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            <i class="bi bi-box-seam" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>কোনো পণ্য নেই। নতুন পণ্য যোগ করুন।</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal" id="addProductModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন পণ্য যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addProductModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_product">

                <div class="form-group">
                    <label class="form-label">পণ্যের নাম (বাংলা) <span class="required">*</span></label>
                    <input type="text" name="name_bn" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">পণ্যের নাম (English)</label>
                    <input type="text" name="name_en" class="form-control">
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">ক্যাটাগরি</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">নির্বাচন করুন</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_bn']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ব্র্যান্ড</label>
                        <input type="text" name="brand" class="form-control">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">ইউনিট</label>
                        <select name="unit" class="form-control">
                            <option value="pcs">পিস</option>
                            <option value="kg">কেজি</option>
                            <option value="liter">লিটার</option>
                            <option value="bag">ব্যাগ</option>
                            <option value="box">বক্স</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">প্রাথমিক স্টক</label>
                        <input type="number" step="0.01" name="stock_quantity" class="form-control" value="0">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">ক্রয় দাম <span class="required">*</span></label>
                        <input type="number" step="0.01" name="buy_price" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">বিক্রয় দাম <span class="required">*</span></label>
                        <input type="number" step="0.01" name="sell_price" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">সর্বনিম্ন স্টক সতর্কতা</label>
                    <input type="number" step="0.01" name="min_stock_alert" class="form-control" value="10">
                </div>

                <div class="form-group">
                    <label class="form-label">বিবরণ</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal" id="editStockModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">স্টক আপডেট</h3>
            <button class="modal-close" onclick="closeModal('editStockModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="product_id" id="edit_product_id">

                <div class="form-group">
                    <label class="form-label">নতুন স্টক পরিমাণ</label>
                    <input type="number" step="0.01" name="new_stock" id="edit_stock_qty" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editStockModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">আপডেট</button>
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

function editStock(productId, currentStock) {
    document.getElementById('edit_product_id').value = productId;
    document.getElementById('edit_stock_qty').value = currentStock;
    openModal('editStockModal');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
