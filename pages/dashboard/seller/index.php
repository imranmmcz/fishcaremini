<?php
/**
 * Fish Care System - Seller Dashboard
 * Invoice/Chalan System, Inventory, Sales Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'বিক্রেতা ড্যাশবোর্ড';

// Get seller statistics
try {
    $pdo = getDBConnection();
    $sellerId = $user['id'];

    // Today's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE seller_id = ? AND DATE(invoice_date) = CURDATE()");
    $stmt->execute([$sellerId]);
    $todaySales = $stmt->fetchColumn();

    // Today's fish sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM fish_sales WHERE seller_id = ? AND DATE(sale_date) = CURDATE()");
    $stmt->execute([$sellerId]);
    $todayFishSales = $stmt->fetchColumn();

    // Total customers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $totalCustomers = $stmt->fetchColumn();

    // Low stock products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND stock_quantity <= min_stock_alert");
    $stmt->execute([$sellerId]);
    $lowStockCount = $stmt->fetchColumn();

    // Total revenue this month
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE seller_id = ? AND MONTH(invoice_date) = MONTH(CURDATE())");
    $stmt->execute([$sellerId]);
    $monthlyRevenue = $stmt->fetchColumn();

    // Recent invoices
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as customer_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE i.seller_id = ?
        ORDER BY i.invoice_date DESC
        LIMIT 10
    ");
    $stmt->execute([$sellerId]);
    $recentInvoices = $stmt->fetchAll();

    // Recent fish sales
    $stmt = $pdo->prepare("
        SELECT fs.*, f.name_bn as species_name, c.name as customer_name
        FROM fish_sales fs
        LEFT JOIN fish_species f ON fs.species_id = f.id
        LEFT JOIN customers c ON fs.customer_id = c.id
        WHERE fs.seller_id = ?
        ORDER BY fs.sale_date DESC
        LIMIT 10
    ");
    $stmt->execute([$sellerId]);
    $recentFishSales = $stmt->fetchAll();

    // Products for quick sale
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND is_active = 1 ORDER BY name_bn LIMIT 10");
    $stmt->execute([$sellerId]);
    $products = $stmt->fetchAll();

    // Fish species for fish sales
    $stmt = $pdo->query("SELECT * FROM fish_species ORDER BY name_bn");
    $fishSpecies = $stmt->fetchAll();

    // Customers for dropdown
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE seller_id = ? ORDER BY name");
    $stmt->execute([$sellerId]);
    $customers = $stmt->fetchAll();

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
            <a href="products.php" class="sidebar-link">
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
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($todaySales + $todayFishSales); ?></h3>
                <p>আজকের বিক্রয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($monthlyRevenue); ?></h3>
                <p>এই মাসের আয়</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalCustomers; ?></h3>
                <p>মোট গ্রাহক</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?php echo $lowStockCount > 0 ? 'danger' : 'success'; ?>">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $lowStockCount; ?></h3>
                <p>কম স্টক</p>
            </div>
        </div>
    </div>

    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Quick Sale Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-cart-plus"></i> দ্রুত বিক্রয়</h3>
            </div>
            <form id="quickSaleForm">
                <div class="form-group">
                    <label class="form-label">বিক্রয়ের ধরন</label>
                    <select class="form-control" id="saleType" onchange="toggleSaleType()">
                        <option value="product">পণ্য বিক্রয়</option>
                        <option value="fish">মাছ বিক্রয়</option>
                    </select>
                </div>

                <!-- Product Sale -->
                <div id="productSaleFields">
                    <div class="form-group">
                        <label class="form-label">পণ্য নির্বাচন করুন</label>
                        <select class="form-control" id="productId" onchange="updateProductPrice()">
                            <option value="">পণ্য নির্বাচন করুন</option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['sell_price']; ?>" data-stock="<?php echo $prod['stock_quantity']; ?>">
                                    <?php echo $prod['name_bn']; ?> - ৳<?php echo $prod['sell_price']; ?>/<?php echo $prod['unit']; ?> (স্টক: <?php echo $prod['stock_quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fish Sale -->
                <div id="fishSaleFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">মাছের প্রজাতি</label>
                        <select class="form-control" id="fishSpeciesId">
                            <option value="">প্রজাতি নির্বাচন করুন</option>
                            <?php foreach ($fishSpecies as $fish): ?>
                                <option value="<?php echo $fish['id']; ?>"><?php echo $fish['name_bn']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">পরিমাণ (কেজি)</label>
                        <input type="number" step="0.01" class="form-control" id="fishQuantity" placeholder="কেজি">
                    </div>
                    <div class="form-group">
                        <label class="form-label">দাম (প্রতি কেজি)</label>
                        <input type="number" step="0.01" class="form-control" id="fishUnitPrice" placeholder="টাকা">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">গ্রাহক</label>
                    <select class="form-control" id="customerId">
                        <option value="">সাধারণ গ্রাহক (ধরন নেই)</option>
                        <?php foreach ($customers as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>"><?php echo $cust['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">পরিমাণ</label>
                    <input type="number" step="0.01" class="form-control" id="quantity" placeholder="পরিমাণ">
                </div>

                <div class="form-group">
                    <label class="form-label">মোট দাম</label>
                    <input type="number" step="0.01" class="form-control" id="totalPrice" placeholder="টাকা" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">পেমেন্ট স্ট্যাটাস</label>
                    <select class="form-control" id="paymentStatus">
                        <option value="paid">পরিশোধিত</option>
                        <option value="partial">আংশিক</option>
                        <option value="due">বাকি</option>
                    </select>
                </div>

                <div class="form-group" id="paidAmountGroup" style="display: none;">
                    <label class="form-label">পরিশোধিত অর্থ</label>
                    <input type="number" step="0.01" class="form-control" id="paidAmount" placeholder="টাকা">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="bi bi-check-circle"></i> বিক্রয় সম্পন্ন করুন
                </button>
            </form>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-clock-history"></i> সাম্প্রতিক লেনদেন</h3>
            </div>

            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#invoices" type="button">পণ্য বিক্রয়</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fishsales" type="button">মাছ বিক্রয়</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="invoices">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>চালান নম্বর</th>
                                    <th>গ্রাহক</th>
                                    <th>মোট</th>
                                    <th>স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInvoices as $inv): ?>
                                <tr>
                                    <td><?php echo $inv['invoice_number']; ?></td>
                                    <td><?php echo $inv['customer_name'] ?? 'সাধারণ'; ?></td>
                                    <td><?php echo formatCurrency($inv['total_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $inv['payment_status'] == 'paid' ? 'success' : ($inv['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo $inv['payment_status'] == 'paid' ? 'পরিশোধিত' : ($inv['payment_status'] == 'partial' ? 'আংশিক' : 'বাকি'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentInvoices)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-secondary);">কোনো লেনদেন নেই</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="fishsales">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>তারিখ</th>
                                    <th>মাছ</th>
                                    <th>পরিমাণ</th>
                                    <th>মোট</th>
                                    <th>স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFishSales as $sale): ?>
                                <tr>
                                    <td><?php echo formatDate($sale['sale_date']); ?></td>
                                    <td><?php echo $sale['species_name']; ?></td>
                                    <td><?php echo $sale['quantity_kg']; ?> কেজি</td>
                                    <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $sale['payment_status'] == 'paid' ? 'success' : ($sale['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo $sale['payment_status'] == 'paid' ? 'পরিশোধিত' : 'বাকি'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentFishSales)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">কোনো মাছ বিক্রয় নেই</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSaleType() {
    const saleType = document.getElementById('saleType').value;
    if (saleType === 'fish') {
        document.getElementById('productSaleFields').style.display = 'none';
        document.getElementById('fishSaleFields').style.display = 'block';
    } else {
        document.getElementById('productSaleFields').style.display = 'block';
        document.getElementById('fishSaleFields').style.display = 'none';
    }
}

function updateProductPrice() {
    const productSelect = document.getElementById('productId');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    document.getElementById('totalPrice').value = price || '';
}

document.getElementById('quantity').addEventListener('input', function() {
    const saleType = document.getElementById('saleType').value;
    const quantity = parseFloat(this.value) || 0;
    let unitPrice = 0;

    if (saleType === 'product') {
        const productSelect = document.getElementById('productId');
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        unitPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    } else {
        unitPrice = parseFloat(document.getElementById('fishUnitPrice').value) || 0;
    }

    document.getElementById('totalPrice').value = (quantity * unitPrice).toFixed(2);
});

document.getElementById('fishUnitPrice').addEventListener('input', function() {
    const quantity = parseFloat(document.getElementById('fishQuantity').value) || 0;
    const unitPrice = parseFloat(this.value) || 0;
    document.getElementById('totalPrice').value = (quantity * unitPrice).toFixed(2);
});

document.getElementById('paymentStatus').addEventListener('change', function() {
    const paidAmountGroup = document.getElementById('paidAmountGroup');
    if (this.value === 'partial') {
        paidAmountGroup.style.display = 'block';
    } else if (this.value === 'paid') {
        paidAmountGroup.style.display = 'none';
        document.getElementById('paidAmount').value = document.getElementById('totalPrice').value;
    } else {
        paidAmountGroup.style.display = 'none';
        document.getElementById('paidAmount').value = 0;
    }
});

document.getElementById('quickSaleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const saleType = document.getElementById('saleType').value;
    const customerId = document.getElementById('customerId').value;
    const totalPrice = parseFloat(document.getElementById('totalPrice').value) || 0;
    const paymentStatus = document.getElementById('paymentStatus').value;
    const paidAmount = paymentStatus === 'paid' ? totalPrice : (parseFloat(document.getElementById('paidAmount').value) || 0);

    const formData = new FormData();
    formData.append('action', saleType === 'fish' ? 'add_fish_sale' : 'add_invoice');
    formData.append('customer_id', customerId);
    formData.append('total_amount', totalPrice);
    formData.append('paid_amount', paidAmount);
    formData.append('payment_status', paymentStatus);

    if (saleType === 'fish') {
        formData.append('species_id', document.getElementById('fishSpeciesId').value);
        formData.append('quantity_kg', document.getElementById('fishQuantity').value);
        formData.append('unit_price', document.getElementById('fishUnitPrice').value);
    } else {
        formData.append('product_id', document.getElementById('productId').value);
        formData.append('quantity', document.getElementById('quantity').value);
    }

    fetch('<?php echo SITE_URL; ?>/api/sales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('বিক্রয় সফল!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('ত্রুটি হয়েছে');
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
